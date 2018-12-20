<?php 
class ServiceAction extends Action {
    public function _initialize(){
        header("Content-type: text/html; charset=utf-8");
        $action = array(
            'permission'=>array(),
            'allow'=>array('order_service', 'index', 'redirectnewremote')
        );
        B('Authenticate', $action);
    }
    
    //发送提醒客户正在解决短信
    //$phone 客户手机 $contacts_name客户联系人姓名 $problem 问题文字
    private function sendSolve($phone,$contacts_name,$problem){
        $time = date('Y-m-d H:i:s');
        
        $content = $contacts_name.'总裁您好,您公司提出的'.$problem.'相关问题已在'.$time.'解决，并已告知解决办法，感谢您的支持与厚爱，祝您生活愉快！';

        import('@.ORG.Message');
        $Message = new Message();
        return $Message->sendMessage($phone,$content);
    }
    
    //故障提交人发送微信模板消息
    //$user_id 用户账号ID  $remote_name远程人员名称 $customer_name公司名称  $contacts_name首要联系人名称 $problem故障问题
    private function sendWxTemplateMessage($user_id,$remote_name,$customer_name,$contacts_name,$problem){
        $time = date('Y-m-d H:i:s');
        $content = '员工'.$remote_name.'已在'.$time.'解决'.$customer_name.$contacts_name.'提出的'.$problem.'故障！';

        import('@.ORG.WeixinEnterprise');
        $WeixinEnterprise = new WeixinEnterprise();
        return $WeixinEnterprise->sendTextMessage('user',$user_id,$content);
    }
    
    //获取字段设置
    private function getFieldInfo($field_name){
        $fields = M('fields');
        $fields_map['model'] = 'service';
        $fields_map['name'] = $field_name;
        $fields_res = $fields->where($fields_map)->find();
        if($fields_res){
            $res->meta->code = 200;
            $res->meta->message = '成功';
            $res->body = $fields_res;
        }else{
            $res->meta->code = 4004;
            $res->meta->message = '数据为空';
        }
        return $res;
    }
    
    //写图片
    private function saveImgList($relation_id,$img_type,$img_arr){
        $img_list = M('img_list');
        foreach($img_arr as $k=>$v){
            $img_list_data['relation_id'] = $relation_id;
            $img_list_data['img_type'] = $img_type;
            $img_list_data['img_sort'] = $k;
            $img_list_data['img_url'] = $v;
            $num[] = $img_list->add($img_list_data);
        }
        
        if(count($num) == count($img_arr)){
            $res->meta->code = 200;
            $res->meta->message = '成功';
        }else{
            $res->meta->code = 400;
            $res->meta->message = '保存图片数据失败';
        }
        return $res;
    }
    
    //获取我的团队成员user_id
    private function getMeGroupMember($user_id){
        $role = M('role');
        $position = M('position');
        $role_map['user_id'] = $user_id;
        $position_map['position_id'] = $role->where($role_map)->cache(true,3600)->getField('position_id');
        $position_map2['department_id'] = $position->where($position_map)->cache(true,3600)->getField('department_id');
        $position_id_arr = $position->where($position_map2)->group('position_id')->field('position_id')->cache(true,3600)->select();
        $role_map2['position_id'] = array('in',array_column($position_id_arr,'position_id'));
        $role_user_res = $role->where($role_map2)->field('user_id')->cache(true,3600)->select();
        $user_group_list = array_column($role_user_res,'user_id');

        $unset_key = array_search($user_id,$user_group_list);
        unset($user_group_list[$unset_key]);
        
        $res->meta->code = 200;
        $res->meta->message = '成功';
        $res->body = $user_group_list;
        return $res;
    }

    public function index(){
        $this->redirect('order_service');
    }

    //远程服务首页
    public function remote_service(){
        $data = I('get.');
        $user_id = $_SESSION['user_id'];
        
        $user = M('user');
        $service = M('service');
        
        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');
        if($field_res->meta->code == 200){
            if($field_res->body['form_type'] == 'box'){
                $this->assign('remote_type',$field_res->body['field']);
            }
        }
        
        //下拉选项条件筛选
        if($data['search'] != ''){
            switch($data['field']){
                case 'customer_name':
                    $service_map['customer.name'] = array('like','%'.$data['search'].'%');
                    break;
                case 'contacts_name':
                    $service_map['contacts.name'] = array('like','%'.$data['search'].'%');
                    break;
                case 'contacts_phone':
                    $service_map['contacts.telephone'] = array('like','%'.$data['search'].'%');
                    break;
                case 'teacher_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.teacher_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                case 'fault_subpeople_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.fault_subpeople_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                case 'sale_people_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.sale_people_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                case 'service_personal_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.service_personal_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                case 'remote_type':
                    $service_map['service.'.$field_res->body['field'].''] = $data['search'];
                    break;
                default:
                    $this->error('字段非法!');
            }
        }
        
        //视图查询选项条件筛选
        if($data['viewOptions'] != ''){
            switch($data['viewOptions']){
                case 'me':
                    $service_map['service.sale_people_id'] = $user_id;
                    $service_map['service.teacher_id'] = $user_id;
                    $service_map['_logic'] = 'OR';
                    break;
                case 'subordinate':
                    $user_group_list = $this->getMeGroupMember($user_id)->body;
                    $service_map['service.sale_people_id'] = array('in',$user_group_list);
                    $service_map['service.teacher_id'] = array('in',$user_group_list);
                    $service_map['_logic'] = 'OR';
                    break;
                case 'me_remote':
                    $service_map['service.service_personal_id'] = $user_id;
                    break;
                case 'subordinate_remote':
                    $user_group_list = $this->getMeGroupMember($user_id)->body;
                    $service_map['service.service_personal_id'] = array('in',$user_group_list);
                    break;
                case 'me_door':
                    $service_map['service.teacher_id'] = $user_id;
                    break;
                case 'subordinate_door':
                    $user_group_list = $this->getMeGroupMember($user_id)->body;
                    $service_map['service.teacher_id'] = array('in',$user_group_list);
                    break;
            }
        }
        
//        import('ORG.Util.Page');
        import("@.ORG.Page");
        $count = $service->alias('service')
                ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = service.customer_id')
                ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
                ->where($service_map)
                ->count();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $Page = new Page($count, $listrows);
        $show = $Page->show();
        $res = $service->alias('service')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = service.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->where($service_map)
            ->field('service.*, customer.name as customer_name, customer.customer_id, contacts.name as contacts_name, contacts.telephone as contacts_phone')
//            ->limit($Page->firstRow.','.$Page->listRows)
            ->page($p, $listrows)
            ->order('remote_time desc')->select();
        //获取各操作员工数据及统计远程次数
        foreach($res as $k=>$v){
            $query = $v['fault_subpeople_id'].','.$v['sale_people_id'].','.$v['service_personal_id'].','.$v['teacher_id'];
            $user_map['user_id'] = array('in',$query);
            $user_res = $user->where($user_map)->field('name,user_id')->cache(true,3600)->select();
            $res[$k]['fault_subpeople_name'] = $user_res[findId($user_res,$v['fault_subpeople_id'])]['name'];
            $res[$k]['sale_people_name'] = $user_res[findId($user_res,$v['sale_people_id'])]['name'];
            $res[$k]['service_personal_name'] = $user_res[findId($user_res,$v['service_personal_id'])]['name'];
            $res[$k]['teacher_name'] = $user_res[findId($user_res,$v['teacher_id'])]['name'];

            //统计远程次数
            $statistics_map['customer_id'] = $v['customer_id'];
            $res[$k]['remote_number']= $service->where($statistics_map)->cache(true,30)->count();
            
            //统计重复事项次数
            $service_res = $service->query('SELECT '.$field_res->body['field'].',COUNT(1) AS COUNT FROM lyfz_service WHERE customer_id='.$v['customer_id'].' GROUP BY '.$field_res->body['field'].' HAVING COUNT>1');
            $repeat_num = 0;
            foreach($service_res as $v){
                $repeat_num  = $v['COUNT'] + $repeat_num -1;
            }
            $res[$k]['repeat_num'] = $repeat_num;
        }
        $this->assign('list',$res);
        $this->assign('page',$show);
        $this->display();
    }
    
    //添加远程记录
    public function addRemoteService(){
      header("Content-type: text/html; charset=utf-8");
        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');

        if(I('post.is_solve')!=''){
            $data = I('post.');

            $time = date('Y-m-d H:i:s');
            
            if($field_res->meta->code == 200){
                if($field_res->body['form_type'] == 'box'){
                    $field_arr = field_list_html("add","service",$leads)['main'];
                    $field_key = findId($field_arr,'远程类型');
                    $remoteField = $field_arr[$field_key]['field'];
                }
            }

            //自我评价
            switch($data['self_satisfaction']){
                case '1':
                    $service_data['self_satisfaction'] = 1;
                    break;
                case '2':
                    $service_data['self_satisfaction'] = 2;
                    break;
                case '3':
                    $service_data['self_satisfaction'] = 3;
                    break;
                case '4':
                    $service_data['self_satisfaction'] = 4;
                    break;
                default:
                    $this->error('字段非法！');
            }

            // 写入数据
            $service = M('service');
            $service_data['is_solve'] = $data['is_solve'] == 1 ? 1:2;
            $service_data['customer_id'] = $data['customer_id'];
            $service_data['fault_subpeople_id'] = $data['fault_subpeople_id'];
            $service_data['operation_people_phone'] = $data['operation_people_phone'];
            $service_data['operator_name'] = $data['operator_name'];
            $service_data['operator_qq'] = $data['operator_qq'];
            $service_data['teacher_id'] = $data['teacher_id'];
            $service_data['sale_people_id'] = $data['sale_people_id'];
            $service_data['service_personal_id'] = $data['service_personal_id'];
            $service_data['desc'] = $data['desc'];
            $service_data['remote_time'] = $time;
            $service_data[$remoteField] = $data[$remoteField];
            $service_id = $service->add($service_data);

            if(!$service_id){
                $this->error('数据保存失败！');
            }

            //写入图片数据
            $img_write_res = imgHandle($data['img_list']);
            if($img_write_res->meta->code != 200){
                $this->error($img_write_res->meta->message);
            }
            $img_save_res = $this->saveImgList($service_id,1,$img_write_res->body);
            if($img_save_res->meta->code != 200){
                $this->error($img_save_res->meta->message);
            }

            //获取远程人员姓名
            $user = M('user');
            $user_map['user_id'] = $data['service_personal_id'];
            $remote_name = $user->where($user_map)->cache(true,3600)->getField('name');

            //获取提交人weixinid
            $user_map2['user_id'] = $data['fault_subpeople_id'];
            $weixinid = $user->where($user_map2)->cache(true,3600)->getField('weixinid');

            //发送短信与企业号信息
            $customer = M('customer');
            $customer_map['customer_id'] = $data['customer_id'];
            $customer_res = $customer->join('lyfz_contacts ON lyfz_contacts.contacts_id = lyfz_customer.contacts_id')->where($customer_map)->field('lyfz_contacts.telephone,lyfz_contacts.name,lyfz_customer.name as customer_name')->find();
            if($customer_res){
                $this->sendSolve($customer_res['telephone'],$customer_res['name'],$data[$remoteField]);
                $this->sendWxTemplateMessage($weixinid,$remote_name,$customer_res['customer_name'],$customer_res['name'],$data[$remoteField]);
            }

            $this->success('添加成功',U('service/index'));
        }else{
            if($field_res->meta->code == 200){
                if($field_res->body['form_type'] == 'box'){
                    $field_arr = field_list_html("add","service",$leads)['main'];
                    $field_key = findId($field_arr,'远程类型');

                    $this->assign('remote_type_field',$field_res->body['field']);
                    $this->assign('remote_type_option',$field_arr[$field_key]['setting']['data']);
                }
            }
            $customer = M('Customer') ;
            $customer_id = I('get.customer_id') ;
            if (!empty($customer_id)){
                $customerData = $customer->alias('customer')
                    ->field('customer.name customer_name,customer.address,contacts.name contacts_name,contacts.telephone,customer.customer_id,contacts.contacts_id')
                    ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
                    ->where('customer.customer_id=%d',$customer_id)
                    ->find() ;
                $this->assign('customer',$customerData) ;
            }

            $this->display();
        }
    }
    
    //查看远程服务记录
    public function remoteServiceView(){
        $service_id = I('get.service_id');
        
        $service = M('service');
        $service_map['service_id'] = $service_id;
        $service_res = $service->alias('service')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = service.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->where($service_map)
            ->field('service.*, customer.name as customer_name, customer.address as customer_address, customer.customer_id, contacts.name as contacts_name, contacts.telephone as contacts_phone')
            ->cache(true,3600)
            ->find();
        
        $query = $service_res['fault_subpeople_id'].','.$service_res['sale_people_id'].','.$service_res['service_personal_id'].','.$service_res['teacher_id'];
        $user_map['user_id'] = array('in',$query);
        $user = M('user');
        $user_res = $user->where($user_map)->field('name,user_id')->cache(true,3600)->select();
        $service_res['fault_subpeople_name'] = $user_res[findId($user_res,$service_res['fault_subpeople_id'])]['name'];
        $service_res['sale_people_name'] = $user_res[findId($user_res,$service_res['sale_people_id'])]['name'];
        $service_res['service_personal_name'] = $user_res[findId($user_res,$service_res['service_personal_id'])]['name'];
        $service_res['teacher_name'] = $user_res[findId($user_res,$service_res['teacher_id'])]['name'];
        
        $img_list = M('img_list');
        $img_list_map['relation_id'] = $service_id;
        $img_list_map['img_type'] = 1;
        $img_arr = $img_list->where($img_list_map)->select();

        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');
        
        $this->assign('img_arr',$img_arr);
        $this->assign('service_res',$service_res);
        $this->assign('remote_type',$service_res[$field_res->body['field']]);
        $this->display();
    }
    
    //客服回访
    public function customerService(){
        $data = I('get.');
        $user_id = $_SESSION['user_id'];
        
        $user = M('user');
        $service = D('ServiceView');
        
        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');
        if($field_res->meta->code == 200){
            if($field_res->body['form_type'] == 'box'){
                $this->assign('remote_type',$field_res->body['field']);
            }
        }
        
        //下拉选项条件筛选
        if($data['search'] != ''){
            switch($data['field']){
                case 'customer_name':
                    $service_map['customer.name'] = array('like','%'.$data['search'].'%');
                    break;
                case 'contacts_name':
                    $service_map['contacts.name'] = array('like','%'.$data['search'].'%');
                    break;
                case 'contacts_phone':
                    $service_map['contacts.telephone'] = array('like','%'.$data['search'].'%');
                    break;
                case 'fault_subpeople_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.fault_subpeople_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                case 'service_personal_name':
                    $teacher_name_map['name'] = array('like','%'.$data['search'].'%');
                    $teacher_name_res = $user->where($teacher_name_map)->field('user_id')->select();
                    $service_map['service.service_personal_id'] = array('in',array_column($teacher_name_res,'user_id'));
                    break;
                default:
                    $this->error('字段非法!');
            }
        }
        
        //视图查询选项条件筛选
        if($data['viewOptions'] != ''){
            switch($data['viewOptions']){
                case 'returnVisit':
                    $service_map['service.return_result'] = array('not in','1');
                    break;
                case 'noReturnVisit':
                    $service_map['service.return_result'] = '1';
                    break;
                case 'overtime':
                    $service_map['service.return_result'] = '1';
                    $service_map['datediff(now(),service.remote_time)'] = array('egt',7);
                    break;
            }
        }
        
//        import('ORG.Util.Page');
        import("@.ORG.Page");
        $count = $service
            ->where($service_map)
            ->count();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $Page = new Page($count, $listrows);
        $show = $Page->show();
        $res = $service
            ->where($service_map)
//            ->limit($Page->firstRow.','.$Page->listRows)
            ->page($p, $listrows)
            ->order('remote_time desc')
            ->select();

        //获取各操作员工数据及统计远程次数
        foreach($res as $k=>$v){
            $query = $v['fault_subpeople_id'].','.$v['visit_user_id'].','.$v['service_personal_id'];
            $user_map['user_id'] = array('in',$query);
            $user_res = $user->where($user_map)->field('name,user_id')->cache(true,3600)->select();
            $res[$k]['fault_subpeople_name'] = $user_res[findId($user_res,$v['fault_subpeople_id'])]['name'];
            $res[$k]['service_personal_name'] = $user_res[findId($user_res,$v['service_personal_id'])]['name'];
            if($v['visit_user_id'] != ''){
                $res[$k]['visit_name'] = $user_res[findId($user_res,$v['visit_user_id'])]['name'];
            }

            //统计远程次数
            $statistics_map['customer_id'] = $v['customer_id'];
            $res[$k]['remote_number']= $service->where($statistics_map)->cache(true,30)->count();
            
            //统计重复事项次数
            $service_res = $service->query('SELECT '.$field_res->body['field'].',COUNT(1) AS COUNT FROM lyfz_service WHERE customer_id='.$v['customer_id'].' GROUP BY '.$field_res->body['field'].' HAVING COUNT>1');
            $repeat_num = 0;
            foreach($service_res as $v){
                $repeat_num++;
            }
            $res[$k]['repeat_num'] = $repeat_num;
        }
        
        $this->assign('list',$res);
        $this->assign('page',$show);
        $this->display();
    }
    
    //审核调查
    public function addBackwardReference(){
        $service = M('service');
        
        if($this->isPost()){
            $data = I('post.');
        
            switch($data['return_result']){
                case '2':
                    $service_data['return_result'] = 2;
                    break;
                case '3':
                    $service_data['return_result'] = 3;
                    break;
                case '4':
                    $service_data['return_result'] = 4;
                    break;
                case '5':
                    $service_data['return_result'] = 5;
                    break;
                default:
                    $this->error('字段非法');
            }
            
            $service_map['service_id'] = $data['service_id'];
            $service_data['return_remarks'] = $data['return_remarks'];
            $service_data['visit_user_id'] = $_SESSION['user_id'];
            $service_res = $service->where($service_map)->save($service_data);
            if($service_res != false){
                $this->success('审核成功',U('service/customerService'));
            }else{
                $this->error('审核失败');
            }
        }else{
            $service_id = I('get.service_id');
        
            $service_map['service_id'] = $service_id;
            $service_res = $service->join('lyfz_customer ON lyfz_customer.customer_id = lyfz_service.customer_id')
                ->join('lyfz_contacts ON lyfz_contacts.contacts_id = lyfz_customer.contacts_id')
                ->where($service_map)
                ->field('lyfz_service.*, lyfz_customer.name as customer_name, lyfz_customer.address as customer_address, lyfz_customer.customer_id, lyfz_contacts.name as contacts_name, lyfz_contacts.telephone as contacts_phone')
                ->find();
            
            $query = $service_res['fault_subpeople_id'].','.$service_res['sale_people_id'].','.$service_res['service_personal_id'].','.$service_res['teacher_id'].','.$service_res['visit_user_id'];
            $user_map['user_id'] = array('in',$query);
            $user = M('user');
            $user_res = $user->where($user_map)->field('name,user_id')->cache(true,3600)->select();
            $service_res['fault_subpeople_name'] = $user_res[findId($user_res,$service_res['fault_subpeople_id'])]['name'];
            $service_res['sale_people_name'] = $user_res[findId($user_res,$service_res['sale_people_id'])]['name'];
            $service_res['service_personal_name'] = $user_res[findId($user_res,$service_res['service_personal_id'])]['name'];
            $service_res['teacher_name'] = $user_res[findId($user_res,$service_res['teacher_id'])]['name'];
            if($service_res['visit_user_id'] != ''){
                $service_res['return_name'] = $user_res[findId($user_res,$service_res['visit_user_id'])]['name'];
            }
            
            $img_list = M('img_list');
            $img_list_map['relation_id'] = $service_id;
            $img_list_map['img_type'] = 1;
            $img_arr = $img_list->where($img_list_map)->select();

            //获取远程类型字段选项
            $field_res = $this->getFieldInfo('远程类型');
            
            $this->assign('img_arr',$img_arr);
            $this->assign('service_res',$service_res);
            $this->assign('remote_type',$service_res[$field_res->body['field']]);
            $this->display();
        }
    }
    
    //远程统计
    public function remoteStatistics(){
        $data = I('get.');
        
        // 检验时间段正确性
        if($data['start_time'] != '' && $data['end_time'] == ''){
            $this->error('请选择一个正确的时间段数据');
        }
        if($data['start_time'] == '' && $data['end_time'] != ''){
            $this->error('请选择一个正确的时间段数据');
        }
        
        $service = M('service');
        $user = M('user');
        if($data['start_time'] != '' && $data['end_time'] != ''){
            if($data['start_time'] == $data['end_time']){
                $service_time_map['remote_time'] = array(array('EGT',$data['start_time'].' 00:00:00'),array('ELT',$data['end_time'].' 23:59:59'),'AND');
                $condition_map['remote_time'] = array(array('EGT',$data['start_time'].' 00:00:00'),array('ELT',$data['end_time'].' 23:59:59'),'AND');
            } else {
                $service_time_map['remote_time'] = array(array('EGT',$data['start_time'].' 00:00:00'),array('ELT',$data['end_time'].' 00:00:00'),'AND');
                $condition_map['remote_time'] = array(array('EGT',$data['start_time'].' 00:00:00'),array('ELT',$data['end_time'].' 23:59:59'),'AND');
            }
        }
        if($data['service_personal_id'] != ''){
            $service_time_map['service_personal_id'] = $data['service_personal_id'];
        }
        if($data['service_personal_id'] == 'all' && $data['department'] != '' && $data['department'] != 'all'){
            $position = M('position');
            $position_map['department_id'] = $data['department'];
            $position_res = $position->where($position_map)->field('position_id')->select();
            $arr = array_column($position_res,'position_id');
            $role = M('role');
            $role_map['position_id'] = array('in',$arr);
            $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id')->select();
            $service_time_map['service_personal_id'] = array('in',array_column($role_res,'user_id'));
        }
        $service_res = $service->where($service_time_map)->group('service_personal_id')->field('return_result,service_personal_id')->select();

        foreach($service_res as $k=>$v){
            $num_list[$k]['service_personal_id'] = $v['service_personal_id'];
            
            //统计远程次数
            $service_map['service_personal_id'] = $v['service_personal_id'];
            $num_list[$k]['num'] = $service->where($condition_map)->where($service_map)->count();

            //查找员工姓名
            $user_map['user_id'] = $v['service_personal_id'];
            $num_list[$k]['name'] = $user->where($user_map)->getField('name');
            
            //统计满意度
            if($data['start_time'] != '' && $data['end_time'] != ''){
                if($data['start_time'] == $data['end_time']){
                    $start_time = $data['start_time'].' 00:00:00';
                    $end_time = $data['end_time'].' 23:59:59';
                }else{
                    $start_time = $data['start_time'].' 00:00:00';
                    $end_time = $data['end_time'].' 23:59:59';
                }
                $satisfaction = $service->query("SELECT return_result,COUNT(1) AS COUNT FROM lyfz_service WHERE service_personal_id=".$v['service_personal_id']." AND ( (remote_time >= '".$start_time."') AND (remote_time <= '".$end_time."')  ) GROUP BY return_result HAVING COUNT>=1");
            }else{
                $satisfaction = $service->query('SELECT return_result,COUNT(1) AS COUNT FROM lyfz_service WHERE service_personal_id='.$v['service_personal_id'].' GROUP BY return_result HAVING COUNT>=1');
            }
            $num_list[$k]['complain'] = 0;
            $num_list[$k]['commonly'] = 0;
            $num_list[$k]['satisfied'] = 0;
            $num_list[$k]['very_satisfied'] = 0;
            $num_list[$k]['waiting_return'] = 0;
            foreach($satisfaction as $value){
                switch($value['return_result']){
                    case '1':
                        $num_list[$k]['waiting_return'] = $value['COUNT'];
                        break;
                    case '2':
                        $num_list[$k]['complain'] = $value['COUNT'];
                        break;
                    case '3':
                        $num_list[$k]['commonly'] = $value['COUNT'];
                        break;
                    case '4':
                        $num_list[$k]['satisfied'] = $value['COUNT'];
                        break;
                    case '5':
                        $num_list[$k]['very_satisfied'] = $value['COUNT'];
                        break;
                }
            }
        }
        
        //排序
        foreach($num_list as $v){
            switch($data['return_result']){
                case '1':
                    $nums[] = $v['waiting_return'];
                    break;
                case '2':
                    $nums[] = $v['complain'];
                    break;
                case '3':
                    $nums[] = $v['commonly'];
                    break;
                case '4':
                    $nums[] = $v['satisfied'];
                    break;
                case '5':
                    $nums[] = $v['very_satisfied'];
                    break;
                default:
                    $nums[] = $v['num'];
            }
        }
        array_multisort($nums,SORT_DESC,$num_list);
        
        //统计总计
        $remote_total = 0;
        $very_satisfied_total = 0;
        $satisfied_total = 0;
        $commonly_total = 0;
        $complain_total = 0;
        $waiting_return_total = 0;
        foreach($num_list as $v){
            $remote_total = $v['num'] + $remote_total;
            $very_satisfied_total = $v['very_satisfied'] + $very_satisfied_total;
            $satisfied_total = $v['satisfied'] + $satisfied_total;
            $complain_total = $v['complain'] + $complain_total;
            $commonly_total = $v['commonly'] + $commonly_total;
            $waiting_return_total = $v['waiting_return'] + $waiting_return_total;
        }
        
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        
        $this->assign('num_list',$num_list);
        $this->assign('remote_total',$remote_total);
        $this->assign('very_satisfied_total',$very_satisfied_total);
        $this->assign('satisfied_total',$satisfied_total);
        $this->assign('commonly_total',$commonly_total);
        $this->assign('complain_total',$complain_total);
        $this->assign('waiting_return_total',$waiting_return_total);
        $this->assign('role_department_res',$role_department_res);
        $this->display();
    }
    
    //远程统计类型分析
    public function remoteTypeAnalysis(){
        $data = I('get.');
        
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        
        //获取远程类型选项
        $field_res = $this->getFieldInfo('远程类型');        
        if($field_res->meta->code == 200){
            if($field_res->body['form_type'] == 'box'){
                $field_arr = field_list_html("add","service",$leads)['main'];
                $field_key = findId($field_arr,'远程类型');
                $this->assign('remote_type',$field_arr[$field_key]['html']);
                
                $option_arr = $field_arr[$field_key]['setting']['data'];
                
                $service = M('service');
                if($data['start_time'] != '' && $data['end_time'] != ''){
                    $service_map['remote_time'] = array(array('EGT',$data['start_time']),array('ELT',$data['end_time']),'AND');
                }
                if($data['service_personal_id'] != ''){
                    $service_map['service_personal_id'] = $data['service_personal_id'];
                }
                if($data['service_personal_id'] == 'all' && $data['department'] != '' && $data['department'] != 'all'){
                    $position = M('position');
                    $position_map['department_id'] = $data['department'];
                    $position_res = $position->where($position_map)->field('position_id')->select();
                    $arr = array_column($position_res,'position_id');

                    $role = M('role');
                    $role_map['position_id'] = array('in',$arr);
                    $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id')->select();
                    $service_map['service_personal_id'] = array('in',array_column($role_res,'user_id'));
                }
                foreach($option_arr as $k=>$v){
                    //远程数量远程率统计
                    $service_map[$field_arr[$field_key]['field']] = $v;
                    $num = $service->where($service_map)->count();
                    $list[$k]['total'] = $num;
                    $list[$k]['name'] = $v;
                    
                    //重复远程数统计
                    $groupSql = $service
                        ->where($service_map)
                        ->group('customer_id')
                        ->field("count(*)>1")
                        ->select();

                    $list[$k]['repeat_num'] = 0;
                    foreach($groupSql as $k2=>$v2){
                        if($v2['count(*)>1'] != 0){
                            $list[$k]['repeat_num']++;
                        }
                    }
                }
                
                //远程总数计算
                $remote_total = 0;
                foreach($list as $k=>$v){
                    $remote_total = $v['total'] + $remote_total;
                }
                
                //计算远程率
                foreach($list as $k=>$v){
                    $list[$k]['remote_rate']= floor($v['total']/$remote_total*100);
                }
                
                //远程类型条件筛选
                if($data[$field_arr[$field_key]['field']] != ''){
                    foreach($list as $k=>$v){
                        if($v['name'] != $data[$field_arr[$field_key]['field']]){
                            unset($list[$k]);
                        }
                    }
                }
            }
        }
        
        $this->assign('list',$list);
        $this->assign('role_department_res',$role_department_res);
        $this->assign('remote_total',$remote_total);
        $this->display();
    }
    
    //远程统计客户远程排名
    public function customerRanking(){
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        
        $data = I('get.');
        
        $user = M('user');
        $service = M('service');

        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');
        $remoteField = $field_res->body['field'];
        
        //按日期查询
        if($data['start_time'] != '' && $data['end_time'] != ''){
            $service_map['lyfz_service.remote_time'] = array(array('EGT',$data['start_time']),array('ELT',$data['end_time']),'AND');
        }
        //按员工查询
        if($data['service_personal_id'] != ''){
            $service_map['lyfz_service.service_personal_id'] = $data['service_personal_id'];
        }
        //按部门查询
        if($data['service_personal_id'] == 'all' && $data['department'] != '' && $data['department'] != 'all'){
            $position = M('position');
            $position_map['department_id'] = $data['department'];
            $position_res = $position->where($position_map)->field('position_id')->select();
            $arr = array_column($position_res,'position_id');

            $role = M('role');
            $role_map['position_id'] = array('in',$arr);
            $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id')->select();
            $service_map['lyfz_service.service_personal_id'] = array('in',array_column($role_res,'user_id'));
        }
        
        //远程总数排序
        $remote_number_sort = $data['remote_number_sort'] == 'asc' ? 'asc':'desc';
        
        import('ORG.Util.Page');
        $groupSql = $service->join('lyfz_customer ON lyfz_customer.customer_id = lyfz_service.customer_id')
            ->where($service_map)
            ->group('lyfz_customer.customer_id')
            ->field("count(*)")
            ->buildSql();
        $count = $service->table("{$groupSql} as t")->count();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $Page = new Page($count, $listrows);
        $show = $Page->show();
        $res = $service->join('lyfz_customer ON lyfz_customer.customer_id = lyfz_service.customer_id')
            ->where($service_map)
            ->field('lyfz_service.*, count(lyfz_service.customer_id) as remote_number, lyfz_customer.name as customer_name, lyfz_customer.customer_id')
//            ->limit($Page->firstRow.','.$Page->listRows)
            ->page($p, $listrows)
            ->group('lyfz_customer.customer_id')
            ->order('remote_number '.$remote_number_sort.'')->select();
    
        //获取各操作员工数据及统计远程次数
        foreach($res as $k=>$v){
            //统计重复事项次数
            $service_res = $service->query('SELECT '.$remoteField.',COUNT(1) AS COUNT FROM lyfz_service WHERE customer_id='.$v['customer_id'].' GROUP BY '.$remoteField.' HAVING COUNT>1');

            $repeat_num = 0;
            foreach($service_res as $v){
                $repeat_num = $v['COUNT'] - 1 + $repeat_num;
            }
            $res[$k]['repeat_num'] = $repeat_num;
        }
        
        $this->assign('list',$res);
        $this->assign('page',$show);
        $this->assign('role_department_res',$role_department_res);
        $this->display();
    }
    
    //上门质量统计
    public function doorQuality(){
        //获取所有部门选项数据
        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        
        $data = I('get.');
        
        $user = M('user');
        $service = M('service');

        //按日期查询
        if($data['start_time'] != '' && $data['end_time'] != ''){
            $service_map['lyfz_service.remote_time'] = array(array('EGT',$data['start_time']),array('ELT',$data['end_time']),'AND');
        }
        //按员工查询
        if($data['service_personal_id'] != ''){
            $service_map['lyfz_service.service_personal_id'] = $data['service_personal_id'];
        }
        //按部门查询
        if($data['service_personal_id'] == 'all' && $data['department'] != '' && $data['department'] != 'all'){
            $position = M('position');
            $position_map['department_id'] = $data['department'];
            $position_res = $position->where($position_map)->field('position_id')->select();
            $arr = array_column($position_res,'position_id');

            $role = M('role');
            $role_map['position_id'] = array('in',$arr);
            $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id')->select();
            $service_map['lyfz_service.service_personal_id'] = array('in',array_column($role_res,'user_id'));
        }
        
        //远程总数排序
        $remote_number_sort = $data['remote_number_sort'] == 'asc' ? 'asc':'desc';
        
        import('ORG.Util.Page');
        $groupSql = $service->join('lyfz_user ON lyfz_user.user_id = lyfz_service.teacher_id')
            ->where($service_map)
            ->group('lyfz_service.teacher_id')
            ->field("count(*)")
            ->buildSql();
        $count = $service->table("{$groupSql} as t")->count();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $this->listrows = $listrows;
        $Page = new Page($count, $listrows);
        $show = $Page->show();
        $res = $service->join('lyfz_user ON lyfz_user.user_id = lyfz_service.teacher_id')
            ->where($service_map)
            ->field('lyfz_service.*, count(lyfz_service.teacher_id) as remote_number, lyfz_user.name as user_name')
//            ->limit($Page->firstRow.','.$Page->listRows)
            ->page($p, $listrows)
            ->group('lyfz_service.teacher_id')
            ->order('remote_number '.$remote_number_sort.'')->select();

        //获取远程类型字段选项
        $field_res = $this->getFieldInfo('远程类型');    
        $remoteField = $field_res->body['field'];

        //获取各操作员工数据及统计远程次数
        foreach($res as $k=>$v){
            $service_map2['teacher_id'] = $v['teacher_id'];
            $res[$k]['repeat_num'] = $service->where($service_map2)->group($remoteField)->count() - 1;
        }
        
        $this->assign('list',$res);
        $this->assign('page',$show);
        $this->assign('role_department_res',$role_department_res);
        $this->display();
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
        $role_res = $role->join('lyfz_user ON lyfz_user.user_id = lyfz_role.user_id')->where($role_map)->field('lyfz_user.user_id,lyfz_user.name')->select();
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
    
    //删除服务记录
    public function delServerRecord(){
        $data = I('get.');
    
        $service = M('service');
        $img_list = M('img_list');
        $service_map['service_id'] = $data['service_id'];
        $server_res = $service->where($service_map)->delete();
        if($server_res){
            $img_list_map['relation_id'] = $data['service_id'];
            $img_list_res = $img_list->where($img_list_map)->select();
            foreach($img_list_res as $k=>$v){
                unlink('./'.$v['img_url']); 
            }
            
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    
    /** 远程服务 */
    public function order_service(){
        $user_id = $_SESSION['user_id'];
        $db_order_product = M('r_order_product');
        $this->assign('search',['checked'=>2]) ;//默认搜索我下属的
        $sub_role_id_list = getSubRoleId();
        $where['order_product.owner_role_id'] = ['in', $sub_role_id_list];
        $noDisplayProductId = unserialize(M('Config')->getFieldByName('not_display_product_id', 'value'));
        $where['order_product.product_id'] = ['not in', $noDisplayProductId];
        $orderBYDesc = I('get.order_by', 'order_product.create_time').' desc';
        if ($checked = I('get.checked', null)){
            $search = I('get.', null);
            $status = I('get.status', null, 'intval');
            $start_time = I('get.start_time',null) ;
            $end_time = I('get.end_time',null) ;
            if($start_time != null && $end_time != null){
                $where['order_product.create_time'] = array(array('EGT',strtotime($start_time)),array('ELT',strtotime($end_time) + 86400), 'AND');
            }

            if (1==$status){
                $where['order_product_extend.contacting_role_id'] = ['exp', 'is null'];
                $where['order_product_extend.contacting_status'] =  ['exp', 'is null'];
            }else if(2==$status){
                $where['order_product_extend.contacting_status'] = 'UNSERVED' ;
            }else if(3==$status){
                $where['order_product_extend.contacting_status'] = 'SERVING' ;
            }else if(4==$status){
                $where['order_product_extend.contacting_status'] = 'FINISH' ;
            }

            if ($checked == 1) //我负责的
                $where['order_product.owner_role_id']=$user_id ;
            elseif ($checked == 2) { //我下属的
                $sub_role_id_list = getSubRoleId();
                $where['order_product.owner_role_id'] = ['in', $sub_role_id_list];
            } elseif ($checked == 3) { //我关注的
                $role_id = I('get.check_role_id', $_SESSION['role_id']);
                $sub_role_id_list = getSubRoleId(true, $role_id);
                $where['order_product.owner_role_id'] = ['in', $sub_role_id_list];
            } else {
                unset($where['order_product.owner_role_id']);
            }
            $this->assign('search',$search) ;
        }

//        import('ORG.Util.Page');
        import('@.ORG.Page3');// 导入分页类
        $count_sql  = $db_order_product->alias('order_product')
        ->join('inner join '.C('DB_PREFIX').'receivables receivables ON receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
        ->join(C('DB_PREFIX').'receivingorder receiving ON receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
        ->join(C('DB_PREFIX').'r_order_product_extend order_product_extend ON order_product_extend.order_product_id = order_product.id')
        ->where($where)
        ->field('id')
        ->group('receivables.receivables_id, order_product.id')
        ->buildSql();

        $count = M()->table("$count_sql t")->count();
        $listrows = I('get.listrows', 15);
        $Page = new Page($count, $listrows);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();// 分页显示输出
        $this->assign('listrows', $listrows);
        $res = $db_order_product->alias('order_product')
            ->join('inner join '.C('DB_PREFIX').'receivables receivables ON receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
            ->join(C('DB_PREFIX').'receivingorder receiving ON receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = order_product.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->join(C('DB_PREFIX').'user user ON order_product.owner_role_id = user.role_id')
            ->join(C('DB_PREFIX').'r_order_product_extend order_product_extend ON order_product_extend.order_product_id = order_product.id')
            ->join(C('DB_PREFIX').'user contacting_user ON order_product_extend.contacting_role_id = contacting_user.role_id')
            ->field(
                'order_product.product_name,
                 order_product.id as order_product_id,
                 customer.customer_id as customer_id,
                 customer.`name` as customer_name,
                 contacts.`name` as contacts_name,
                 contacts.telephone as contacts_phone,
                 `user`.`name` as owner_name,
                 contacting_user.`name` as contacting_name,
                 order_product_extend.contacting_status as contacting_status,
                 order_product.create_time as create_time,
                 receivables.price,
                 COALESCE(SUM(receiving.money),0) as received,
                 receivables.price - COALESCE(SUM(receiving.money),0) as debt')
            ->where($where)
            ->order($orderBYDesc)
            ->group('receivables.receivables_id, order_product.id');

        if (I('get.export')==1){
            $res = $res->select();
            foreach($res as $key => $item){
                $res[$key]['create_time'] =  Date('Y-m-d', $item['create_time']);
                if (empty($item['contacting_name'])){
                    $res[$key]['contacting_name'] = "未安排";
                }
                if (empty($item['contacting_status'])){
                    $res[$key]['contacting_status'] = "UNSERVED";
                }
            }

            $this->excelExport($res);
        }
        $res = $res->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($res as $key => $item){
            $res[$key]['create_time'] =  Date('Y-m-d', $item['create_time']);
            if (empty($item['contacting_name'])){
                $res[$key]['contacting_name'] = "未安排";
            }
            if (empty($item['contacting_status'])){
                $res[$key]['contacting_status'] = "UNSERVED";
            }
        }

        $this->assign('list',$res);
        $this->assign('page',$show);
        $this->display();
    }

    public function excelExport($list=false){
        C('OUTPUT_ENCODE', false);
        import("ORG.PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("5kcrm");
        $objProps->setLastModifiedBy("5kcrm");
        $objProps->setTitle("5kcrm Service");
        $objProps->setSubject("5kcrm Service Data");
        $objProps->setDescription("5kcrm Service Data");
        $objProps->setKeywords("5kcrm Service Data");
        $objProps->setCategory("5kcrm");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();

        $objActSheet->getColumnDimension('A')->setWidth(40);
        $objActSheet->getColumnDimension('B')->setWidth(30);
        $objActSheet->getColumnDimension('C')->setWidth(18);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(15);
        $objActSheet->getColumnDimension('F')->setWidth(15);
        $objActSheet->getColumnDimension('G')->setWidth(20);
        $objActSheet->getColumnDimension('H')->setWidth(15);
        $objActSheet->getColumnDimension('I')->setWidth(15);
        $objActSheet->getColumnDimension('J')->setWidth(15);

        $objActSheet->setTitle('服务安排');
        $objPHPExcel->getActiveSheet()->setCellValue('A1',  '影楼名称');//这里是设置A1单元格的内容
        $objPHPExcel->getActiveSheet()->setCellValue('B1',  '购买产品');////这里是设置B1单元格的内容
        $objPHPExcel->getActiveSheet()->setCellValue('C1',  '购买时间');
        $objPHPExcel->getActiveSheet()->setCellValue('D1',  '订单应收款');
        $objPHPExcel->getActiveSheet()->setCellValue('E1',  '订单欠款');
        $objPHPExcel->getActiveSheet()->setCellValue('F1',  '客户姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('G1',  '电话');
        $objPHPExcel->getActiveSheet()->setCellValue('H1',  '负责人员');
        $objPHPExcel->getActiveSheet()->setCellValue('I1',  '对接人员');
        $objPHPExcel->getActiveSheet()->setCellValue('J1',  '对接状态');
        foreach ($list as $key => $value) {
            $i=$key+2;//表格是从1开始的
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,  $value['customer_name']);//这里是设置A1单元格的内容
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,  $value['product_name']);////这里是设置B1单元格的内容
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,  $value['create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,  $value['price']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,  $value['price'] - $value['received']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,  $value['contacts_name']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('G'.$i,  $value['contacts_phone'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,  $value['owner_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.$i,  $value['contacting_name']);
            switch ($value['contacting_status']){
                case 'FINISH':
                    $value['contacting_status'] = '已完成';break;
                case 'SERVING':
                    $value['contacting_status'] = '服务中';break;
                case 'UNSERVED':
                    $value['contacting_status'] = '待服务';break;
            }
            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i,  $value['contacting_status']);
        }

        $objActSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1:J1')->getAlignment()->setWrapText(true);

//        //设置背景色
        $objActSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objActSheet->getStyle('A1:J1')->getFill()->getStartColor()->setARGB('F5DEB3');

        $current_page = intval($_GET['current_page']);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=customer_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
        session('export_status', 0);
    }


    public function redirectNewRemote()
    {
        $user_id = $_SESSION['user_id'];
        $token = M('user')->where(['user_id'=>$user_id])->getField('token');
        redirect('http://crm.lyfzvip.com/#/RemoteService/remoteService?token='.$token);
    }
}