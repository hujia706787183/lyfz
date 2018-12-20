  <?php
use App\Lib\Api\ApiBase;
use App\Extend\Message;
class DoorserviceApi extends ApiBase
{

    public function _initialize(){
        parent::_initialize();
        $action = array(
            'permission' => [],
            'allow' => ['getsheduling', 'getdoorserver', 'getdropinfo','editdropinfo','getimage', 'resend'],
            'roles' => [
                'customer' => []
            ]
        );
        try {
            $this->checkPermission($action);
        } catch (\Exception $e){
            $code = -1;
            $e->getCode() && $code = $e->getCode();
            $this->ajaxReturn(null, $code, L($e->getMessage()));
            return;
        }
    }

    /**
     * 调度安排列表
     * @param int $page
     * @param int $page_size
     * @param string $customer_name
     */
    public function getScheduling($page = 1, $page_size = 15, $customer_name = '')
    {
        $mod = M('onsiteservice');
//        // 培训状态
//        if (I('get.status')) $mag['onsiteservice.status'] = I('get.status');
//        // 培训类型
//        if (I('get.type')) $mag['onsiteservice.type'] = I('get.type');
//        //地址
//        $address = I('get.address');
//        if (I('get.address')) $mag['onsiteservice.address'] = ['LIKE',"%{$address}%"];
        //影楼名称
        if (!empty($customer_name)){
            $customer_name = preg_replace('/^|$|\s+/', '%', trim($customer_name));
            $mag['onsiteservice.customer_name'] = ['LIKE',"%{$customer_name}%"];
        }
//        //提交人
//        if (I('get.sid')) $mag['onsiteservice.sid'] = I('get.sid');
//        //培训老师
//        if (I('get.tid')) $mag['onsiteservice.tid'] = I('get.tid');
        // 客户类型
        switch (I('get.industry')) {
            case '1':
                $flag = 'flag=1';
                break;

            case '2':
                $flag = "flag=2";
                break;
        }
        $count = $mod->alias('onsiteservice')
            ->field('receivables.*,onsiteservice.*,CASE count(receivables.customer_id) WHEN 0 THEN 2 ELSE 1 END AS flag')
            ->join(C('DB_PREFIX').'receivables receivables ON receivables.customer_id = onsiteservice.customer_id','LEFT')
            ->where($mag)
            ->having($flag)
            ->group('onsiteservice.id')
            ->select() ;
        $total=count($count);
        $list = $mod->alias('onsiteservice')
            ->field('receivables.*,onsiteservice.*,CASE count(receivables.customer_id) WHEN 0 THEN 2 ELSE 1 END AS flag')
            ->join(C('DB_PREFIX').'receivables receivables ON receivables.customer_id = onsiteservice.customer_id','LEFT')
            ->where($mag)
            ->having($flag)
            ->group('onsiteservice.id')
            ->order('id desc')
            ->page($page, $page_size)
            ->select() ;
        $sql = M()->getLastSql();
        $schedulingInfo = [
            'list' => $list,
            'page_info' => [ 'size' => $page_size, 'total' => $total ],
            'sql'=>$sql
        ];
        $this->ajaxReturn($schedulingInfo);
    }

    /**
     * 上门服务列表
     * @param int $page
     * @param int $page_size
     * @param string $customer_name
     */
    public function getDoorServer($page = 1, $page_size = 15, $customer_name='')
    {
        $subwhere['status'] = ['neq', 4];
        $subwhere['return_status'] = ['neq', 3];
        $subwhere['_logic'] = 'OR';

        $where['tid'] = $_SESSION['role_id'] ?? $this->user['role_id'];
        $where['send_status'] = 2;
        $where['_complex'] = $subwhere;

        $mod = M('onsiteservice');
        if (!empty($customer_name)) {
            $customer_name = preg_replace('/^|$|\s+/', '%', trim($customer_name));
            $where['customer_name'] = array('LIKE',"%{$customer_name}%");
        }
        $total = $mod->where($where)->count();

        $list = $mod -> page($page, $page_size) ->order('id desc')-> where($where)->select();
        $sql = M()->getLastSql();
        $doorServiceInfo = [
            'list'  => $list ?? [],
            'page_info' => ['size' => $page_size, 'total' => $total],
            'sql'=>$sql
        ];
        $this->ajaxReturn($doorServiceInfo);
    }

    /**
     * 获取某个上门详情表单数据
     * @param $id
     */
    public function getDropInfo($id = null)
    {
        if (empty($id)){
            $this->ajaxReturn(null, -1, 'id不能为空');
        }
        $mod = M('onsiteservice');
        $list = $mod -> where("$id = id") -> find();
        // $list['order_product_id']=33930;
        // if ($order_product_id = $list['order_product_id']){ //有订单产品id时
        //     $money = M('ROrderProduct')->alias('order_product')
        //         ->join(C('DB_PREFIX').'receivables receivables ON receivables.receivables_id = order_product.order_id AND receivables.is_deleted=0')
        //         ->join(C('DB_PREFIX').'receivingorder receivingorder ON receivingorder.receivables_id = receivables.receivables_id AND receivingorder.is_deleted=0')
        //         ->where(['order_product.id'=>$order_product_id, 'order_product.is_deleted'=>0])
        //         // ->field('sum(receivingorder.money) receipt,order_product.price')
        //         ->field('sum(receivingorder.money) receipt,receivables.price')
        //         ->find();
        //     // $list['final_payment'] = ($money['receipt']>=$money['price']) ? 'on' : null;
        //     $list['final_payment'] = $money['price']-$money['receipt'];
        // }else{
        //     // $list['final_payment'] = 'on';
        //     $list['final_payment'] = 0;
        // }
        $order_list = M('Receivables')->alias('receivables')
            ->join(C('DB_PREFIX').'receivingorder receiving ON receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0 and receiving.status = 1')
            ->join(C('DB_PREFIX').'role role ON role.role_id = receivables.owner_role_id')
            ->join(C('DB_PREFIX').'user user ON user.user_id = role.user_id')
            ->where(['customer_id'=>$list['customer_id'],'receivables.is_deleted' => 0])
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
            ->order('order_time desc')
            ->select();
        $debt = 0;
        foreach ($order_list as $order){
            $debt += $order['price'] - $order['received'];
        }
        $list['final_payment'] = $debt;
        $this->ajaxReturn($list);
    }

    /**
     * 上门详情表单提交
     */
    public function editDropInfo()
    {
        $mod = M('onsiteservice');
        $arr = I('post.');
        $list = $mod->where("id = %d", $arr['id'])->save($arr);
        if ($list !== false) {
            $onsiteservice = $mod ->where(['id'=>$arr['id']])->field('status,customer_id,type')->find();
            // if ($onsiteservice['status'] == 4 && $onsiteservice['type'] != 5){ //注意：在此步骤之后的对该条数据修改也会触发短信发送 更换铂金版暂时不发短信
            if ($onsiteservice['status'] == 4){// 去掉更换铂金版不发短信的限制
                $message = new Message();
                $message ->sendProcessMessage($onsiteservice['customer_id'], Message::AFTER_INSTALLATION, null, null, $arr['id'], $this->user['role_id']);//安装后
            }
            $this->ajaxReturn(null, 0, '保存成功');
        }else{
            $this->ajaxReturn(null, -1, '保存失败');
        }
    }

    /**
     * 获取合同、合影图片
     * @param $id
     *
     */
    public function getImage($id)
    {
        if (empty($id)){
            $this->ajaxReturn(null, -1, 'id不能为空');
        }
        $customer_id = M('onsiteservice') -> getFieldById($id, 'customer_id');

        // $groupPhoto = M('r_doorservice_photo')->alias('doorservice_photo')//合影数据查找
        //     ->where('doorservice_photo.doorservice_id=%d', $id)
        //     ->join(C('DB_PREFIX').'file file ON file.file_id=doorservice_photo.file_id')
        //     ->getField('file_path', true);
        // $contactPhoto = M('r_customer_file')->alias('customer_file')//合同数据查找
        //     ->where('customer_file.customer_id=%d', $customer_id)
        //     ->join(C('DB_PREFIX').'file file ON file.file_id=customer_file.file_id')
        //     ->getField('file_path', true);

        $groupPhoto = M('r_doorservice_photo')->alias('doorservice_photo')//合影数据查找
        ->where('doorservice_photo.doorservice_id=%d', $id)
            ->join(C('DB_PREFIX').'file file ON file.file_id=doorservice_photo.file_id')
            ->field('file_path src')
            ->select();
        $contactPhoto = M('r_customer_file')->alias('customer_file')//合同数据查找
        ->where('customer_file.customer_id=%d', $customer_id)
            ->join(C('DB_PREFIX').'file file ON file.file_id=customer_file.file_id')
            ->field('file_path src')
            ->select();
        $summaryPhoto = M('r_doorservice_summary')->alias('doorservice_summary')//客户总结截图查找
        ->where('doorservice_summary.doorservice_id=%d', $id)
            ->join(C('DB_PREFIX').'file file ON file.file_id=doorservice_summary.file_id')
            ->field('file_path src')
            ->select();
        $this->ajaxReturn(compact('groupPhoto', 'contactPhoto', 'summaryPhoto'));
    }

    public function resend($id)
    {
        if (empty($id)){
            $this->ajaxReturn(null, -1, 'doorservice_id不能为空');
        }
        $onsiteservice = M('onsiteservice')->where(['id'=>$id])->field('status,customer_id,type')->find();
        // if ($onsiteservice['type'] == 5){ // 更换铂金版暂时不发短信
        //     $this->ajaxReturn(null ,-1, '更换铂金版,暂时不发短信');
        // }
        if ($onsiteservice['status'] == 4){
            $message = new Message();
            $message ->sendProcessMessage($onsiteservice['customer_id'], Message::AFTER_INSTALLATION, null, null, $id, session('role_id') ?? $this->user['role_id'])
                ? $this->ajaxReturn(null, 0, '发送成功')
                : $this->ajaxReturn(null, -1, '发送失败');
        }else {
            $this->ajaxReturn(null ,-1, '培训还未结束');
        }
    }
}