<?php

// namespace App\Api;

use App\Lib\Api\ApiBase;
use App\Lib\Model\Order;
use App\Common\Utils;
use App\Extend\Weixin\Pay as WeixinPay;
use App\Extend\Alibaba\Pay as Alipay;
use App\Extend\Message;
use App\Extend\Weixin\Enterprise as WeixinEnterprise;
use EasyWeChat\Kernel\Messages\TextCard;
class FinanceApi extends ApiBase
{
    public function _initialize(){
        parent::_initialize();
        $action = array(
            'permission' => ['order_print', 'receiving_money_by_system', 'alipay_web_page_pay_callback', 'wx_qrcode_pay_callback', 'daily_report','daily','month'],
            'allow' => ['get_receiving_list', 'get_order_list', 'get_order_details'],
            'roles' => [
                'customer' => ['alipay_web_page_pay', 'wx_qrcode_pay', 'get_record_status']
            ]
        );
        try {
            $this->checkPermission($action);
        } catch (\Exception $e){
            $this->ajaxReturn(null, -2, L($e->getMessage()));
            return;
        }
    }

    /**
     * 获取收款列表
     * @param null $order_id
     * @param null $customer_id
     * @param int $is_deleted 为0时获取订单列表 为1时获取订单列表回收站
     */
    public function get_receiving_list($order_id = null, $customer_id = null, $is_deleted=0){
        if (!($order_id || $customer_id)){
            $this->ajaxReturn(['code' => -1, 'info' => L('%s or %s must give one', ['id, customer_id'])]);
        }
        $receivables = D('ReceivablesView');
        $receivingorder = D('ReceivingorderView');
        if (!empty($order_id)){
            $info = $receivables->where([
                'receivables_id' => $order_id
            ])->find();
            if ( empty($info) )
                $this->ajaxReturn([
                    'code' => -1, 
                    'info' => L('RECORD NOT EXIST', [''])
                ]);
    
            $info['receivingorder'] = $receivingorder->where('receivingorder.is_deleted <> 1 and receivingorder.receivables_id = %d', $order_id)->select();
                   
            $num = 0;                   //已收款金额
            $num_unCheckOut = 0;        //未结账状态的金额
            $num_unReceivables = 0;     //还剩多少金额未收款
            foreach($info['receivingorder'] as $k=>$v){
                $info['receivingorder'][$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                if($v['status'] == 1){
                    //计算已结账状态的金额
                    $num = $num + $v['money'];
                }else{
                    //未结账状态的金额
                    $num_unCheckOut = $num_unCheckOut + $v['money'];
                }
            }
            $num_unReceivables = ($info['price'] - $num) < 0 ? 0 : ($info['price'] - $num);
            $info['num'] = $num;
            $info['num_unReceivables'] = $num_unReceivables;
            $info['num_unCheckOut'] = $num_unCheckOut;
            $info['owner'] = getUserByRoleId($info['owner_role_id']);
        }

        if (!empty($customer_id)) {
            $info = M('receivables')
            ->alias('receivables')
            ->join(C('DB_PREFIX').'receivingorder receivingorder on receivingorder.receivables_id = receivables.receivables_id')
            ->join(C('DB_PREFIX').'user user on user.role_id=receivingorder.owner_role_id')
            ->where([
                'receivingorder.is_deleted' => $is_deleted,
                'receivables.customer_id' => $customer_id
            ])
            ->order('receivingorder.receivingorder_id desc')
            ->field( 
                'receivables.receivables_id as order_id,
                 receivables.order_number as order_number,
                 receivingorder.receiving_num as receiving_num,
                 receivingorder.money as amount,
                 receivingorder.payment_way as payment_way,
                 receivingorder.pay_time as pay_time,
                 receivingorder.status as status,
                 receivingorder.description as description,
                 receivingorder.receivingorder_id as receivingorder_id,
                 user.name as owner_name,
                 receivingorder.create_time as create_time,
                 receivables.name as order_name'
            )->select();

            foreach($info as $key => $receivingorder){
                $info[$key]['pay_time'] = date('Y-m-d', $receivingorder['pay_time']);
            }
        }
        if (empty($info)){
            $info = [];
        }
        $this->ajaxReturn($info);
    }

    /**
     * 获取订单列表
     * @param $customer_id
     * @param int $is_deleted 为0时获取订单列表 为1时获取订单列表回收站
     */
    public function get_order_list($customer_id = null, $is_deleted = 0, $page = null){
        $where = [
            'receivables.is_deleted' => $is_deleted
        ];
        $customer_id && $where['customer_id'] = $customer_id;

        $order_list_query = M('Receivables')->alias('receivables')
        ->join(C('DB_PREFIX').'receivingorder receiving ON receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0 and receiving.status = 1')
        ->join(C('DB_PREFIX').'role role ON role.role_id = receivables.owner_role_id')
        ->join(C('DB_PREFIX').'user user ON user.user_id = role.user_id')
        ->where($where)
        ->field(
            'receivables.receivables_id as order_id,
             receivables.name as name,
             receivables.order_number as order_number,
             receivables.owner_role_id as owner_role_id,
             receivables.price as price,
             receivables.pay_time as order_time,
             user.name as owner_name,
             IFNULL(sum(receiving.money), 0) as received')
        ->group('receivables.receivables_id')
        ->order('order_time desc');

        if (!empty($page)){
            $order_list_query = $order_list_query->page($page, 15);
        }
        
        $order_list = $order_list_query->select();

        if ($order_list === false){
            $this->ajaxReturn(['sql' => M()->getLastSql()], -1, L('QUERY_FAILD'));
        }

        $total = M('Receivables')->alias('receivables')->where($where)->count();

        foreach($order_list as $key => $order){
            $order_list[$key]['order_time'] = date('Y-m-d', $order['order_time']);
        }

        $this->ajaxReturn(['order_list' => $order_list, 'pageinfo' => [
                'total' => intval($total),
                'size' => 15
        ]]);

    }

    /**
     * 获取订单详细信息
     * @param null $order_id
     * @param null $order_number
     */
    public function get_order_details($order_id = null, $order_number = null){

        if (!empty($order_id)){
            $order_query = [ 'receivables_id' => $order_id];
        }

        if (!empty($order_number)){
            $order_query = [ 'order_number' => $order_number ];
        }

        if (empty($order_query)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
            return;
        }
        $order_info = M('Receivables')->alias('receivables')
        ->join(C('DB_PREFIX').'role role on role.role_id = receivables.owner_role_id')
        ->join(C('DB_PREFIX').'user user on user.user_id = role.user_id')
        ->where($order_query)
        ->field('receivables.*, user.name as owner_name')
        ->find();

        if (!$order_info){
            $this->ajaxReturn(null, -1, 'CAN NOT FIND THIS ORDER');
            return;
        }

        $received = M('receivingorder')->where($order_query)->where(['is_deleted'=>0])->sum('money');
        $receive_record = M('receivingorder')->where($order_query)->where(['is_deleted'=>0])->select();

        $order_info['received'] = $received;
        $order_info['receive_record'] = $receive_record;

        $order_info['pay_time'] = date('Y-m-d', $order_info['pay_time']);

        $product_list = M('r_order_product')->where(['order_id' => $order_info['receivables_id']])->select();
        $this->ajaxReturn(['details' => array_merge($order_info, ['product_list' => $product_list])]);
    }

    protected function parseProductListStr($str){
        $result = [];
        array_walk(explode(',', $str), function($value) use (&$result){
            list($k, $v) = explode(':', $value);
            $result[$k] = $v;
        });
        return $result;
    }
    /**
     * 保存订单
     */
    public function save_order($order_id = null){
        $post = I('post.');  

        if (!$order_number = trim($post['order_number'])){
            $order_number = 'O'.date('Ymd').mt_rand(1000,9999);
        }
        
        $mustable_arguments = ['customer_id', 'total', 'owner_role_id'];
        $post_arguments = array_keys($post);
        foreach($mustable_arguments as $argument){
            if (!in_array($argument, $post_arguments)){
                $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
                return;
            }
        }

        $order_info = [
            'customer_id' => $post['customer_id'],
            'name' => empty($post['name']) ? $order_number : $post['name'],
            'order_number' => $order_number,
            'price' => $post['total'],
            'description' => $post['description'],
            'pay_time' => empty($post['create_time'])? time(): strtotime($post['create_time']),
            'creator_role_id' => session('role_id') ?? $this->user['role_id'],
            'owner_role_id' => $post['owner_role_id'],
            'create_time' => time(),
        ];
        
        try {
            $order = Order::createOrder($order_info);

            $product_list = [];

            if (!empty($post['product_list'])){
                $product_list = $this->parseProductListStr($post['product_list']);
                $order->addProduct($product_list, session('role_id'));
            }

            $order_info['order_id'] = $order->getOrderId();
            $order_info['order_time'] = date('Y-m-d', $order_info['pay_time']);
            $order_info['received'] = 0.00;

            if ($order_info['pay_time'] >= strtotime(date('Y-m-d'))) {
                $this->sendOrderMessage($order_info, array_keys($product_list));
            }

            $this->ajaxReturn(['order_info' => $order_info]);

        } catch (\Exception $e){

            $this->ajaxReturn(['sql' => M()->getLastSql()], -1, $e->getMessage());

        }
    }

    protected function sendOrderMessage($orderInfo, $productids) {

        $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8']])->getField('product_id', true);
        
        if (count(array_intersect($productids, $platinum_ids)) <= 0) {
            return;
        }

        $smsTemplate = M('sms_template') -> where('template_id = %d', 2)->field('content, is_auto_send')->find();

        if ($smsTemplate['is_auto_send'] == 2) {
            return;
        }

        $contacts = M('customer')->alias('customer')
            ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
            ->where(['customer_id' => $orderInfo['customer_id']])->field('contacts.name as contacts_name, contacts.telephone as telephone,customer.name customer_name')->find();

        $patterns = ['/{contacts_name}/', '/{product_name}/', '/{operator_name}/', '/{current_time}/'];
        $replacements = [$contacts['contacts_name'], $orderInfo['name'], $contacts['operator_name'], date('Y-m-d H:i:s')];

        $content = preg_replace($patterns, $replacements, $smsTemplate['content']);

        $message = new Message();
        $messageInfo =  $message->sendMessage($contacts['telephone'], $content);
        if ($messageInfo['code']==200){
            $record = ['content'=>$content, 'telephone'=>$contacts['telephone'], 'sendtime'=>time(), 'to_customer_id'=>$orderInfo['customer_id'],
                'to_customer_name'=>$contacts['customer_name'], 'to_contacts_name'=>$contacts['contacts_name'], 'role_id'=>$_SESSION['role_id']??$this->user['role_id']];
            M('sms_record')->add($record);
            return $messageInfo;//发送成功
        }else{
            return $messageInfo;//发送失败
        }
    }
    /**
     * 收款
     */
    public function receiving_money(){
        $post_info = I('post.');
        if (!$order_id = $post_info['order_id']){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $receiving_number = 'R'.date('Ymd').mt_rand(1000,9999);
        $receiving_info = [
            'name' => empty($post_info['name']) ? $receiving_number: $post_info['name'],
            'receiving_num' => $receiving_number,
            'money' => $post_info['amount'],
            'receivables_id' => $post_info['order_id'],
            'description' => $post_info['description'],
            'payment_way' => $post_info['payment_way'],
            'pay_time' => empty($post_info['receiving_time'])? time(): strtotime($post_info['receiving_time']),
            'creator_role_id' => session('role_id') ?? $this->user['role_id'],
            'owner_role_id' => $post_info['owner_role_id'],
            'create_time' => time(),
            'status' => 1, 
        ];
        $order = Order::find($order_id);
        $receiving_id = $order->receiving($receiving_info);

        if ($receiving_id !== false){
            $post_info['receiving_id'] = $receiving_id;
            $post_info['receiving_num'] = $receiving_info['receiving_num'];
            $post_info['order_name'] = $receiving_info['name'];
            $post_info['status'] = $receiving_info['status'];
            $post_info['pay_time'] = Date('Y-m-d', $receiving_info['pay_time']);
            $post_info['amount'] = $receiving_info['money'];
            $this->ajaxReturn(['receiving_info' => $post_info]);
        } else {
            $this->ajaxReturn(['sql' => M()->getLastSql()], -1, 'SAVE FAILD');
        }
    }
   /**
     * 系统收款
     */
    public function receiving_money_by_system(){
        $post_info = I('post.');
        if (!$order_id = $post_info['order_id']){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $receiving_number = 'R'.date('Ymd').mt_rand(1000,9999);

        $order = Order::find($order_id, true);

        $receiving_info = [
            'name' => $receiving_number,
            'receiving_num' => $receiving_number,
            'money' => $post_info['amount'] ?? $order->getPrice(),
            'receivables_id' => $post_info['order_id'],
            'description' => "系统自动创建收款",
            'payment_way' => '',
            'pay_time' => 0,
            'creator_role_id' => 0,
            'owner_role_id' => $order->getOwnerRoleId(),
            'create_time' => time(),
            'status' => 0, 
        ];

        $receiving_id = $order->receiving($receiving_info);

        if ($receiving_id !== false){
            $post_info['receiving_id'] = $receiving_id;
            $post_info['receiving_num'] = $receiving_info['receiving_num'];
            $post_info['order_name'] = $receiving_info['name'];
            $post_info['status'] = $receiving_info['status'];
            $post_info['pay_time'] = Date('Y-m-d', $receiving_info['pay_time']);
            $post_info['amount'] = $receiving_info['money'];
            $this->ajaxReturn(['receiving_info' => $post_info]);
        } else {
            $this->ajaxReturn(['sql' => M()->getLastSql()], -1, 'SAVE FAILD');
        }
    }

    public function wx_qrcode_pay ($receiving_id) {

        $receiving_info = M('receivingorder')->alias('ro')
        ->join(C('DB_PREFIX').'receivables r on r.receivables_id = ro.receivables_id')
        ->where(['receivingorder_id' => $receiving_id])
        ->field('r.name as order_name, receiving_num, ro.money as money, r.description as description')
        ->find();

        if (!$receiving_info) {
            $this->ajaxReturn('CAN NOT FIND THIS RECEIVING ORDER');
            return;
        }

        $payment = WeixinPay::getPayment();

        $attributes = [
            'trade_type' => 'NATIVE',
            'body' => $receiving_info['order_name'],
            'detail' => $receiving_info['description'],
            'out_trade_no' => $receiving_info['receiving_num'],
            'total_fee' => floatval($receiving_info['money'])*100,
            'notify_url' => 'http://mcrm.lyfz.net/api.php/finance/wx_qrcode_pay_callback'
        ];

        $result = $payment->order->unify($attributes);
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            $this->ajaxReturn(['code_url' => $result['code_url']]);
        }
    }

    public function alipay_web_page_pay_return () {
        echo "支付完成，请关闭当前页面，返回原网页中继续操作";
    }

    public function alipay_web_page_pay ($receiving_id) {
        $receiving_info = M('receivingorder')->alias('ro')
        ->join(C('DB_PREFIX').'receivables r on r.receivables_id = ro.receivables_id')
        ->where(['receivingorder_id' => $receiving_id])
        ->field('r.name as order_name, receiving_num, ro.money as money, r.description as description')
        ->find();

        if (!$receiving_info) {
            $this->ajaxReturn('CAN NOT FIND THIS RECEIVING ORDER');
            return;
        }
        $gateway = Alipay::getGateway();

        $gateway->setReturnUrl('http://mcrm.lyfz.net/api.php/finance/alipay_web_page_pay_return');
        $gateway->setNotifyUrl('http://mcrm.lyfz.net/api.php/finance/alipay_web_page_pay_callback');
        
        $payment = $gateway->purchase();
        $attributes = [
            'out_trade_no' => $receiving_info['receiving_num'],
            'total_amount' => $receiving_info['money'],
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'body' => $receiving_info['description'],
            'subject' => $receiving_info['order_name'],
        ];

        $response = $payment->setBizContent($attributes)->send();

        $this->ajaxReturn(['redirect_url' => $response->getRedirectUrl()]);
    }

    /** 订单插件 */
    protected function invokeOrderPlugin($receiving){
        $order_id = $receiving['receivables_id'];
        
        $plugins = M('order_plugin')->where(['order_id' => $order_id])->select();

        foreach($plugins as $plugin){
            $pluginClassName = 'App\Lib\Plugins\Order\\'.$plugin['plugin_name'];

            $pluginInstance = new $pluginClassName();

            $pluginInstance->onOrderReceiving($receiving, $plugin['plugin_params']);
        }
    }

    public function alipay_web_page_pay_callback () {
        file_put_contents('./alipay_web_page_pay_callback_data', json_encode($_POST));

        $gateway = Alipay::getGateway();

        $request = $gateway->completePurchase();

        $request->setParams($_POST);

        try {
            $response = $request->send();
            
            if($response->isPaid()){

                $out_trade_no = $_POST['out_trade_no'];

                $receiving = M('receivingorder')->where(['receiving_num' => $out_trade_no])->find();

                if ($receiving['status'] == 1) {
                    die('success'); 
                }

                M('receivingorder')->where(['receiving_num' => $out_trade_no])->save(['status' => 1, 'payment_way' => '支付宝网页支付', 'update_time' => time(), 'pay_time'=>time()]);

                $this->invokeOrderPlugin($receiving);

                die('success'); 
            }else{
                die('fail'); 
            }
        } catch (Exception $e) {
            die('fail');
        }
    }

    /**
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function wx_qrcode_pay_callback () {
        $payment = WeixinPay::getPayment();

        $response = $payment->handlePaidNotify(function($message, $fail) use ($payment){
            $receiving = M('receivingorder')->where(['receiving_num' => $message['out_trade_no']])->find();

            if (!$receiving || $receiving['status'] == 1) {
                return true; 
            }
            $message = $payment->order->queryByTransactionId($message['transaction_id']);
            if ($message['return_code'] === 'SUCCESS') {
                if (array_get($message, 'trade_state') === 'SUCCESS') {
                    M('receivingorder')->where(['receiving_num' => $message['out_trade_no']])->save(['status' => 1, 'payment_way'=>'微信扫码支付', 'update_time' => time(), 'pay_time'=>time()]);
                    $this->invokeOrderPlugin($receiving);
                    $messageClass = new Message();
                    $messageClass->sendMessage('13421637288,18665281860', 'CRM系统自动创建收款，收款金额为'.$receiving['money'].'，收款单号为：'.$receiving['receiving_num']);
                } else {
                    M('receivingorder')->where(['receiving_num' => $message['out_trade_no']])->save(['status' => -1, 'payment_way'=>'微信扫码支付', 'update_time' => time(), 'pay_time'=>time()]);
                }
            } else {
                return $fail('error');
            }

            M('wxpay_logs')->add([
                'out_trade_no' => $message['out_trade_no'],
                'openid' => $message['openid'],
                'receiving_id' => $receiving['receivingorder_id'],
                'status' => $message['result_code']
            ]);

            return true;
        });

        $response->send();
    }

    /**
     * 获取收款状态
     * @param $record_id
     */
    public function get_record_status($record_id=null)
    {
        if (empty($record_id)){
            $this->ajaxReturn(null, -1, '参数缺失');
        }
        $this->ajaxReturn(M('Receivingorder')->where('receivingorder_id = %d', $record_id)->getField('status'));
    }
    /**
     * 删除订单
     * @param null $order_id
     * @param int $completely_delete 参数为0 逻辑删除 参数为1 彻底删除 若为其它报参数缺失
     */
    public function delete_order($order_id=null,$completely_delete=0)
    {
        if (($completely_delete!=0 && $completely_delete!=1) || empty($order_id)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        //判断是否有收款记录且没有被删除的情况下 若有收款记录，便不删除 ，否则删除
        $receivingOrder = M('Receivingorder')->where('receivables_id = %d AND is_deleted = %d',$order_id, 0)->find();
        if (!$receivingOrder){//该订单下收款记录已全部删除
            $receivingOrderIsExist = M('Receivingorder')->where('receivables_id = %d',$order_id)->find();
            // is_deleted 字段  0 表示未删除  1 表示已删除
            if ((!$receivingOrderIsExist) && $completely_delete){//收款记录是否已全部物理删除
                $bool = M('Receivables')->where('receivables_id = %d',$order_id)->delete();
                M('orderPlugin')->where('order_id = %d', $order_id)->delete();//删除订单插件
                M('r_order_product')->where('order_id = %d', $order_id)->delete();
            }else{
                $bool = M('Receivables')->where('receivables_id = %d',$order_id)->save(['is_deleted' => 1, 'delete_time' => time(), 'delete_role_id' => $this->user['role_id']]);
                M('r_order_product')->where('order_id = %d', $order_id)->save(['is_deleted' => 1, 'delete_time' => time(), 'delete_role_id' => $this->user['role_id']]);
            }
            $bool?$this->ajaxReturn(null):$this->ajaxReturn(null, -1, 'DELETE FAILED');
        }else{
            $this->ajaxReturn(null, -1, 'PLEASE_DELETE_RECEIVING_RECORD_FIRST');
        }
    }

    /**
     * 删除收款记录
     * @param null $record_id 收款记录id
     * @param int $completely_delete  参数为0 逻辑删除 参数为1 彻底删除 若为其它报参数缺失
     */
    public function delete_receiving_record($record_id = null, $completely_delete=0)
    {
        if (($completely_delete!=0 && $completely_delete!=1) || empty($record_id)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        if ($completely_delete){
            $bool = M('Receivingorder')->where('receivingorder_id = %d', $record_id)->delete() ;
        }else{
            $bool = M('Receivingorder')->where('receivingorder_id = %d', $record_id)->save(['is_deleted' => 1, 'delete_time' => time(), 'delete_role_id' => $this->user['role_id']]);
        }
        $bool?$this->ajaxReturn(null):$this->ajaxReturn(null, -1, 'DELETE FAILED');
    }

    /**
     * 订单还原
     * @param $order_id
     */
    public function order_reduction($order_id)
    {
        if (empty($order_id)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $bool = M('Receivables')->where('receivables_id = %d',$order_id)->save(['is_deleted' => 0]);
        M('r_order_product')->where('order_id = %d', $order_id)->save(['is_deleted' => 0]);
        $bool?$this->ajaxReturn(null):$this->ajaxReturn(null, -1, '还原失败');
    }

    /**
     * 收款记录还原
     * @param $record_id
     */
    public function receiving_record_reduction($record_id)
    {
        if (empty($record_id)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $order_id = M('Receivingorder')->where('receivingorder_id = %d', $record_id)->getField('receivables_id');
        $orderIsExist = M('Receivables')->where('receivables_id = %d AND is_deleted = %d', $order_id, 0)->find();
        if ($orderIsExist){
            $bool = M('Receivingorder')->where('receivingorder_id = %d', $record_id)->save(['is_deleted' => 0]);
            $bool?$this->ajaxReturn(null):$this->ajaxReturn(null, -1, '还原失败');
        }else{
            $this->ajaxReturn(null, -1, '该收款记录对应的订单已删除');
        }

    }

    public function order_print($order_id = null, $receivingorder_id = null){
        if (empty($order_id) && empty($receivingorder_id)){
            $this->ajaxReturn(null, -1 ,'缺少参数');
        }
        if ($order_id){
            $order_info = M('receivables')->alias('orders')
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = orders.customer_id')
                ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->field('orders.price,orders.order_number, customer.name as customer_name, contacts.name as contacts_name, contacts.telephone as telephone')
                ->where(['receivables_id' => $order_id])
                ->find();
            $receiving_list = M('receivingorder')
                ->alias('receivingorder')
                ->where(['receivables_id' => $order_id, 'is_deleted' => 0])
                ->join(C('DB_PREFIX').'user user on user.role_id = receivingorder.owner_role_id')
                ->field('user.name as owner_name, receivingorder.description, receivingorder.money,receivingorder.payment_way')
                ->select();

            $received = array_sum(array_column($receiving_list, 'money'));
            $price = floatval($order_info['price']);
            $order_info['received'] = number_format($received, 2);
            $order_info['price'] = number_format($price, 2);
            $order_info['arrearage'] = number_format($price - $received, 2);

            $result = [[
                [
                    [
                        "TypeList"=> [
                        "复位",
                        "居中",
                        "高加倍"
                        ],
                        "TextInfo"=>"利 亚 方 舟\n"
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "复位",
                        ],
                        "TextInfo"=>"流程单号："
                    ],
                    [
                        "TypeList"=> [
                            "下划线",
                        ],
                        "TextInfo"=>$order_info['order_number']
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "复位",
                        ],
                        "TextInfo"=>"客户姓名："
                    ],
                    [
                        "TypeList"=> [
                            "下划线",
                        ],
                        "TextInfo"=>$order_info['contacts_name']
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "复位",
                        ],
                        "TextInfo"=>"联系电话："
                    ],
                    [
                        "TypeList"=> [
                            "下划线",
                        ],
                        "TextInfo"=>$order_info['telephone']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                    "TextInfo"=>" - - - - - - - - - - - - - - - -"
                    ]
                ],
            ]];
            foreach ($receiving_list as $receiving){
                $receiving_result = [
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"收款描述："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['description']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"支付方式："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['payment_way']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"  金  额："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['money']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"  接单人："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['owner_name']
                        ],
                    ],
                ];
                $result[0] = array_merge($result[0], $receiving_result);
            }
            $payStatus = [
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>" - - - - - - - - - - - - - - - -"
                    ]
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"应付金额："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>$order_info['price']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"实付金额："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>$order_info['received']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"  欠  款："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>$order_info['arrearage']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"  备  注："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>''
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"服务电话："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>"4006-067-068\n"
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"客户确认："
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>"\n"
                    ]
                ],
                [
                    [
                        "TypeList"=> [
                            "取消下划线",
                            "居中"
                        ],
                        "TextInfo"=> "打印时间：".date('Y-m-d H:i:s')
                    ],
                ],
//                [
//                    [
//                        "TypeList"=> [
//                            "取消下划线",
//                            "居中"
//                        ],
//                        "TextInfo"=> "流水号："
//                    ],
//                ],
                [
                    [
                        "TypeList"=> [
                            "取消下划线",
                            "居中"
                        ],
                        "TextInfo"=> "\n谢谢惠顾！"
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>"\n"
                    ]
                ],
            ];
//            $order_info['money_cn'] = Utils::money_number_2_cn($received);
//            $this->ajaxReturn(['order_info'=>$order_info, 'receiving_list'=>$receiving_list]);
            $result[0] = array_merge($result[0], $payStatus);
            $this->ajaxReturn($result, 200, '请求成功');
        }elseif ($receivingorder_id){
            $receivingorder_ids = explode(',',$receivingorder_id);
            $data['receivingorder_id']=['in',$receivingorder_ids];
            $order_info = M('receivingorder')->alias('receivingorder')
                ->join(C('DB_PREFIX').'receivables orders on orders.receivables_id = receivingorder.receivables_id')
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = orders.customer_id')
                ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->field('orders.price,orders.order_number, customer.name as customer_name, contacts.name as contacts_name, contacts.telephone as telephone,orders.receivables_id')
                ->where($data)
                ->find();
            $receiving_list = M('receivingorder')
                ->alias('receivingorder')
                ->where($data)
                ->join(C('DB_PREFIX').'user user on user.role_id = receivingorder.owner_role_id')
                ->join(C('DB_PREFIX').'receivables orders on orders.receivables_id = receivingorder.receivables_id')
                ->field('user.name as owner_name, receivingorder.description, receivingorder.money,receivingorder.payment_way,orders.price,orders.order_number,orders.receivables_id')
                ->limit(4)
                ->select();
            $received = array_sum(array_column($receiving_list, 'money'));
            $order_info['received'] = number_format($received, 2);
            $result = [[
                [
                    [
                        "TypeList"=> [
                            "复位",
                            "居中",
                            "高加倍"
                        ],
                        "TextInfo"=>"利 亚 方 舟\n"
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "复位",
                        ],
                        "TextInfo"=>"客户姓名："
                    ],
                    [
                        "TypeList"=> [
                            "下划线",
                        ],
                        "TextInfo"=>$order_info['contacts_name']
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "复位",
                        ],
                        "TextInfo"=>"联系电话："
                    ],
                    [
                        "TypeList"=> [
                            "下划线",
                        ],
                        "TextInfo"=>$order_info['telephone']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>" - - - - - - - - - - - - - - - -"
                    ]
                ],
            ]];
            foreach ($receiving_list as $receiving){
                $receiving_result = [
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"收款描述："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['description']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"支付方式："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['payment_way']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"  金  额："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['money']
                        ],
                    ],
                    [
                        [
                            "TypeList"=>[
                                "取消下划线"
                            ],
                            "TextInfo"=>"  接单人："
                        ],
                        [
                            "TypeList"=> [
                                "取消下划线",
                            ],
                            "TextInfo"=>$receiving['owner_name']
                        ],
                    ],
                ];
                $result[0] = array_merge($result[0], $receiving_result);
            }
            $payStatus = [
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>" - - - - - - - - - - - - - - - -"
                    ]
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"实付金额："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>$order_info['received']
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"  备  注："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>''
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"服务电话："
                    ],
                    [
                        "TypeList"=> [
                            "取消下划线",
                        ],
                        "TextInfo"=>"4006-067-068\n"
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "取消下划线"
                        ],
                        "TextInfo"=>"客户确认:"
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>"\n"
                    ]
                ],
                [
                    [
                        "TypeList"=> [
                            "取消下划线",
                            "居中"
                        ],
                        "TextInfo"=> "打印时间：".date('Y-m-d H:i:s')
                    ],
                ],
                [
                    [
                        "TypeList"=> [
                            "取消下划线",
                            "居中"
                        ],
                        "TextInfo"=> "\n谢谢惠顾！"
                    ],
                ],
                [
                    [
                        "TypeList"=>[
                            "复位"
                        ],
                        "TextInfo"=>"\n"
                    ]
                ],
            ];
            $result[0] = array_merge($result[0], $payStatus);
            $this->ajaxReturn($result, 200, '请求成功');
        }
    }

    private function sendFinanceDailyReport($order_count, $money_of_order, $money_of_receiving, $money_of_other_payment, $money_of_other_income, $total_of_receiving, 
    $income_clearly, $money_of_order_month, $money_of_receiving_month, $money_of_other_payment_month, $money_of_other_income_month,
    $total_of_receiving_month, $income_clearly_month, $product_sales_info_by_category){
        $content = 
            "今日订单数:".$order_count."\n".
            '今日订单金额:'.$money_of_order.",本月订单金额:".$money_of_order_month."\n".
            '今日实收金额:'.$money_of_receiving.",本月实收金额:".$money_of_receiving_month."\n".
            '今日其他收入:'.$money_of_other_income.",本月其他收入:".$money_of_other_income_month."\n".
            '今日其他支出:'.$money_of_other_payment.",本月其他支出:".$money_of_other_payment_month."\n".
            '今日总收入:'.$total_of_receiving.",本月总收入:".$total_of_receiving_month."\n".
            '今日净收入:'.$income_clearly.",本月净收入:".$income_clearly_month."\n";
        $salesInfo = '';
        foreach ($product_sales_info_by_category as $category_name => $sales_count) {
            $salesInfo .= $category_name . ': ' . $sales_count . "\n";
        }

        if ($salesInfo) {
            $content .= "产品分类销售情况：\n" . $salesInfo;
        }

        $message = new Message();

        $message->sendMessage('18602065722,18665281860,13421637288', "CRM财务信息:\n".$content);


        $notify_userlist = M('user')->where(['user_id' => ['in', [35, 2, 3, 4]]])->getField('weixinid', true);

        $app = WeixinEnterprise::getApp();
        $textCard = new TextCard();
        $textCard->title = '每日财务汇报';
        $textCard->description = $content;
        $textCard->url = 'http://mcrm.lyfz.net/m';
        $app->messenger->message($textCard)->toUser($notify_userlist)->send();
        // return $this->sendWxEnterpriseMessage($notify_userlist, $content);
    }


    public function daily_report () {
        $today_start = strtotime(date('Y-m-d'));
        // 今日产品销售数
        // $order_count = M('Receivables')->alias('Receivables')
        // ->join('inner join ' . C('DB_PREFIX') . 'Receivingorder receiving on receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
        // ->where(['pay_time' => ['gt', $today_start], 'is_deleted' => 0])
        // ->count();

        // 今日订单金额  去掉已结账的条件
        $money_of_order = M('Receivables')->where(['pay_time' => ['gt', $today_start], 'is_deleted' => 0])->sum('price');

        // 今日实收金额
        // $money_of_receiving = M('Receivingorder')->where(['pay_time' => ['gt', $today_start], 'status' => 1, 'is_deleted' => 0])->sum('money');
        $money_of_receiving = M('Receivingorder')->where(['pay_time' => ['gt', $today_start], 'is_deleted' => 0])->sum('money');

        // 今日其他支出
        // $money_of_other_payment = M('Paymentorder')->where(['pay_time' => ['gt', $today_start], 'status' => 1, 'is_deleted' => 0])->sum('money')??0;
        $money_of_other_payment = M('Paymentorder')->where(['pay_time' => ['gt', $today_start], 'is_deleted' => 0])->sum('money')??0;

        // 今日其他收入
        // $money_of_other_income = M('Otherrevenue')->where(['pay_time' => ['gt', $today_start], 'status' => 1, 'is_deleted' => 0])->sum('money')??0;
        $money_of_other_income = M('Otherrevenue')->where(['pay_time' => ['gt', $today_start], 'is_deleted' => 0])->sum('money')??0;


        // 总收入 = 今日实收金额 + 其他收入
        $total_of_receiving = $money_of_receiving + $money_of_other_income;

        // 净收入 = 总收入 - 总支出
        $income_clearly = $total_of_receiving - $money_of_other_payment;

        $month_start = strtotime(date('Y-m-01'));
        // 本月订单金额
        $money_of_order_month = M('Receivables')->where(['pay_time' => ['gt', $month_start], 'is_deleted' => 0])->sum('price');

        // 本月实收金额
        // $money_of_receiving_month = M('Receivingorder')->where(['pay_time' => ['gt', $month_start], 'status' => 1, 'is_deleted' => 0])->sum('money');
        $money_of_receiving_month = M('Receivingorder')->where(['pay_time' => ['gt', $month_start],  'is_deleted' => 0])->sum('money');

        // 本月其他支出
        // $money_of_other_payment_month = M('Paymentorder')->where(['pay_time' => ['gt', $month_start], 'status' => 1, 'is_deleted' => 0])->sum('money')??0;
        $money_of_other_payment_month = M('Paymentorder')->where(['pay_time' => ['gt', $month_start], 'is_deleted' => 0])->sum('money')??0;

        // 本月其他收入
        // $money_of_other_income_month = M('Otherrevenue')->where(['pay_time' => ['gt', $month_start], 'status' => 1, 'is_deleted' => 0])->sum('money')??0;
        $money_of_other_income_month = M('Otherrevenue')->where(['pay_time' => ['gt', $month_start],  'is_deleted' => 0])->sum('money')??0;

        // 总收入 = 本月实收金额 + 本月其他收入
        $total_of_receiving_month = $money_of_receiving_month + $money_of_other_income_month;

        // 净收入 = 总收入 - 总支出
        $income_clearly_month = $total_of_receiving_month - $money_of_other_payment_month;

        $product_sales_info_by_category = M('ROrderProduct')->alias('order_product')
        ->join('inner join '. C('DB_PREFIX'). 'receivables receivables on order_product.order_id = receivables.receivables_id and receivables.is_deleted = 0')
        ->join('inner join '. C('DB_PREFIX'). 'receivingorder receiving on receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
        ->join(C('DB_PREFIX').'product product on product.product_id = order_product.product_id')
        ->join(C('DB_PREFIX').'product_category product_category on product_category.category_id = product.category_id')
        ->where(['order_product.create_time' => ['gt', $today_start]])
        ->group('product_category.category_id')
        ->getField('product_category.name as category_name, count(product_category.category_id) as count');

        $order_count = array_sum($product_sales_info_by_category);

        $this->sendFinanceDailyReport($order_count, 
            $money_of_order,
            $money_of_receiving,
            $money_of_other_payment,
            $money_of_other_income,
            $total_of_receiving,
            $income_clearly,
        
            $money_of_order_month,
            $money_of_receiving_month,
            $money_of_other_payment_month,
            $money_of_other_income_month,
            $total_of_receiving_month,
            $income_clearly_month,

            $product_sales_info_by_category
        );
        
        // 产品 分类销售量
    }

    /**
     * 财务日报统计
     * @param null $year
     * @param null $month
     * @param int $is_first_have_month
     */
    public function daily($year=null,$month=null,$is_first_have_month=1)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        if ($is_first_have_month){//上半月
            $start_time = strtotime( $year.'-'.$month.'-01');//某年某月的开始时间
            $end_time = strtotime($year.'-'.$month.'-16')+86399;//某年某月的结束时间
        }else{//下半月
            $start_time = strtotime( $year.'-'.$month.'-17');//某年某月的开始时间
            $end_time = strtotime($year.'-'.$month.'-'.date('t', $start_time))+86399;//某年某月的结束时间
        }

        $where_shoukuan['pay_time'] = [array('egt', $start_time), array('elt', $end_time), 'and'];

        $sub_sql = M('receivables')->alias('receivables')
            ->where($where_shoukuan)
            ->field('receivables.price, receivables.pay_time')
            ->buildSql();

        $receivables_statistics_by_days = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%d\')')
            ->field('sum(price) as total_receivables, date_format(FROM_UNIXTIME(pay_time), \'%d\') as day')
            ->select();
        $where_shoukuan['money'] = ['gt', 0];

        $sub_sql = M('receivingorder')->alias('receiving')
            ->where($where_shoukuan)
            ->field('receiving.money, receiving.pay_time')
            ->buildSql();

        $receivied_statistics_by_days = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%d\')')
            ->field('sum(money) as total_receivied, date_format(FROM_UNIXTIME(pay_time), \'%d\') as day')
            ->select();

        $sub_sql = M('Paymentorder')->alias('paymentorder')
            ->where($where_shoukuan)
            ->field('paymentorder.money, paymentorder.pay_time')
            ->buildSql();

        $paymentorder_statistics_by_days = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%d\')')
            ->field('sum(money) as total_paymentorder, date_format(FROM_UNIXTIME(pay_time), \'%d\') as day')
            ->select();

        $sub_sql = M('Otherrevenue')->alias('otherrevenue')
            ->where($where_shoukuan)
            ->field('otherrevenue.money, otherrevenue.pay_time')
            ->buildSql();

        $otherrevenue_statistics_by_days = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%d\')')
            ->field('sum(money) as total_otherrevenue, date_format(FROM_UNIXTIME(pay_time), \'%d\') as day')
            ->select();
        $day_count['shoukuan'] = array_map('intval',array_column($receivables_statistics_by_days, 'total_receivables'));
        $day_count['shijishoukuan'] = array_map('intval',array_column($receivied_statistics_by_days, 'total_receivied'));
        $day_count['paymentorder'] = array_map('intval',array_column($paymentorder_statistics_by_days, 'total_paymentorder'));
        $day_count['otherrevenue'] =  array_map('intval',array_column($otherrevenue_statistics_by_days, 'total_otherrevenue'));

        foreach ($day_count as $key=>$day_type){
            $type_total[$key] = number_format(array_sum($day_type),2);
        }
        $this->ajaxReturn([ 'day_count' => $day_count, 'type_total'=>$type_total ]);
    }

    /**
     * 财务月统计
     * @param null $year
     */
    public function month($year = null){
        $start_time = strtotime( ($year ? $year : date('Y')).'-01-01');
        $end_time = strtotime( ($year ? $year : date('Y')).'-12-31') + 86399;
        $where_shoukuan['pay_time'] = [array('egt', $start_time), array('elt', $end_time), 'and'];
        $where_shoukuan['is_deleted'] = 0;
        $sub_sql = M('receivables')->alias('receivables')
            ->where($where_shoukuan)
            ->field('receivables.price, receivables.pay_time')
            ->buildSql();

        $receivables_statistics_by_months = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%m\')')
            ->field('sum(price) as total_receivables, date_format(FROM_UNIXTIME(pay_time), \'%m\') as month')
            ->select();

        $where_shoukuan['money'] = ['gt', 0];

        $sub_sql = M('receivingorder')->alias('receiving')
            ->where($where_shoukuan)
            ->field('receiving.money, receiving.pay_time')
            ->buildSql();

        $receivied_statistics_by_months = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%m\')')
            ->field('sum(money) as total_receivied, date_format(FROM_UNIXTIME(pay_time), \'%m\') as month')
            ->select();

        $sub_sql = M('Paymentorder')->alias('paymentorder')
            ->where($where_shoukuan)
            ->field('paymentorder.money, paymentorder.pay_time')
            ->buildSql();

        $paymentorder_statistics_by_months = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%m\')')
            ->field('sum(money) as total_paymentorder, date_format(FROM_UNIXTIME(pay_time), \'%m\') as month')
            ->select();

        $sub_sql = M('Otherrevenue')->alias('otherrevenue')
            ->where($where_shoukuan)
            ->field('otherrevenue.money, otherrevenue.pay_time')
            ->buildSql();

        $otherrevenue_statistics_by_months = M()->table("$sub_sql t")->group('date_format(FROM_UNIXTIME(t.pay_time), \'%m\')')
            ->field('sum(money) as total_otherrevenue, date_format(FROM_UNIXTIME(pay_time), \'%m\') as month')
            ->select();

        $moon_count['shoukuan'] = array_map('intval',array_column($receivables_statistics_by_months, 'total_receivables'));
        $moon_count['shijishoukuan'] = array_map('intval',array_column($receivied_statistics_by_months, 'total_receivied'));
        $moon_count['paymentorder'] = array_map('intval',array_column($paymentorder_statistics_by_months, 'total_paymentorder'));
        $moon_count['otherrevenue'] =  array_map('intval',array_column($otherrevenue_statistics_by_months, 'total_otherrevenue'));

        foreach ($moon_count as $key=>$moon_type){
            $type_total[$key] = number_format(array_sum($moon_type),2);
        }

        $this->ajaxReturn([ 'moon_count' => $moon_count, 'type_total' => $type_total]);
    }
}