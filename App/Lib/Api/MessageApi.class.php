<?php
use App\Lib\Api\ApiBase;
use App\Extend\Http;
use App\Lib\Plugins\Product\ProductMessage;
use App\Lib\Model\OrderProduct;

/**
 * 短信接口
 */
class MessageApi extends ApiBase
{

    const ACCOUNT  = 'admin';
    const PASSWORD = '123';
    const BASE_URL = 'http://msg.lyfz.net:8600/ISmsService/';


    /**
     * 发送短信
     */
    public function sendMessage()
    {
        $posts = I('post.');
        $params = [
            'account'  =>  $posts['account'],
            'password' =>  strtoupper(md5($posts['account'].$posts['password'])),
            'phone'    =>  $posts['prone'],
            'content'  =>  $posts['content'],
            'time'     =>  date('Y-m-d H:i:s')
        ];
        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'SendMessage', $params));
    }

    /**
     * 查询账户余额接口
     * @param null $account
     * @param null $password
     */
    public function queryBalance($account = null, $password = null)
    {
        if (is_null($account) || is_null($password)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $params = [
            'account'  =>  $account,
            'password' =>  strtoupper(md5($account.$password))
        ];
        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'QueryBalance', $params));
    }

    /**
     * 查询短信充值单价
     */
    public function smsPrice()
    {
        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'SmsPrice'));
    }

    /**
     * 查询短信发送通道接口
     */
    public function smsChannel()
    {
        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'SmsChannel')['data']);
    }

    /**
     * 获取Token
     * @return mixed
     */
    public function getToken()
    {
        $params = [
            'account'   =>  self::ACCOUNT,
            'password'  =>  strtoupper(md5(self::ACCOUNT.self::PASSWORD))
        ];
        $information = Http::PostJson(self::BASE_URL.'GetToken', $params);
        if ($information['code'] == 200){
            return $information['data'];
        }else{
            return $information;
        }
    }

    /**
     * 获取指定短信账号客户信息
     * @param $account
     */
    public function getCustomerAccount($account = null)
    {
        if (is_null($account) || empty($account)){
            $this -> ajaxReturn(null,-1 , '请输入账号信息');
        }else{
            $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'GetCustomerAccount/'.$account.'/'.$this->getToken()['token']));
        }
    }

    /**
     * 获取指定短信账号客户信息
     * @param $order_product_id
     */
    public function getCustomerAccountById($order_product_id = null)
    {
        if (is_null($order_product_id) || empty($order_product_id)){
            $this -> ajaxReturn(null,-1 , '参数缺失');
        }else{
            $account = OrderProduct::getMessageAccount($order_product_id);
            $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'GetCustomerAccount/'.$account.'/'.$this->getToken()['token']));
        }
    }

    /**
     * 获取充值记录
     */
//    public function getRechargeRecord()
//    {
//        $posts = I('post.');
//        $params = [
//            'QueryCondition' =>   isset($posts['QueryCondition']) ? $posts['QueryCondition'] : '',
//            'OrderStr'       =>   isset($posts['OrderStr']) ? $posts['OrderStr'] : '',
//            'PageSize'       =>   isset($posts['PageSize']) ? $posts['PageSize'] : 10,
//            'PageIndex'      =>   isset($posts['PageIndex']) ? $posts['PageIndex'] : 1,
//        ];
//        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'GetRechargeRecord/'.$this->getToken()['token'], $params));
//    }

    /**
     * 获取短信帐号客户列表
     * 'QueryCondition'=>"CustomerName like '%江西%'"
     * 'OrderStr'=>'FilingDate  desc'
     */
//    public function getCustomerList()
//    {
//        $posts = I('post.');
//        $params = [
//            'QueryCondition' =>   isset($posts['QueryCondition']) ? $posts['QueryCondition'] : '',
//            'OrderStr'       =>   isset($posts['OrderStr']) ? $posts['OrderStr'] : '',
//            'PageSize'       =>   isset($posts['PageSize']) ? $posts['PageSize'] : 10,
//            'PageIndex'      =>   isset($posts['PageIndex']) ? $posts['PageIndex'] : 1,
//        ];
//        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'GetCustomerList/'.$this->getToken()['token'], $params));
//    }

    /**
     * 获取短信发送记录接口
     * 'QueryCondition'=>"SMSAccount='681900'"
     * 'OrderStr'=>'SendDateTime desc'
     */
//    public function getMessageRecord()
//    {
//        $posts = I('post.');
//        $params = [
//            'QueryCondition' =>   isset($posts['QueryCondition']) ? $posts['QueryCondition'] : '',
//            'OrderStr'       =>   isset($posts['OrderStr']) ? $posts['OrderStr'] : '',
//            'PageSize'       =>   isset($posts['PageSize']) ? $posts['PageSize'] : 10,
//            'PageIndex'      =>   isset($posts['PageIndex']) ? $posts['PageIndex'] : 1,
//        ];
//        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'GetMessageRecord/'.$this->getToken()['token'], $params));
//    }
    /**
     * @param string $method getRechargeRecord  getCustomerList getMessageRecord
     * @param array $args
     * @return mixed|void
     */
    public function __call($method, $args)
    {
        if (in_array($method, ['getrechargerecord', 'getcustomerlist', 'getmessagerecord'])){
            $posts = I('post.');
            $params = [
                'QueryCondition' =>   isset($posts['QueryCondition']) ? $posts['QueryCondition'] : '',
                'OrderStr'       =>   isset($posts['OrderStr']) ? $posts['OrderStr'] : '',
                'PageSize'       =>   isset($posts['PageSize']) ? $posts['PageSize'] : 10,
                'PageIndex'      =>   isset($posts['PageIndex']) ? $posts['PageIndex'] : 1,
            ];
            $this -> ajaxReturn(Http::PostJson(self::BASE_URL.$method.'/'.$this->getToken()['token'], $params));
        }else{
            $this -> ajaxReturn(null, -1, '你访问的方法不存在');
        }
    }

    /**
     * 添加或修改客户信息接口
     */
    public function customerSmsAccount()
    {
        $posts = I('post.');
        if (empty($posts['customerName'])){
            $this -> ajaxReturn(null, -1, '企业名不能为空');
        }
        if (empty($posts['shopName'])){
            $this -> ajaxReturn(null, -1, '门店名称不能为空');
        }
        if (empty($posts['signature'])){
            $this -> ajaxReturn(null, -1, '短信签名不能为空 签名格式为“【安徒生童话】”');
        }
        $params = [
            'Account'           =>     isset($posts['account']) ? $posts['account'] : '',
            'Password'          =>     isset($posts['password']) ? $posts['password'] : '',
            'ShopName'          =>     isset($posts['shopName']) ? $posts['shopName'] : '',
            'Signature'         =>     isset($posts['signature']) ? $posts['signature'] : '',
            'CustomerName'      =>     isset($posts['customerName']) ? $posts['customerName'] : '',
            'DomainName'        =>     isset($posts['domainName']) ? $posts['domainName'] : '',
            'SMSInterface'      =>     isset($posts['smsinterface']) ? $posts['smsinterface'] : '',
            'ValidDateRuns'     =>     isset($posts['validDateRuns']) ? $posts['validDateRuns'] : date("Y-m-d H:i:s",strtotime("+1 year")),
            'PermanentRegister' =>     $posts['PermanentRegister'] ? true: false,
//            'Sex'               =>     isset($posts['Sex']) ? $posts['Sex'] : '',
            'ContactPerson'     =>     isset($posts['contactperson']) ? $posts['contactperson'] : '',
            'Telephone'         =>     isset($posts['telephone']) ? $posts['telephone'] : '',
            'QQ'                =>     isset($posts['qq']) ? $posts['qq'] : '',
            'MicroSignal'       =>     isset($posts['microsignal']) ? $posts['microsignal'] : '',
            'Address'           =>     isset($posts['address']) ? $posts['address'] : '',
            'Remarks'           =>     isset($posts['remarks']) ? $posts['remarks'] : '',
        ];
        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'customerSmsAccount/'.$this->getToken()['token'], $params));
    }

    /**
     * 删除短信客户
     * 传入数组的形式$accountList = ['1', '2', '3']
     * 传入字符串的形式’1,2,3‘以逗号隔开
     * @param array|null $accountList
     */
    public function deleteCustomer($accountList = null)
    {
//        $this -> ajaxReturn($accountList);
        if (is_null($accountList) || empty($accountList)){
            $this -> ajaxReturn(null, -1 , '您并没有选择要删除的用户');
        }elseif(is_array($accountList)){
            $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'DeleteCustomer/'.$this->getToken()['token']), ['AccountList' => $accountList]);
        }else{
            $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'DeleteCustomer/'.$this->getToken()['token']), ['AccountList' => explode(',', $accountList)]);
        }
    }
    /**
     * 短信充值
     */
    public function recharge()
    {
        $posts = I('post.');
        if (empty($posts['account'])){
            $this -> ajaxReturn(null, -1, '请添加被充值的用户');
        }
        if (is_null($posts['money'])){
            $this -> ajaxReturn(null, -1, '请添加要充值的金额');
        }
        if (empty($posts['rechargeCount'])){
            $this -> ajaxReturn(null, -1, '请添加要充值的条数');
        }
       $params = [
           'Account'       =>  isset($posts['account']) ? $posts['account'] : '',
           'Money'         =>  isset($posts['money']) ? floatval($posts['money']) : 0,
           'RechargeCount' =>  isset($posts['rechargeCount']) ? (int)$posts['rechargeCount'] : 0,
           'Remarks'       =>  isset($posts['remarks']) ? $posts['remarks'] : '',
       ];
       $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'SmsRecharge/'.$this->getToken()['token'], $params));

    }

    /**
     * 短信充值接口  将原来直接充值的改为了创建短信充值订单
     */
    public function smsRecharge()
    {
        $posts = I('post.');
        // if (empty($posts['account'])){
        //     $this -> ajaxReturn(null, -1, '请添加被充值的用户');
        // }
        if (is_null($posts['money'])){
            $this -> ajaxReturn(null, -1, '请添加要充值的金额');
        }
        if (empty($posts['rechargeCount'])){
            $this -> ajaxReturn(null, -1, '请添加要充值的条数');
        }

        // $owner_role_id = session('role_id') ?? $this->user['role_id'];
        $owner_role_id = intval($posts['owner_role_id']);


//        $params = [
//            'Account'       =>  isset($posts['account']) ? $posts['account'] : '',
//            'Money'         =>  isset($posts['money']) ? floatval($posts['money']) : 0,
//            'RechargeCount' =>  isset($posts['rechargeCount']) ? (int)$posts['rechargeCount'] : 0,
//            'Remarks'       =>  isset($posts['remarks']) ? $posts['remarks'] : '',
//        ];
//        $this -> ajaxReturn(Http::PostJson(self::BASE_URL.'SmsRecharge/'.$this->getToken()['token'], $params));
        $this->ajaxReturn(['order_id' => ProductMessage::createOrder($posts['order_product_id'], floatval($posts['money']), $owner_role_id, intval($posts['rechargeCount']), $posts['remarks'])]);
    }


}