<?php

namespace App\Modules\NetTrainService\controllers;

use App\Extend\Message;
use App\Extend\Weixin\Enterprise as WeixinEnterprise;
use EasyWeChat\Kernel\Messages\TextCard;

class Service extends \Action {
    protected $pageSize = 100;
    public function __construct(){
        header("Content-type: text/html; charset=utf-8");
        $action = array(
            'permission'=>array('getproblemlist','postanswer'),
            'allow'=>array('lookupdepartmentstaff')
        );
        B('Authenticate', $action);


        $this->table_name = 'net_train_service';
    }

    public function search(){
        $a = I('get.c');
        $param = I('param.');
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $key = I('param.key');
        $search = I('param.search');
        $this->assign('key', $key);
        $this->assign('search', $search);
        $mod = M($this->table_name);
        $params['c'] = $a;
        $params['p'] = $p;
        $params['key'] = $key;
        $params['search'] = $search;
        $params['train_status'] = I('param.train_status');
        $params['return_status'] = I('param.return_status');
        $params['manyidu'] = I('param.manyidu');
        $params['send_status'] = I('param.send_status');
        $this->assign('params', $params);
  
        $where = [];
        // if ($param['train_status']==1){
        //     // 未上门
        //     $where['status'] = ['in', [1,2]];
        // }elseif ($param['train_status']==2){
        //     //已上门
        //     $where['status'] = ['in', [3,4]];
        // }else{
        //     unset($where['status']);
        // }
        if ($param['train_status']) {
            $where['status'] = $param['train_status'];
        }
        if ($param['return_status']==1){
            $where['return_status'] = ['in', [1,2]]; //1未联系 2联系不上
        }elseif ($param['return_status']==2){//已回访
            $where['return_status'] = 3;
        }else{
            unset($where['return_status']);
        }

        if ($param['manyidu']){//满意度
            $where['manyidu'] = $param['manyidu'];
        }else{
            unset($where['manyidu']);
        }

        if ($param['send_status']){//满意度
            $where['send_status'] = $param['send_status'];
        }else{
            unset($where['send_status']);
        }
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        switch ($a) {
            case '1':
                //首页搜索
                switch ($key) {
                    // 影楼名称
                    case 'customer_name':
                        $search = preg_replace('/^|$|\s+/', '%', trim($search));
                        $where['customer_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人
                    case 'contacts_name':
                        $where['contacts_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人电话
                    case 'contacts_telephone':
                        $where['contacts_telephone'] = array('LIKE',"%{$search}%");
                        break;
                    // 上门老师
                    case 'operator_name':
                        $where['operator_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 销售人员
                    case 'saleman':
                        $where['saleman'] = array('LIKE',"%{$search}%");
                        break;
                    // 提交人
                    case 'submitter':
                        $where['submitter'] = array('LIKE',"%{$search}%");
                        break;
                }
                import('@.ORG.Page');// 导入分页类
                $count = $mod->where($where)->count();
                $Page = new \Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
                $Page -> parameter = http_build_query($params);
                $show  = $Page->show();// 分页显示输出
                $list = $mod->where($where)->page($p, $listrows)->select();

                $this->assign('page',$show);// 赋值分页输出
                $this -> assign('list',$list);
                $this -> display('index');
                break;
            case '2':
                // 上门搜索
                 switch ($key) {
                    // 影楼名称
                    case 'customer_name':
                        $search = preg_replace('/^|$|\s+/', '%', trim($search));
                        $where['customer_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人
                    case 'contacts_name':
                        $where['contacts_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人电话
                    case 'contacts_telephone':
                        $where['contacts_telephone'] = array('LIKE',"%{$search}%");
                        break;
                    // 上门老师
                    case 'operator_name':
                        $where['operator_name'] = array('LIKE',"%{$search}%");
                        break;
                     // 销售人员
                     case 'saleman':
                         $where['saleman'] = array('LIKE',"%{$search}%");
                         break;
                     // 提交人
                     case 'submitter':
                         $where['submitter'] = array('LIKE',"%{$search}%");
                         break;
                }
                $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'), 0);//获取下属role_id
                array_unshift($teamMembersRoleIds, session('role_id'));
                $where['tid'] = ['in', $teamMembersRoleIds];
                import('@.ORG.Page');// 导入分页类
                $count = $mod->where($where)->count();
                $Page = new \Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
                $Page -> parameter = http_build_query($params);
                $show  = $Page->show();// 分页显示输出
                $list = $mod->alias('net_train_service')->field('net_train_service.*,order_product.product_name')
                    ->join(C('DB_PREFIX').'r_order_product order_product ON net_train_service.order_product_id = order_product.id','LEFT')->where($where)->order('id desc')->page($p, $listrows)->select();
                $this->assign('page',$show);// 赋值分页输出
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;

            case '3':
                // 回访搜索
                switch ($key) {
                    // 影楼名称
                    case 'customer_name':
                        $search = preg_replace('/^|$|\s+/', '%', trim($search));
                        $where['customer_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人
                    case 'contacts_name':
                        $where['contacts_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人电话
                    case 'contacts_telephone':
                        $where['contacts_telephone'] = array('LIKE',"%{$search}%");
                        break;
                    // 上门老师
                    case 'operator_name':
                        $where['operator_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 销售人员
                    case 'saleman':
                        $where['saleman'] = array('LIKE',"%{$search}%");
                        break;
                    // 提交人
                    case 'submitter':
                        $where['submitter'] = array('LIKE',"%{$search}%");
                        break;
                }

                import('@.ORG.Page');// 导入分页类
                $count = $mod->where($where)->count();
                $Page = new \Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
                $Page -> parameter = http_build_query($params);
                $show  = $Page->show();// 分页显示输出
                // $list = $mod->where($where)->page($p.',15')->select();
                $list =$mod->alias('net_train')->field('net_train.*,order_product.product_name')->join(C('DB_PREFIX').'r_order_product order_product ON order_product.id = net_train.order_product_id') -> page($p, $listrows)->where($where)->order('addtime desc')-> select();
                $this->assign('page',$show);// 赋值分页输出
                $this -> assign('list',$list);
                $this -> display('visit');
                break;
            case '4':
                // 回访审批
                switch ($key) {
                    // 影楼名称
                    case 'customer_name':
                        $search = preg_replace('/^|$|\s+/', '%', trim($search));
                        $where['customer_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人
                    case 'contacts_name':
                        $where['contacts_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 首要联系人电话
                    case 'contacts_telephone':
                        $where['contacts_telephone'] = array('LIKE',"%{$search}%");
                        break;
                    // 上门老师
                    case 'operator_name':
                        $where['operator_name'] = array('LIKE',"%{$search}%");
                        break;
                    // 销售人员
                    case 'saleman':
                        $where['saleman'] = array('LIKE',"%{$search}%");
                        break;
                    // 提交人
                    case 'submitter':
                        $where['submitter'] = array('LIKE',"%{$search}%");
                        break;
                }

                import('@.ORG.Page');// 导入分页类
                $count = $mod->where($where)->count();
                $Page = new \Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
                $Page -> parameter = http_build_query($params);
                $show  = $Page->show();// 分页显示输出
                $list = $mod->where($where)->page($p, $listrows)->select();
                $this->assign('page',$show);// 赋值分页输出
                $this -> assign('list',$list);
                $this -> display('verify');
                break;
            default:
                echo "<script>history.back();</script>";
                break;
        }
    }

    public function delete(){
        $mod = M($this->table_name);
        $id['id'] = I('get.id');
        $tab = I('cookie.tab','') ;
        $list = $mod -> where($id) -> delete();
        
        if ($list) {
//                $this->redirect('index') ;
            alert('success','删除成功', $_SERVER['HTTP_REFERER'].$tab);
        }else{
            echo "<script>alert('删除失败');history.back();</script>";
        }
        
    } 


    //提交上门首页
    public function index(){
        $mod = M($this->table_name);
        $user_id = I('session.user_id');
        import("@.ORG.Page");

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $status = I('get.status', 5);
        $where['status'] = $status;
        if ($status == -1)  unset($where['status']);
        $where['submitter_id'] = ['in', '0,'.$user_id];

        $list = $mod->page($p.','.$listrows)->order('addtime desc')->where($where)->select();
        $count = $mod->where($where)->count();
        //实例化分页
        $Page = new \Page($count, $listrows);
        // 分页导航信息
        $show = $Page -> show();

        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $this->display();
    }
    
    //添加上门需求 添加从客户详情页处添加上门服务时，自动添加订单产品id（铂金版的），如果存在多个订单产品(默认最后一个) 如果要选择其他的，请在搜索结果处选择对应的订单产品
    public function addHomeDemand(){
        import("@.ORG.Page");
        $m_customer = M('Customer');
        $m_r_order_product = M('ROrderProduct');
        $m_onsiteservice = M($this->table_name);
        $position_id = I('session.position_id');
        $customer_id = I('get.customer_id') ;
        $product_ids = M('product')->where(['category_id' => ['in', '3']])->getField('product_id', true);//获取营销产品id
        if (!empty($customer_id)){
            $customerData = $m_customer->alias('customer')
                ->field('customer.name customer_name,customer.address,contacts.name contacts_name,contacts.telephone,customer.customer_id,contacts.contacts_id,user.name owner_name,customer.owner_role_id,order_product.id order_product_id')
                ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
                ->join(C('DB_PREFIX').'user user ON user.user_id=customer.owner_role_id')
                ->join(C('DB_PREFIX').'r_order_product order_product ON order_product.customer_id=customer.customer_id')
                ->where(['customer.customer_id'=>$customer_id, 'order_product.is_deleted'=>0, 'order_product.product_id'=>['in', $product_ids]])
                ->order('order_product.id desc')
                ->find();
            $this->assign('customer',$customerData);
        }
        $mod = M('permission');
        $list = $mod -> where("position_id = $position_id") -> select();
        $this -> assign('list',$list);
        $is_schedu_update = $mod -> where("position_id = %d AND url = '%s' ", $position_id, 'Doorservice/schedu_update')->find();
        $this -> assign('is_schedu_update',$is_schedu_update);

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        if ($customer_name = I('get.customer_name')){
            $search = preg_replace('/^|$|\s+/', '%', trim($customer_name));
            $where['customer.name'] = array('LIKE',"%{$search}%");
        }
        if (I('get.start_time') && I('get.start_time')){
            $where['order_product.create_time'] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', null, 'strtotime')+86399]];
        }elseif(I('get.end_time')){
            $where['order_product.create_time'] = ['lt',I('get.end_time', null, 'strtotime')+86399];
        }elseif(I('get.start_time')){
            $where['order_product.create_time'] = ['gt',I('get.start_time', null, 'strtotime')];
        }
        $this->listrows = $listrows;

        $customer_ids = $m_onsiteservice->where(['status'=>['neq', 4]])->order('customer_id desc')->getField('customer_id', true);//获取已提交上门的客户id 且上门状态还未结束
        $customer_ids && $where['order_product.customer_id'] = ['not in',$customer_ids];

        $tablename = $this->table_name;
        $order_product_list = $m_r_order_product->alias('order_product')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = order_product.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->join(C('DB_PREFIX').'user user ON user.role_id = order_product.owner_role_id')
            ->join('left join '.C('DB_PREFIX')."$tablename $tablename on $tablename.order_product_id = order_product.id")
            ->field("
                customer.customer_id,
                customer.name customer_name,
                contacts.name contacts_name,
                contacts.telephone,
                order_product.id order_product_id,
                order_product.product_name,
                user.name owner_role_name,
                order_product.create_time,
                customer.address,
                user.role_id owner_role_id,
                count($tablename.order_product_id) as service_times
            ")
            ->where($where)
            ->where(['order_product.is_deleted'=>0, 'order_product.product_id'=>['in', $product_ids]])
            ->order('service_times asc , order_product.id desc')
            ->group('order_product.id')
            ->page($p, $listrows)
            ->select();

        $count = $m_r_order_product->alias('order_product')
            ->where($where)
            ->where(['order_product.is_deleted' => 0, 'order_product.product_id' => ['in', $product_ids]])
            ->count();
        $Page = new \Page($count, $listrows);
        $show = $Page -> show();
        $this->assign('not_the_door', $order_product_list);
        $this->assign('page',$show);
        $this->display();
    }

    public function insertHomeDemand(){
        $mod = M($this->table_name);
        $arr = I('post.');
        $tab = I('cookie.tab','') ;
        $arr['submitter_id'] = I('session.user_id');
        $arr['submitter'] = I('session.name');
        $is_exist = $mod -> where('type = %d AND order_product_id = "%s"', $arr['type'], $arr['order_product_id']) ->find();//判断是否之前是否有过提交
        if (is_null($is_exist)){
            if ($arr['operator_name']) $arr['status'] = 1; //如果此时填了上门人直接将状态改为已安排
            $list = $mod -> add($arr);
            if ($list) {
                if (1 == I('get.is_next')){
                    alert('success','添加成功', U('schedu_update','id='.$list));
                }else{
                    if ($customer_id = I('get.customer_id_flag')){
                        alert('success','添加成功', U('customer/view','id='.$customer_id.$tab));
                    }else{
                        echo "<script>window.location.href='".U('me', ['viewOptions'=>'me'])."'</script>";
                    }
                }
            }else{
                echo "<script>alert('提交失败');history.back();</script>";
            }
        }else{
            echo "<script>alert('提交失败,".$is_exist['submitter']."已经提交过了！');history.go(-2);</script>";
        }
    }
    /**
     * 上门需求编辑 */
    public function update(){
        $permission = M('permission');
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> where("$id = id") -> find();
        $this -> assign('vo',$list);
        $position_id = I('session.position_id');
        $is_schedu_update = $permission -> where("position_id = %d AND url = '%s' ", $position_id, 'Doorservice/schedu_update')->find();
        $this -> assign('is_schedu_update',$is_schedu_update);
        $this -> display();
    }
    //调度安排
    public function edit(){
        $mod = M($this->table_name);
        $arr = I('post.');
        $id = I('post.id');
        if ($arr['operator_name']) $arr['status'] = 1; //如果此时填了上门人直接将状态改为已安排
        $list = $mod ->where("$id = id")-> save($arr);
        if ($list!==false) {
            echo "<script>window.location.href='".U('Doorservice/index')."'</script>";
        }else{
            echo "<script>alert('提交失败');history.back();</script>";
        }
    }

    /**
     * 上门调度编辑
     */
    public function schedu_update()
    {
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> where("$id = id") -> find();
        $this -> assign('vo',$list);
        $this -> display();
    }

    public function schedu_edit()
    {
        $mod = M($this->table_name);
        $arr = I('post.');
        $id = I('post.id');
        if ($arr['status']==3){
            $arr['remote_time'] = date('Y-m-d H:i:s');
            $arr['status_change_time'] = time();
        }
        $list = $mod ->where("$id = id")-> save($arr);
        if (false !== $list) {
            $message = new Message();
            $onsiteservice = $mod ->where("$id = id")->field('type,order_product_id,customer_id')->find();
            if ($onsiteservice['order_product_id'] && (1 == $onsiteservice['type'])){
                $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8']])->getField('product_id', true);//获取铂金版,黄金版,标准版产品id
                if (M('ROrderProduct')->where(['id'=>$onsiteservice['order_product_id'], 'product_id'=>['in',$platinum_ids]])->find()){
                    $message ->sendProcessMessage($onsiteservice['customer_id'], Message::BEFORE_INSTALLATION, $onsiteservice['order_product_id']);//安装前
                }
            }
            if (1 == I('get.is_next'))
                echo "<script>window.location.href='".U('teacher','id='.$id)."'</script>";
            else
                echo "<script>window.location.href='".U('scheduling')."'</script>";
        }else{
            echo "<script>alert('提交失败');history.back();</script>";
        }
    }

    //回访调查
    public function huifang(){
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> where("$id = id") -> find();
        $this -> assign('vo',$list);
        $this -> display();
    }

    public function huifangbianxie(){
        $mod = M($this->table_name);
        $arr = I('post.');
        $id = I('post.id');
        $list = $mod ->where("$id = id")-> save($arr);
        if ($list) {
            if (1 == I('get.is_next'))
                echo "<script>window.location.href='".U('verify_edit', 'id='.$id)."'</script>";
            else
                echo "<script>window.location.href='".U('visit')."'</script>";
        }else{
            echo "<script>alert('提交失败');history.back();</script>";
        }
    }

    /**
     * 回访审批
     * 显示自己团队成员上门客户，且已经经过回访调查的客户
     */
    public function verify()
    {
        $mod = M($this->table_name);
        $gets = I('get.');
        import("@.ORG.Page");
        $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'));//获取团队成员role_id
        $where['tid'] = ['in', $teamMembersRoleIds];
        $where['return_status'] = 3;//已回访

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }

        if ($gets['send_status']){
            $where['send_status'] = $gets['send_status'];
        }else{//全部
            unset($where['send_status']);
        }

        $this->listrows = $listrows;
        $count = $mod->where($where)->count();
        $page = new \Page($count, $listrows);
        $show = $page -> show();
        $list = $mod->alias('net_train')->field('net_train.*,order_product.product_name')->join(C('DB_PREFIX').'r_order_product order_product ON order_product.id = net_train.order_product_id') -> page($p, $listrows)->order('addtime desc')->where($where)->select();

        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $this->display();
    }

    public function verify_edit()
    {
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> where("$id = id") -> find();
        $this -> assign('vo',$list);
        $this -> display();
    }

    public function verify_update()
    {
        $mod = M($this->table_name);
        $arr = I('post.');
        $id = I('post.id');
        $arr['step'] = 6 ;
        $list = $mod ->where("$id = id")-> save($arr);
        if ($list) {
            echo "<script>window.location.href='".U('verify')."'</script>";
        }else{
            echo "<script>alert('提交失败');history.back();</script>";
        }
    }
    // 老师填的两个
    public function teacher(){
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> where("$id = id") -> find();
        $this -> assign('vo',$list);
        $this -> display();
    }

    public function teacherxiede(){
        $mod = M($this->table_name);
        $arr = I('post.');
        $id = I('post.id');
        
        if (trim($arr['remore_time'])) {
            $arr['status'] = 4; //培训结束
        }

        $list = $mod ->where("$id = id")-> save($arr);
        if ($list!==false) {

            $onsiteservice = $mod ->where(['id'=>$arr['id']])->field('status,customer_id,type')->find();
            if ($arr['remore_time'] && (1 == $onsiteservice['type'])){
                $message = new Message();
                $customer_id = $mod ->where("$id = id")->getField('customer_id');
                $message ->sendProcessMessage($customer_id, Message::AFTER_INSTALLATION, null, null, $id);//安装后
            }
            if (1 == I('get.is_next'))
                echo "<script>window.location.href='".U('huifang', 'id='.$id)."'</script>";
            else
                echo "<script>window.location.href='".U('thedoorserver')."'</script>";
        }else{
            echo "<script>alert('提交失败');history.back();</script>";
        }
    }

    // 详细查看
    public function remoteServiceView(){
        $mod = M($this->table_name);
        $id = I('get.id');
        $list = $mod -> alias('onsiteservice')
//            ->join(C('DB_PREFIX').'user user ON user.user_id = onsiteservice.tid')
            -> where("onsiteservice.id = $id")
            -> find();
        $groupPhoto = M('r_doorservice_photo')->alias('doorservice_photo')//合影数据查找
            ->where('doorservice_photo.doorservice_id=%d', $id)
            ->join(C('DB_PREFIX').'file file ON file.file_id=doorservice_photo.file_id')
            ->field('file_path')
            ->select();
        $customerFile = M('r_customer_file')->alias('customer_file')//合同数据查找
            ->where('customer_file.customer_id=%d', $list['customer_id'])
            ->join(C('DB_PREFIX').'file file ON file.file_id=customer_file.file_id')
            ->field('file_path')
            ->select();
        $this->assign('groupPhoto', $groupPhoto);
        $this->assign('customerFile', $customerFile);

        $this -> assign('vo',$list);

        $this -> display('remoteServiceView');
    }

    /**
     * 获取客户答题情况
     */
    public function getCustomerAnswer()
    {
        $id = I('get.id');
        $answers = M('onsiteservice_customer')->alias('onsiteservice_customer')
            ->join(C('DB_PREFIX').'onsiteservice_answer onsiteservice_answer ON onsiteservice_answer.onsiteservice_customer_id = onsiteservice_customer.id')
            ->join(C('DB_PREFIX').'onsiteservice_problem onsiteservice_problem ON onsiteservice_answer.problem_id = onsiteservice_problem.problem_id')
            ->where(['onsiteservice_customer.onsiteservice_id' => $id])
            ->field('onsiteservice_problem.problem_id, onsiteservice_problem.problem, onsiteservice_answer.answer')
            ->select();
        $suggestion = M('onsiteservice_customer')->where(['onsiteservice_id'=>$id])->getField('suggestion');
        $this->assign('answers', $answers);
        $this->assign('suggestion', $suggestion);
        $this->display('getCustomerAnswer');
    }
    
    /**
     * 上门服务
     * 显示安排给自己的还未上门的客户
     */
    public function theDoorServer(){
        $mod = M($this->table_name);
        $gets = I('get.');
        import("@.ORG.Page");
        $role_id = session('role_id');
        // $role_ids = \App\Lib\Model\User::getTeamMembersRoleIds($role_id, 0);
        // array_unshift($role_ids, $role_id);
        // $where['tid'] = ['in', $role_ids];
        $where['tid'] = $role_id;// 默认显示自己上门的
        // $where['remote_time'] = ['exp','is null'];
        $where['status'] = ['in', [ 1 ,2 ,3 ]];

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }

        // if ($gets['train_status']==1){
        //     // 未上门
        //     $where['status'] = ['in', [1,2]];
        // }elseif ($gets['train_status']==2){
        //     //已上门
        //     $where['status'] = ['in', [3]];
        // }else{
        //     unset($where['status']);
        // }

        // if ($gets['return_status']==1){
        //     // 未回访
        //     unset($where['status']);
        //     $where['return_status'] = ['in', [1,2]]; //1未联系 2联系不上
        // }elseif ($gets['return_status']==2){
        //     //已回访
        //     unset($where['status']);
        //     $where['return_status'] = 3;
        // }
        
        $count = $mod->where($where)->count();
        $this->listrows = $listrows;
        $page = new \Page($count, $listrows);
        $show = $page -> show();
        $list = $mod->alias('net_train_service')->field('order_product.product_name,net_train_service.*')
            ->join(C('DB_PREFIX').'r_order_product order_product ON net_train_service.order_product_id = order_product.id','LEFT') -> page($p, $listrows) ->order('addtime desc')->where($where)-> select();
        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $this->display();
    }

    /**
     * 回访调查
     * 显示各团队（（该角色和该角色下属））成员上门客户 显示已上门未回访
     */
    public function visit(){
        $mod = M($this->table_name);
        $gets = I('get.');
        import("@.ORG.Page");
        $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'));//获取团队成员role_id （该角色和该角色下属）
        $where['tid'] = ['in', $teamMembersRoleIds];
        $where['status'] = 4;//已上门 培训结束
        $where['return_status'] = ['in', [1,2]]; //1未联系 2联系不上

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }

        if ($gets['return_status']==1){
            // 未回访
            $where['return_status'] = ['in', [1,2]]; //1未联系 2联系不上
        }elseif ($gets['return_status']==2){
            //已回访
            $where['return_status'] = 3;
        }else{
            unset($where['return_status']);
        }

        if ($gets['manyidu']){
            $where['manyidu'] = $gets['manyidu'];
        }

        $this->listrows = $listrows;
        $count = $mod->where($where)->count();
        $page = new \Page($count, $listrows);
        $show = $page -> show();
        $list = $mod->alias('net_train')->field('net_train.*,order_product.product_name')->join(C('DB_PREFIX').'r_order_product order_product ON order_product.id = net_train.order_product_id') -> page($p, $listrows)->where($where)->order('addtime desc')-> select();

        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $this->display();
    }
    

    public function scheduling()
    {
        import("@.ORG.Page");
        $mod = M($this->table_name);
        $a = I('get.soso');
        $mag['onsiteservice.status'] = ['in', [2,5]];

        if ($a == 'soso') {
            // 培训状态
            $keyword = I('get.status');
            if ($keyword == '0') {
                unset($mag['onsiteservice.status']);
            }else{
                $mag['onsiteservice.status'] = $keyword;
            }
            // 培训类型
            $keyword1 = I('get.type');
            if ($keyword1 !== '0') {
                $mag['onsiteservice.type'] = $keyword1;
            }
            //地址
            $keyword2 = I('get.address') ;
            if ($keyword2!==''){
                $mag['onsiteservice.address'] = ['LIKE',"%{$keyword2}%"] ;
            }
            //影楼名称
            $keyword6 = I('get.customer_name');
            if ($keyword6!==''){
                $keyword6 = preg_replace('/^|$|\s+/', '%', trim($keyword6));
                $mag['onsiteservice.customer_name'] = ['LIKE',"%{$keyword6}%"] ;
            }
            //提交人
            $keyword4 = I('get.sid');
            if (!empty($keyword4)) {
                $mag['onsiteservice.sid'] = $keyword4;
            }
            //培训老师
            $keyword5 = I('get.tid');
            if (!empty($keyword5)) {
                $mag['onsiteservice.tid'] = $keyword5;
            }

            $search = I('get.') ;
            $this -> assign('search',$search);
        }
        $count = $mod->alias('onsiteservice')
            ->where($mag)
            ->group('onsiteservice.id')
            ->select();

        $count = count($count);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $page = new \Page($count, $listrows);
        $page->parameter = http_build_query($search);
        $show = $page -> show();
        $list = $mod->alias('onsiteservice')
            ->field('order_product.product_name,onsiteservice.*,customer.name customer_name, contacts.name contacts_name, contacts.telephone contacts_telephone')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id=onsiteservice.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
            ->join(C('DB_PREFIX').'r_order_product order_product ON order_product.id = onsiteservice.order_product_id')
            ->where($mag)
            ->group('onsiteservice.id')
            ->order('addtime desc')
            ->page($p, $listrows)
            ->select() ;
        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $this -> display();

    }
    // 有需求
    public function scheduling2(){
        import('ORG.Util.Page');
        $mod = M($this->table_name);
        $made = M('sign');
        $a = I('post.soso');
    
        if ($a == 'soso') {
            // 培训状态
            $keyword = I('post.status');
            if ($keyword !== '0') {
                $mag['status'] = $keyword;
            }
            // 培训类型
            $keyword1 = I('post.type');
            if ($keyword1 !== '0') {
                $mag['type'] = $keyword1;
            }
            $keyword4 = I('post.sid');
            if (!empty($keyword4)) {
                $mag['sid'] = $keyword4;
            }
            $keyword5 = I('post.tid');
            if (!empty($keyword5)) {
                $mag['tid'] = $keyword5;
            }

            // 客户类型
            $user_type = I('post.industry'); 
                switch ($user_type) {
                    case '0':
                            // all
                            $mag['lyfz_business.status_id'] = array('in','1,2,3,5,6,7,100');
                            $keyword3 = I('post.address');
                            $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                            $mag_c['address'] = array('LIKE',"%{$keyword3}%");

                        break;

                    case '1':
                            // 订单
            
                                $mag['lyfz_business.status_id'] = array('in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                                $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                            
                    break;

                    case '2':
                            // 意向   
                            
                                $mag['lyfz_business.status_id'] = array('not in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                                $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                    
                        break;

                    case '3':
                        // 线索
                        
                            $mag['lyfz_business.status_id'] = array('in','30');
                            $keyword3 = I('post.address');
                            $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                            $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                        break;
                }

                $count = $mod -> join('lyfz_business ON lyfz_business.customer_id = lyfz_onsiteservice.customer_id') -> group('lyfz_onsiteservice.customer_id') -> where($mag)->where($mag_a) -> count();
                $page = new \Page($count,28);
                $show = $page -> show();
                
            $data = $mod -> join('lyfz_business ON lyfz_business.customer_id = lyfz_onsiteservice.customer_id') -> group('lyfz_onsiteservice.customer_id') -> where($mag)->where($mag_a) -> limit($page->firstRow.','.$page->listRows) -> select();

            if ($data){
                $arr['count'] = $count;
                $arr['list']  = $data;
                $res->meta->code = 200;
                $res->meta->message = '成功';
                $res->body = $arr;
            }else{
                $res->meta->code = 400;
                $res->meta->message = '失败';
            }
            $this -> assign('page',$show);
            $this -> assign('list',$data);
            // ajax返回值
            $this->ajaxReturn($res);
            exit;
        
        }
    }
    // 没有需求
    public function scheduling1(){
            $mod = M($this->table_name);
            $made = M('sign');
                    // 培训状态
            $keyword = I('post.status');
            if ($keyword !== '0') {
                $mag['status'] = $keyword;
            }
            // 培训类型
            $keyword1 = I('post.type');
            if ($keyword1 !== '0') {
                $mag['type'] = $keyword1;
            }
            $keyword4 = I('post.sid');
            if (!empty($keyword4)) {
                $mag['sid'] = $keyword4;
            }
            $keyword5 = I('post.tid');
            if (!empty($keyword5)) {
                $mag['tid'] = $keyword5;
            }
            $pno1 = I('post.pno');
            $psize = I('post.psize');
            $pno = $psize*$pno1;
            // 客户类型
            $user_type = I('post.industry'); 
                switch ($user_type) {
                    case '0':
                            // all
                            $mag['lyfz_business.status_id'] = array('in','1,2,3,5,6,7,100');
                            $keyword3 = I('post.address');
                            $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                            $mag_c['address'] = array('LIKE',"%{$keyword3}%");

                        break;

                    case '1':
                            // 订单
                        if (isset($mag)) {
                                $mag['lyfz_business.status_id'] = array('in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                                $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                            }else{
                                $mag['lyfz_business.status_id'] = array('in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag['address'] = array('LIKE',"%{$keyword3}%");

                                $mod = M('business');

                                    $list = $mod -> join('lyfz_customer ON lyfz_customer.customer_id = lyfz_business.customer_id') -> where($mag) -> field('lyfz_customer.*, lyfz_business.name as business_name')-> limit($pno.','.$psize) -> select();
                                    $count = $mod -> join('lyfz_customer ON lyfz_customer.customer_id = lyfz_business.customer_id') -> where($mag) -> field('lyfz_customer.*, lyfz_business.name as business_name') -> count();

                                if ($list){
                                    $arr['list']  = $list;
                                    $arr['count']  = $count;
                                    $res->meta->code = 200;
                                    $res->meta->message = '成功';
                                    $res->body = $arr;
                                }else{
                                    $res->meta->code = 400;
                                    $res->meta->message = '失败';
                                }
                            $this->ajaxReturn($res);
                            exit;
                            }
                            
                    break;

                    case '2':
                            // 意向   
                            if (isset($mag)) {
                                $mag['lyfz_business.status_id'] = array('not in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                                $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                            }else{
                                $mag['lyfz_business.status_id'] = array('not in','5,6,7,100');
                                $keyword3 = I('post.address');
                                $mag['address'] = array('LIKE',"%{$keyword3}%");
                                $mod = M('business');

                                    $count = $mod -> join('lyfz_customer ON lyfz_customer.customer_id = lyfz_business.customer_id') -> where($mag) -> field('lyfz_customer.*, lyfz_business.name as business_name') -> count();
                                    $list = $mod -> join('lyfz_customer ON lyfz_customer.customer_id = lyfz_business.customer_id') -> where($mag) -> field('lyfz_customer.*, lyfz_business.name as business_name') -> limit($pno.','.$psize) -> select();  

                                if ($list){
                                    $arr['list']  = $list;
                                    $arr['count']  = $count;
                                    $res->meta->code = 200;
                                    $res->meta->message = '成功';
                                    $res->body = $arr;
                                }else{
                                    $res->meta->code = 400;
                                    $res->meta->message = '失败';
                                }
                            $this->ajaxReturn($res);
                            exit;
                            }
                    break;

                    case '3':
                        // 线索
                        if (!isset($mag)) {
                            $mag['lyfz_business.status_id'] = array('in','30');
                            $keyword3 = I('post.address');
                            $mag_a['lyfz_onsiteservice.address'] = array('LIKE',"%{$keyword3}%");
                            $mag_c['address'] = array('LIKE',"%{$keyword3}%");
                        }else{
                            $keyword3 = I('post.address');
                            $mag['address'] = array('LIKE',"%{$keyword3}%");
                            $mod = M('leads');

                            $count = $mod -> group('customer_id') ->where($mag) -> count();
                            $list = $mod -> group('customer_id') ->where($mag) -> limit($pno.','.$psize) -> select();

                            if ($list){
                                $arr['list']  = $list;
                                $arr['count']  = $count;
                                $res->meta->code = 200;
                                $res->meta->message = '成功';
                                $res->body = $arr;
                            }else{
                                $res->meta->code = 400;
                                $res->meta->message = '失败';
                            }
                        $this->ajaxReturn($res);
                        exit;
                        }
                    break;
                }

                $count = $mod -> join('lyfz_business ON lyfz_business.customer_id = lyfz_onsiteservice.customer_id') -> group('lyfz_onsiteservice.customer_id') -> where($mag)->where($mag_a) -> count();

                
            $data = $mod -> join('lyfz_business ON lyfz_business.customer_id = lyfz_onsiteservice.customer_id') -> group('lyfz_onsiteservice.customer_id') -> where($mag)->where($mag_a) -> limit($pno.','.$psize) -> select();

            if ($data){
                $arr['count'] = $show;
                $arr['list']  = $data;
                $res->meta->code = 200;
                $res->meta->message = '成功';
                $res->body = $arr;
            }else{
                $res->meta->code = 400;
                $res->meta->message = '失败';
            }
            $this -> assign('page',$show);
            $this -> assign('list',$data);
            // ajax返回值
            $this->ajaxReturn($res);
            exit;
    }

    // 上门统计
    public function aboveStatistics(){
        $azics = I('get.c');
        $user = M('User') ;
        $onsiteservice = D('OnSiteServiceView') ;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 1 ;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        import('@.ORG.Page');// 导入分页类

        if ($azics == 'asd') {
            $zxc = I('post.');
            $where = [] ;
            if($zxc['start_time'] != '' && $zxc['end_time'] != ''){
                $where['onsiteservice.addtime'] = array(array('EGT',$zxc['start_time']),array('ELT',$zxc['end_time']),'AND');
            }
            if($zxc['department'] != 'all'){
                $where['position.department_id'] = intval($zxc['department']) ;
            }
            if($zxc['sid'] != 'all'&&$zxc['sid']!=null){
                $where['user.user_id'] = $zxc['sid'] ;
            }
            /*if ($zxc['manyidu']!=0){
                $where['onsiteservice.manyidu'] = $zxc['manyidu'] ;
            }*/
            $this -> assign('zxc',$zxc);
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $list = $user -> alias('user')
            ->field('user.name,COUNT(onsiteservice.manyidu) sum,COUNT(onsiteservice.manyidu=1 or null) one,COUNT(onsiteservice.manyidu=2 or null) two,COUNT(onsiteservice.manyidu=3 or null) three,COUNT(onsiteservice.manyidu=4 or null) four')
            ->join(C('DB_PREFIX').'onsiteservice onsiteservice ON onsiteservice.tid=user.user_id','LEFT')
            ->join(C('DB_PREFIX').'role role ON role.role_id = user.role_id')
            ->join(C('DB_PREFIX').'position position ON position.position_id=role.position_id')
            ->page($p, $listrows)
            ->group('user.user_id')
            ->where($where)
            ->select() ;
        $this -> assign('date',$list);
        $count = $user->where($where)->count();
        $Page = new \Page($count, $listrows);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter = "id=".$id.'&status=' . $status;
        $show  = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出

        $sum['sum'] = $onsiteservice  ->where($where)->count('id'); //合计总数
        $sum['one'] = $onsiteservice  ->where($where)->where('manyidu=1')->count('id');
        $sum['two'] = $onsiteservice  ->where($where)->where('manyidu=2')->count('id');
        $sum['three'] = $onsiteservice  ->where($where)->where('manyidu=3')->count('id');
        $sum['four'] = $onsiteservice  ->where($where)->where('manyidu=4')->count('id');
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        $this->assign('sum',$sum);// 赋值分页输出
        $this->assign('role_department_res',$role_department_res);
        $this->display();
    }

    //上门情况分析
    public function theDoorAnalysis(){
        $azics = I('get.c');
        $user = M('User') ;
        $onsiteservice = D('OnSiteServiceView') ;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 1 ;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        import('@.ORG.Page');// 导入分页类
        if ($azics == 'asd') {
            $zxc = I('post.');
            $where = [] ;
            if($zxc['start_time'] != '' && $zxc['end_time'] != ''){
                $where['onsiteservice.addtime'] = array(array('EGT',$zxc['start_time']),array('ELT',$zxc['end_time']),'AND');
            }
            if($zxc['department'] != 'all'){
                $where['position.department_id'] = intval($zxc['department']) ;
            }
            if($zxc['sid'] != 'all'&&$zxc['sid']!=null){
                $where['user.user_id'] = $zxc['sid'] ;
            }
//            if ($zxc['type']!=0){
//                $where['type'] = $zxc['type'] ;
//            }
            $this -> assign('zxc',$zxc);
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $list = $user -> alias('user')
            ->field('user.name,position.department_id,COUNT(onsiteservice.type) sum,COUNT(onsiteservice.type=1 or null) one,COUNT(onsiteservice.type=2 or null) two,COUNT(onsiteservice.type=3 or null) three,COUNT(onsiteservice.type=4 or null) four')
            ->join(C('DB_PREFIX').'onsiteservice onsiteservice ON onsiteservice.tid=user.user_id','LEFT')
            ->join(C('DB_PREFIX').'role role ON role.role_id = user.role_id')
            ->join(C('DB_PREFIX').'position position ON position.position_id=role.position_id')
//            ->join(C('DB_PREFIX').'role_department role_department ON role_department.department_id=position.department_id')
            ->page($p, $listrows)
            ->group('user.user_id')
            ->where($where)
            ->select() ;
        $sum['sum'] = $onsiteservice->where($where)-> count('customer_id'); //合计总数
        $sum['one'] = $onsiteservice ->where($where)->where('type=1')->count('customer_id'); //合计非常满意
        $sum['two'] = $onsiteservice ->where($where)->where('type=2')->count('customer_id'); //合计满意
        $sum['three'] = $onsiteservice ->where($where)->where('type=3')->count('customer_id'); //合计一般
        $sum['four'] = $onsiteservice ->where($where)->where('type=4')->count('customer_id');//合计客怨
        $this -> assign('date',$list);
        $count = $user->count();
        $Page = new \Page($count, $listrows);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter = "id=".$id.'&status=' . $status;
        $show  = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('sum',$sum) ;
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        $this->assign('role_department_res',$role_department_res);
        $this->display();
    }


    //培训能力分析
    public function trainingAbility(){
        //获取所有部门选项数据
        $azics = I('get.c');
        $user = M('User') ;
        $onsiteserviceview = D('OnSiteServiceView') ;
        $onsiteservice = M($this->table_name) ;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 1 ;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        import('@.ORG.Page');// 导入分页类
        if ($azics == 'asd') {
            $zxc = I('post.');
            $where = [] ;
            if($zxc['start_time'] != '' && $zxc['end_time'] != ''){
                $where['onsiteservice.addtime'] = array(array('EGT',$zxc['start_time']),array('ELT',$zxc['end_time']),'AND');
            }
            if($zxc['department'] != 'all'){
                $where['position.department_id'] = intval($zxc['department']) ;
            }
            if($zxc['sid'] != 'all'&&$zxc['sid']!=null){
                $where['user.user_id'] = $zxc['sid'] ;
            }
            $this -> assign('zxc',$zxc);
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $list = $user -> alias('user')
            ->field('user.name,user.user_id,position.department_id')
            ->join(C('DB_PREFIX').'onsiteservice onsiteservice ON onsiteservice.tid=user.user_id','LEFT')
            ->join(C('DB_PREFIX').'role role ON role.role_id = user.role_id')
            ->join(C('DB_PREFIX').'position position ON position.position_id=role.position_id')
            ->page($p, $listrows)
            ->group('user.user_id')
            ->where($where)
            ->select() ;
        for ($type=2;$type<5;$type++){
            $data[$type] = $onsiteservice->alias('a')
                ->field('count(a.tid) sum,a.id,a.tid user_id')
                ->join(C('DB_PREFIX').'onsiteservice b ON a.tid=b.last_id')
                ->group('a.id')
                ->where('a.customer_id = b.customer_id AND b.type=%d',$type)
                ->select();
            foreach ($list as $k=>$v){
                foreach ($data[$type] as $value){
                    if ($v['user_id']==$value['user_id'])
                        $list[$k][$type] = $value['sum'] ;
                    elseif(!$list[$k][$type])
                        $list[$k][$type] = 0 ;
                }
            }
        }
        $data = $onsiteservice->field('operator_name,tid,count(tid) sum')->where('last_id is null OR last_id=0')->group('tid')->select() ;
        foreach ($list as $k=>$v){
            foreach ($data as $value){
                if ($v['user_id']==$value['tid'])
                    $list[$k][1] = $value['sum'] ;
                elseif(!$list[$k][1])
                    $list[$k][1] = 0 ;
            }
        }
//        $sum['sum'] = $onsiteservice->where($where)-> count('customer_id'); //合计总数
        $sum['one'] = $onsiteserviceview ->where($where)->where('last_id is null OR last_id=0 AND type=1')->count('id'); //合计正常客户
        $sum['two'] = $onsiteserviceview ->where($where)->where('last_id is not null AND type=2')->count('id'); //合计被第二次上门
        $sum['three'] = $onsiteserviceview ->where($where)->where('last_id is not null AND type=3')->count('id'); //合计被第三次上门
        $sum['four'] = $onsiteserviceview ->where($where)->where('last_id is not null AND type=4')->count('id');//合计被多次上门

        $this -> assign('date',$list);
        $count = $user->count();
        $Page = new \Page($count, $listrows);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter = "id=".$id.'&status=' . $status;
        $show  = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('sum',$sum) ;
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        $this->assign('role_department_res',$role_department_res);
        $this->display();
    }


    // 获取我团队成员的user_id
    private function getMeGroupMember($user_id){
        $role = M('role');
        $position = M('position');
        $role_map['user_id'] = $user_id;

        $position_map['position_id'] = $role->where($role_map)->cache(true,3600)->getField('position_id');
        $position_map2['department_id'] = $position->where($position_map)->cache(true,3600)->getField('department_id');
        //返回用户名下团队成员个数
        $position_id_arr = $position->where($position_map2)->group('position_id')->field('position_id')->cache(true,3600)->select();
        
        $role_map2['parent_id'] = array('in',array_column($position_id_arr,'position_id'));
        $role_map22['position_id'] = array('in',array_column($position_id_arr,'position_id'));
        $role_parent_res = $position->where($role_map2)->field('position_id')->cache(true,3600)->select();
        $role_user_res = $role->where($role_map22)->field('user_id')->cache(true,3600)->select();
        $user_group_list['1'] = array_column($role_user_res,'user_id');

            if ($role_parent_res) {
                $role_map3['parent_id'] = array('in',array_column($role_parent_res,'position_id'));
                $role_map33['position_id'] = array('in',array_column($role_parent_res,'position_id'));
                $role_parent_res1 = $position->where($role_map3)->field('position_id')->cache(true,3600)->select();
                $role_user_res1 = $role->where($role_map33)->field('user_id')->cache(true,3600)->select();
                $user_group_list['2'] = array_column($role_user_res1,'user_id');

                if ($role_parent_res1) {
                    $role_map4['parent_id'] = array('in',array_column($role_parent_res1,'position_id'));
                    $role_map44['position_id'] = array('in',array_column($role_parent_res1,'position_id'));
                    $role_parent_res2 = $position->where($role_map4)->field('position_id')->cache(true,3600)->select();
                    $role_user_res2 = $role->where($role_map44)->field('user_id')->cache(true,3600)->select();
                    $user_group_list['3'] = array_column($role_user_res2,'user_id');

                    if ($role_parent_res2) {
                        $role_map5['parent_id'] = array('in',array_column($role_parent_res2,'position_id'));
                        $role_map55['position_id'] = array('in',array_column($role_parent_res2,'position_id'));
                        $role_parent_res3 = $position->where($role_map5)->field('position_id')->cache(true,3600)->select();
                        $role_user_res3 = $role->where($role_map55)->field('user_id')->cache(true,3600)->select();
                        $user_group_list['4'] = array_column($role_user_res3,'user_id');

                        if ($role_parent_res3) {
                            $role_map6['parent_id'] = array('in',array_column($role_parent_res3,'position_id'));
                            $role_map66['position_id'] = array('in',array_column($role_parent_res3,'position_id'));
                            $role_parent_res4 = $position->where($role_map6)->field('position_id')->cache(true,3600)->select();
                            $role_user_res4 = $role->where($role_map66)->field('user_id')->cache(true,3600)->select();
                            $user_group_list['5'] = array_column($role_user_res4,'user_id');
                        }
                    }
                }
            }

        $unset_key = array_search($user_id,$user_group_list);
        unset($user_group_list[$unset_key]);
        $res->meta->code = 200;
        $res->meta->message = '成功';
        $res->body = $user_group_list;
       
        return $res;
    }

    //我提交的
    public function me(){
        $sid = I('get.sid');
        $mod = M($this->table_name);
        import("@.ORG.Page");
        if (empty($sid)) {
            $user = I('session.user_id');
            $sid = $user;
        }

        $count = $mod -> where("$sid = submitter_id") -> count();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $Page = new \Page($count,$listrows);
        $show = $Page->show();
        
        $list = $mod -> where("$sid = submitter_id") -> page($p.','.$listrows) -> select();
        $this -> assign('list',$list);
        $this -> assign('page',$show);

        $this -> display('index');
    }

    // 下属提交的
    public function under(){
        $subRoleId = getSubRoleId();
        $subRoleId = array_merge(array_diff($subRoleId, [I('session.user_id')]));//去掉自己提交的
        $map['submitter_id'] = array('in',$subRoleId);
        $mod = M($this->table_name);

        import("@.ORG.Page");
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $count = $mod -> where($map) -> count();
        $Page = new \Page($count,$listrows);
        $show = $Page->show();
        $list = $mod -> where($map) -> page($p, $listrows) -> select();
        $this -> assign('list',$list);
        $this -> assign('page',$show);
        $this -> display('index');
    }

    // 超时未安排的
    // 先获取当前时间 当前时间减去下单时间 超过3天 下单时间为addtime
    public function remore(){
        // $user_id = I('session.user_id');
        $mod = M($this->table_name);
        // $showtime = strtotime('-10 day',strtotime(date('Y-m-d',time())));
        // $result['addtime'] = date("Y-m-d H:i:s",$showtime);
        // $map['addtime'] = array('ELT',$result['addtime']);
        $overdue_return = M('config')->getFieldByName('overdue_return', 'value');

        $map['status'] = 5;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;

        // import('ORG.Util.Page');
        import('@.ORG.Page');// 导入分页类
      
        $count = $mod -> where($map)->where(' TO_DAYS(NOW()) - TO_DAYS(addtime) > %d', $overdue_return) -> count();

        $Page = new \Page($count,$listrows);
        $show = $Page->show();
        $list = $mod -> page($p, $listrows) -> where($map)->where(' TO_DAYS(NOW()) - TO_DAYS(addtime) > %d', $overdue_return) -> select();

        $this -> assign('page',$show);
        $this -> assign('list',$list);
        $a = I('get.chaoshi');
        switch ($a) {
            case '1':
                $this -> display('thedoorserver');
                break;
            case '2':
                $this -> display('index');
                break;
            default:
                echo "<script>history.back();</script>";
                break;
        }    
    }

    // 上门
    public function drop(){
        $a = I('get.c');
        $mod = M($this->table_name);
        $gets = I('get.');
        $user_id = I('session.user_id');
        import('@.ORG.Page');
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'));//获取团队成员role_id
        $map['tid'] = ['in', $teamMembersRoleIds];

        $where = [];
        if ($gets['train_status']==1){
            // 未上门
            $where['status'] = ['in', [1,2]];
        }elseif ($gets['train_status']==2){
            //已上门
            $where['status'] = ['in', [3,4]];
        }else{
            unset($where['status']);
        }

        if ($gets['return_status']==1){
            // 未回访
            unset($where['status']);
            $where['return_status'] = ['in', [1,2]]; //1未联系 2联系不上
        }elseif ($gets['return_status']==2){
            //已回访
            unset($where['status']);
            $where['return_status'] = 3;
        }
        switch ($a) {
            // 我上门
            case 'me':
                $user_id = I('session.user_id');
                $count = $mod -> where($where)->where("$user_id = tid") -> count();
                $page = new \Page($count, $listrows);
                $show = $page -> show();
                $list = $mod-> where($where) -> where("$user_id = tid") -> page($p, $listrows) -> select();

                foreach($list as &$item) {
                    if ($item['status_change_time']) {
                        // $item['stay_time'] = gmdate('H:i:s', time() - intval($item['status_change_time']));
                        $item['stay_time'] = date_diff(date_create(), date_create(date('Y-m-d H:i:s', $item['status_change_time'])))->format("%d天%h时%i分");
                    }
                }

                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;
            // 下属上门
            case 'under':
                
                // $user_group_list = $this->getMeGroupMember($user_id)->body;
                // foreach ($user_group_list as $key => $value) {
                //     foreach ($value as $k => $v) {
                //         $tid[] = $v;
                //     }
                // }
                $role_ids = \App\Lib\Model\User::getTeamMembersRoleIds(I('session.role_id'),0);
                $map['tid'] = array('in', $role_ids);
                $count = $mod -> where($where)->where($map) -> count();
                $Page = new \Page($count,$listrows);
                $show = $Page->show();
                
                $list = $mod -> where($where)-> where($map)-> page($p, $listrows) -> select();

                foreach($list as &$item) {
                    if ($item['status_change_time']) {
                        // $item['stay_time'] = gmdate('H:i:s', time() - intval($item['status_change_time']));
                        $item['stay_time'] = date_diff(date_create(), date_create(date('Y-m-d H:i:s', $item['status_change_time'])))->format("%d天%h时%i分");
                    }
                }

                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;
            case 'all' :
                $role_ids = \App\Lib\Model\User::getTeamMembersRoleIds(I('session.role_id'),0);
                array_unshift($role_ids, I('session.user_id'));
                $map['tid'] = array('in', $role_ids);
                $count = $mod -> where($where)->where($map) -> count();
                $Page = new \Page($count,$listrows);
                $show = $Page->show();

                $list = $mod -> where($where)-> where($map)-> page($p, $listrows) -> select();

                foreach($list as &$item) {
                    if ($item['status_change_time']) {
                        // $item['stay_time'] = gmdate('H:i:s', time() - intval($item['status_change_time']));
                        $item['stay_time'] = date_diff(date_create(), date_create(date('Y-m-d H:i:s', $item['status_change_time'])))->format("%d天%h时%i分");
                    }
                }

                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;
            // 需要我回访
            case 'me_over':
                $map['tid'] = $user_id;
                $map['status'] = 4;
                $count = $mod -> where($map) ->count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();

                $list = $mod -> where($map) -> page($p, $listrows)-> select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;
            // 需要下属回访
            case 'under_over':
                $user_group_list = $this->getMeGroupMember($user_id)->body;

                foreach ($user_group_list as $key => $value) {
                    foreach ($value as $k => $v) {
                        $tid[] = $v;
                    }
                }
                $map['status'] = 4;
                $map['tid'] = array('in', $tid);

                    $count = $mod -> where($map) -> count();

                $page = new \Page($count, $listrows);
                $show = $page -> show();
                
                $list = $mod -> where($map) ->page($p, $listrows)-> select();

                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('thedoorserver');
                break;

            default:
                echo "<script>history.back();</script>";
                break;
        }
    }

    //回访
    public function callOn(){
        $a = I('get.c');
        import('@.ORG.Page');
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'));//获取团队成员role_id
        $map['tid'] = ['in', $teamMembersRoleIds];
        $mod = M($this->table_name);
        switch ($a) {
            #已回访
            case '3':
                $map['status'] = 4;
                $map['return_status'] = 3;
                $count = $mod -> where($map) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();
                $list = $mod -> where($map)->page($p,$listrows)-> select();

                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('visit');
                break;
            #超时未回访
            case '2':
                $showtime = strtotime('-10 day',strtotime(date('Y-m-d',time())));
                $result['remote_time'] = date("Y-m-d H:i:s",$showtime);
                $map['remote_time'] = array('ELT',$result['remote_time']);
                $map['status'] = 4;
                $map['return_status'] = array(1,2,'OR');
                $count = $mod -> where($map) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();

                $list = $mod -> where($map) -> page($p, $listrows)->  select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('visit');
                break;
            #未回访
            case '1':
                $map['status'] = 4;
                $map['return_status'] = array(1,2,'OR');
                $count = $mod -> where($map) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();

                $list = $mod -> where($map) ->page($p, $listrows)-> select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('visit');
                break;

            default:
                echo "<script>history.back();</script>";
                break;
        }
    }

    //审核
    public function check(){
        $a = I('get.c');
        import('@.ORG.Page');// 导入分页类
        $mod = M($this->table_name);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;
        }
        $this->listrows = $listrows;
        $map['return_status'] = 3;
        $teamMembersRoleIds = \App\Lib\Model\User::getTeamMembersRoleIds(session('role_id'));//获取团队成员role_id
        $map['tid'] = ['in', $teamMembersRoleIds];
        switch ($a) {
            #已审核
            case '3':
                $map['status'] = 4;
                $map['verifier_id'] = ['exp', 'is not null'];
                $count = $mod -> where($map) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();
                $list = $mod -> where($map) -> page($p,$listrows)-> select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('verify');
                break;
            #超时未审核
            case '2':
                $review_deadline = M('config')->getFieldByName('review_deadline', 'value');
                $map['status'] = 4;
                $map['verifier_id'] = ['exp','is null'];
                $count = $mod -> where($map)->where(' TO_DAYS(NOW()) - TO_DAYS(return_time) > %d', $review_deadline) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();
                $list = $mod -> where($map) -> page($p,$listrows)->where(' TO_DAYS(NOW()) - TO_DAYS(return_time) > %d', $review_deadline)->select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('verify');
                break;
            #未审核
            case '1':
                $map['status'] = 4;
                $map['verifier_id'] = array('exp','is null');
                $count = $mod -> where($map) -> count();
                $page = new \Page($count,$listrows);
                $show = $page -> show();

                $list = $mod -> where($map) ->page($p,$listrows)-> select();
                $this -> assign('page',$show);
                $this -> assign('list',$list);
                $this -> display('verify');
                break;

            default:
                echo "<script>history.back();</script>";
                break;
        }
    }
    //查找部门员工
    public function lookupDepartmentStaff(){
        $data = I('post.');

        $position = M('position');
        $position_map['department_id'] = $data['department_id'];
        $position_res = $position->where($position_map)->field('position_id')->select();
        $arr = array_column($position_res,'position_id');

        $role = M('role');
        $role_map['position_id'] = array('in',$arr);
        $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id,lyfz_user.name')->order('convert(lyfz_user.name using gbk)')->select();

        if($role_res){
            $res->meta->code = 200;
            $res->meta->message = '成功';
            $res->body = $role_res;
        }else{
            $res->meta->code = 400;
            $res->meta->message = '失败';
        }
        $this->ajaxReturn($res);
    }

    // 地图 
    public function ajaxMap(){
        $mada = M('sign');
        $res = (object)[] ;

        $data['start_time'] = strtotime(date('Y-m-d '));
        $data['end_time'] = strtotime(date('Y-m-d',strtotime("+1 day")));

        $map['create_time'] = array(array('EGT',$data['start_time']),array('ELT',$data['end_time']),'AND');
        
        $list = $mada->alias('sign')
                     ->join(C('DB_PREFIX').'role `role` ON `role`.role_id = sign.role_id')
                     ->join(C('DB_PREFIX').'user `user` ON `user`.user_id = role.user_id')
//                     ->where($map)
                     ->group('role.user_id')
                     ->select();
        if ($list){
            $res->meta->code = 200;
            $res->meta->message = '成功';
            $res->body = $list;
        }else{
            $res->meta->code = 400;
            $res->meta->message = '失败';
        }

        // ajax返回值
        $this->ajaxReturn($res);
    }

    //短信流程
    public function sendMessageCron()
    {
        $message = new Message();
        $onSiteService = M($this->table_name);
        $current_time = time();
        $onSiteServiceArr = $onSiteService->alias('onsiteservice')
//            ->field('onsiteservice.contacts_name, onsiteservice.contacts_telephone, onsiteservice.operator_name, receivables.name product_name, receivingorder.receiving_num')
//            ->join(C('DB_PREFIX').'receivables receivables ON receivables.customer_id = onsiteservice.customer_id')
//            ->join(C('DB_PREFIX').'receivingorder receivingorder ON receivingorder.receivables_id = receivables.receivables_id')
            ->where('onsiteservice.remore_time <> ""')
            ->select();
        foreach ($onSiteServiceArr as $value){
            $time = strtotime($value['remore_time']);//离店时间
            if (1 == $value['type']){
                //发送短信
                if ($time+86400 < $current_time  &&  $time+86400*2 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_ONE_DAY_AWAY);//安装离店一天
                }elseif ($time+86400*3 < $current_time  &&  $time+86400*4 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_THREE_DAYS_AWAY);//安装离店三天
                }elseif ($time+86400*7 < $current_time  &&  $time+86400*8 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_SEVEN_DAYS_AWAY);//安装离店七天
                }elseif ($time+86400*15 < $current_time  &&  $time+86400*16 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_FIFTEEN_DAYS_AWAY);//安装离店15天
                }elseif ($time+86400*30 < $current_time  &&  $time+86400*31 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_ONE_MONTH_AWAY);//安装离店一个月
                }elseif ($time+86400*60 < $current_time  &&  $time+86400*61 > $current_time){
                    $message -> sendProcessMessage($value['customer_id'], Message::INSTALLATION_TWO_MONTHS_AWAY);//安装离店两个月
                }
            }
        }
    }

    /**
     * 超时未回访提醒 这是一个计划任务，需在服务器上添加计划任务
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function returnVisitCron()
    {
        $return_deadline = M('config')->getFieldByName('return_deadline', 'value');
        $where['return_status'] = ['neq', 3];
        // $where['status'] = 4;
        $onSiteService = M($this->table_name);
        //超时未回访用户信息
        $overdueReturns = $onSiteService
            ->alias('onsiteservice')
            ->field('tid,weixinid,telephone,group_concat("客户名：",customer_name,"，联系人：",contacts_name,"，电话：",contacts_telephone SEPARATOR "\n") content')
            ->join(C('DB_PREFIX').'user user ON user.user_id=onsiteservice.tid')
            ->where($where)
            ->where('TO_DAYS(NOW()) - TO_DAYS(remore_time) > %d', $return_deadline)
            ->group('tid')
            ->select();
        $message = new Message();
        $app = WeixinEnterprise::getApp();
        $textCard = new TextCard();
        $textCard->title = '超时未回访提醒';
        $textCard->url = 'http://mcrm.lyfz.net/m';
        foreach ($overdueReturns as $overdueReturn){
            $message->sendMessage($overdueReturn['telephone'], "超时未回访客户提醒:\n".$overdueReturn['content']);
            $textCard->description = $overdueReturn['content'];
            $app->messenger->message($textCard)->toUser($overdueReturn['weixinid'])->send();
        }
    }

    //获取签到信息
    public function getSign()
    {
        //默认查询昨天这个时候到现在的打卡信息
       $singData =  \App\Extend\Dingding::getSignData(1,(time()-86400)*1000);

       $this->ajaxReturn($singData);
    }

    /**
     * 答题情况统计
     */
    public function customerSatisfaction()
    {
        $problems = M('OnsiteserviceProblem')->alias('problem')
            ->join(C('DB_PREFIX').'onsiteservice_answer answer ON answer.problem_id = problem.problem_id')
            ->where('problem.status = %d', 1)
            ->field('problem.problem_id,problem.description,problem.problem,count(answer_id) answer_count, count(answer.answer) answer_distribute')
            ->group('problem_id')
            ->select();
        foreach ($problems as $key=>$problem){
            $problems[$key]['answer'] = M('OnsiteserviceProblem')->alias('problem')
                ->join(C('DB_PREFIX').'onsiteservice_answer answer ON answer.problem_id = problem.problem_id')
                ->where('problem.problem_id = %d', $problem['problem_id'])
                ->field('answer.answer,count(answer.answer) answer_distribute')
                ->group('answer.answer')
                ->select();
        }
        $this->assign('problems', $problems);
        $this->display('customerSatisfaction');
    }

    /**获取问题列表
     * @param int $id
     */
    public function getProblemList($id=0)
    {
        $customerInfo = M($this->table_name)->alias('onsiteservice')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = onsiteservice.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->where('onsiteservice.id = %d', $id)
            ->field('onsiteservice.id onsiteservice_id,customer.name customer_name,customer.address,contacts.telephone')
            ->find();
        $problems = M('OnsiteserviceProblem')->where('status = %d', 1)->order('description')->select();
        $adjustmentProblem = [];
        foreach ($problems as $problem){
            switch ($problem['description']){
                case 1: $adjustmentProblem['一、培训流程执行'][] = ['problem_id'=>$problem['problem_id'], 'problem'=>$problem['problem'], 'answer'=>explode(',', $problem['answer'])]; break;
                case 2: $adjustmentProblem['二、软件功能执行'][] = ['problem_id'=>$problem['problem_id'], 'problem'=>$problem['problem'], 'answer'=>explode(',', $problem['answer'])]; break;
                case 3: $adjustmentProblem['三、软件维护操作'][] = ['problem_id'=>$problem['problem_id'], 'problem'=>$problem['problem'], 'answer'=>explode(',', $problem['answer'])]; break;
                case 4: $adjustmentProblem['四、满意度调查'][]  = ['problem_id'=>$problem['problem_id'],  'problem'=>$problem['problem'], 'answer'=>explode(',', $problem['answer'])]; break;
            }
        }
        $this->assign('adjustmentProblem', $adjustmentProblem);
        $this->assign('customerInfo', $customerInfo);
        $this->display('survey');
    }

    /**
     *接收答题情况
     */
    public function postAnswer()
    {
        $posts = I('post.');
        $onSiteServiceCustomer = M('OnsiteserviceCustomer');
        $onsiteservice_id = $posts['onsiteservice_id'];
        if ($onSiteServiceCustomer->where('onsiteservice_id = %d', $onsiteservice_id)->find()){
            $this->assign('prompt','该满意度调查表您已填，不能重复填写！');
            $this->display('prompt');
            exit();
        }
        $posts['remore_time'] = strtotime($posts['remore_time']);
        $posts['remote_time'] = strtotime($posts['remote_time']);
        $posts['create_time'] = time();

        $answer = [];
        if ($onSiteServiceCustomerId = $onSiteServiceCustomer->add($posts)){
            foreach ($posts as $key=>$post){
                if (strpos($key, 'answer') !== false){
                    $problem_id = substr($key, strpos($key, '_')+1);
                    $answer[] = ['onsiteservice_customer_id'=>$onSiteServiceCustomerId, 'problem_id'=>$problem_id, 'answer'=>$post];
                }
            }
            M('OnsiteserviceAnswer')->addAll($answer);
            $this->assign('prompt','评论成功！感谢您的评论');
        }
        else{
            $this->assign('prompt','提交失败!');

        }
        $this->display('prompt');
    }
}