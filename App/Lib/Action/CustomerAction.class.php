<?php
class CustomerAction extends Action {

    public function _initialize(){
        $action = array(
            'permission'=>array(),
            'allow'=>array('getcustomerlist','analytics', 'validate','check', 'remove', 'fenpei', 'revert','changecontent','customerlock',
                'check_customer_limit','excelimportdownload','search','listDialog','getcustomeroriginal','batchclose','batchfocus','close_share',
                'share','getcurrentstatus','ordered','ordered_search','product','productbutton','express','editfilename','delete_express')
        );
        //在项目开始之前执行这个函数,后面必须为数组
        B('Authenticate', $action);
    }

    /*无法验证中文
    public function checkName(){
        $customer = M('customer');
        if ($customer->where('name = "' . $_GET['name'] . '"' )->find()){
            $this->ajaxReturn(1, "用户名不可以使用啊！",  1);
        }else{
            $this->ajaxReturn(0, "用户名可以使用！", 0);
        }
    }
    */

    public function check(){
        import("@.ORG.SplitWord");
        $sp = new SplitWord();
        $m_customer = M('customer');
        $useless_words = array(L('COMPANY'),L('LIMITED'),L('DI'),L('LIMITED_COMPANY'));
        if ($this->isAjax()) {
            $split_result = $sp->SplitRMM($_POST['name']);
            if(!is_utf8($split_result)) $split_result = iconv("GB2312//IGNORE", "UTF-8", $split_result) ;
            $result_array = explode(' ',trim($split_result));
            if(count($result_array) < 2){
                $this->ajaxReturn(0,'',0);
                die;
            }
            foreach($result_array as $k=>$v){
                if(in_array($v,$useless_words)) unset($result_array[$k]);
            }
            $name_list = $m_customer->getField('name', true);
            $seach_array = array();
            foreach($name_list as $k=>$v){
                $search = 0;
                foreach($result_array as $k2=>$v2){
                    if(strpos($v, $v2) > -1){
                        $v = str_replace("$v2","<span style='color:red;'>$v2</span>", $v, $count);
                        $search += $count;
                    }
                }
                if($search > 2) $seach_array[$k] = array('value'=>$v,'search'=>$search);
            }
            $seach_sort_result = array_sort($seach_array,'search','desc');
            if(empty($seach_sort_result)){
                $this->ajaxReturn(0,L('ABLE_ADD'),0);
            }else{
                $this->ajaxReturn($seach_sort_result,L('CUSTOMER_IS_CREATED'),1);
            }
        }
    }

    public function validate() {
        if($this->isAjax()){
            if(!$this->_request('clientid','trim') || !$this->_request($this->_request('clientid','trim'),'trim')) $this->ajaxReturn("","",3);
            $field = M('Fields')->where('model = "customer" and field = "%s"', $this->_request('clientid','trim'))->find();
            $m_customer = $field['is_main'] ? D('Customer') : D('CustomerData');
            $where[$this->_request('clientid','trim')] = array('eq',$this->_request($this->_request('clientid','trim'),'trim'));
            if($this->_request('id','intval',0)){
                $where[$m_customer->getpk()] = array('neq',$this->_request('id','intval',0));
            }
            if($this->_request('clientid','trim')) {
                if ($m_customer->where($where)->find()) {
                    $this->ajaxReturn("","",1);
                } else {
                    $this->ajaxReturn("","",0);
                }
            }else{
                $this->ajaxReturn("","",0);
            }
        }
    }

    public function remove(){
        if($this->isPost()){
            $m_customer = M('Customer');
            $customer_ids = is_array($_POST['customer_id']) ? implode(',', $_POST['customer_id']) : '';
            if('' == $customer_ids){
                alert('error', L('NOT_CHOOSE_ANY'), $_SERVER['HTTP_REFERER']);
            }
            $lock_names = $m_customer->where('customer_id in (%s) and is_locked = 1',$customer_ids)->getField('name',true);
            if($lock_names){
                $customers = implode(' , ',$lock_names);
                alert('error','客户('.$customers.')已被锁定，不能放入客户池！',$_SERVER['HTTP_REFERER']);
            }
            if($m_customer->where('customer_id in (%s)', $customer_ids)->setField('owner_role_id',0)){
                alert('success', L('BATCH_INTO_THE_SUCCESSFUL_CUSTOMER_POOL'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', L('BATCH_INTO_THE_CUSTOMER_POOL_FAILURE'), $_SERVER['HTTP_REFERER']);
            }

        }
    }
    public function receive(){
        $m_customer = M('Customer');
        $m_config = M('Config');
        $m_customer_record = M('customer_record');
        if(!empty($_POST['owner_role_id'])){
            $owner_role_id = $_POST['owner_role_id'];
        }elseif(!empty($_POST['owner_role'])){
            $owner_role_id = $_POST['owner_role'];
        }else{
            $owner_role_id = session('role_id');
        }
        $data['owner_role_id'] = $owner_role_id;
        $data['update_time'] = time();
        //是否是分配需要提醒
        $need_alert = false;
        //单个领取
        if($this->isGet()){
            $customer_id = isset($_GET['customer_id']) ? intval(trim($_GET['customer_id'])) : 0;
            //判断是否符合领取条件
            $customer_limit_counts = $m_config->where('name = "customer_limit_counts"')->getField('value');
            $customer_record_count = $this->check_customer_limit(session('user_id'), 1);
            if($customer_record_count < $customer_limit_counts){
                $contacts = M('rContactsCustomer')->where('customer_id = %d', $customer_id)->select();
                foreach($contacts as $k=>$v ){
                    M('contacts')->where('contacts_id = %d', $v['contacts_id'])->setField('owner_role_id',$owner_role_id);
                }
                if($m_customer->where('customer_id = %d', $customer_id)->save($data)){
                    $info['customer_id'] = $customer_id;
                    $info['user_id'] = session('user_id');
                    $info['start_time'] = time();
                    $info['type'] = 1;
                    $m_customer_record->add($info);
                    alert('success', L('GET_THE_SUCCESS'), $_SERVER['HTTP_REFERER']);
                }else{
                    alert('error', L('GET_THE_FAILURE'), $_SERVER['HTTP_REFERER']);
                }
            }else{
                alert('error', L('GET_THE_FAILURE_OVER_GET'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            $customer_name = array();
            $customer_ids = $_POST['customer_id'];
            //是否批量操作 否的话是单个分配
            if(!$_POST['customer_id']){alert('error', L('NO_CHANCE_CUSTOMER'), $_SERVER['HTTP_REFERER']);}
            if(is_array($customer_ids)){
                //检查用户是否符合领取客户池资源资格
                //判断领取或分配  operating_type  receive:领取  assign:分配
                $customer_limit_counts = $m_config->where('name = "customer_limit_counts"')->getField('value');
                $customer_record_count = $this->check_customer_limit(session('user_id'), 1);
                if(sizeof($customer_ids) + $customer_record_count <= $customer_limit_counts){
                    if($_POST['operating_type'] == 'receive'){

                        if($customer_record_count >= $customer_limit_counts){
                            alert('error', L('GET_THE_FAILURE_OVER_GET'), $_SERVER['HTTP_REFERER']);
                        }
                    }
                }else{
                    alert('error', L('GET_THE_FAILURE_OVER_GET_LIMIT',array($customer_limit_counts)),$_SERVER['HTTP_REFERER']);
                }

                $where['update_time'] = array('lt',(time()-86400));
                $where['customer_id'] = array('in',implode(',',$customer_ids));
                $where['owner_role_id'] = array('gt',0);
                $contacts = M('rContactsCustomer')->where('customer_id in (%s)', implode(',',$customer_ids))->select();
                foreach($contacts as $k=>$v ){
                    M('contacts')->where('contacts_id = %d', $v['contacts_id'])->setField('owner_role_id',$owner_role_id);
                }
                $updated_owner = $m_customer->where($where)->save($data);
                unset($where['update_time']);
                $where['owner_role_id'] = array('eq',0);
                $customer_name = $m_customer->where($data)->getField('name', true);
                $updated_time = $m_customer->where($where)->save($data);

                //是否操作成功
                if($updated_owner || $updated_time){
                    //增加customer_record记录
                    $m_user = M('user');
                    $user_id = $m_user->where('role_id = %d', $owner_role_id)->getField('user_id');
                    $info['start_time'] = time();
                    foreach($customer_ids as $v){
                        $info['customer_id'] = $v;
                        if($_POST['operating_type'] == 'receive'){
                            $info['user_id'] = session('user_id');
                            $info['type'] = 1;
                        }else{
                            $info['user_id'] = $user_id;
                            $info['type'] = 2;
                        }
                        $m_customer_record->add($info);
                    }
                    //是分配还是领取
                    if($_POST['owner_role']){
                        $title=L('you_have_new_customer');
                        $content=L('THE_CUSTOMER_RESOURCES',array(session('name'),implode(',', $customer_name)));
                        $need_alert = true;
                    }else{
                        alert('success', L('BATCH_TO_GET_SUCCESS'), $_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('BATCH_ALLOCATION_FAILURE'), $_SERVER['HTTP_REFERER']);
                }
            }else{
                $customer_limit_counts = $m_config->where('name = "customer_limit_counts"')->getField('value');
                $customer_record_count = $this->check_customer_limit(session('user_id'), 1);
                if(1 + $customer_record_count <= $customer_limit_counts){
                    if($_POST['operating_type'] == 'receive'){

                        if($customer_record_count >= $customer_limit_counts){
                            alert('error', L('GET_THE_FAILURE_OVER_GET'), $_SERVER['HTTP_REFERER']);
                        }
                    }
                }else{
                    alert('error', L('GET_THE_FAILURE_OVER_GET_LIMIT',array($customer_limit_counts)),$_SERVER['HTTP_REFERER']);
                }

                $where['update_time'] = array('lt',(time()-86400));
                $where['customer_id'] = intval($customer_ids);
                $where['owner_role_id'] = array('gt',0);
                $contacts = M('rContactsCustomer')->where('customer_id = %d', $customer_ids)->select();
                foreach($contacts as $k=>$v ){
                    M('contacts')->where('contacts_id = %d', $v['contacts_id'])->setField('owner_role_id',$owner_role_id);
                }
                $updated_owner = $m_customer->where($where)->save($data);

                unset($where['update_time']);
                $where['owner_role_id'] = array('eq',0);
                $updated_time = $m_customer->where($where)->save($data);

                if($updated_owner || $updated_time){
                    $customer = $m_customer->where('customer_id = %d', intval($customer_ids))->find();
                    $title=L('you_have_new_customer');
                    $content=L('THE_CUSTOMER_RESOURCES',array(session('name'),U('Customer/view','id='.$customer_ids),$customer['name']));
                    $need_alert = true;
                }else{
                    alert('error', L('ASSIGNMENT_FAILURE'), $_SERVER['HTTP_REFERER']);
                }
            }

            //分配需要提醒
            if($need_alert){
                if(intval($_POST['message_alert']) == 1) {
                    sendMessage($owner_role_id,$content,1);
                }
                if(intval($_POST['email_alert']) == 1){
                    $email_result = sysSendEmail($owner_role_id,$title,$content);
                    if(!$email_result) alert('error', L('EMAIL_FAILURE_NOT_SET_EFFECTIVE_MAILBOX'),$_SERVER['HTTP_REFERER']);
                }
                if(intval($_POST['sms_alert']) == 1){
                    $sms_result = sysSendSms($owner_role_id,$content);
                    if(100 == $sms_result){
                        alert('error', L('MESSAGE_FAILURE_NOT_SET_EFFECTIVE_MOBILE'),$_SERVER['HTTP_REFERER']);
                    }elseif($sms_result < 0){
                        alert('error',L('MESSAGE_FAILURE_ERRORCODE',array($sms_result)),$_SERVER['HTTP_REFERER']);
                    }
                }
                alert('success', L('DISTRIBUTION_OF_SUCCESS'), $_SERVER['HTTP_REFERER']);
            }

        }
    }

    public function fenpei(){
        $customer_id = intval($_GET['customer_id']);
         if ($this->isGET()) {
            if($_GET['by'] == 'put'){
                if($customer_id){
                    $customer = M('customer')->where('customer_id = %d', $customer_id)->find();
                    if($customer['is_locked'] == 0){
                        if(M('customer')->where('customer_id = %d', $customer_id)->setField('owner_role_id',0)){
                            alert('success', L('IN_THE_SUCCESSFUL_CUSTOMER_POOL'), U('customer/index'));
                        }else{
                            alert('error', L('IN_THE_CUSTOMER_POOL'), $_SERVER['HTTP_REFERER']);
                        }
                    }else{
                        alert('error', L('ISLOCK_CAN_NOT_PUT_IN_CUSTOMER_POOL'), $_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
                }
            }else{
                $this->customer_id = $customer_id;
                $this->display();
            }
        }
    }

     //高级收索
    public function search(){
        $d_v_customer = D('CustomerView');
        $by = isset($_GET['by']) ? trim($_GET['by']) : '';
        $below_ids = getSubRoleId(false);
        $all_ids = getSubRoleId();
        $outdays = M('config') -> where('name="customer_outdays"')->getField('value');
        $outdate = empty($outdays) ? time() : time()-86400*$outdays;
        $where = array();
        $params = array();
        $order = "";
        switch ($by) {
            case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
            case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d')) - (date('N', time()) - 1) * 86400)); break;
            case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
            case 'add' : $order = 'create_time desc'; break;
            case 'update' : $order = 'update_time desc'; break;
            case 'sub' : $where['owner_role_id'] = array('in',implode(',', $below_ids)); break;
            case 'deleted' : $where['is_deleted'] = 1;break;
            case 'me' : $where['owner_role_id'] = session('role_id'); break;
            default :
                if($this->_get('content') == 'resource'){
                    $where['_string'] = "customer.owner_role_id=0 or customer.update_time < $outdate";
                    $all_ids[] = "";
                    $where['owner_role_id'] = array('in', $all_ids);
                }else{
                    $where['owner_role_id'] = array('in',implode(',', $all_ids));
                }
            break;
        }
        if ($by != 'deleted') {
            $where['is_deleted'] = array('neq',1);
        }
        if (!isset($where['owner_role_id'])) {
            $where['owner_role_id'] = array('in', $all_ids);
        }
        if($this->_get('content') != 'resource'){
            $where['update_time'] = array('gt',$outdate);
        }
        if($this->_get('create_time')){
            $create_times = $this->_get('create_time');
            $create_time = strtotime($create_times['value']);
            switch ($create_times['condition']) {
                case "tgt" :  $where['create_time'] = array('egt',$create_time);break;  //晚于
                case "lt" : $where['create_time'] = array('elt',$create_time);break;        //早于
                case "between" : $where['create_time'] = array('between',array($create_time-1,$create_time+86400));break;//在
                case "nbetween" : $where['create_time'] = array('not between',array($create_time,$create_time+86399));break;//不在
            }
        }

        if($this->_get('update_time')){
            $update_times = $this->_get('update_time');
            $update_time = strtotime($update_times['value']);
            switch ($update_times['condition']) {
                case "tgt" :  $where['update_time'] = array('egt',$update_time);break;  //晚于
                case "lt" : $where['update_time'] = array('elt',$update_time);break;        //早于
                case "between" : $where['update_time'] = array('between',array($update_time-1,$update_time+86400));break;//在
                case "nbetween" : $where['update_time'] = array('not between',array($update_time,$update_time+86399));break;//不在
            }
        }


        if($by == 'deleted') unset($where['update_time']);

        if($_GET){
            foreach($_GET as $k=>$v){
                if($k != 'act' && $k != 'content' && $k != 'p' && $k != 'create_time' && $k != 'update_time'){
                    if(is_array($v)){
                         if ($v['state']){
                            $address_where[] = '%'.$v['state'].'%';

                            if($v['city']){
                                $address_where[] = '%'.$v['city'].'%';

                                if($v['area']){
                                    $address_where[] = '%'.$v['area'].'%';
                                }
                            }

                            if($v['search']) $address_where[] = '%'.$v['search'].'%';
                            //echo $v['condition']; die();
                            if($v['condition'] == 'not_contains'){
                                $where[$k] = array('notlike', $address_where, 'OR');
                            }else{
                                $where[$k] = array('like', $address_where, 'AND');
                            }
                        }elseif(!empty($v['value'])){
                            $where[$k] = field($v['value'],$v['condition']);
                        }
                    }else{
                        if(!empty($v)){
                            $where[$k] = field($v);
                        }
                    }
                }
                if(is_array($v)){
                    foreach ($v as $key => $value) {
                        $parames[] = $k.'['.$key.']='.$value;
                    }
                }else{
                    $parames[] = $k.'='.$v;
                }
            }
        }
        unset($where['area']);
        if(trim($_GET['act'] == 'sms')){
            $customer_ids = $d_v_customer->where($where)->getField('customer_id', true);
            $contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', implode(',', $customer_ids))->getField('contacts_id', true);
            $contacts_ids = implode(',', $contacts_ids);
            $contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
            $this->contacts = $contacts;
            $this->display('Setting:sendsms');
        }elseif(trim($_GET['act']) == 'excel'){
            if(vali_permission('customer', 'export')){
                $order = $order ? $order : 'create_time desc';
                $order = $order ? $order : 'create_time desc';
                $customerList = $d_v_customer->where($where)->order($order)->select();
                $this->excelExport($customerList);
            }else{
                alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            if ($order) {
                $list = $d_v_customer->where($where)->order($order)->limit(1)->select();
            } else {
                $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
                $list = $d_v_customer->where($where)->order('create_time desc')->page($p.',15')->select();
                $count = $d_v_customer->where($where)->count();
                import("@.ORG.Page");
                $Page = new Page($count,15);
                if (!empty($_GET['by'])) {
                    $params[] = "by=" . trim($_GET['by']);
                }
                $Page->parameter = implode('&', $params);
                $this->assign('page',$Page->show());
            }
            if($by == 'deleted') {
                foreach ($list as $k => $v) {
                    $list[$k]["delete_role"] = D('RoleView')->where('role.role_id = %d', $v['delete_role_id'])->find();
                    $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                    $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                }
            } else {
                foreach ($list as $k => $v) {
                    $days = 0;
                    $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                    $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                    $days =  M('Customer')->where('customer_id = %d', $v['customer_id'])->getField('update_time');
                    $list[$k]["days"] = $outdays-floor((time()-$days)/86400);
                }
            }
            $this->customerlist = $list;
            $this->field_array = getIndexFields('customer');
            $this->field_list = getMainFields('customer');
            $this->alert = parseAlert();
            $this->display();
        }
    }

    /**
     * 订单客户高级搜索
     */
    public function ordered_search(){
        $where = ['customer.is_deleted' => 0];
        $params = [];
        $order = "";

        $search = I('get.');
        if (in_array('listrows', $search)) unset($search['listrows']);
        if($_GET['desc_order']){
            $order = trim($_GET['desc_order']).' desc';
        }elseif($_GET['asc_order']){
            $order = trim($_GET['asc_order']).' asc';
        }
        //创建人
        if ($search['role_id'] != 'all' && $search['role_id'] != '') {
            $where['customer.owner_role_id'] = $search['role_id'];
        }elseif($search['department'] != 'all' && $search['department'] != ''){
            $department_id = $search['department'];
            $roleList = getRoleByDepartmentId($department_id, true);//true表示部门所有人
            foreach ($roleList as $role){
                $roleIds[] = $role['role_id'];
            }
            $where['customer.owner_role_id'] = array('in', $roleIds);
        }
        //创建时间
        if($search['create_time']){
            $create_times = $search['create_time'];
            $create_time = strtotime($create_times['value']);
            switch ($create_times['condition']) {
                case "tgt" :  $where['customer.create_time'] = array('egt',$create_time);break;  //晚于
                case "lt" : $where['customer.create_time'] = array('elt',$create_time);break;        //早于
                case "between" : $where['customer.create_time'] = array('between',array($create_time-1,$create_time+86400));break;//在
                case "nbetween" : $where['customer.create_time'] = array('not between',array($create_time,$create_time+86399));break;//不在
            }
        }
        //更新时间
        if($search['update_time']){
            $update_times = $search['update_time'];
            $update_time = strtotime($update_times['value']);
            switch ($update_times['condition']) {
                case "tgt" :  $where['customer.update_time'] = array('egt',$update_time);break;  //晚于
                case "lt" : $where['customer.update_time'] = array('elt',$update_time);break;        //早于
                case "between" : $where['customer.update_time'] = array('between',array($update_time-1,$update_time+86400));break;//在
                case "nbetween" : $where['customer.update_time'] = array('not between',array($update_time,$update_time+86399));break;//不在
            }
        }
//        var_dump($search);
        if($search){
            foreach($search as $k=>$v){
                if($k != 'act' && $k != 'content' && $k != 'p' && $k != 'create_time' && $k != 'update_time' && $k != 'department' && $k != 'role_id'){
                    if(is_array($v)){
                        if ($v['state']){
                            $address_where[] = '%'.$v['state'].'%';

                            if($v['city']){
                                $address_where[] = '%'.$v['city'].'%';

                                if($v['area']){
                                    $address_where[] = '%'.$v['area'].'%';
                                }
                            }

                            if($v['address_search']) $address_where[] = '%'.$v['address_search'].'%';
                            if($v['condition'] == 'not_contains'){
                                $where['customer.'.$k] = array('notlike', $address_where, 'OR');
                            }else{
                                $where['customer.'.$k] = array('like', $address_where, 'AND');
                            }
                        }elseif(!empty($v['value'])){
                            if($k == 'contacts_name') $k='name';
                            $where[$v['model'].'.'.$k] = field($v['value'],$v['condition']);
                        }
                    }else{
                        if(!empty($v)){
                            $where['customer.'.$k] = field($v);
                        }
                    }
                }
            }
            $params = http_build_query($where);
        }
        $order = empty($order) ? 'customer.create_time desc' : $order;
//        var_dump($where);

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        if($_GET['listrows']){
            $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
            cookie('list_rows', $listrows);
        }else{
            $listrows = cookie('list_rows')?cookie('list_rows'):15;
            $params['listrows'] = $listrows;

        }
        $query_sql = M('customer')->alias('customer')
            ->join('right join '.C('DB_PREFIX').'receivables receivables on receivables.customer_id=customer.customer_id and receivables.is_deleted = 0')
            ->join('LEFT join '.C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
            ->field(
                'customer.name AS name,
                 customer.customer_id AS customer_id,
                 customer.owner_role_id AS owner_role_id,
                 customer.creator_role_id AS creator_role_id,
                 customer.contacts_id AS contacts_id,
                 customer.create_time AS create_time,
                 customer.update_time as update_time,
                 contacts.name as contacts_name,
                 contacts.telephone as contacts_telephone,
                 receivables.create_time as first_order_time')
            ->where($where)
            ->order($order)
            ->buildSql();

        $list =  M()->table("$query_sql t") -> page($p.','.$listrows)->order('first_order_time desc')->group('customer_id')->select();

        $count = M()->table("$query_sql t") ->field('count(distinct(customer_id)) as count') -> find()['count'];
        $customer_id_list = [];
        foreach($list as $customer){
            $customer_id_list[] = $customer['customer_id'];
        }

        $order_receiving_info = M('receivables')->alias('receivables')
            ->join(C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
            ->where(['receivables.customer_id' => ['in', $customer_id_list], 'receivables.is_deleted' => 0])
            ->field(
                'receivables.customer_id,
             receivables.receivables_id as order_id,
             receivables.order_number,
             receivables.name,
             receivables.price,
             IFNULL(sum(receiving.money), 0) as total_received')
            ->group('receivables.receivables_id')
            ->buildSql();

        $customer_order_receiving_info = M()->table("$order_receiving_info t")
            ->group('customer_id')
            ->field(
                'customer_id,
             sum(price) as total_receiables,
             sum(total_received) as total_received'
            )->select();

        $result = array_column($customer_order_receiving_info, null, 'customer_id');

        foreach($list as $key => $customer){
            $list[$key] = array_merge($customer, $result[$customer['customer_id']]);
            /* 计算欠款 */
            $list[$key]['total_arrearage'] = floatval($list[$key]['total_receiables']) - floatval($list[$key]['total_received']);
            /* 客户名处理 */
            if(empty($list[$key]['name'])){
                $list[$key]['name'] = L('UNKNOWN');
            }
        }

        import("@.ORG.Page");
        $Page = new Page($count,$listrows);
        if (!empty($_GET['by'])) {
            $params['by'] = trim($_GET['by']);
        }
        if (!empty($_GET['content'])) {
            $params['content'] = trim($_GET['content']);
        }
        $Page->parameter = $this->parameter = http_build_query($params);
        $this->assign('page',$Page->show());
//        $search = '/'.preg_replace('/%/', '|', trim($search, '%')).'/';
        foreach ($list as $k => $v) {
            $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
            $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
//            if ($field == 'customer.name' && $search){
//
//                $list[$k]['name'] = preg_replace($search, '<span style="color:red">\\0</span>', $v['name']);
//            }

        }

        $this->listrows = $listrows;
        $this->customerlist = $list;
        $this->assign('count',$count);

        $field_array = getIndexFields('customer');
        /* 暂时写死 */
        $field_array = array_merge($field_array, [[
            'field' => 'contacts_name',
            'name' => '联系人姓名'
        ], [
            'field' => 'contacts_telephone',
            'name' => '联系人电话'
        ], [
            'field' => 'total_receiables',
            'name' => '总消费'
        ], [
            'field' => 'total_arrearage',
            'name' => '总欠款'
        ], [
            'field' => 'first_order_time',
            'name' => '首次下单时间',
            'form_type' => 'datetime'
        ]]);

        $this->assign('field_array', $field_array);

        $field_list = getMainFields('customer');

        /* 暂时写死 */
        $field_list = array_merge($field_list, [[
            'field' => 'contacts_name',
            'name' => '联系人姓名',
            'model' => 'contacts'
        ],[
            'field' => 'telephone',
            'name' => '联系人电话',
            'model' => 'contacts'
        ]]);

        $this->assign('field_list', $field_list);

        $role_department = M('role_department');
        $role_department_res = $role_department->cache(true,3600)->select();
        $this->assign('role_department_res',$role_department_res);

        $this->alert = parseAlert();
//        var_dump(I('get.'));die;
//        var_dump($field_list);exit;
        $this->display();

    }


    public function changeContent(){
        $pages = 14;
        if($this->isAjax()){
            $m_customer = M('Customer');
            $user = M('user');
            $config = M('config');
            $below_ids = getSubRoleId(false);
            $where = array();
            $params = array();
            $where['is_deleted'] = array('neq',1);
            //$where['owner_role_id'] = array('in',implode(',', getSubRoleId(true)));

            if ($_REQUEST["field"]) {
                if (trim($_REQUEST['field']) == "all") {
                    $field = is_numeric(trim($_REQUEST['search'])) ? 'name|origin|address|email|telephone|website|account_type|industry|annual_revenue|sic_code|ticker_symbol|ownership|rating|description' : 'name|origin|address|email|telephone|website|account_type|industry|annual_revenue|sic_code|ticker_symbol|ownership|rating|description|create_time|update_time';
                } else {
                    $field = trim($_REQUEST['field']);
                }
                $search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
                $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);

                if ('create_time' == $field || 'update_time' == $field) {
                    $search = is_numeric($search)?$search:strtotime($search);
                }
                if ($field == 'name'){
                    $search = preg_replace('/^|$|\s+/', '%', trim($search));
                }
                switch ($condition) {
                    case "is" : $where[$field] = array('eq',$search);break;
                    case "isnot" :  $where[$field] = array('neq',$search);break;
                    case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
                    case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
                    case "start_with" :  $where[$field] = array('like',$search.'%');break;
                    case "end_with" :  $where[$field] = array('like','%'.$search);break;
                    case "is_empty" :  $where[$field] = array('eq','');break;
                    case "is_not_empty" :  $where[$field] = array('neq','');break;
                    case "gt" :  $where[$field] = array('gt',$search);break;
                    case "egt" :  $where[$field] = array('egt',$search);break;
                    case "lt" :  $where[$field] = array('lt',$search);break;
                    case "elt" :  $where[$field] = array('elt',$search);break;
                    case "eq" : $where[$field] = array('eq',$search);break;
                    case "neq" : $where[$field] = array('neq',$search);break;
                    case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
                    case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
                    case "tgt" :  $where[$field] = array('gt',$search+86400);break;
                    default : $where[$field] = array('eq',$search);
                }
                $params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
            }
            $p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
            $list = $m_customer->where($where)->order('create_time desc')->page($p.','.$pages)->select();
            foreach($list as $k => $v){
                $contacts_res = M('contacts')->where('contacts_id = %d',$v['contacts_id'])->find();
                $list[$k]['contacts_name'] = $contacts_res['name'];
                $list[$k]['contacts_telephone'] = $contacts_res['telephone'];

                //添加版本信息
                $receivablesData = M('receivables')->where('customer_id = %d', $v['customer_id'])->find();
                $list[$k]['product_name'] = $receivablesData['name'];

                //二次开发修改增加创建人信息
                $list[$k]['create'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                $user_map['name'] = $v['crm_iuvacq'];
                $user_res = $user->where($user_map)->field('name,user_id')->find();
                if($user_res){
                    $list[$k]['owner']['user_name'] = $user_res['name'];
                    $list[$k]['owner']['user_id'] = $user_res['user_id'];
                }else{
                    $config_map['name'] = 'default_user_id';
                    $config_res = $config->where($config_map)->find();
                    $user_map2['user_id'] = $config_res['value'];
                    $user_res = $user->where($user_map2)->field('name,user_id')->find();
                    $list[$k]['owner']['user_name'] = $user_res['name'];
                    $list[$k]['owner']['user_id'] = $user_res['user_id'];
                }
            }
            $count = $m_customer->where($where)->count();
            $data['list'] = $list;
            $data['p'] = $p;
            $data['count'] = $count;
            $data['total'] = $count%$pages > 0 ? ceil($count/$pages) : $count/$pages;

            $this->ajaxReturn($data,"",1);
        }
    }
   //新建客户
    public function add(){
        if($this->isPost()){
            $m_customer = D('Customer');
            $m_customer_data = D('CustomerData');
            $field_list = M('Fields')->where('model = "customer" and in_add = 1')->order('order_id')->select();
            foreach ($field_list as $v){
                switch($v['form_type']) {
                    case 'address':
                        $a = $_POST[$v['field']];
                        //chr /n
                        $_POST[$v['field']] = !empty($a) ? implode(chr(10),$a) : '';
                    break;
                    case 'datetime':
                        $_POST[$v['field']] = strtotime($_POST[$v['field']]);
                    break;
                    case 'box':
                        eval('$field_type = '.$v['setting'].';');
                        if($field_type['type'] == 'checkbox'){
                            $b = array_filter($_POST[$v['field']]);
                            $_POST[$v['field']] = !empty($b) ? implode(chr(10),$b) : '';
                        }
                    break;
                }
            }
            if($m_customer->create()){
                if($m_customer_data->create()!==false){
                    if($_POST['con_name']){
                        $contacts = array();
                        if($_POST['con_name']) $contacts['name'] = $_POST['con_name'];
                        if($_POST['owner_role_id']) $contacts['owner_role_id'] = $_POST['owner_role_id'];
                        if($_POST['saltname']) $contacts['saltname'] = $_POST['saltname'];
                        if($_POST['con_email']) $contacts['email'] = $_POST['con_email'];
                        if($_POST['con_post']) $contacts['post'] = $_POST['con_post'];
                        if($_POST['con_qq']) $contacts['qq_no'] = $_POST['con_qq'];
                        if($_POST['con_telephone']) $contacts['telephone'] = $_POST['con_telephone'];
                        if($_POST['con_description']) $contacts['description'] = $_POST['con_description'];
                        if(!empty($contacts)){
                            $contacts['creator_role_id'] = session('role_id');
                            $contacts['create_time'] = time();
                            $contacts['update_time'] = time();
                            if(!$contacts_id = M('Contacts')->add($contacts)){
                                alert('error', L('ADD_THE_PRIMARY_CONTACT_FAILURE'), U('customer/add'));
                            }
                        }
                    }
                    $m_customer->create_time = time();
                    $m_customer->update_time = time();
                    if($contacts_id) $m_customer->contacts_id = $contacts_id;
                    $m_customer->creator_role_id = session('role_id');
                    if(!$customer_id = $m_customer->add()){
                        alert('error', L('ADD_CUSTOMER_FAILS_CONTACT_ADMIN'), U('customer/add'));
                    }
                    $m_customer_data->customer_id = $customer_id;
                    $m_customer_data->add();

                    if ($_POST['leads_id']) {
                        $leads_id = intval($_POST['leads_id']);
                        $r_module = array(
                            array('key'=>'log_id','r1'=>'RCustomerLog','r2'=>'RLeadsLog'),
                            array('key'=>'file_id','r1'=>'RCustomerFile','r2'=>'RFileLeads'),
                            array('key'=>'event_id','r1'=>'RCustomerEvent','r2'=>'REventLeads'),
                            array('key'=>'task_id','r1'=>'RCustomerTask','r2'=>'RLeadsTask')
                        );

                        foreach ($r_module as $key=>$value) {
                            $key_id_array = M($value['r2'])->where('leads_id = %d', $leads_id)->getField($value['key'],true);
                            $r1 = M($value['r1']);
                            $data['customer_id'] = $customer_id;
                            foreach($key_id_array as $k=>$v){
                                $data[$value['key']] = $v;
                                $r1->add($data);
                            }
                        }
                        $leads_data['is_transformed'] = 1;
                        $leads_data['update_time'] = time();
                        $leads_data['customer_id'] = $customer_id;
                        $leads_data['contacts_id'] = $contacts_id;
                        $leads_data['transform_role_id'] = session('role_id');
                        M('Leads')->where('leads_id = %d', $leads_id)->save($leads_data);
                    }

                    //记录操作记录
                    actionLog($customer_id);
                    if ($contacts_id && $customer_id) {
                        $rcc['contacts_id'] = $contacts_id;
                        $rcc['customer_id'] = $customer_id;
                        M('RContactsCustomer')->add($rcc);
                    }
                    if(intval($_POST['create_business1']) == 1 || intval($_POST['create_business1']) == 1){
                        alert('success', L('ADD_CUSTOMER_SUCCESS'), U('business/add','customer_id='.$customer_id));
                    }else{
                        if($_POST['submit'] == L('SAVE')) {
                            if($_POST['refer_url'])
                            {
                                if(strstr($_POST['refer_url'],'view') || strstr($_POST['refer_url'],'add'))
                                {
                                   alert('success', L('ADD_CUSTOMER_SUCCESS'), U('customer/index'));
                                }
                                alert('success', L('ADD_CUSTOMER_SUCCESS'), $_POST['refer_url']);
                            }
                            else{
                               alert('success', L('ADD_CUSTOMER_SUCCESS'), U('customer/index'));
                            }

                        } else {
                            alert('success', L('ADD_CUSTOMER_SUCCESS'), U('customer/add'));
                        }
                    }
                }else{
                    $this->error($m_customer_data->getError());
                }
            }else{
                $this->error($m_customer->getError());
            }

        }else{
            if(intval($_GET['leads_id'])){
                $leads = D('LeadsView')->where('leads.leads_id = %d', intval($_GET['leads_id']))->find();
                $this->leads = $leads;
                $this->field_list = field_list_html("edit","customer",$leads);
            }else{
                $this->field_list = field_list_html("add","customer",$leads);
            }
            $this->refer_url=$_SERVER['HTTP_REFERER'];
            $alert = parseAlert();
            $this->alert = $alert;
            $this->display();
        }
    }

    public function delete(){
        $m_customer = M('Customer');
        if ($this->isPost()) {
            $customer_ids = is_array($_POST['customer_id']) ? implode(',', $_POST['customer_id']) : '';
            if ('' == $customer_ids) {
                alert('error', L('HAVE_NOT_CHOOSE_ANY_CONTENT'), $_SERVER['HTTP_REFERER']);
            } else {
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                if($m_customer->where('customer_id in (%s)', $customer_ids)->setField($data)){
                    //记录操作记录
                    foreach($_POST['customer_id'] as $customer_id){
                        actionLog($customer_id);
                    }
                    alert('success', L('DELETED_SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                } else {
                    alert('error', L('DELETE_FAILED_CONTACT_ADMIN'),$_SERVER['HTTP_REFERER']);
                }
            }
        } elseif($_GET['id']) {
            $customer = $m_customer->where('customer_id = %d', $_GET['id'])->find();
            if (is_array($customer)) {
                if($customer['owner_role_id'] == session('role_id') || session('?admin')){
                    $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                    if($m_customer->where('customer_id = %d', $_GET['id'])->setField($data)){
                        actionLog($_GET['id']);
                        //判断客户是否属于客户池
                        $outdays = M('config') -> where('name="customer_outdays"')->getField('value');
                        $outdate = empty($outdays) ? time() : time()-86400*$outdays;
                        if($customer['update_time'] < $outdate){
                            alert('success', L('DELETED_SUCCESSFULLY'),U('Customer/index','content=resource'));
                        }else{
                            alert('success', L('DELETED_SUCCESSFULLY'),U('Customer/index'));
                        }
                    }else{
                        alert('error', L('DELETE_FAILED_CONTACT_ADMIN') ,$_SERVER['HTTP_REFERER']);
                    }
                } else {
                    alert('error', L('HAVE_NOT_PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }

            } else {
                alert('error', L('RECORD_NOT_EXIST'), $_SERVER['HTTP_REFERER']);
            }
        } else {
            alert('error', L('PLEASE_SELECT_A_CLUE_TO_DELETE'),$_SERVER['HTTP_REFERER']);
        }
    }

    public function completeDelete() {
        $m_customer = M('Customer');
        $r_module = array('Log'=>'RCustomerLog', 'File'=>'RCustomerFile', 'Event'=>'RCustomerEvent', 'Task'=>'RCustomerTask', 'RContactsCustomer');
        if (!session('?admin')) {
            alert('error', L('HAVE_NO_RIGHT_TO_DELETE_OPERATION'), $_SERVER['HTTP_REFERER']);
        }
        if ($this->isPost()) {
            $customer_ids = is_array($_POST['customer_id']) ? implode(',', $_POST['customer_id']) : '';
            if ('' == $customer_ids) {
                alert('error', L('HAVE_NOT_CHOOSE_ANY_CONTENT'), $_SERVER['HTTP_REFERER']);
            } else {
                if (!session('?admin')) {
                    foreach($_POST['customer_id'] as $key => $value){
                        if(!$m_customer->where('owner_role_id = %d and customer_id = %d', session('role_id'), $value) -> find()){
                            alert('error', L('DO_NOT_HAVE_PERMISSION_TO_OPERATE_ALL'), $_SERVER['HTTP_REFERER']);
                        }else{
                            actionLog($value);
                        }
                    }
                }
                if($m_customer->where('customer_id in (%s)', $customer_ids)->delete()){
                    M('CustomerDate')->where('customer_id in (%s)', $customer_ids)->delete();
                    foreach ($_POST['customer_id'] as $value) {
                        foreach ($r_module as $key2=>$value2) {
                            $module_ids = M($value2)->where('customer_id = %d', $value)->getField($key2 . '_id', true);
                            M($value2)->where('customer_id = %d', $value) -> delete();
                            if(!is_int($key2)){
                                M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
                            }
                        }
                    }
                    alert('success', L('DELETED_SUCCESSFULLY'), U('Customer/index','by=deleted'));
                } else {
                    alert('error', L('DELETE_FAILED_CONTACT_ADMIN'), $_SERVER['HTTP_REFERER']);
                }
            }
        } elseif($_GET['id']) {
            $customer = $m_customer->where('customer_id = %d', $_GET['id'])->find();
            if (is_array($customer)) {
                if($customer['owner_role_id'] == session('role_id') || session('?admin')){
                    if($m_customer->where('customer_id = %d', $_GET['id'])->delete()){
                        actionLog($_GET['id']);
                        M('CustomerDate')->where('customer_id = %d', $_GET['id'])->delete();
                        foreach ($r_module as $key2=>$value2) {
                            $module_ids = M($value2)->where('customer_id = %d', $_GET['id'])->getField($key2 . '_id', true);
                            M($value2)->where('customer_id = %d', $_GET['id']) -> delete();
                            if(!is_int($key2)){
                                M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
                            }
                        }
                        alert('success', L('DELETED_SUCCESSFULLY'), U('Customer/index','by=deleted'));
                    }else{
                        alert('error', L('DELETE_FAILED_CONTACT_ADMIN'), $_SERVER['HTTP_REFERER']);
                    }
                } else {
                    alert('error', L('DO_NOT_HAVE_PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }

            } else {
                alert('error', L('RECORD_NOT_EXIST'), $_SERVER['HTTP_REFERER']);
            }
        } else {
            alert('error', L('PLEASE_CHOOSE_TO_DELETE_CLUES!'),$_SERVER['HTTP_REFERER']);
        }
    }
    //编辑客户
    public function edit(){
        if(!check_permission(intval($this->_request('id')), 'customer')) $this->error(L('HAVE NOT PRIVILEGES'));
        $customer = D('CustomerView')->where('customer.customer_id = %d',$this->_request('id'))->find();

        if (!$customer) {
            alert('error', L('CUSTOMER_DOES_NOT_EXIST!'),$_SERVER['HTTP_REFERER']);
        }
        $customer['owner'] = D('RoleView')->where('role.role_id = %d', $customer['owner_role_id'])->find();
        $customer['contacts_name'] = M('contacts')->where('contacts_id = %d', $customer['contacts_id'])->getField('name');

        $field_list = M('Fields')->where('model = "customer"')->order('order_id')->select();

        if($this->isPost()){
            $m_customer = D('Customer');
            $m_customer_data = D('CustomerData');
            foreach ($field_list as $v){
                switch($v['form_type']) {
                    case 'address':
                        $_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
                    break;
                    case 'datetime':
                        $_POST[$v['field']] = strtotime($_POST[$v['field']]);
                    break;
                    case 'box':
                        eval('$field_type = '.$v['setting'].';');
                        if($field_type['type'] == 'checkbox'){
                            $_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
                        }
                    break;
                }
            }
            if($customer_crete = $m_customer->create()){
                if(($customer_create_data = $m_customer_data->create()) !== false){
                    $m_customer->update_time = time();
                    $a = $m_customer->where('customer_id =%d ', $customer['customer_id'])->save($customer_crete);
                    if ($m_customer_data->where('customer_id =%d', $customer['customer_id'])->find()){
                        $b = $m_customer_data->where('customer_id =%d', $customer['customer_id'])->save($customer_create_data);
                    }else{
                        $b = $m_customer_data -> add();
                    }
                    if($a !== false && $b !== false){
                        if($_POST['contacts_id'] && ($_POST['contacts_id'] != $customer['contacts_id'])){
                            $rcc['contacts_id'] = intval($_POST['contacts_id']);
                            $rcc['customer_id'] = $customer['customer_id'];
                            if(!M('RContactsCustomer')->where($rcc)->find()){
                                M('RContactsCustomer')->add($rcc);
                            }
                        }
                        actionLog($customer['customer_id']);
                        alert('success', L('EDIT_CLIENTS_SUCCESS'), U('customer/index'));

                    }else{
                        alert('error', L('CUSTOMER_EDITING_FAILED!'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->error($m_customer_data->getError());
                }
            }else{
               $this->error($m_customer->getError());
            }
        }else{
            $alert = parseAlert();
            $this->alert = $alert;
            $this->customer = $customer;
            $this->field_list = field_list_html("edit","customer",$customer);
            $this->display();
        }

    }

    #客户视图栏目
    public function index(){

        $d_v_customer = D('CustomerView');
        $by = isset($_GET['by']) ? trim($_GET['by']) : '';
        $below_ids = getSubRoleId(false);
        $all_ids = getSubRoleId(true);
        $outdays = M('config') -> where('name="customer_outdays"')->getField('value');
        $outdate = empty($outdays) ? time() : time()-86400*$outdays;
        $where = array();
        $params = array();
        $order = "create_time desc";

        if(isset($_GET['desc_order'])){
            $order = trim($_GET['desc_order']).' desc';
        }elseif(isset($_GET['asc_order'])){
            $order = trim($_GET['asc_order']).' asc';
        }

        //查询关注
        $m_focus = M('customerFocus');
        $focus_id = $m_focus ->where('user_id =%d',session('role_id'))->getField('customer_id',true);
        //查询分享给我的
        $m_share =  M('customerShare');
        $sharing_id = session('role_id');
        $m_customer_share = $m_share ->select();
        foreach($m_customer_share as $k=>$v){
            $by_sharing_id = explode(',',$v['by_sharing_id']);
            if(in_array($sharing_id,$by_sharing_id)){
                $customerid[] = $v['customer_id'];
            }
        }
        //查询我分享的
        $share_customer_ids = $m_share ->where('share_role_id =%d',session('role_id'))->getField('customer_id',true);

        switch ($by) {
            case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
            case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d')) - (date('N', time()) - 1) * 86400)); break;
            case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
            case 'add' : $order = 'create_time desc'; break;
            case 'update' : $order = 'update_time desc'; break;
            case 'sub' : $where['owner_role_id'] = array('in',implode(',', $below_ids)); break;
            case 'deleted' : $where['is_deleted'] = 1;break;
            case 'me' : $where['owner_role_id'] = session('role_id'); break;
            case 'focus' : $where['customer_id'] = array('in',$focus_id);break;
            case 'share' : $where['customer_id'] = array('in',$customerid);break;
            case 'myshare' : $where['customer_id'] = array('in',$share_customer_ids);break;
            default :
                if($this->_get('content') == 'resource'){
                    $where['_string'] = "customer.owner_role_id=0 or customer.update_time < $outdate";
                    $all_ids[] = "";
                    //$where['owner_role_id'] = array('in', $all_ids);
                    $where['is_locked'] = 0;
                }else{
                    $where['customer.owner_role_id'] = array('in',implode(',', $all_ids));
                }
            break;
        }
        if ($by != 'deleted') {
            $where['is_deleted'] = array('neq',1);
        }
        if (!isset($where['owner_role_id']) && $by!='share' && $this->_get('content') != 'resource') {
            if($by != 'deleted'){
                $where['customer.owner_role_id'] = array('in', $all_ids);
            }
        }
        if($by == 'deleted') unset($where['update_time']);
        if($this->_get('content') != 'resource'){
            if($by != 'deleted'){
                $where['_string'] = 'customer.update_time > '.$outdate.' OR is_locked = 1';
            }
        }
        if (isset($_REQUEST["field"])) {
            $field = trim($_REQUEST['field']);
            $search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
            $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);

            if ($this->_request('state')){

                $state = $this->_request('state', 'trim');
                $address_where[] = '%'.$state.'%';

                if($this->_request('city')){
                    $city = $this->_request('city', 'trim');
                    $address_where[] = '%'.$city.'%';

                    if($this->_request('area')){
                        $area = $this->_request('area', 'trim');
                        $address_where[] = '%'.$this->_request('area', 'trim').'%';
                    }
                }

                if($search) $address_where[] = '%'.$search.'%';

                $params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));

                if($condition == 'not_contain'){
                    $where[$field] = array('notlike', $address_where, 'OR');
                }else{
                    $where[$field] = array('like', $address_where, 'AND');
                }
            }else{
                $field_date = M('Fields')->where('is_main=1 and (model="" or model="customer") and form_type="datetime"')->select();
                foreach($field_date as $v){
                    if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
                }
                $field = str_replace('->', '.', $field);
                if ($field == 'customer.name'){
                    $search = preg_replace('/^|$|\s+/', '%', trim($search));
                }
                if (in_array($field, ['customer.create_time','customer.update_time'])){
                    $where[$field] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', null, 'strtotime')+86399]];
                }else{
                    switch ($condition) {
                        case "is":
                            if ($search=='all'){
                                $department_id = I('get.department');
                                $roleList = getRoleByDepartmentId($department_id, true);//true表示部门所有人
                                foreach ($roleList as $role){
                                    $roleIds[] = $role['role_id'];
                                }
                                $where[$field] = array('in', $roleIds);
                            }else{
                                $where[$field] = array('eq',$search);
                            }
                            break;
                        case "isnot" :  $where[$field] = array('neq',$search);break;
                        case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
                        case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
                        case "start_with" :  $where[$field] = array('like',$search.'%');break;
                        case "not_start_with" :  $where[$field] = array('notlike',$search.'%');break;
                        case "end_with" :  $where[$field] = array('like','%'.$search);break;
                        case "is_empty" :  $where[$field] = array('eq','');break;
                        case "is_not_empty" :  $where[$field] = array('neq','');break;
                        case "gt" :  $where[$field] = array('gt',$search);break;
                        case "egt" :  $where[$field] = array('egt',$search);break;
                        case "lt" :  $where[$field] = array('lt',$search);break;
                        case "elt" :  $where[$field] = array('elt',$search);break;
                        case "eq" : $where[$field] = array('eq',$search);break;
                        case "neq" : $where[$field] = array('neq',$search);break;
                        case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
                        case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
                        case "tgt" :  $where[$field] = array('gt',$search+86400);break;
                        default : $where[$field] = array('eq',$search);
                    }
                }

//                $params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$search);
                $params = I('get.');
                unset($params['p']);
            }
        }

        if(trim($_GET['act'] == 'sms')){
            $customer_ids = $d_v_customer->where($where)->getField('customer_id', true);
            $contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', implode(',', $customer_ids))->getField('contacts_id', true);
            $contacts_ids = implode(',', $contacts_ids);
            $contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
            $this->contacts = $contacts;
            $this->display('Setting:sendsms');
        }elseif(trim($_GET['act']) == 'excel'){
            if(vali_permission('customer', 'export')){
                $dc_id = $_GET['daochu'];
                if($dc_id !=''){
                    $where['customer_id'] = array('in',$dc_id);
                }
                $current_page = intval($_GET['current_page']);
                $export_limit = intval($_GET['export_limit']);
                $limit = ($export_limit*($current_page-1)).','.$export_limit;
                $customerList = $d_v_customer->where($where)->order($order)->limit($limit)->select();
                session('export_status', 1);
                $this->excelExport($customerList);
            }else{
                alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
            if($_GET['listrows']){
                $listrows = intval($_GET['listrows']);
                $params['listrows'] =  $listrows;
                cookie('list_rows', $listrows);
            }else{
                $listrows = cookie('list_rows')?cookie('list_rows'):15;
                $params['listrows'] = $listrows;
            }

            $list = $d_v_customer
                ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->where($where)
                ->order($order)
                ->field(
                    'customer.name AS name,
                    customer.origin AS origin,
                    customer.address AS address,
                    customer.industry AS industry,
                    customer.customer_id AS customer_id,
                    customer.owner_role_id AS owner_role_id,
                    customer.creator_role_id AS creator_role_id,
                    customer.contacts_id AS contacts_id,
                    customer.delete_role_id AS delete_role_id,
                    customer.create_time AS create_time,
                    customer.delete_time AS delete_time,
                    customer.update_time AS update_time,
                    contacts.name as contacts_name,
                    contacts.telephone as contacts_telephone')
                ->page($p.','.$listrows)
                ->select();
            $count = $d_v_customer->where($where)->count();
            import("@.ORG.Page");
            $Page = new Page($count,$listrows);
            if (!empty($_GET['by'])) {
                $params['by'] = trim($_GET['by']);
            }
            if (!empty($_GET['content'])) {
                $params['content'] =  trim($_GET['content']);
            }
//            $this->parameter = implode('&', $params);
            $this->parameter =  $params;

//            if ($_GET['desc_order']) {
//                $params[] = "desc_order=" . trim($_GET['desc_order']);
//            } elseif($_GET['asc_order']){
//                $params[] = "asc_order=" . trim($_GET['asc_order']);
//            }
            $Page->parameter = $params;
            $this->assign('page',$Page->show());

            if($by == 'deleted') {
                foreach ($list as $k => $v) {
                    $list[$k]["delete_role"] = D('RoleView')->where('role.role_id = %d', $v['delete_role_id'])->find();
                    $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                    $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                }
            } else {
                $search = '/'.preg_replace('/%/', '|', trim($search, '%')).'/';
                foreach ($list as $k => $v) {
                    $days = 0;
                    $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                    $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                    $days =  M('Customer')->where('customer_id = %d', $v['customer_id'])->getField('update_time');
                    $list[$k]["days"] = $outdays-floor((time()-$days)/86400);
                    if ($field == 'customer.name' && $search){

                        $list[$k]['name'] = preg_replace($search, '<span style="color:red">\\0</span>', $v['name']);
                    }
                }
            }

            $this->listrows = $listrows;
            $this->customerlist = $list;
            $this->assign('count',$count);
            $field_array = getIndexFields('customer');
            $field_array = array_merge($field_array, [[
                'field' => 'contacts_name',
                'name' => '联系人姓名',
            ],[
                'field' => 'contacts_telephone',
                'name' => '联系人电话',
            ]]);

            $this->assign('field_array', $field_array);

            $field_list = getMainFields('customer');
            /* 暂时写死 */
            $field_list = array_merge($field_list, [[
                'field' => 'name',
                'name' => '联系人姓名',
                'model' => 'contacts'
            ],[
                'field' => 'telephone',
                'name' => '联系人电话',
                'model' => 'contacts'
            ]]);

            $this->assign('field_list', $field_list);
            $this->alert = parseAlert();
            $this->display();
        }
    }

    /* 订单客户 */
    #客户首页面显示
    public function ordered(){
        $d_v_customer = D('CustomerView');
        $by = isset($_GET['by']) ? trim($_GET['by']) : '';
        $below_ids = getSubRoleId(false);
        $where = ['customer.is_deleted' => 0];
         //定义一个参数
        $params = [];
        $order = 'first_order_time desc';
          //判断升序和降序
          //升序
        if($_GET['desc_order']){
            $order = trim($_GET['desc_order']).' desc';
         //降序
        }elseif($_GET['asc_order']){
            $order = trim($_GET['asc_order']).' asc';
        }

        //查询关注
        $m_focus = M('customerFocus');
        //获得顾客的id
        $focus_id = $m_focus ->where('user_id =%d',session('role_id'))->getField('customer_id',true);
         //判断写入where条件
        switch ($by) {
           //今日新建
            case 'today': $where['customer.create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
            //本周新建
            case 'week': $where['customer.create_time'] =  array('gt',(strtotime(date('Y-m-d')) - (date('N', time()) - 1) * 86400)); break;
            //本月新建
            case 'month': $where['customer.create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
            case 'add': $order = 'customer.create_time desc'; break;
            case 'update': $order = 'customer.update_time desc'; break;
            //下属客户
            case 'sub': $where['customer.owner_role_id'] = array('in',implode(',', $below_ids)); break;
            //我的客户
            case 'me': $where['customer.owner_role_id'] = session('role_id'); break;
            //我关关注的
            case 'focus': $where['customer.customer_id'] = array('in',$focus_id);break;
        }
          //获得查询条件
        $search = I('get.search', '', 'trim');
        //国家地区
        if ($field = trim($_REQUEST["field"])) {
            $field = str_replace('->', '.', $field);
             //判断是否有条件
            $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
             //国家
            if ($this->_request('state')){
                $state = $this->_request('state', 'trim');
                $address_where[] = '%'.$state.'%';
                   //城市
                if($this->_request('city')){
                    $city = $this->_request('city', 'trim');
                    $address_where[] = '%'.$city.'%';
                   //地区
                    if($this->_request('area')){
                        $area = $this->_request('area', 'trim');
                        $address_where[] = '%'.$area.'%';
                    }
                }
                     //角色的id
                if($search) $address_where[] = '%'.$search.'%';
                         //??
                $params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));
                  //没有条件的情况下
                if($condition == 'not_contain'){
                    //不是本条件的数据
                    $where[$field] = array('notlike', $address_where, 'OR');
                }else{
                    //是本条件的数据
                    $where[$field] = array('like', $address_where, 'AND');
                }
            }else{
                  //查询根据日期和模型查询
                $field_date = M('Fields')->where('is_main=1 and (model="" or model="customer") and form_type="datetime"')->select();
                //  dump($field_date);exit;
                foreach($field_date as $v){
                    if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
                }
                $field = str_replace('->', '.', $field);

                if ($field == 'customer.name'){
                    $search = preg_replace('/^|$|\s+/', '%', trim($search));
                }
                if (in_array($field, ['customer.create_time','customer.update_time'])){
                    $where[$field] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', null, 'strtotime')+86399]];
                } else {


                    switch ($condition) {
                        case "is":
                            if ($search=='all'){
                                $department_id = I('get.department');
                                $roleList = getRoleByDepartmentId($department_id, true);//true表示部门所有人
                                foreach ($roleList as $role){
                                    $roleIds[] = $role['role_id'];
                                }
                                $where[$field] = array('in', $roleIds);
                            }else{
                                $where[$field] = array('eq',$search);
                            }
                            break;
                        //包含
                        case "isnot":  $where[$field] = array('neq',$search);break;
                        case "contains":  $where[$field] = array('like','%'.$search.'%');break;
                        case "not_contain":  $where[$field] = array('notlike','%'.$search.'%');break;
                        case "start_with":  $where[$field] = array('like',$search.'%');break;
                        case "not_start_with":  $where[$field] = array('notlike',$search.'%');break;
                        case "end_with":  $where[$field] = array('like','%'.$search);break;
                        case "is_empty":  $where[$field] = array('eq','');break;
                        case "is_not_empty":  $where[$field] = array('neq','');break;
                        case "gt":  $where[$field] = array('gt',$search);break;
                        case "egt":  $where[$field] = array('egt',$search);break;
                        case "lt":  $where[$field] = array('lt',$search);break;
                        case "elt":  $where[$field] = array('elt',$search);break;
                        case "eq": $where[$field] = array('eq',$search);break;
                        case "neq": $where[$field] = array('neq',$search);break;
                        case "between": $where[$field] = array('between',array($search-1,$search+86400));break;
                        case "nbetween": $where[$field] = array('not between',array($search,$search+86399));break;
                        case "tgt":  $where[$field] = array('gt',$search+86400);break;
                        default: $where[$field] = array('eq',$search);
                    }
                }
                $params = $_GET;//array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.trim($_REQUEST["search"]));

                unset($params['p']);
            }
        }
        // $order = empty($order) ? 'customer.create_time desc' : $order;

        if(trim($_GET['act'] == 'sms')){
            $customer_ids = $d_v_customer->where($where)->getField('customer_id', true);
            $contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', implode(',', $customer_ids))->getField('contacts_id', true);
            $contacts_ids = implode(',', $contacts_ids);
            $contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
            $this->contacts = $contacts;
            $this->display('Setting:sendsms');
        }elseif(trim($_GET['act']) == 'excel'){
            if(vali_permission('customer', 'export')){
                $dc_id = $_GET['daochu'];
                if($dc_id !=''){
                    $where['customer_id'] = array('in',$dc_id);
                }
                $current_page = intval($_GET['current_page']);
                $export_limit = intval($_GET['export_limit']);
                $limit = ($export_limit*($current_page-1)).','.$export_limit;
                $customerList = $d_v_customer->where($where)->order($order)->limit($limit)->select();
                session('export_status', 1);
                $this->excelExport($customerList);
            }else{
                alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
            if($_GET['listrows']){
                $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');
                cookie('list_rows', $listrows);
            }else{
                $listrows = cookie('list_rows')?cookie('list_rows'):15;
                $params['listrows'] = $listrows;
            }
            $query_sql = M('customer')->alias('customer')
                ->join('right join '.C('DB_PREFIX').'receivables receivables on receivables.customer_id=customer.customer_id and receivables.is_deleted = 0')
                ->join('LEFT join '.C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->field(
                    'customer.name AS name,
                     customer.customer_id AS customer_id,
                     customer.owner_role_id AS owner_role_id,
                     customer.creator_role_id AS creator_role_id,
                     customer.contacts_id AS contacts_id,
                     customer.create_time AS create_time,
                     customer.update_time as update_time,
                     contacts.name as contacts_name,
                     contacts.telephone as contacts_telephone,
                     receivables.create_time as first_order_time')
                ->where($where)
                ->buildSql();
            $list =  M()->table("$query_sql t") -> page($p.','.$listrows)->order($order)->group('customer_id')->select();
            $count = M()->table("$query_sql t") ->field('count(distinct(customer_id)) as count') -> find()['count'];
            $customer_id_list = [];
            foreach($list as $customer){
                $customer_id_list[] = $customer['customer_id'];
            }

            $order_receiving_info = M('receivables')->alias('receivables')
            ->join(C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id = receivables.receivables_id and receiving.is_deleted = 0')
            ->where(['receivables.customer_id' => ['in', $customer_id_list], 'receivables.is_deleted' => 0])
            ->field(
                'receivables.customer_id,
                 receivables.receivables_id as order_id,
                 receivables.order_number,
                 receivables.name,
                 receivables.price,
                 IFNULL(sum(receiving.money), 0) as total_received')
            ->group('receivables.receivables_id')
            ->buildSql();

            $customer_order_receiving_info = M()->table("$order_receiving_info t")
            ->group('customer_id')
            ->field(
                'customer_id,
                 sum(price) as total_receiables,
                 sum(total_received) as total_received'
            )->select();

            $result = array_column($customer_order_receiving_info, null, 'customer_id');

            foreach($list as $key => $customer){
                $list[$key] = array_merge($customer, $result[$customer['customer_id']]);
                /* 计算欠款 */
                $list[$key]['total_arrearage'] = floatval($list[$key]['total_receiables']) - floatval($list[$key]['total_received']);
                /* 客户名处理 */
                if(empty($list[$key]['name'])){
                    $list[$key]['name'] = L('UNKNOWN');
                }
            }

            import("@.ORG.Page");
            $Page = new Page($count,$listrows);
            if (!empty($_GET['by'])) {
//                $params[] = "by=" . trim($_GET['by']);
                $params['by'] = trim($_GET['by']);
            }
            if (!empty($_GET['content'])) {
//                $params[] = "content=" . trim($_GET['content']);
                $params['content'] = trim($_GET['content']);
            }
//            $this->parameter = implode('&', $params);
            unset($params['asc_order'], $params['desc_order']);
            $Page->parameter  = http_build_query($params);
            $this->parameter = http_build_query($params);

//            if ($_GET['desc_order']) {
//                $params[] = "desc_order=" . trim($_GET['desc_order']);
//            } elseif($_GET['asc_order']){
//                $params[] = "asc_order=" . trim($_GET['asc_order']);
//            }
//            $Page->parameter = implode('&', $params);
            $this->assign('page',$Page->show());
            $search = '/'.preg_replace('/%/', '|', trim($search, '%')).'/';
            foreach ($list as $k => $v) {
                $list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                $list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                if ($field == 'customer.name' && $search){

                    $list[$k]['name'] = preg_replace($search, '<span style="color:red">\\0</span>', $v['name']);
                }
                
            }

            $this->listrows = $listrows;
            $this->customerlist = $list;
            $this->assign('count',$count);

            $field_array = getIndexFields('customer');
            /* 暂时写死 */
            $field_array = array_merge($field_array, [[
                    'field' => 'contacts_name',
                    'name' => '联系人姓名'
                ], [
                    'field' => 'contacts_telephone',
                    'name' => '联系人电话'
                ], [
                    'field' => 'total_receiables',
                    'name' => '总消费'
                ], [
                    'field' => 'total_arrearage',
                    'name' => '总欠款'
                ], [
                    'field' => 'first_order_time',
                    'name' => '首次下单时间',
                    'form_type' => 'datetime'
            ]]);

            $this->assign('field_array', $field_array);

            $field_list = getMainFields('customer');
            /* 暂时写死 */
            $field_list = array_merge($field_list, [[
                'field' => 'name',
                'name' => '联系人姓名',
                'model' => 'contacts',
                'form_type' =>'text'
            ],[
                'field' => 'telephone',
                'name' => '联系人电话',
                'model' => 'contacts',
                'form_type' => 'mobile'
            ]]);

            $this->assign('field_list', $field_list);

            $this->alert = parseAlert();
            $this->display();
        }
    }

    public function listDialog(){
        $m_customer = M('Customer');
        $m_contacts = M('Contacts');
        $m_r_contacts_customer = M('RContactsCustomer');
        $pages = 12;
        $underling_ids = getSubRoleId();
        $business_id = intval($_GET['bid']);
        if(!empty($business_id)){
            $customer_id = M('business')->where('business_id = %d',$business_id)->getField('customer_id');
            $customer = $m_customer->where('customer_id = %d and is_deleted = 0',$customer_id)->order('create_time desc')->limit($pages)->select();
        }else{
            $customer = $m_customer->where('is_deleted = 0')->order('create_time desc')->limit($pages)->select();
        }
        foreach($customer as $k=>$v){
            //如果存在首要联系人，则查出首要联系人。否则查出联系人中第一个。
            if(!empty($v['contacts_id'])){
                $contacts = $m_contacts->where('is_deleted = 0 and contacts_id = %d',$v['contacts_id'])->find();
                $customer[$k]['contacts_name'] = $contacts['name'];
                $customer[$k]['contacts_telephone'] = $contacts['telephone'];
            }else{
                $contacts_customer = $m_r_contacts_customer->where('customer_id = %d',$v['customer_id'])->limit(1)->order('id desc')->select();
                $contacts = $m_contacts->where('is_deleted = 0 and contacts_id = %d',$contacts_customer[0]['contacts_id'])->find();
                $customer[$k]['contacts_id'] = $contacts['contacts_id'];
                $customer[$k]['contacts_name'] = $contacts['name'];
                $customer[$k]['contacts_telephone'] = $contacts['telephone'];
            }
            //添加上门人信息以及负责人信息
            $customer[$k]['create'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
            $onSiteService = M('Onsiteservice');
            $onSiteServiceData = $onSiteService->where('customer_id = %d', $v['customer_id'])->order('addtime desc')->find();
            if (!empty($onSiteServiceData['operator_name'])){
                $customer[$k]['owner']['user_name'] = $onSiteServiceData['operator_name'];
                $customer[$k]['owner']['user_id'] = $onSiteServiceData['tid'];
            }else{
                $onSiteServiceData = $onSiteService->where('customer_id = %d', $v['customer_id'])->order('addtime desc')->limit(1,1)->select();
                $customer[$k]['owner']['user_name'] = $onSiteServiceData[0]['operator_name'];
                $customer[$k]['owner']['user_id'] = $onSiteServiceData[0]['tid'];
            }
            //添加版本信息
            $receivables = M('receivables');
            $receivablesData = $receivables->where('customer_id = %d', $v['customer_id'])->find();
            $customer[$k]['product_name'] = $receivablesData['name'];
        }

        $this->customerList = $customer;
        $count = $m_customer->where('is_deleted = 0')->count();
        $this->total = $count%$pages > 0 ? ceil($count/$pages) : $count/$pages;
        $data = getIndexFields('customer');
        $this->count_num = $count;
        $this->field_num = sizeof($data)+1;
        $this->field_array = $data;
        $this->display();
    }

   #这是客户的基本信息页
    public function view(){
        //获得传过来客户的id进行3元运算
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        //没有进行跳转到本页面
        if (0 == $customer_id) {
            alert('error', L('parameter_error'), U('customer/index'));
        } else {
            //检查是否有权限
            if(!check_permission($customer_id, 'customer', 'by_sharing_id')){
                $this->error(L('HAVE NOT PRIVILEGES'));
            }
            //查询客户数据
            $customer = D('CustomerView')->where('customer.customer_id = %d', $customer_id)->find();
            //dump($customer);exit;
            //取得字段列表 ， 获得customer列表
            $field_list = M('Fields')->where('model = "customer"')->order('order_id')->select();

            //查询固定信息
            $customer['owner'] = D('RoleView')->where('role.role_id = %d', $customer['owner_role_id'])->find();
            $customer['create'] = D('RoleView')->where('role.role_id = %d', $customer['creator_role_id'])->find();
            if($customer['contacts_id']) $customer['contacts_name'] = M('contacts')->where('contacts_id = %d', $customer['contacts_id'])->getField('name');

            if($customer['is_deleted'] == 1){
                $customer['deleted'] = D('RoleView')->where('role.role_id = %d', $customer['delete_role_id'])->find();
            }

            //合并客户、联系人附件
            $customer_file_ids = M('rCustomerFile')->where('customer_id = %d', $customer_id)->getField('file_id', true);
            $customer_file_ids = $customer_file_ids ? $customer_file_ids : array();
            $contacts_file_ids = M('rContactsFile')->where('contacts_id = %d', $customer['contacts_id'])->getField('file_id', true);
            $contacts_file_ids = $contacts_file_ids ? $contacts_file_ids : array();
            $customer['file'] = M('file')->where('file_id in (%s)',  implode(',', array_merge($customer_file_ids,$contacts_file_ids)))->select();
            $file_count = 0;
            foreach ($customer['file'] as $key=>$value) {
                $customer['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
                $file_count ++;
            }
            $customer['file_count'] = $file_count;

            $task_ids = M('rCustomerTask')->where('customer_id = %d', $customer_id)->getField('task_id', true);
            $customer['task'] = M('task')->where('task_id in (%s) and is_deleted=0', implode(',', $task_ids))->select();
            $task_count = 0;
            foreach ($customer['task'] as $key=>$value) {
                $customer['task'][$key]['owner'] = D('RoleView')->where('role.role_id in (%s)', '0'.$value['owner_role_id'].'0')->select();
                $customer['task'][$key]['about_roles'] = D('RoleView')->where('role.role_id in (%s)', '0'.$value['about_roles'].'0')->select();
                $task_count ++;
            }
            $customer['task_count'] = $task_count;

            $event_ids = M('rCustomerEvent')->where('customer_id = %d', $customer_id)->getField('event_id', true);
            $customer['event'] = M('event')->where('event_id in (%s) and is_deleted=0', implode(',', $event_ids))->select();
            $event_count = 0;
            foreach ($customer['event'] as $key=>$value) {
                $customer['event'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['owner_role_id'])->find();
                $event_count ++;
            }
            $customer['event_count'] = $event_count;

            $customer['business'] = M('business')->where('customer_id = %d and is_deleted=0', $customer['customer_id'])->select();
            $customer['business_count'] = sizeof($customer['business']);
            foreach($customer['business'] as $k=>$v){
                $customer['business'][$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                $customer['business'][$k]['status'] = M('BusinessStatus')->where('status_id = %d', $v['status_id'])->getField('name');
                $business_id[] = $v['business_id'];
            }

            $customer_extra_info = M('customer_extra')->where(['customer_id' => $customer_id])->field('extra_key, extra_value')->select();

            $this->assign('customer_extra_info', $customer_extra_info);
            //合并客户、商机、联系人沟通日志
            $customer_log_ids = M('rCustomerLog')->where('customer_id = %d', $customer_id)->getField('log_id', true);
            $customer_log_ids = $customer_log_ids ? $customer_log_ids : array();
            //商机日志
            $business_log_ids = M('rBusinessLog')->where('business_id in (%s)', implode(',', $business_id))->getField('log_id', true);
            $business_log_ids = $business_log_ids ? $business_log_ids : array();
            //联系人日志
            $contacts_log_ids = M('rContactsLog')->where('contacts_id = %d', $customer['contacts_id'])->getField('log_id', true);
            $contacts_log_ids = $contacts_log_ids ? $contacts_log_ids : array();
            $customer['log'] = M('log')->where('log_id in (%s)', implode(',', array_merge($customer_log_ids,$business_log_ids,$contacts_log_ids)))->select();
            $log_count = 0;
            foreach ($customer['log'] as $key=>$value) {
                $customer['log'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
                $log_count ++;
            }
            $customer['log_count'] = $log_count;

            $customer['receivables'] = D('ReceivablesView')->where('receivables.customer_id = %d and receivables.is_deleted=0', $customer['customer_id'])->select();
            $customer['receivables_count'] = $customer['receivables'] ? sizeof($customer['receivables']):0;
            foreach($customer['receivables'] as $k=>$v){
                $customer['receivables'][$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
            }

            $customer['payables'] = D('PayablesView')->where('payables.customer_id = %d and payables.is_deleted=0', $customer['customer_id'])->select();
            $customer['payables_count'] = $customer['payables'] ? sizeof($customer['payables']):0;
            foreach($customer['payables'] as $k=>$v){
                $customer['payables'][$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
            }
              //客户关怀
            $customer['cares'] = D('CaresView')->where('customer_cares.customer_id = %d', $customer['customer_id'])->select();
            $customer['cares_count'] = sizeof($customer['cares']);

            $customer['contract'] = D('ContractView')->where('contract.business_id in (%s) and contract.is_deleted=0', implode(',', $business_id))->select();

            $customer['contract_count'] = $customer['contract'] ? sizeof($customer['contract']):0;
            foreach($customer['contract'] as $k=>$v){
                $customer['contract'][$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
            }

            // $customer['product'] = D('BusinessProductView')->where('r_business_product.business_id in (%s)', implode(',', $business_id))->select();
            // echo M()->getLastSql();
            // echo implode(',', $business_id);exit;
            // $customer['product_count'] = $customer['product'] ? sizeof($customer['product']) : 0;

            $customer['product'] = M('r_order_product')->alias('order_products')
            ->join('inner join '.C('DB_PREFIX').'receivables receivables on receivables.receivables_id = order_products.order_id and receivables.is_deleted = 0')
            ->where(['order_products.customer_id' => $customer_id, 'order_products.is_deleted' => 0])
            ->field('
                order_products.id as order_product_id,
                order_products.product_id as product_id,
                order_products.product_name as name,
                order_products.price as unit_price,
                receivables.price as order_price,
                order_products.amount as amount
            ')
            ->select();

            $customer['product_count'] = count($customer['product']);

            /* 查询订单产品的扩展信息 */
            $order_product_extra_info_item_list = M('r_order_product_extra')->alias('order_product_extra')
            ->where(['order_product_id' => ['in', implode(',', array_column($customer['product'], 'order_product_id'))]])
            ->field('order_product_id, extra_key, extra_value')
            ->select();

            /* 将扩展信息项合并成扩展信息 */
            $order_product_extra_info_list = [];
            foreach($order_product_extra_info_item_list as $order_product_extra_info){
                $extra_value = $order_product_extra_info['extra_value'];
                // if ($order_product_extra_info['extra_key'] == 'next_service_fee_date'){
                //     $extra_value = date('Y-m-d', $extra_value);
                // }
                $order_product_extra_info_list[$order_product_extra_info['order_product_id']][$order_product_extra_info['extra_key']] = $extra_value;
            }

            foreach($customer['product'] as $key => $order_product){
                $customer['product'][$key]['extra_info'] = $order_product_extra_info_list[$order_product['order_product_id']];
                $customer['product'][$key]['subtotal'] = $order_product['unit_price'] * $order_product['amount'];
            }
            /* 原系统内的上门数据，临时显示 */
            $door_service_person_list = M('order_service')->alias('order_service')
            ->join(C('DB_PREFIX').'user user on order_service.owner_role_id = user.role_id')
            ->where(['order_service.customer_id' => $customer_id])
            ->getField('name', true);

            $this->assign('door_service_person_list', $door_service_person_list);

            $contacts_ids = M('rContactsCustomer')->where('customer_id = %d', $customer_id)->getField('contacts_id', true);
            $customer['contacts'] = M('contacts')->where('contacts_id in (%s) and is_deleted=0', implode(',', $contacts_ids))->select();
            foreach($customer['contacts'] as $k=>$v){
                if(M('Customer')->where('contacts_id = %d',$v['contacts_id'])->select()){
                    $customer['contacts'][$k]['is_firstContact'] = 'true';
                }else{
                    $customer['contacts'][$k]['is_firstContact'] = 'false';
                }
            }

            $contacts_count = M('contacts')->where('contacts_id in (%s) and is_deleted=0', implode(',', $contacts_ids))->count();
            $customer['contacts_count'] = empty($contacts_count)?0:$contacts_count;
            $customer_len = strlen($customer['name']);

            //获取影楼远程服务记录(二次开发新增)
            $service = M('service');
            $service_map['customer_id'] = $customer['customer_id'];
            $service_res = $service->where($service_map)->order('remote_time desc')->select();
            $customer['remote_service_count'] = count($service_res);

            //获取远程类型字段选项
            $field_res = $this->getFieldInfo('远程类型');
            if($field_res->meta->code == 200){
                if($field_res->body['form_type'] == 'box'){
                    $this->assign('remote_type',$field_res->body['field']);
                }
            }

            //获取各操作员工数据
            $user = M('user');
            foreach($service_res as $k=>$v){
                $query = $v['fault_subpeople_id'].','.$v['sale_people_id'].','.$v['service_personal_id'].','.$v['teacher_id'].','.$v['visit_user_id'];
                $user_map['user_id'] = array('in',$query);
                $user_res = $user->where($user_map)->field('name,user_id')->cache(true,3600)->select();
                $service_res[$k]['fault_subpeople_name'] = $user_res[findId($user_res,$v['fault_subpeople_id'])]['name'];
                $service_res[$k]['service_personal_name'] = $user_res[findId($user_res,$v['service_personal_id'])]['name'];
                $service_res[$k]['sale_people_name'] = $user_res[findId($user_res,$v['sale_people_id'])]['name'];
                if($v['visit_user_id'] != ''){
                    $service_res[$k]['visit_user_name'] = $user_res[findId($user_res,$v['visit_user_id'])]['name'];
                }
            }
            //上门
            $OnSiteService = M('Onsiteservice');
            $OnSiteService_map['customer_id'] = $customer['customer_id'];
            $OnSiteService_res = $OnSiteService -> where($OnSiteService_map) -> select();
            $customer['door_service_count'] = count($OnSiteService_res);

            // 网络培训服务
            $netTrainService = M('net_train_service');
            $netTrainService_map['net_train_service.customer_id'] = $customer['customer_id'];
            $netTrainService_res = $netTrainService->alias('net_train_service')
                ->field('order_product.product_name,net_train_service.*')
                ->join(C('DB_PREFIX').'r_order_product order_product ON net_train_service.order_product_id = order_product.id','LEFT')
                -> where($netTrainService_map) -> select();
            $customer['net_train_service_count'] = count($netTrainService_res);
            //快递模块数据
            $express = M('express')->where('customer_id=%d', $customer_id)->select();
            $extra_value = M('r_order_product')
                ->where('customer_id=%d', $customer_id)
                ->field('id,product_name')
                 ->select();
            foreach ($extra_value as $key=>$value){
                $extraInfo[$key] = M('r_order_product_extra')->where('(extra_key="express_number" or extra_key="express_company") AND order_product_id=%d', $value['id'])->getField('extra_key,extra_value');
                if ($extraInfo[$key])
                    $extraInfo[$key]['content'] = $value['product_name'];
            }
            $extraInfo = array_filter($extraInfo);
            $express = array_merge($express, $extraInfo);
            $this->assign('express', $express);
            $this ->customer_len =$customer_len;
            $this->customer = $customer;
            $this->field_list = $field_list;
            $this->assign('service_res',$service_res);
            $this->assign('OnSiteService_res',$OnSiteService_res);
            $this->assign('netTrainService_res',$netTrainService_res);
            $this->alert = parseAlert();
            $this->display();
        }
    }

    public function editFileName()
    {
        $file_id = I('post.file_id') ;
        $data['name'] = I('post.name');
        $tab = I('cookie.tab','') ;
        $file = M('file') ;
        $bool = $file->where('file_id=%d',$file_id)->save($data) ;
        if ($bool===false){
            alert('error',L('编辑失败'), $_SERVER['HTTP_REFERER'].$tab);
        }else{
            alert('success',L('编辑成功'), $_SERVER['HTTP_REFERER'].$tab);
        }
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

    /**
     * 客户导出
     * @param bool $customerList
     * @throws Exception
     */
    public function excelExport($customerList=false){
        C('OUTPUT_ENCODE', false);
        import("ORG.PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("5kcrm");
        $objProps->setLastModifiedBy("5kcrm");
        $objProps->setTitle("5kcrm Customer");
        $objProps->setSubject("5kcrm Customer Data");
        $objProps->setDescription("5kcrm Customer Data");
        $objProps->setKeywords("5kcrm Customer Data");
        $objProps->setCategory("5kcrm");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();

        $objActSheet->setTitle('Sheet1');
        $ascii = 65;
        $cv = '';
        $field_list = M('Fields')->where('model = \'customer\'')->order('order_id')->select();
        foreach($field_list as $field){
            $objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
            $ascii++;
            if($ascii == 91){
                $ascii = 65;
                $cv .= chr(strlen($cv)+65);
            }
        }
        $mark_customer_ascii = $ascii;
        $mark_customer_cv = $cv;
        //联系人字段
        $contacts_fields_list = array();
        $contacts_fields_list[0]['field'] = 'name';
        $contacts_fields_list[0]['name'] = '联系人姓名';
        $contacts_fields_list[1]['field'] = 'saltname';
        $contacts_fields_list[1]['name'] = '尊称';
        $contacts_fields_list[2]['field'] = 'post';
        $contacts_fields_list[2]['name'] = '职位';
        $contacts_fields_list[3]['field'] = 'telephone';
        $contacts_fields_list[3]['name'] = '电话';
        $contacts_fields_list[4]['field'] = 'email';
        $contacts_fields_list[4]['name'] = '邮件';
        $contacts_fields_list[5]['field'] = 'qq_no';
        $contacts_fields_list[5]['name'] = 'qq';
        $contacts_fields_list[6]['field'] = 'zip_code';
        $contacts_fields_list[6]['name'] = '邮编';
        $contacts_fields_list[7]['field'] = 'address';
        $contacts_fields_list[7]['name'] = '联系地址';
        $contacts_fields_list[8]['field'] = 'description';
        $contacts_fields_list[8]['name'] = '备注';

        foreach($contacts_fields_list as $field){
            $objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
            $ascii++;
            if($ascii == 91){
                $ascii = 65;
                $cv .= chr(strlen($cv)+65);
            }
        }
        $mark_contacts_ascii = $ascii;
        $mark_contacts_cv = $cv;

        if(is_array($customerList)){
            $list = $customerList;
        }else{
            $where['owner_role_id'] = array('in',implode(',', getSubRoleId()));
            $where['is_deleted'] = 0;
            $list = M('Customer')->where($where)->select();
        }

        $i = 2;
        foreach ($list as $k => $v) {
            $date = M('CustomerData')->where("customer_id = $v[customer_id]")->find();
            if(!empty($date)){
                $v = $v+$date;
            }
            $i++;
            $ascii = 65;
            $cv = '';
            foreach($field_list as $field){
                if($field['form_type'] == 'datetime'){
                    $objActSheet->setCellValue($cv.chr($ascii).$i, date('Y-m-d',$v[$field['field']]));
                }elseif($field['form_type'] == 'number' || $field['form_type'] == 'floatnumber' || $field['form_type'] == 'phone' || $field['form_type'] == 'mobile' || ($field['form_type'] == 'text' && is_numeric($v[$field['field']]))){
                    //防止使用科学计数法，在数据前加空格
                    $objActSheet->setCellValue($cv.chr($ascii).$i, ' '.$v[$field['field']]);
                }else{
                    $objActSheet->setCellValue($cv.chr($ascii).$i, $v[$field['field']]);
                }
                $ascii++;
                if($ascii == 91){
                    $ascii = 65;
                    $cv .= chr(strlen($cv)+65);
                }
            }

            //联系人
            $mark_ascii = $ascii;
            $mark_cv = $cv;
            $m_contacts = M('contacts');
            $m_rContactsCustomer = M('rContactsCustomer');
            $contactsIdArr = $m_rContactsCustomer->where('customer_id = %d', $v['customer_id'])->getField('contacts_id',true);
            $contacts_list = $m_contacts->field('name,saltname,post,telephone,email,qq_no,zip_code,address,description')->where(array('contacts_id'=>array('in',$contactsIdArr)))->select();
            if($contacts_list){
                foreach($contacts_list as $val){
                    foreach($contacts_fields_list as $valu){
                        //防止使用科学计数法，在数据前加空格
                        if($valu['field'] == 'telephone' || $valu['field'] =='qq_no'){
                            $objActSheet->setCellValue($cv.chr($ascii).$i, ' '.$val[$valu['field']]);
                        }else{
                            $objActSheet->setCellValue($cv.chr($ascii).$i, $val[$valu['field']]);
                        }

                        $ascii++;
                        if($ascii == 91){
                            $ascii = 65;
                            $cv .= chr(strlen($cv)+65);
                        }
                    }
                    $ascii = $mark_ascii;
                    $cv = $mark_cv;
                    $i++;
                }
            //$ascii--;
                $i--;
            }
        }
        $objActSheet->mergeCells('A1:'.$mark_customer_cv.chr($mark_customer_ascii-1).'1');
        $objActSheet->mergeCells($mark_customer_cv.chr($mark_customer_ascii).'1'.':'.$mark_contacts_cv.chr($mark_contacts_ascii).'1');
        $objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getAlignment()->setWrapText(true);
        $objActSheet->setCellValue('A1', L('CUSTOMER_INFO'));
        $objActSheet->setCellValue($mark_customer_cv.chr($mark_customer_ascii).'1', L('CONTACTS_INFO'));
        //设置背景色
        $objActSheet->getStyle('A1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objActSheet->getStyle('A1')->getFill()->getStartColor()->setARGB('F5DEB3');
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getFill()->getStartColor()->setARGB('FFFFE0');

        $current_page = intval($_GET['current_page']);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=5kcrm_customer_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
        session('export_status', 0);
    }

    public function getCurrentStatus(){
        $this->ajaxReturn(intval(session('export_status')), 'success', 1);

    }

    /**
    *  客户导入
    *
    **/
    public function excelImport(){

        $m_customer = D('Customer');
        $m_customer_data = D('CustomerData');
        if($this->isPost()){
            if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
                import('@.ORG.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 20000000;
                $upload->allowExts  = array('xls');
                $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                    alert('error', L('ATTACHMENTS_TO_UPLOAD_DIRECTORY_CANNOT_WRITE'), $_SERVER['HTTP_REFERER']);
                }
                $upload->savePath = $dirname;
                if(!$upload->upload()) {
                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                }else{
                    $info =  $upload->getUploadFileInfo();
                }
            }
            if(is_array($info[0]) && !empty($info[0])){
                $savePath = $dirname . $info[0]['savename'];
            }else{
                alert('error', L('UPLOAD_FAILED'), $_SERVER['HTTP_REFERER']);
            }
            import("ORG.PHPExcel.PHPExcel");
            $PHPExcel = new PHPExcel();
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if(!$PHPReader->canRead($savePath)){
                $PHPReader = new PHPExcel_Reader_Excel5();
            }
            $PHPExcel = $PHPReader->load($savePath);
            $currentSheet = $PHPExcel->getSheet(0);
            $allRow = $currentSheet->getHighestRow();

            if ($allRow <= 2) {
                alert('error', L('UPLOAD_A_FILE_WITHOUT_A_VALID_DATA'), $_SERVER['HTTP_REFERER']);
            } else {
                $field_list = M('Fields')->where('model = \'customer\'')->order('order_id')->select();
                for($currentRow = 3;$currentRow <= $allRow;$currentRow++){
                    $data = array();
                    $data['owner_role_id'] = intval($_POST['owner_role_id']);
                    $data['creator_role_id'] = session('role_id');
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $ascii = 65;
                    $cv = '';
                    foreach($field_list as $field){
                        //$info = (String)$currentSheet->getCell($cv.chr($ascii).$currentRow)->getValue();
                        // if ($field['is_main'] == 1){
                            // $data[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(PHPExcel_Shared_Date::ExcelToPHP($info))-8*60*60 : $info;
                        // }else{
                            // $data_date[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(PHPExcel_Shared_Date::ExcelToPHP($info))-8*60*60 : $info;
                        // }

                        $cell =$currentSheet->getCell($cv.chr($ascii).$currentRow);
                        $info = $cell->getValue();
                        if($cell->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){
                            $cellstyleformat=$cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat();

                            //formatcode 为 yyyy/m 时间格式
                            $formatcode=$cellstyleformat->getFormatCode();
                            if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
                                $info=gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($info));
                            }else{
                                $info=PHPExcel_Style_NumberFormat::toFormattedString($info,$formatcode);
                            }
                        }else{
                            $info = (String)$cell->getCalculatedValue();
                        }
                        if ($field['is_main'] == 1){
                            $data[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? strtotime($info) : $info;
                        }else{
                            $data_date[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? strtotime($info) : $info;
                        }

                        $ascii++;
                        if($ascii == 91){
                            $ascii = 65;
                            $cv .= chr(strlen($cv)+65);
                        }
                    }
                    //联系人字段
                    $contacts_fields_list = array();
                    $contacts_fields_list[0]['field'] = 'name';
                    $contacts_fields_list[1]['field'] = 'saltname';
                    $contacts_fields_list[2]['field'] = 'post';
                    $contacts_fields_list[3]['field'] = 'telephone';
                    $contacts_fields_list[4]['field'] = 'email';
                    $contacts_fields_list[5]['field'] = 'qq_no';
                    $contacts_fields_list[6]['field'] = 'zip_code';
                    $contacts_fields_list[7]['field'] = 'address';
                    $contacts_fields_list[8]['field'] = 'description';

                    foreach($contacts_fields_list as $field){
                        $info = (String)$currentSheet->getCell($cv.chr($ascii).$currentRow)->getValue();
                        $contacts_data[$field['field']] = $info;

                        $ascii++;
                        if($ascii == 91){
                            $ascii = 65;
                            $cv .= chr(strlen($cv)+65);
                        }
                    }
                    if ($m_customer->create($data) && $m_customer_data->create($data_date)) {
                        $customer_id = $m_customer->add();
                        $m_customer_data->customer_id = $customer_id;
                        $m_customer_data->add();
                        //添加联系人
                        $m_contacts = M('contacts');
                        $contacts_data['creator_role_id'] = intval($_POST['owner_role_id']);
                        $contacts_data['create_time'] = time();
                        $contacts_id = $m_contacts->add($contacts_data);
                        //添加客户联系人（客户联系人关系表）
                        $m_rContactsCustomer = M('rContactsCustomer');
                        $m_rContactsCustomer->add(array('contacts_id'=>$contacts_id, 'customer_id'=>$customer_id));
                        //设置首要联系人
                        $m_customer->where('customer_id = %d', $customer_id)->setField('contacts_id', $contacts_id);

                    }else{
                        if($this->_post('error_handing','intval',0) == 0){
                            alert('error', L('ERROR INTRODUCED INTO THE LINE',array($currentRow,$m_customer->getError().$m_customer_data->getError())), $_SERVER['HTTP_REFERER']);
                        }else{
                            $error_message .= L('LINE ERROR',array($currentRow,$m_customer->getError().$m_customer_data->getError()));
                            $m_customer->clearError();
                            $m_customer_data->clearError();
                        }
                    }
                }

                alert('success', $error_message .L('IMPORT_SUCCESS'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            $this->display();
        }
    }

    /**
    *  客户导入模板下载
    *
    **/
    public function excelImportDownload(){
        C('OUTPUT_ENCODE', false);
        import("ORG.PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("5kcrm");
        $objProps->setLastModifiedBy("5kcrm");
        $objProps->setTitle("5kcrm Customer");
        $objProps->setSubject("5kcrm Customer Data");
        $objProps->setDescription("5kcrm Customer Data");
        $objProps->setKeywords("5kcrm Customer Data");
        $objProps->setCategory("5kcrm");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();

        $objActSheet->setTitle('Sheet1');
        $ascii = 65;
        $cv = '';
        $field_list = M('Fields')->where('model = \'customer\' ')->order('order_id')->select();
        $contacts_fields_list = array();
        $contacts_fields_list[0]['name'] = '姓名';
        $contacts_fields_list[1]['name'] = '尊称';
        $contacts_fields_list[2]['name'] = '职位';
        $contacts_fields_list[3]['name'] = '电话';
        $contacts_fields_list[4]['name'] = '邮件';
        $contacts_fields_list[5]['name'] = 'QQ';
        $contacts_fields_list[6]['name'] = '邮编';
        $contacts_fields_list[7]['name'] = '联系地址(长文本)';
        $contacts_fields_list[8]['name'] = '备注';

        foreach($field_list as $field){
            $objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
            $ascii++;
            if($ascii == 91){
                $ascii = 65;
                $cv .= chr(strlen($cv)+65);
            }
            $mark_customer_cv = $cv;
            $mark_customer_ascii = $ascii;
        }
        foreach($contacts_fields_list as $field){
            $objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
            $ascii++;
            if($ascii == 91){
                $ascii = 65;
                $cv .= chr(strlen($cv)+65);
            }
            $mark_contacts_cv = $cv;
            $mark_contacts_ascii = $ascii;
        }
        $objActSheet->mergeCells('A1:'.$mark_customer_cv.chr($mark_customer_ascii-1).'1');
        $objActSheet->mergeCells($mark_customer_cv.chr($mark_customer_ascii).'1'.':'.$mark_contacts_cv.chr($mark_contacts_ascii).'1');
        $objActSheet->getRowDimension('1')->setRowHeight(50);
        $objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle($mark_customer_cv.chr($mark_customer_ascii).'1')->getAlignment()->setWrapText(true);
        $content = L('ADRESS');
        $objActSheet->setCellValue('A1', $content);
        $objActSheet->setCellValue($mark_customer_cv.chr($mark_customer_ascii).'1', L('FIRST_CONTACTS_INFO'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=5kcrm_customer.xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
    }

    public function revert(){
        $customer_id = isset($_GET['id']) ? intval(trim($_GET['id'])) : 0;
        if ($customer_id > 0) {
            $m_customer = M('customer');
            $customer = $m_customer->where('customer_id = %d', $customer_id)->find();
            if ($customer['delete_role_id'] == session('role_id') || session('?admin')) {
                if (isset($customer['is_deleted']) || $customer['is_deleted'] == 1) {
                    if ($m_customer->where('customer_id = %d', $customer_id)->setField('is_deleted', 0)) {
                        alert('success', L('RESTORE_SUCCESSFUL'), $_SERVER['HTTP_REFERER']);
                    } else {
                        alert('error', L('RESTORE_FAILURE'), $_SERVER['HTTP_REFERER']);
                    }
                } else {
                    alert('error', L('ALREADY_REDUCTION!'), $_SERVER['HTTP_REFERER']);
                }
            } else {
                alert('error', L('HAVE_NO_PERMISSION_TO_RESTORE!'), $_SERVER['HTTP_REFERER']);
            }
        } else {
            alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
        }
    }

    public function getCustomerList(){
        $idArray = getSubRoleId();
        $idArray[] = session("role_id");

        //获取下级和自己的客户列表,搜索
        $customerList = M('customer')->where('owner_role_id in (%s) and is_deleted = 0', implode(',', $idArray))->select();
        $this->assign('customerlist',$list);

        $this->ajaxReturn($customerList, '', 1);
    }

    //客户关怀列表
    public function cares(){
        $m_cares = M('CustomerCares');
        $below_ids = getSubRoleId(false);
        $all_ids = getSubRoleId();
        $by = isset($_GET['by']) ? trim($_GET['by']) : '';
        $where = array();
        $params = array();

        $order = "create_time desc";

        if($_GET['desc_order']){
            $order = trim($_GET['desc_order']).' desc';
        }elseif($_GET['asc_order']){
            $order = trim($_GET['asc_order']).' asc';
        }

        switch ($by) {
            case 'me' : $where['owner_role_id'] = session('role_id'); break;
            case 'sub' : $where['owner_role_id'] = array('in',implode(',', $below_ids)); break;
            case 'today' :
                $where['care_time'] = array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
                break;
            case 'week' :
                $week = (date('w') == 0)?7:date('w');
                $where['care_time'] = array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
                break;
            case 'month' :
                $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                $where['care_time'] = array('between',array($beginThismonth ,$endThismonth));
                break;
            case 'email' : $where['type'] = 'email'; break;
            case 'phone' : $where['type'] = 'phone';  break;
            case 'add' : $order = 'create_time desc';  break;
            case 'update' : $order = 'update_time desc';  break;
            case 'message' : $where['type'] = 'message';  break;
            case 'other' : $where['type'] = 'other';  break;
            default : $where['owner_role_id'] = array('in',implode(',', $all_ids)); break;
        }
        if ($by != 'deleted') {
            $where['is_deleted'] = array('neq',1);
        }
        if ($by != 'me' && $by != 'sub') {
            $where['owner_role_id'] = array('in',implode(',', getSubRoleId(true)));
        }

        if ($_REQUEST["field"]) {
            $field = trim($_REQUEST['field']) == 'all' ? 'subject|description' : $_REQUEST['field'];
            $search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
            $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
            if  ('create_time' == $field || 'update_time' == $field || 'care_time' == $field) {
                $search = is_numeric($search)?$search:strtotime($search);
            }
            switch ($_REQUEST['condition']) {
                case "is" : $where[$field] = array('eq',$search);break;
                case "isnot" :  $where[$field] = array('neq',$search);break;
                case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
                case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
                case "start_with" :  $where[$field] = array('like',$search.'%');break;
                case "end_with" :  $where[$field] = array('like','%'.$search);break;
                case "is_empty" :  $where[$field] = array('eq','');break;
                case "is_not_empty" :  $where[$field] = array('neq','');break;
                case "gt" :  $where[$field] = array('gt',$search);break;
                case "egt" :  $where[$field] = array('egt',$search);break;
                case "lt" :  $where[$field] = array('lt',$search);break;
                case "elt" :  $where[$field] = array('elt',$search);break;
                case "eq" : $where[$field] = array('eq',$search);break;
                case "neq" : $where[$field] = array('neq',$search);break;
                case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
                case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
                case "tgt" :  $where[$field] = array('gt',$search+86400);break;
                default : $where[$field] = array('eq',$search);
            }
            $params = array('field='.$field, 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
        }

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $list = $m_cares->where($where)->order($order)->page($p.',10')->select();
        $count = $m_cares->where($where)->count();
        import("@.ORG.Page");
        $Page = new Page($count,10);
        $params[] = 'a=cares';
        $params[] = 'by='.$_GET['by'];
        $this->parameter = implode('&', $params);

        if ($_GET['desc_order']) {
            $params[] = "desc_order=" . trim($_GET['desc_order']);
        } elseif($_GET['asc_order']){
            $params[] = "asc_order=" . trim($_GET['asc_order']);
        }

        $Page->parameter = implode('&', $params);
        $show = $Page->show();
        $this->assign('page',$show);

        foreach ($list as $k => $v) {
            $list[$k]["customer"] = M('customer')->where('customer_id = %d', $v['customer_id'])->find();
            $list[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
            $list[$k]["owner"] = getUserByRoleId($v['owner_role_id']);
        }
        $this->assign('caresList',$list);
        $this->alert = parseAlert();
        $this->display();
    }
    public function caresAdd(){
        $m_customer = M('Customer');
        $m_contacts = M('Contacts');
        $m_r_contacts_customer = M('RContactsCustomer');
        if($this->isPost()){
            $m_cares = M('CustomerCares');
            if($m_cares->create()){
                if(!$_POST['subject']) $this->error(L('CARE_SUBJECT_CANNOT_BE_EMPTY!'));
                if(!$_POST['care_time']) $this->error(L('CARE_CARETIME_CANNOT_BE_EMPTY'));
                if($_POST['care_time']) $m_cares->care_time = strtotime($_POST['care_time']);
                $m_cares->create_time = time();
                $m_cares->update_time = time();
                $m_cares->creator_role_id = session('role_id');
                if($m_cares->add()){
                    if($_POST['submit'] == L('SAVE')){
                        if($_POST['refer_url'])
                        {
                            alert('success', L('ADD_SUCCESSFUL'), $_POST['refer_url']);
                        }
                        alert('success', L('ADD_SUCCESSFUL'), U('customer/cares'));
                    }else{
                        alert('success', L('Add_successful'), U('customer/caresadd'));
                    }
                }else{
                    alert('error', L('ADD_FAILURE_PLEASE_CONTACT_YOUR_ADMIN'), $_SERVER['HTTP_REFERER']);
                }
            }else{
                $this->error($m_cares->getError());
            }
        }else{
            $customer_id = $_GET['customer_id'];
            $m_customer = M('Customer');
            $customer = $m_customer->where('customer_id = %d',$customer_id)->find();
            if(!empty($customer['contacts_id'])){
                $contacts = $m_contacts->where('is_deleted = 0 and contacts_id = %d',$customer['contacts_id'])->find();
                $customer['contacts_name'] = $contacts['name'];
            }else{
                $contacts_customer = $m_r_contacts_customer->where('customer_id = %d',$customer['customer_id'])->limit(1)->order('id desc')->select();
                $contacts = $m_contacts->where('is_deleted = 0 and contacts_id = %d',$contacts_customer[0]['contacts_id'])->find();
                $customer['contacts_id'] = $contacts['contacts_id'];
                $customer['contacts_name'] = $contacts['name'];
            }
            $this->customer = $customer;
            $this->refer_url=$_SERVER['HTTP_REFERER'];
            $this->alert = parseAlert();
            $this->display();
        }
    }
    public function caresEdit(){
        $care_id = $_POST['care_id'] ? intval($_POST['care_id']) : intval($_GET['id']);
        if($care_id && !check_permission($care_id, 'customerCares')) $this->error(L('HAVE NOT PRIVILEGES'));

        if ($this->isPost()) {
            $m_cares = M('CustomerCares');
            if($m_cares->create()){
                if(!$_POST['subject']) alert('error', L('CARE_SUBJECT_CANNOT_BE_EMPTY'), $_SERVER['HTTP_REFERER']);
                if($_POST['care_time']) $m_cares->care_time = strtotime($_POST['care_time']);
                $m_cares->update_time = time();
                if($m_cares->save()){
                    alert('success', L('MODIFY_THE_SUCCESS'), U('customer/cares'));
                }else{
                    alert('error', L('MODIFY_THE_SUCCESS'), $_SERVER['HTTP_REFERER']);
                }
            }else{
                $this->error($m_cares->getError());
            }
        } else {
            if($care_id>0){
                $m_care = M('CustomerCares');
                $care = $m_care->where('care_id = %d', $care_id)->find();
                $care['owner'] = getUserByRoleId($care['owner_role_id']);
                $care['customer'] = M('customer')->where('customer_id = %d', $care['customer_id'])->find();
                $care['contacts'] = M('contacts')->where('contacts_id = %d', $care['contacts_id'])->find();
                $this->care = $care;
                $this->alert = parseAlert();
                $this->display();
            }else{
                alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function caresView(){
        $care_id = intval($_GET['id']);
        if($care_id && !check_permission($care_id, 'customerCares')) $this->error(L('HAVE NOT PRIVILEGES'));

        if($care_id>0){
            $m_care = M('CustomerCares');
            $care = $m_care->where('care_id = %d', $_GET['id'])->find();
            if (is_array($care)) {

                $care = $m_care->where('care_id = %d', $care_id)->find();
                $care['owner'] = getUserByRoleId($care['owner_role_id']);
                $care['customer'] = M('customer')->where('customer_id = %d', $care['customer_id'])->find();
                $care['contacts'] = M('contacts')->where('contacts_id = %d', $care['contacts_id'])->find();
                $this->care = $care;
                $this->alert = parseAlert();
                $this->display();
            } else {
                alert('error', L('RECORD_NOT_EXIST'), U('customer/cares'));
            }
        }else{
            alert('error',L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
        }
    }

    public function caresDelete(){
        $m_cares = M('CustomerCares');
        if ($this->isPost()) {
            // foreach($_POST['care_id'] as $k => $v){
                // if($m_cares->where('care_id = %d', $v['care_id'])->getField('owner_role_id') != session('role_id')){
                    // alert('error', '您没有全部的权限', U('leads/index'));
                // }
            // }
            $care_id = is_array($_POST['care_id']) ? implode(',', $_POST['care_id']) : '';
            if ('' == $care_id) {
                alert('error', L('HAVE_NOT_CHOOSE_ANY_CONTENT'), U('customer/cares'));
            } else {
                if($m_cares->where('care_id in (%s)', $care_id)->delete()){
                    alert('success', L('DELETED_SUCCESSFULLY'),U('customer/cares'));
                } else {
                    alert('error', L('DELETE_FAILED_CONTACT_ADMIN'), U('customer/cares'));
                }
            }
        } elseif($_GET['id']) {
            $care = $m_cares->where('care_id = %d', $_GET['id'])->find();
            if (is_array($care)) {
                if ($care['owner_role_id'] == session('role_id') || session('?admin')) {
                    if($m_cares->where('care_id = %d', $_GET['id'])->delete()){
                        alert('success', L('DELETED_SUCCESSFULLY'), U('customer/cares'));
                    }else{
                        alert('error', L('DELETE_FAILED_CONTACT_ADMIN'), U('customer/cares'));
                    }
                } else {
                    alert('error', L('DO_NOT_HAVE_PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
            } else {
                alert('error', L('RECORD_NOT_EXIST'), U('customer/cares'));
            }
        } else {
            alert('error', L('PLEASE_SELECT_A_CLUE_TO_DELETE'),$_SERVER['HTTP_REFERER']);
        }
    }
   //客户的统计
    public function analytics($page = 1, $page_size = 15){
        $m_customer = M('Customer');

        if($_GET['role']) {
            $role_id = intval($_GET['role']);
        }else{
            $role_id = 'all';
        }

        if($_GET['department'] && $_GET['department'] != 'all'){
            $department_id = intval($_GET['department']);
        } else {
            $department_id = D('RoleView')->where('role.role_id = %d', session('role_id'))->getField('department_id');
        }

        if($_GET['start_time']) $start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
        $end_time = $_GET['end_time'] ?  strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time()));
        if($role_id == "all") {
            $roleList = getRoleByDepartmentId($department_id);

            $role_id_array = array_column($roleList, 'role_id');

            $where_role_id = array('in', implode(',', $role_id_array));
            $where_source['creator_role_id'] = $where_role_id;
            $where_industry['owner_role_id'] = $where_role_id;
            $where_renenue['creator_role_id'] = $where_role_id;
            $where_employees['creator_role_id'] = $where_role_id;
        }else{
            $where_source['creator_role_id'] = $role_id;
            $where_industry['owner_role_id'] = $role_id;
            $where_renenue['creator_role_id'] = $role_id;
            $where_employees['creator_role_id'] = $role_id;
        }
        if($start_time){
            $where_create_time = array(array('elt',$end_time),array('egt',$start_time), 'and');
            $where_source['create_time'] = $where_create_time;
            $where_industry['create_time'] = $where_create_time;
            $where_renenue['create_time'] = $where_create_time;
            $where_employees['create_time'] = $where_create_time;

        }else{
            $where_source['create_time'] = array('elt',$end_time);
            $where_industry['create_time'] = array('elt',$end_time);
            $where_renenue['create_time'] = array('elt',$end_time);
            $where_employees['create_time'] = array('elt',$end_time);
        }

        //统计表内容
        $role_id_array = array();
        if($role_id == "all"){
            if($_GET['department'] != 'all'){
                $role_id_array = getRoleByDepartmentId($department_id);
            } else {
                $role_id_array = getSubRoleId();
            }
        }else{
            $role_id_array[] = $role_id;
        }
        $role_id_array = array_column($role_id_array, 'role_id');
        if($start_time){
            $create_time= [['elt', $end_time],['egt', $start_time], 'and'];
        }else{
            $create_time =['elt', $end_time];
        }

        $add_count_total = 0;
        $own_count_total = 0;
        $success_count_total = 0;
        $deal_count_total = 0;
        $busi_customer_array = M('Business')->getField('customer_id', true);
        $busi_customer_id = implode(',', $busi_customer_array);

        $add_count_list = $m_customer
        ->where(array('is_deleted'=>0, 'creator_role_id' => ['in', $role_id_array], 'create_time'=>$create_time))
        ->group('creator_role_id')
        ->getField('creator_role_id, count(creator_role_id) as count');
        $add_count_total = array_sum($add_count_list);

        $own_count_list = $m_customer
        ->where(array('is_deleted'=>0, 'owner_role_id' => ['in', $role_id_array], 'create_time'=>$create_time))
        ->group('owner_role_id')
        ->getField('owner_role_id, count(owner_role_id) as count');

        $own_count_total = array_sum($add_count_list);

        $success_count_list = $m_customer
        ->where(array('is_deleted'=>0, 'customer_id'=>array('in', $busi_customer_id), 'create_time'=>$create_time))
        ->group('owner_role_id')
        ->getField('owner_role_id, count(owner_role_id) as count');

        $success_count = array_sum($success_count_list);

        foreach($role_id_array as $role_id){
            $user = getUserByRoleId($role_id);
            $reportList[] = ["user" => $user,
             "add_count" => $add_count_list[$role_id] + 0,
             "own_count" => $own_count_list[$role_id] + 0,
             'success_count' => $success_count_list[$role_id] + 0
            ];
        }

        //来源统计图
        $source_count_array = array();
        $setting = M('Fields')->where("model = 'customer' and field = 'origin'")->getField('setting');
        $setting_str = '$sourceList='.$setting.';';
        eval($setting_str);
        $source_total_count = 0;
        foreach($sourceList['data'] as $v){
            unset($where_source['origin']);
            $where_source['origin'] = $v;
            $target_count = $m_customer ->where($where_source)->count();
            $source_count_array[] = '['.'"'.$v.'",'.$target_count.']';
            $source_total_count += $target_count;
        }
        $source_count_array[] = '["'.L('OTHER').'",'.($add_count_total-$source_total_count).']';
        $this->source_count = implode(',', $source_count_array);



        //客户行业统计图
        $industry_count_array = array();
        $setting = M('Fields')->where("model = 'customer' and field = 'industry'")->getField('setting');
        $setting_str = '$industryList='.$setting.';';
        eval($setting_str);
        $where_industry['is_deleted'] = 0;
        $industry_total_count = 0;
        foreach($industryList['data'] as $v){
            unset($where_employees['industry']);
            $where_industry['industry'] = $v;
            $target_count = $m_customer ->where($where_industry)->count();
            $industry_total_count += $target_count;
            $industry_count_array[] = '["'.$v.'",'.$target_count.']';
        }
        $industry_count_array[] = '["'.L('OTHER').'",'.($add_count_total-$industry_total_count).']';
        $this->industry_count = implode(',', $industry_count_array);
        //客户员工数统计
        $employees_count_array = array();
        $setting = M('Fields')->where("model = 'customer' and field = 'no_of_employees'")->getField('setting');
        $setting_str = '$no_List='.$setting.';';
        eval($setting_str);
        $where_employees['is_deleted'] = 0;
        $no_total_count = 0;
        foreach($no_List['data'] as $v){
            unset($where_employees['no_of_employees']);
            $where_employees['no_of_employees'] = $v;
            $target_count = $m_customer ->where($where_employees)->count();
            $no_total_count+=$target_count;
            $employees_count_array[] = '["'.$v.'",'.$target_count.']';
        }
        $employees_count_array[] = '["'.L('OTHER').'",'.($add_count_total-$no_total_count).']';
        $this->employees_count = implode(',', $employees_count_array);
        //客户营业额统计
        $revenue_count_array = array();
        $setting = M('Fields')->where("model = 'customer' and field = 'annual_revenue'")->getField('setting');
        $setting_str = '$revenueList='.$setting.';';
        eval($setting_str);
        $where_renenue['is_deleted'] = 0;
        $revenue_total_count = 0;
        foreach($revenueList['data'] as $v){
            unset($where_renenue['annual_revenue']);
            $where_renenue['annual_revenue'] = $v;
            $target_count = $m_customer ->where($where_renenue)->count();
            $revenue_count_array[] = '['.'"'.$v.'",'.$target_count.']';
            $revenue_total_count+=$target_count;
        }
        $revenue_count_array[] = '["'.L('OTHER').'",'.($add_count_total-$target_count).']';
        $this->revenue_count = implode(',', $revenue_count_array);



        $this->total_report = array("add_count"=>$add_count_total, "own_count"=>$own_count_total, "success_count"=>$success_count_total);
        $this->reportList = $reportList;
        if (session('?admin')){
            $idArray = M('role')->where('user_id <> 0')->getField('role_id',true);
        }else{
            $idArray = getSubRoleId();
        }
        $roleList = array();
        foreach($idArray as $roleId){
            $roleList[$roleId] = getUserByRoleId($roleId);
        }
        $this->roleList = $roleList;

        $departments = M('roleDepartment')->select();
        $departmentList[] = M('roleDepartment')->where('department_id = %d', session('department_id'))->find();
        $departmentList = array_merge($departmentList, getSubDepartment(session('department_id'),$departments,''));
        $this->assign('departmentList', $departmentList);
        $this->alert = parseAlert();
        $this->display();
    }

    //检查用户是否符合领取或被分配到客户池资源资格
    //type 1：领取 2：分配
    public function check_customer_limit($user_id, $type){
        $m_config = M('config');
        $m_customer_record = M('customer_record');
        $customer_limit_condition = $m_config->where('name = "customer_limit_condition"')->getField('value');

        $today_begin = strtotime(date('Y-m-d',time()));
        $today_end = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $this_week_begin = ($today_begin -((date('w'))-1)*86400);
        $this_week_end = ($today_end+(7-(date('w')==0?7:date('w')))*86400);
        $this_month_begain = strtotime(date('Y-m', time()).'-01 00:00:00');
        $this_month_end = mktime(23,59,59,date('m'),date('t'),date('Y'));

        $condition['user_id'] = $user_id;
        $condition['type'] = $type;
        if($customer_limit_condition == 'day'){
            $condition['start_time'] = array('between', array($today_begin, $today_end));
        }elseif($customer_limit_condition == 'week'){
            $condition['start_time'] = array('between', array($this_week_begin, $this_week_end));
        }elseif($customer_limit_condition == 'month'){
            $condition['start_time'] = array('between', array($this_month_begain, $this_month_end));
        }

        $customer_record = $m_customer_record->where($condition)->count();
        return $customer_record;
    }

    public function customerlock(){
        if(intval($_GET['customer_id'])){
            $m_customer = M('Customer');
            $customer = $m_customer->where('customer_id = %d ', intval($_GET['customer_id']))->find();
            if(!empty($customer)){
                if(!in_array(session('role_id'), getSubRoleId(true)) && !session('?admin'))
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                if($customer['is_locked']){
                    if($m_customer->where('customer_id = %d ', intval($_GET['customer_id']))->setField('is_locked',0)){
                        alert('success', L('UNLOCKING_SUCCESS'), $_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error', L('UNLOCKING_FAILD'), $_SERVER['HTTP_REFERER']);
                    }
                }else{
                    if($m_customer->where('customer_id = %d ', intval($_GET['customer_id']))->setField('is_locked',1)){
                        alert('success', L('LOCKING_SUCCESS'), $_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error', L('UNLOCKING_FAILD'), $_SERVER['HTTP_REFERER']);
                    }
                }
            }else{
                alert('error', L('RECORD_NOT_EXIST'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
        }
    }

    /**
     * 首页客户来源统计
     * @ level 0:自己的数据  1:自己和下属的数据
     **/
    public function getCustomerOriginal (){
        $dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
        $widget = unserialize($dashboard);
        $where['owner_role_id'] = array('in',getSubRoleId());

        $m_customer = M('customer');
        $original = $m_customer->Distinct(true)->field('origin')->getField('origin',true);
        $originalArr = array_filter($original);

        $customerArr = array();
        $where['is_deleted'] = array('eq',0);
        foreach($originalArr as $v){
            $where['origin'] = array('eq',$v);
            $origin_count = $m_customer ->where($where)->count();
            $customerArr['series'][] = array('value'=>intval($origin_count), 'name'=>$v);
            $customerArr['legend'][] = $v;
        }
        $this->ajaxReturn($customerArr,'success',1);
    }
    //客户分享
    public function share(){
        if($this->isPost()){
            $m_share =M('customerShare');
            $customer_ids = explode(',',$_POST['customer_id']);
            $m_customer_share = $m_share->select();
            $sharing_id = session('role_id');
            foreach($m_customer_share as $k=>$v){
                $by_sharing_id = explode(',',$v['by_sharing_id']);
                if(in_array($sharing_id,$by_sharing_id)){
                    $customerid[] = $v['customer_id'];
                }
            }
            foreach($customer_ids as $ko=>$vo){
                $is_share = in_array($vo,$customerid);
                if($is_share !=''){
                    $is_shares[] = $is_share;
                }
            }
            if($is_shares !=''){
                $this->error('不能重复分享');
            }

            $customers_ids = explode(',',$_POST['customer_id']);
            $to_role = implode(',',$_POST['to_role_id']);
            $i = 0;
            foreach($customers_ids as $vo){
                $data['share_role_id'] = session('role_id');
                $data['by_sharing_id'] = $to_role;
                $data['customer_id'] = $vo;
                $data['share_time']  = time();
                $m_share -> add($data);
                $i++;
            }
            if($i > 0){
                alert('success','共享成功！',$_SERVER["HTTP_REFERER"]);
            }else{
                alert('error','共享失败！',$_SERVER["HTTP_REFERER"]);
            }
        }else{
            $d_role = D('RoleView');
            $customer_ids = $_GET['customer_id'];
            $departments_list = M('roleDepartment')->select();
            foreach($departments_list as $k=>$v){
                $roleList = $d_role->where('position.department_id = %d', $v['department_id'])->select();
                $departments_list[$k]['user'] = $roleList;
            }
            $this->customer_id = $customer_ids;
            $this->departments_list = $departments_list;
            $this->display();
        }
    }

    public function close_share(){
        if($this->isPost()){
            $m_share = M('customerShare');
            $customer_ids = is_array($_POST['customer_id']) ? implode(',', $_POST['customer_id']) : '';
            if (empty($customer_ids)) {
                alert('error', L('HAVE_NOT_CHOOSE_ANY_CONTENT'), $_SERVER['HTTP_REFERER']);
            }
            else {
                $is_deleted = $m_share ->where('customer_id in (%s)',$customer_ids)->delete();
                if($is_deleted){
                    alert('success','关闭共享成功！',$_SERVER["HTTP_REFERER"]);
                }else{
                    alert('error','关闭共享失败！',$_SERVER["HTTP_REFERER"]);
                }
            }
        }elseif($_GET['customer_id']){
            $m_share = M('customerShare');
            $customer_id = $_GET['customer_id'];
            if (empty($customer_id)) {
                alert('error','参数错误', $_SERVER['HTTP_REFERER']);
            }
            else {
                $is_deleted = $m_share ->where('customer_id = %d',$customer_id)->delete();
                if($is_deleted){
                    alert('success','关闭共享成功！',$_SERVER["HTTP_REFERER"]);
                }else{
                    alert('error','关闭共享失败！',$_SERVER["HTTP_REFERER"]);
                }
            }
        }
    }
    //未关注
    public function batchfocus(){
        if($this->isPost()){
            $m_focus = M('customerFocus');
            $customer_ids = $_POST['customer_id'];
            if('' == $customer_ids){
                alert('error', L('NOT_CHOOSE_ANY'), $_SERVER['HTTP_REFERER']);
            }
            $i=0;
            foreach($customer_ids as $v){
                if($m_focus->where('customer_id = "%s" and user_id ="%d"', $v,session('role_id'))->count() <= 0){
                    $data['customer_id'] = $v;
                    $data['user_id'] = session('role_id');
                    $data['focus_time'] = time();
                    $m_focus ->add($data);
                    $i++;
                }
            }
            if($i > 0){
                alert('success', L('FOCUS_SUCCESS'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', '关注失败', $_SERVER['HTTP_REFERER']);
            }
        }elseif($_GET['customer_id']){
            $m_focus = M('customerFocus');
            $customer_id = $_GET['customer_id'];
            if('' == $customer_id){
                alert('error','参数错误', $_SERVER['HTTP_REFERER']);
            }
            $i=0;
            if($m_focus->where('customer_id = "%s" and user_id ="%d"', $customer_id,session('role_id'))->count() <= 0){
                $data['customer_id'] = $customer_id;
                $data['user_id'] = session('role_id');
                $data['focus_time'] = time();
                $m_focus ->add($data);
                $i = 1;
            }
            if($i > 0){
                alert('success', L('FOCUS_SUCCESS'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', '关注失败', $_SERVER['HTTP_REFERER']);
            }
        }
    }
    //以关注
    public function batchclose(){
        if($this->isPost()){
            $m_focus = M('customerFocus');
            $customer_ids = $_POST['customer_id'];
            if('' == $customer_ids){
                alert('error', L('NOT_CHOOSE_ANY'), $_SERVER['HTTP_REFERER']);
            }
            $i=0;
            foreach($customer_ids as $v){
                if($m_focus->where('customer_id = "%s" and user_id ="%d"', $v,session('role_id'))->count() > 0){
                    $m_focus ->where('customer_id = "%s" and user_id ="%d"', $v,session('role_id'))->delete();
                    $i++;
                }
            }
            if($i >0){
                alert('success',L('CANCEL_THE_ATTENTION'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', L('CANCEL_THE_ATTENTION_ERROR'), $_SERVER['HTTP_REFERER']);
            }
        }elseif($_GET['customer_id']){
            $m_focus = M('customerFocus');
            $customer_id = $_GET['customer_id'];
            if('' == $customer_id){
                alert('error', '参数错误', $_SERVER['HTTP_REFERER']);
            }
            $i=0;
            if($m_focus->where('customer_id = "%d" and user_id ="%d"', $customer_id,session('role_id'))->count() > 0){
                $m_focus ->where('customer_id = "%d" and user_id ="%d"', $customer_id,session('role_id'))->delete();
                $i = 1;
            }
            if($i >0){
                alert('success',L('CANCEL_THE_ATTENTION'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', L('CANCEL_THE_ATTENTION_ERROR'), $_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function getAddress()
    {
        $this -> display();
    }

    public function getAddress1()
    {
        $addressData = M('customer') -> field('customer_id, address')
            -> limit(4000, 2000)
//            -> order('customer_id desc')
            -> select();

        $this->ajaxReturn($addressData);
    }

    public function setAddress()
    {
        $newAddress = I('post.');
//        echo $newAddress['customer_id'] ."--".$newAddress['address'];
        $bool = M('customer') -> where('customer_id = %d', $newAddress['customer_id']) -> save(['address'=>$newAddress['address']]);
//        echo $newAddress['customer_id'] ."--". $newAddress['address'];
        echo $bool;
    }

    /**
     * 获取对应产品表单
     * @param $product_id
     * @param $order_product_id
     */
    public function product($product_id, $order_product_id)
    {
        $render = new \App\Lib\Plugins\Product\Product();
//        echo $render -> getForm($product_id, 32517);
        $pluginName = 'ProductOriginal';
        if ($product_id){
            $pluginNameTemp = M('product')->where('product_id=%d',$product_id)->getField('plugin');
            $pluginNameTemp && $pluginName = $pluginNameTemp;
        }
        echo $render -> getForm($pluginName, $order_product_id);
    }

    /**
     * 获取对应产品按钮
     * @param $product_id
     * @param $order_product_id
     */
    public function productButton($product_id, $order_product_id)
    {
        $render = new \App\Lib\Plugins\Product\Product();
        $pluginName = 'ProductOriginal';
        if ($product_id){
            $pluginNameTemp = M('product')->where('product_id=%d',$product_id)->getField('plugin');
            $pluginNameTemp && $pluginName = $pluginNameTemp;
        }
        $buttons = $render -> getButton($pluginName, $order_product_id);
        echo $buttons;
    }

    /**
     * 添加快递信息，并发货
     */
    public function express()
    {
        $express = I('post.');
        $tab = I('cookie.tab','');
        $express_id = M('express')->add($express);
        if ($express_id){
            $message = new \App\Extend\Message();
            // $message ->sendProcessMessage($express['customer_id'], \App\Extend\Message::SHIPMENTS, $express['order_product_id'], $express_id);//发货短信
            $date = $message->sendExpressMessage($express, session('role_id'));
            alert('success', '添加成功', $_SERVER['HTTP_REFERER'].$tab);
        }
        else
            alert('error', '添加失败', $_SERVER['HTTP_REFERER'].$tab);
    }

    public function delete_express($express_id)
    {
        $tab = I('cookie.tab','');
        if ($express_id){
            M('express')->where('express_id = %d', $express_id)->delete() ?
                alert('success', '删除成功', $_SERVER['HTTP_REFERER'].$tab):
                alert('error', '删除失败', $_SERVER['HTTP_REFERER'].$tab);
        }else{
            alert('error', '删除失败', $_SERVER['HTTP_REFERER'].$tab);
        }
    }
}