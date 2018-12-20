<?php

use App\Common\Utils;

/**
财务的页面
 **/
class ExcelColumn {
    protected $_key = '';
    protected $_title = '';
    protected $_width = '';
    protected $_color = '';

    public $_fomatter = null;

    public function __construct($key, $title, $formatter = null, $width = '', $color = '') {
        $this->_key = $key;
        $this->_title = $title;
        $this->_width = $width;
        $this->_color = $color;
        $this->_fomatter = $formatter;
    }
    
    public function formatter($data) {
        $callback = $this->_fomatter;
        if ($callback) {
            return $callback($data);
        }
        return $data;
    }

    public function __call ($name, $arguments) {
        if (strpos($name, 'get') === 0){
            $propertyName = '_'.strtolower(substr($name, 3));
            return isset($this->$propertyName) ? $this->$propertyName: null;
        }

        if (strpos($name, 'set') === 0){
            $propertyName = '_'.strtolower(substr($name, 3));
            isset($this->$propertyName) && $this->$propertyName = $arguments[0];
            return $this;
        }
    }
}

class FinanceAction extends Action{
    public function _initialize(){
        header("Content-type: text/html; charset=utf-8");
        $action = array(
            'permission' => [],
            'allow' => [
                'changecontent', 'listdialog', 'revert', 'adddialog', 'analytics', 'checkout',
                'getmonthlyreceive','getyearreceivecomparison','getreceivablesmoney', 'getpayablesmoney',
                'orderprint','getcostitem','cost_item_add','paymentorder_image_delete', 'otherrevenue_image_delete', 'uploadimage'
            ]
        );

        B('Authenticate', $action);
        $this->type = trim($_GET['t'])?trim($_GET['t']):'receivables';

        if (!in_array($this->type, ['receivables', 'payables', 'receivingorder', 'paymentorder', 'otherrevenue'])) {
            alert('error',L('PARAMETER_ERROR'),U('index/index'));
        }
    }

    public function changecontent(){
        $where = array();
        $params = array();
        $order = "";
        $p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
        $below_ids = getSubRoleId();
        $where[$this->type . '.is_deleted'] = 0;
        $where[$this->type . '.owner_role_id'] = array('in',implode(',', $below_ids)); 
        $where['receivables.status'] = array('neq',2);
        if ($_REQUEST["field"]) {
            $field = trim($_REQUEST['field']) == 'all' ? $this->type . '.name|'.$this->type .'.description' : $this->type .'.'. $_REQUEST['field'];
            $search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
            $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
            if  ('create_time' == $field || 'update_time' == $field ) {
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
        }
        $order = empty($order) ? $this->type . '.update_time desc' : $order;
        
        switch ($this->type) {
            case 'receivables' :
                $receivables = D('ReceivablesView');
                $list = $receivables->order($order)->where($where)->page($p.',10')->select();
                 
                foreach($list as $k=>$v){
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    $list[$k]['pay_time'] = date("Y-m-d",$v['pay_time']);
                }
                $count = $receivables->where($where)->count();
                $data['list'] = $list;
                $data['p'] = $p;
                $data['count'] = $count;
                $data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
                $this->ajaxReturn($data,"",1);
                break;
            case 'payables' :
                $payables = D('PayablesView');
                $list = $payables->order($order)->where($where)->page($p.',10')->select();
                
                foreach($list as $k=>$v){
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    $list[$k]['pay_time'] = date("Y-m-d",$v['pay_time']);
                }
                $count = $payables->where($where)->count();
                $data['list'] = $list;
                $data['p'] = $p;
                $data['count'] = $count;
                $data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
                $this->ajaxReturn($data,"",1);
                break;
        }
    }
    
    public function index(){
        $where = array();
        $params = array();
        $order = "";
        if($_GET['desc_order']){
            $order = trim($_GET['desc_order']).' desc';
        }elseif($_GET['asc_order']){
            $order = trim($_GET['asc_order']).' asc';
        }
        
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        $by = isset($_GET['by']) ? trim($_GET['by']) : '';
        $below_ids = getSubRoleId(false);
        $all_ids = getSubRoleId();
        $today_timestamp = strtotime(date('Y-m-d', time()));
        $today_end_timestamp = $today_timestamp + 86400;
        switch ($by) {
            case 'create' : $where[$this->type . '.handler_name_id'] = session('role_id'); break;//我经手的
            case 'sub' : $where[$this->type . '.payee_id'] = array('in',implode(',', $below_ids)); break;
            case 'subcreate' : $where[$this->type . '.handler_name_id'] = array('in',implode(',', $below_ids)); break;
            case 'none' : $where[$this->type . '.status'] = array('eq',0); break;
            case 'part' : $where[$this->type . '.status'] = array('eq',1); break;
            case 'all' : $where[$this->type . '.status'] = array('eq',2); break;
            case 'today' : 
                $where[$this->type . '.pay_time'] =  array(array('lt', $today_end_timestamp), array('egt', $today_timestamp ), 'and');
                $where[$this->type . '.status'] = array('neq',2);
                break;
            case 'week' : 
                $where[$this->type . '.pay_time'] =  array(array('lt', $today_end_timestamp), array('egt', $today_timestamp - (date('N', time()) - 1) * 86400 ),'and'); 
                $where[$this->type . '.status'] = array('neq',2);
                break;
            case 'month' : 
                $where[$this->type . '.pay_time'] =  array(array('lt', $today_end_timestamp), array('egt', strtotime(date('Y-m-01', time()))),'and'); 
                $where[$this->type . '.status'] = array('neq',2);
                break;
            case 'deleted' : $where[$this->type . '.is_deleted'] = 1; break;
            case 'add' : $order = $this->type . '.create_time desc'; break;
            case 'update' : $order = $this->type . '.update_time desc'; break;
            case 'me' : $where[$this->type . '.payee_id'] = session('role_id'); break; // 我取款的
            
        }

        if (!isset($where[$this->type . '.is_deleted'])) {
            $where[$this->type . '.is_deleted'] = 0;
        }
        if ($_REQUEST["field"]) {
            // $field = trim($_REQUEST['field']) == 'all' ? $this->type . '.name|'.$this->type .'.description|'.$this->type .'.payee|'.$this->type .'.dept|cost_item.cost_item' : $this->type .'.'. $_REQUEST['field'];
            if ($_REQUEST["field"] == 'all'){
                switch ($_REQUEST['t']){
                    case 'receivables': $field = 'receivables.name|receivables.description|customer.name';break;
                    case 'receivingorder': $field = 'receivingorder.payment_way|receivingorder.description|customer.name';break;
                    case 'otherrevenue': $field = 'otherrevenue.receiving_way|otherrevenue.description|owner_user.name|otherrevenue.from|otherrevenue.name';break;
                    case 'paymentorder': $field = 'paymentorder.name|paymentorder.description|paymentorder.dept|cost_item.cost_item|paymentorder.payee|paymentorder.handler_name';break;
                }
            }else{
                $field = $this->type .'.'. $_REQUEST['field'];
            }
            $search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
            $condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
            if  ('receivables.create_time' == $field || 'receivables.update_time' == $field ) {
                $search = is_numeric($search)?$search:strtotime($search);
            }

            if (in_array($field, [$this->type.'.pay_time'])){
                $where[$field] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', null, 'strtotime')+86399]];
            } else {
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
                   case "lt" :  $where[$field] = array('lt', $search); break;
                   case "elt" :  $where[$field] = array('elt',$search);break;
                   case "eq" : $where[$field] = array('eq',$search);break;
                   case "neq" : $where[$field] = array('neq',$search);break;
                   case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
                   case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
                   case "tgt" :  $where[$field] = array('gt',$search+86400);break;
                   default : $where[$field] = array('eq',$search);
               }
            }
            if ($field==$this->type.'.payee_id' || $field==$this->type.'.handler_name_id' || $field==$this->type.'.owner_role_id'){
                if ($_GET['department']=='all'){
                    $where[$field] = array('in', getSubRoleId(true,0));
                }else if ($_GET['department']!='all' && $_GET['search']=='all'){
                    $department_id = I('get.department');
                    $roleList = getRoleByDepartmentId($department_id, true);//true表示部门所有人
                    foreach ($roleList as $role){
                        $roleIds[] = $role['role_id'];
                    }
                    $where[$field] = array('in', $roleIds);
                }
            }

            if($_REQUEST["field"] == 'customer_name'){
                unset($where['receivables.customer_name']);
                $where['customer_name'] = array('like','%'.$search.'%');
            }

            if ($_REQUEST["field"] == 'owner_role_id' && I('get.start_time', null ,'strtotime')){
                $where[$this->type.'.pay_time'] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', date('Y-m-d'), 'strtotime')+86399]];
            }

            if ($_REQUEST["field"] == 'pay_time'){
                $where[$this->type.'.pay_time'] = ['between', [I('get.start_time', null ,'strtotime'), I('get.end_time', null, 'strtotime')+86399]];
            }

            $params = $_GET;//array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
            unset($params['p']);
        }

        $order = empty($order) ? $this->type . '.create_time desc' : $this->type .'.'.$order;
        $params['listrows'] = $listrows = I('get.listrows', 15, 'intval');

        switch ($this->type) {
            case 'receivables' :
                $receivables = D('ReceivablesView');
                $list = $receivables
                ->join(C('DB_PREFIX').'user owner_user on owner_user.role_id = receivables.owner_role_id')
                ->field(
                    'receivables.receivables_id AS receivables_id,
                     receivables.order_number AS order_number,
                     receivables.name AS name,
                     receivables.price AS price,
                     receivables.creator_role_id AS creator_role_id,
                     receivables.owner_role_id AS owner_role_id,
                     receivables.delete_role_id AS delete_role_id,
                     receivables.is_deleted AS is_deleted,
                     receivables.delete_time AS delete_time,
                     receivables.pay_time AS pay_time,
                     receivables.contract_id AS contract_id,
                     receivables.customer_id AS customer_id,
                     receivables.create_time AS create_time,
                     receivables.description AS description,
                     receivables.create_time AS create_time,
                     receivables. STATUS AS status,
                     customer.name AS customer_name,
                     customer.contacts_id AS contacts_id,
                     contract.number AS contract_name,
                     user.name AS creator_name,
                     owner_user.name as owner_name')
                ->order($order)
                ->where($where)
                ->page($p.','.$listrows)
                ->select();

                $sum_money = $receivables->where($where)->sum('receivables.price');
                foreach($list as $k=>$v){
                    if($by == 'deleted'){
                        $list[$k]['deleted'] = getUserByRoleId($v['delete_role_id']);
                    }
                    if ($v['status'] == 1){
                        $num = D('ReceivingorderView')
                        ->where('receivingorder.is_deleted <> 1 and receivingorder.receivables_id = %d and receivingorder.status = 1', $v['receivables_id'])
                        ->sum('money');
                        $list[$k]['un_payable'] = $v['price'] - $num;
                    }
                }
                $sum_money = number_format($sum_money,2);
                $count = $receivables->join(C('DB_PREFIX').'user owner_user on owner_user.role_id = receivables.owner_role_id')->where($where)->count();
                
                //添加首要联系人显示(二次开发新增)
                $contacts = M('contacts');
                foreach($list as $k=>$v){
                    $contacts_map['customer_id'] = $v['customer_id'];
                    $contacts_map['contacts_id'] = $v['contacts_id'];
                    $list[$k]['contacts'] = $contacts->where($contacts_map)->find();
                }
                break;
            case 'payables' :
                $payables = D('PayablesView');
                $list = $payables->order($order)->where($where)->page($p.','.$listrows)->select();
                $sum_money = $payables->where($where)->sum('payables.price');
                foreach($list as $k=>$v){
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    $money += $v['price'];
                    $list[$k]['purchase_sn_code'] = M('purchase')->where('purchase_id = %d', $v['purchase_id'])->getField('sn_code');
                    if($by == 'deleted'){
                        $list[$k]['deleted'] = getUserByRoleId($v['delete_role_id']);
                    }
                }
                $money = number_format($money,2);
                $sum_money = number_format($sum_money,2);
                $count = $payables->where($where)->count();
                break;
            case 'receivingorder' :
                $receivingorder = D('ReceivingorderView');
                /* 临时解决方案 考虑将客户id与收款信息关联，直接查出客户信息 */
                $sqlBuilder = $receivingorder
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = receivables.customer_id')
                ->order($order)
                ->where($where)
                ->field(
                    'receivingorder.receivingorder_id AS receivingorder_id,
                     receivingorder.name AS name,
                     receivingorder.money AS money,
                     receivingorder.status AS status,
                     receivingorder.receivables_id AS receivables_id,
                     receivingorder.owner_role_id AS owner_role_id,
                     receivingorder.delete_role_id AS delete_role_id,
                     receivingorder.is_deleted AS is_deleted,
                     receivingorder.delete_time AS delete_time,
                     receivingorder.description AS description,
                     receivingorder.pay_time AS pay_time,
                     receivingorder.creator_role_id AS creator_role_id,
                     receivingorder.create_time AS create_time,
                     receivingorder.update_time AS update_time,
                     receivables.name AS receivables_name,
                     receivables.price AS price,
                     customer.customer_id as customer_id,
                     customer.name as customer_name,
                     user.name AS creator_name');

                if (I('get.export')==1){
                    $list = $sqlBuilder->select();
                    $this->excelExport($list, $this->type, [
                        (new ExcelColumn('name', '收款单号'))->setWidth(15),
                        (new ExcelColumn('description', '收款标题'))->setWidth(15),
                        (new ExcelColumn('money', '收款金额'))->setWidth(15),
                        (new ExcelColumn('customer_name', '客户名称'))->setWidth(40),
                        (new ExcelColumn('pay_time', '收款时间', function($value){
                            return date('Y-m-d', $value);
                        }))->setWidth(15)
                    ], '实收款');
                    exit;
                }
                $list = $sqlBuilder->page($p.','.$listrows)->select();

                $sum_money = $receivingorder->where($where)->sum('money');
                foreach($list as $k=>$v){
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    if($by == 'deleted'){
                        $list[$k]['deleted'] = getUserByRoleId($v['delete_role_id']);
                    }
                }
                $sum_money = number_format($sum_money,2);
                $count = $receivingorder ->join(C('DB_PREFIX').'customer customer on customer.customer_id = receivables.customer_id')->where($where)->count();
                break;
            case 'paymentorder' :
//                $paymentorder = D('PaymentorderView');
                if (I('get.pay_start_time', null)||I('get.pay_end_time', null)){
                    $where['pay_time'] = ['between', [I('get.pay_start_time', null ,'strtotime'), I('get.pay_end_time', null, 'strtotime')+86399]];
                }
                $paymentorder = M('paymentorder');
                $list = $paymentorder -> alias('paymentorder')
                            ->field('paymentorder.*, cost_item.*, count(paymentorder_image.id) image_count')
                            -> where($where)
                            -> order($order)
                            -> join(C('DB_PREFIX').'cost_item cost_item ON cost_item.cost_item_id = paymentorder.payable')
                            -> join(C('DB_PREFIX').'paymentorder_image paymentorder_image ON paymentorder_image.paymentorder_id = paymentorder.paymentorder_id')
                            ->group('paymentorder.paymentorder_id')
                            -> page($p.','.$listrows)
                            -> select();
                // var_dump($list);die;
//                $list = $paymentorder->order($order)->where($where)->page($p.','.$listrows)->select();
                if (I('get.export')==1){
                    $data = $paymentorder->alias('paymentorder')->where($where)->order($order)->join(C('DB_PREFIX').'cost_item cost_item ON cost_item.cost_item_id = paymentorder.payable')->select();
                    $this->excelExport($data, $this->type);
                }
                $sum_money = $paymentorder-> alias('paymentorder') -> join(C('DB_PREFIX').'cost_item cost_item ON cost_item.cost_item_id = paymentorder.payable') -> where($where)->sum('money');
                foreach($list as $k=>$v){
                    $money +=$v['money'];
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    if($by == 'deleted'){
                        $list[$k]['deleted'] = getUserByRoleId($v['delete_role_id']);
                    }
                }
                $money = number_format($money,2);
                $sum_money = number_format($sum_money,2);
                $count = $paymentorder->alias('paymentorder') -> join(C('DB_PREFIX').'cost_item cost_item ON cost_item.cost_item_id = paymentorder.payable')->where($where)->count();
                $this->assign('pay_time', I('get.'));
                break;
            case 'otherrevenue' :
                $otherrevenue = M('otherrevenue');
                $list = $otherrevenue -> alias('otherrevenue')
                    -> join(C('DB_PREFIX').'user owner_user on owner_user.role_id = otherrevenue.owner_role_id')
                    -> where($where)
                    -> field('otherrevenue.*, owner_user.name as owner_role_name')
                    -> order($order)
                    -> page($p.','.$listrows)
                    -> select();
                $sum_money = $otherrevenue-> alias('otherrevenue') -> where($where) -> sum('money');
                foreach($list as $k=>$v){
                    $money +=$v['money'];
                    $list[$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                    if($by == 'deleted'){
                        $list[$k]['deleted'] = getUserByRoleId($v['delete_role_id']);
                    }
                }
                $money = number_format($money,2);
                $sum_money = number_format($sum_money,2);
                $count = $otherrevenue->alias('otherrevenue')-> join(C('DB_PREFIX').'user owner_user on owner_user.role_id = otherrevenue.owner_role_id')->where($where)->count();
                $this->assign('pay_time', I('get.'));
                break;
        }
        import("@.ORG.Page");
        $Page = new Page($count,$listrows);
        $params['by'] = trim($_GET['by']);
        $params['t'] = $this->type;
        
//        if ($_GET['desc_order']) {
//            $params['desc_order'] = trim($_GET['desc_order']);
//        } elseif($_GET['asc_order']){
//            $params['asc_order'] = trim($_GET['asc_order']);
//        }

        $Page->parameter = $this->parameter = http_build_query($params);
        $show = $Page->show();

        $this->listrows = $listrows;
        $this->alert = parseAlert();
        $this->assign('page',$show);
        $this->assign('money',$money);
        $this->assign('sum_money',$sum_money);
        $this->assign('list', $list);
        $this->display($this->type);
    }
    
    public function add(){
        $cost_item_dept = M('cost_item')->where('level=%d', 1)->select();
        $cost_item = M('cost_item')->where('level=%d', 2)->select();
        $this->assign('cost_item',$cost_item);
        $this->assign('cost_item_dept',$cost_item_dept);
        switch ($this->type) {
            case 'receivables' :
                if($_POST['submit']){
                    $receivables = M('receivables');
                    $data['name'] = trim($_POST['name'])?trim($_POST['name']):alert('error',L('PLEASE_FILL_IN_THE_NAME'),$_SERVER['HTTP_REFERER']);
                    $data['price'] = $_POST['price'];
                    if(empty($data['price'])){
                        alert('error','请填写金额', $_SERVER['HTTP_REFERER']);
                    }
                    $data['customer_id'] = intval($_POST['customer_id'])?intval($_POST['customer_id']):alert('error',L('PLEASE_SELECT_CUSTOMERS'),$_SERVER['HTTP_REFERER']);
                    $data['contract_id'] = intval($_POST['contract_id']);
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    $data['creator_role_id'] = session('role_id');
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):session('role_id');
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $data['status'] = 0;
                    
                    if($id = $receivables->add($data)){
                        if(intval($_POST['check_add_order']) == 1){
                            $data_order['name'] = (trim($_POST['order_name']) && (trim($_POST['order_name']) != L('AUTOMATIC_GENERATION')))?trim($_POST['name']):'5kcrm'.date('Ymd').mt_rand(1000,9999);
                            $data_order['money'] = trim($_POST['order_money'])?trim($_POST['order_money']):alert('error',L('PLEASE_FILL_IN_THE_AMOUNT'),$_SERVER['HTTP_REFERER']);
                            $data_order['status'] = $_POST['order_status'];
                            $data_order['description'] = trim($_POST['order_description']);
                            $data_order['pay_time'] = strtotime($_POST['order_pay_time'])?strtotime($_POST['order_pay_time']):time();
                            $data_order['creator_role_id'] = session('role_id');
                            $data_order['owner_role_id'] = $data['owner_role_id'];
                            $data_order['create_time'] = time();
                            $data_order['receivables_id'] = $id;
                            $ro_id = M('receivingorder')->add($data_order);
                            actionLog($ro_id,'t=receivingorder');
                            if($_POST['order_status'] == 1){
                                $receivables = M('receivables')->where(array('receivables_id'=>$id))->find();
                                
                                if($data_order['money'] >= $receivables['price']){
                                    M('receivables')->where(array('receivables_id'=>$id))->setField('status','2');
                                }elseif($data_order['money'] > 0){
                                    M('receivables')->where(array('receivables_id'=>$id))->setField('status','1');
                                }
                            }
                        }
                        if($_POST['submit'] == L('SAVE')){
                            actionLog($id,'t=receivables');
                            if($_POST['refer_url']){
                                alert('success',L('ADD SUCCESS',array('')),$_POST['refer_url']);
                            }else{
                                alert('success',L('ADD SUCCESS',array('')),U('finance/index', 't=receivables'));
                            }
                        }else{
                            alert('success',L('ADD SUCCESS',array('')),$_SERVER['HTTP_REFERER']);
                        }
                    }else{
                        alert('error',L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->alert = parseAlert();
                    $this->display('receivablesadd');
                }
                break;
            case 'payables' :
                if($_POST['submit']){
                    $payables = M('payables');
                    $data['name'] = trim($_POST['name'])?trim($_POST['name']):alert('error',L('PLEASE_FILL_IN_THE_NAME'),$_SERVER['HTTP_REFERER']);
                    $data['price'] = $_POST['price'];
                    if(empty($data['price'])){
                        alert('error','请填写金额', $_SERVER['HTTP_REFERER']);
                    }
//                    $data['customer_id'] = intval($_POST['customer_id'])?intval($_POST['customer_id']):alert('error',L('PLEASE_SELECT_CUSTOMERS'),$_SERVER['HTTP_REFERER']);
                    $data['contract_id'] = intval($_POST['contract_id']);
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    $data['creator_role_id'] = session('role_id');
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):session('role_id');
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $data['status'] = 0;
//                    var_dump($_POST);exit;

                    if($id = $payables->add($data)){
                        if(intval($_POST['check_add_order']) == 1){
                            $data_order['name'] = (trim($_POST['order_name']) && (trim($_POST['order_name']) != L('AUTOMATIC_GENERATION')))?trim($_POST['name']):'5kcrm'.date('Ymd').mt_rand(1000,9999);
                            $data_order['money'] = trim($_POST['order_money'])?trim($_POST['order_money']):alert('error',L('PLEASE_FILL_IN_THE_AMOUNT'),$_SERVER['HTTP_REFERER']);
                            $data_order['status'] = $_POST['order_status'];
                            $data_order['description'] = trim($_POST['order_description']);
                            $data_order['pay_time'] = strtotime($_POST['order_pay_time'])?strtotime($_POST['order_pay_time']):time();
                            $data_order['creator_role_id'] = session('role_id');
                            $data_order['owner_role_id'] = $data['owner_role_id'];
                            $data_order['create_time'] = time();
                            $data_order['payables_id'] = $id;
                            $po_id = M('paymentorder')->add($data_order);
                            actionLog($po_id,'t=paymentorder');

                            if($_POST['order_status'] == 1){
                                $payables = M('payables')->where(array('payables_id'=>$id))->find();

                                if($data_order['money'] >= $payables['price']){
                                    M('payables')->where(array('payables_id'=>$id))->setField('status','2');
                                }elseif($data_order['money'] > 0){
                                    M('payables')->where(array('payables_id'=>$id))->setField('status','1');
                                }
                            }
                        }

                        if($_POST['submit'] == L('SAVE')){
                            actionLog($id,'t=payables');
                            if($_POST['refer_url']){
                                alert('success',L('ADD SUCCESS',array('')),$_POST['refer_url']);
                            }else{
                                alert('success',L('ADD SUCCESS',array('')),U('finance/index', 't=payables'));
                            }
                        }else{
                            alert('success',L('ADD SUCCESS',array('')),$_SERVER['HTTP_REFERER']);
                        }
                    }else{
                        alert('error',L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{

                    $this->alert = parseAlert();
                    $this->display('payablesadd');
                }
                break;
            case 'receivingorder' :
                if($_POST['submit']){
                    $receivingorder = M('receivingorder');
                    $data['name'] = (trim($_POST['name']) && (trim($_POST['name']) != L('AUTOMATIC_GENERATION')))?trim($_POST['name']):'PO'.date('Ymd').mt_rand(1000,9999);
                    $data['money'] = $_POST['money'];
                    $data['receivables_id'] = intval($_POST['receivables_id'])?intval($_POST['receivables_id']):alert('error',L('PLEASE_SELECT_RECEIVABLES'),$_SERVER['HTTP_REFERER']);
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    $data['creator_role_id'] = session('role_id');
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    $data['create_time'] = time();
                    $data['status'] = $_POST['status'];
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }
                    
                    if($receivingorder->add($data)){
                        actionLog($id,'t=receivingorder');
                        $receivables = M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->find();
                        $moneys = $receivingorder->where(array('receivables_id'=>$data['receivables_id'],'status'=>1))->select();
                        foreach($moneys as $money){
                            $money_sum += $money['money'];
                        }
                        if($money_sum >= $receivables['price']){
                            M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->save(array('status'=>2));
                        }elseif($money_sum > 0){
                            M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->save(array('status'=>1));
                        }
                        if($_POST['submit'] == L('SAVE')){
                            alert('success', L('ADD SUCCESS',array('')), U('finance/index','t='.$this->type));
                        }else{
                            alert('success',L('ADD SUCCESS',array('')),$_SERVER['HTTP_REFERER']);
                        }
                    }else{
                        alert('error',L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->alert = parseAlert();
                    $this->display('receivingorderadd');
                }
                break;
            case 'paymentorder' :
                if($_POST['submit']){
                    $data = I('post.');
                    $paymentorder = M('paymentorder');
                    $data['name'] = (trim($_POST['name']) && (empty(trim($_POST['name']))))?trim($_POST['name']):'PO'.date('Ymd').mt_rand(1000,9999);
//                    /** 0表示其他支出,与应付款无关 */
//                    $data['payables_id'] = intval($_POST['payables_id'])?intval($_POST['payables_id']):0;

                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
//                    $data['creator_role_id'] = session('role_id');
//                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    $data['create_time'] = time();
                    $data['status'] = $_POST['status'];
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }

                    if($paymentorder_id = $paymentorder->add($data)){
                        if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
                            import('@.ORG.UploadFile');
                            $upload = new UploadFile();
                            $upload->maxSize = 20000000;
                            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
                            $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                            if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                                alert('error',L("ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE"),U('finance/index','t=paymentorder'));
                            }
                            $upload->savePath = $dirname;
                            if ($upload->upload()){
                                $info =  $upload->getUploadFileInfo();
                            }
                            if(is_array($info[0]) && !empty($info[0])){
                                $paymentorder_image = M('paymentorder_image');
                                foreach ($info as $imageInfo){
                                    $paymentorder_image_data['paymentorder_id'] = $paymentorder_id;
                                    $paymentorder_image_data['paymentorder_image'] = $imageInfo['savepath'].$imageInfo['savename'];
                                    $paymentorder_image->add($paymentorder_image_data);
                                }
                            }
                        }
                        if($_POST['submit'] == L('SAVE')){
                            alert('success', L('ADD SUCCESS',array('')),  U('finance/index','t='.$this->type));
                        }else{
                            alert('success',L('ADD SUCCESS', [ '支出信息' ]));
                            echo "<script>window.history.back()</script>";
                        }
                    }else{
                        alert('error',L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->alert = parseAlert();
                    $this->display('paymentorderadd');
                }
                break;
            case 'otherrevenue' :
                if($_POST['submit']){
                    $data = I('post.');
                    $otherrevenue = M('otherrevenue');
                    $data['other_receiving_num'] = 'OR'.date('Ymd').mt_rand(1000,9999);
                    $data['name'] =  I('post.name', '');
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    $data['receiving_way'] = I('post.receiving_way');
                    $data['create_time'] = time();
                    $data['status'] = $_POST['status'];
                    $data['creator_role_id'] = session('role_id');
                    $data['owner_role_id'] = I('post.owner_role_id', session('role_id'));
                    $data['from'] = I('post.from', '未知来源');
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }
                    if($otherrevenue_id = $otherrevenue->add($data)){
                        if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
                            import('@.ORG.UploadFile');
                            $upload = new UploadFile();
                            $upload->maxSize = 20000000;
                            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
                            $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                            if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                                alert('error',L("ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE"),U('finance/index','t=otherrevenue'));
                            }
                            $upload->savePath = $dirname;
                            if ($upload->upload()){
                                $info =  $upload->getUploadFileInfo();
                            }
                            if(is_array($info[0]) && !empty($info[0])){
                                $otherrevenue_image = M('otherrevenue_image');
                                foreach ($info as $imageInfo){
                                    $otherrevenue_image_data['otherrevenue_id'] = $otherrevenue_id;
                                    $otherrevenue_image_data['otherrevenue_image'] = $imageInfo['savepath'].$imageInfo['savename'];
                                    $otherrevenue_image->add($otherrevenue_image_data);
                                }
                            }
                        }
                        if($_POST['submit'] == L('SAVE')){
                            alert('success', L('ADD SUCCESS',array('')),  U('finance/index','t='.$this->type));
                        }else{
                            alert('success',L('ADD SUCCESS', [ '其它收入' ]));
                            echo "<script>window.history.back()</script>";
                        }
                    }else{
                        alert('error',L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->alert = parseAlert();
                    $this->display('otherrevenueadd');
                }
                break;
        }
    }

    public function edit(){
        $id = intval($_REQUEST['id']);
        if($id == 0) alert('error',L('PARAMETER_ERROR'),U('finance/index','t='.$this->type));
        $cost_item_dept = M('cost_item')->where('level=%d', 1)->select();
        $cost_item = M('cost_item')->where('level=%d', 2)->select();
        $this->assign('cost_item',$cost_item);
        $this->assign('cost_item_dept',$cost_item_dept);
        switch ($this->type) {
            case 'receivables' :
                
                $receivables = D('ReceivablesView');
                $info = $receivables->where(array('receivables_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                if($_POST['submit']){
                    $data['name'] = trim($_POST['name'])?trim($_POST['name']):alert('error',L('PLEASE_FILL_IN_THE_NAME'),$_SERVER['HTTP_REFERER']);
                    $data['price'] = $_POST['price'];
                    $data['customer_id'] = intval($_POST['customer_id'])?intval($_POST['customer_id']):alert('error',L('PLEASE_SELECT_CUSTOMERS'),$_SERVER['HTTP_REFERER']);
                    $data['contract_id'] = intval($_POST['contract_id']);
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    
                    if(M('receivables')->where(array('receivables_id'=>$id))->save($data)){
                        actionLog($id,'t=receivables');
                        if($_POST['refer_url']){
                            alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
                        }else{
                            alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
                        }
                    }else{
                        alert('error',L('EDIT FAILED',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->assign('info',$info);
                    $this->alert = parseAlert();
                    $this->display('receivablesedit');
                }
                break;
            case 'payables' :
                $payables = D('PayablesView');
                $info = $payables->where(array('payables_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                if($_POST['submit']){
                    $data['name'] = trim($_POST['name'])?trim($_POST['name']):alert('error',L('PLEASE_FILL_IN_THE_NAME'),$_SERVER['HTTP_REFERER']);
                    $data['price'] = $_POST['price'];
                    $data['customer_id'] = intval($_POST['customer_id'])?intval($_POST['customer_id']):alert('error',L('PLEASE_SELECT_CUSTOMERS'),$_SERVER['HTTP_REFERER']);
                    $data['contract_id'] = intval($_POST['contract_id']);
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    $data['description'] = trim($_POST['description']);
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    
                    if(M('payables')->where(array('payables_id'=>$id))->save($data)){
                        actionLog($id,'t=payables');
                        if($_POST['refer_url']){
                            alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
                        }else{
                            alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
                        }
                    }else{
                        alert('error',L('EDIT FAILED',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->assign('info',$info);
                    $this->alert = parseAlert();
                    $this->display('payablesedit');
                }
                break;
            case 'receivingorder' :
                $receivingorder = D('ReceivingorderView');
                $info = $receivingorder->where(array('receivingorder_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                if($info['status'] == 1) alert('error',L('THE RECEIVABLES ORDER HAS BEEN CLOSING'),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                if($_POST['submit']){
                    $data['name'] = trim($_POST['name']);
                    $data['money'] = $_POST['money'];
                    $data['receivables_id'] = intval($_POST['receivables_id'])?intval($_POST['receivables_id']):alert('error',L('PLEASE_SELECT_PAYABLES'),$_SERVER['HTTP_REFERER']);
                    $data['description'] = trim($_POST['description']);
                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    if($info['owner_role_id'] == session('role_id')){
                        $data['status'] = intval($_POST['status']);
                    }
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }
                    
                    if(M('receivingorder')->where(array('receivingorder_id'=>$id))->save($data)){
                        actionLog($id,'t=receivingorder');
                        $receivables = M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->find();
                        $moneys = $receivingorder->where(array('receivables_id'=>$data['receivables_id']))->select();
                        foreach($moneys as $money){
                            $money_sum += $money['money'];
                        }
                        if($money_sum >= $receivables['price']){
                            M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->save(array('status'=>2));
                        }elseif($money > 0){
                            M('receivables')->where(array('receivables_id'=>$data['receivables_id']))->save(array('status'=>1));
                        }
                        if($_POST['refer_url'])
                        {
                           alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
                        }
                        alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));

                    }else{
                        alert('error',L('EDIT FAILED',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->assign('info',$info);
                    $this->refer_url=$_SERVER['HTTP_REFERER'];
                    $this->alert = parseAlert();
                    $this->display('receivingorderedit');
                }
                break;
            case 'paymentorder' :
                $data = I('post.');
//                $paymentorder = D('PaymentorderView');
                $paymentorder = M('Paymentorder');
                $info = $paymentorder->where(array('paymentorder_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                if($info['status'] == 1) alert('error',L('THE PAYMENT ORDER HAS BEEN CLOSING'),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                if($_POST['submit']){
//                    $data['name'] = trim($_POST['name']);
                    $data['money'] = $_POST['money'];
//                    $data['payables_id'] = intval($_POST['payables_id'])?intval($_POST['payables_id']):alert('error',L('PLEASE_SELECT_PAYABLES'),$_SERVER['HTTP_REFERER']);
                    $data['description'] = trim($_POST['description']);
//                    $data['owner_role_id'] = intval($_POST['owner_role_id'])?intval($_POST['owner_role_id']):alert('error',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),$_SERVER['HTTP_REFERER']);
                    if($info['owner_role_id'] == session('role_id')){
                        $data['status'] = intval($_POST['status']);
                    }
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }
                    if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
                        import('@.ORG.UploadFile');
                        $upload = new UploadFile();
                        $upload->maxSize = 20000000;
                        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
                        $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                            alert('error',L("ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE"),U('finance/index','t=paymentorder'));
                        }
                        $upload->savePath = $dirname;
//                        if(!$upload->upload()) {
//                            alert('error',$upload->getErrorMsg(), U('finance/index','t=paymentorder'));
//                        }else{
//                            $info =  $upload->getUploadFileInfo();
//                        }
                        if ($upload->upload()){
                            $info =  $upload->getUploadFileInfo();
                        }
                        if(is_array($info[0]) && !empty($info[0])){
                            $paymentorder_image = M('paymentorder_image');
                            foreach ($info as $imageInfo){
                                $paymentorder_image_data['paymentorder_id'] = $id;
                                $paymentorder_image_data['paymentorder_image'] = $imageInfo['savepath'].$imageInfo['savename'];
                                $paymentorder_image->add($paymentorder_image_data);
                            }
                        }
//                        else{
//                            alert('error',L('LOGO EDIT FAILED'), U('finance/index','t=paymentorder'));
//                        }
                    }
                    if(M('paymentorder')->where(array('paymentorder_id'=>$id))->save($data)!==false){
                        actionLog($id,'t=paymentorder');
                        $payables = M('payables')->where(array('payables_id'=>$data['payables_id']))->find();
                        $moneys = $paymentorder->where(array('payables_id'=>$data['payables_id']))->select();
                        foreach($moneys as $money){
                            $money_sum += $money['money'];
                        }
                        if($money_sum >= $payables['price']){
                            M('payables')->where(array('payables_id'=>$data['payables_id']))->save(array('status'=>2));
                        }elseif($money > 0){
                            M('payables')->where(array('payables_id'=>$data['payables_id']))->save(array('status'=>1));
                        }
                        alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
                    }else{
                        alert('error',L('EDIT FAILED',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->assign('info',$info);
                    $paymentorder_image = M('paymentorder_image') -> where('paymentorder_id = %d', $id) -> select();
                    $this ->assign('paymentorder_image', $paymentorder_image);
                    $this->alert = parseAlert();
                    $this->display('paymentorderedit');
                }
                break;
            case 'otherrevenue' :
                $data = I('post.');
                $otherrevenue = M('otherrevenue');
                $info = $otherrevenue->alias('otherrevenue')
                    ->field('otherrevenue.*, user.name create_name')
                    ->join(C('DB_PREFIX').'user user ON otherrevenue.creator_role_id = user.role_id')->where(array('otherrevenue.otherrevenue_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                if($info['status'] == 1) alert('error',L('THE PAYMENT ORDER HAS BEEN CLOSING'),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                if($_POST['submit']){
                    $data['money'] = $_POST['money'];
                    $data['description'] = trim($_POST['description']);
                    if($info['owner_role_id'] == session('role_id')){
                        $data['status'] = intval($_POST['status']);
                    }
                    $data['pay_time'] = strtotime($_POST['pay_time'])?strtotime($_POST['pay_time']):time();
                    if($data['status'] == 1){
                        $data['update_time'] = time();
                    }
                    if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
                        import('@.ORG.UploadFile');
                        $upload = new UploadFile();
                        $upload->maxSize = 20000000;
                        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
                        $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                            alert('error',L("ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE"),U('finance/index','t=otherrevenue'));
                        }
                        $upload->savePath = $dirname;
                        if ($upload->upload()){
                            $info =  $upload->getUploadFileInfo();
                        }
                        if(is_array($info[0]) && !empty($info[0])){
                            $otherrevenue_image = M('otherrevenue_image');
                            foreach ($info as $imageInfo){
                                $otherrevenue_image_data['otherrevenue_id'] = $id;
                                $otherrevenue_image_data['otherrevenue_image'] = $imageInfo['savepath'].$imageInfo['savename'];
                                $otherrevenue_image->add($otherrevenue_image_data);
                            }
                        }
                    }
                    if(M('otherrevenue')->where(array('otherrevenue_id'=>$id))->save($data)!==false){
                        alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
                    }else{
                        alert('error',L('EDIT FAILED',array('')),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    $this->assign('info',$info);
                    $otherrevenue_image = M('otherrevenue_image') -> where('otherrevenue_id = %d', $id) -> select();
                    $this ->assign('otherrevenue_image', $otherrevenue_image);
                    $this->alert = parseAlert();
                    $this->display('otherrevenueedit');
                }
                break;
        }
    }

    public function view(){
        $id = intval($_GET['id']);
        if($id == 0) alert('error',L('PARAMETER_ERROR'),U('finance/index','t='.$this->type));
        switch ($this->type) {
            
            case 'receivables' :
                $receivables = D('ReceivablesView');
                $receivingorder = D('ReceivingorderView');
                $info = $receivables->where(array('receivables_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['receivingorder'] = $receivingorder->where('receivingorder.is_deleted <> 1 and receivingorder.receivables_id = %d', $id)->select();
                $num = 0;                   //已收款金额
                $num_unCheckOut = 0;        //未结账状态的金额
                $num_unReceivables = 0;     //还剩多少金额未收款
                foreach($info['receivingorder'] as $k=>$v){
                    if($v['status'] == 1){
                        //计算已结账状态的金额
                        $info['receivingorder'][$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                        $num = $num + $v['money'];
                    }else{
                        //未结账状态的金额
                        $info['receivingorder'][$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                        $num_unCheckOut = $num_unCheckOut + $v['money'];
                    }
                }
                $num_unReceivables = ($info['price'] - $num) < 0 ? 0 : ($info['price'] - $num);
                $info['num'] = $num;
                $info['num_unReceivables'] = $num_unReceivables;
                $info['num_unCheckOut'] = $num_unCheckOut;
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                $this->assign('info',$info);
                $this->alert = parseAlert();
                $this->display('receivablesview');
                break;
            case 'payables' :
                $payables = D('PayablesView');
                $paymentorder = D('PaymentorderView');
                $info = $payables->where(array('payables_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['paymentorder'] = $paymentorder->where('paymentorder.is_deleted <> 1 and paymentorder.payables_id = %d', $id)->select();
                $num = 0;                   //已付款金额
                $num_unCheckOut = 0;        //未结账状态的金额
                $num_unPayment = 0;         //还剩多少金额未付款
                foreach($info['paymentorder'] as $k=>$v){
                    if($v['status'] == 1 ){
                        //计算已结账状态的金额
                        $info['paymentorder'][$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                        $num += $v['money'];
                    }else{
                        //未结账状态的金额
                        $info['paymentorder'][$k]['owner'] = getUserByRoleId($v['owner_role_id']);
                        $num_unCheckOut += $v['money'];
                    }
                }
                $num_unPayment = ($info['price'] - $num) < 0 ? 0 : ($info['price'] - $num);
                $info['num'] = $num;
                $info['num_unPayment'] = $num_unPayment;
                $info['num_unCheckOut'] = $num_unCheckOut;
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                $this->assign('info',$info);
                $this->alert = parseAlert();
                $this->display('payablesview');
                break;
            case 'receivingorder' :
                $receivingorder = D('ReceivingorderView');
                $info = $receivingorder->where(array('receivingorder_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                $contract_id = M('receivables')->where(array('receivables_id'=>$info['receivables_id']))->getField('contract_id');
                $info['other'] = D('ContractView')->where(array('contract_id'=>$contract_id))->find();
                $this->assign('info',$info);
                $this->alert = parseAlert();
                $this->display('receivingorderview');
                break;
            case 'paymentorder' :
//                $paymentorder = D('PaymentorderView');
                $paymentorder = M('paymentorder');
                $info = $paymentorder->alias('paymentorder')
                    -> join(C('DB_PREFIX').'cost_item cost_item ON cost_item.cost_item_id = paymentorder.payable')
                    -> where(array('paymentorder.paymentorder_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                $contract_id = M('payables')->where(array('payables_id'=>$info['payables_id']))->getField('contract_id');
                $info['other'] = D('ContractView')->where(array('contract_id'=>$contract_id))->find();
                $this->assign('info',$info);
                $paymentorder_image = M('paymentorder_image') -> where('paymentorder_id = %d', $id) -> select();
                $this ->assign('paymentorder_image', $paymentorder_image);
                $this->alert = parseAlert();
                $this->display('paymentorderview');
                break;
            case 'otherrevenue' :
                $otherrevenue = M('otherrevenue');
                $info = $otherrevenue->alias('otherrevenue')
                    ->field('otherrevenue.*, user.name create_name')
                    ->join(C('DB_PREFIX').'user user ON otherrevenue.creator_role_id = user.role_id')
                    ->where(array('otherrevenue.otherrevenue_id'=>$id))->find();
                if(empty($info)) alert('error',L('RECORD NOT EXIST',array('')),U('finance/index','t='.$this->type));
                $info['owner'] = getUserByRoleId($info['owner_role_id']);
                $this->assign('info',$info);
                $otherrevenue_image = M('otherrevenue_image') -> where('otherrevenue_id = %d', $id) -> select();
                $this ->assign('otherrevenue_image', $otherrevenue_image);
                $this->alert = parseAlert();
                $this->display('otherrevenueview');
                break;
        }
    }

    public function delete(){
        switch ($this->type) {
            case 'receivables' :
                $receivables_ids = is_array($_REQUEST['receivables_id']) ? implode(',', $_REQUEST['receivables_id']) : $_REQUEST['id'];
                if($receivables_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $receivables = M('receivables');
                $receivingorder = M('Receivingorder');
                //如果应收款下有收款单记录，提示先删除收款单
                $error_tip = '';
                $receivables_record = $receivables->where('is_deleted <> 1 and receivables_id in ('.$receivables_ids.')')->select();
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                foreach($receivables_record as $k=>$v){
                    $receivingorder_record = $receivingorder->where('receivables_id = %d',$v['receivables_id'])->count();
                    if($receivingorder_record == 0){
                        if(!$receivables->where('receivables_id = %d', $v)->setField($data)){
                            alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                        }else{
                            M('r_order_product')->where('order_id = %d', $v)->setField($data);
                        }
                    }else{
                        $error_tip .= $v['name'].',';
                        actionLog($v,'t=receivables');
                    }       
                }
                if($error_tip){
                    alert('error',L('PARTIAL DELETION FAILED',array($error_tip)),$_SERVER['HTTP_REFERER']);
                }else{
                    if($_GET['refer']){
                        alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('success',L('DELETED SUCCESSFULLY'),U('finance/index','t='.$this->type));
                    }
                }
                break;
            case 'payables' :
                $payables_ids = is_array($_REQUEST['payables_id']) ? implode(',', $_REQUEST['payables_id']) : $_REQUEST['id'];
                if($payables_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                
                $payables = M('Payables');
                $paymentorder = M('Paymentorder');
                //如果应付款下有付款单记录，提示先删除付款单
                $error_tip = '';
                $payables_record = $payables->where('is_deleted <> 1 and payables_id in ('.$payables_ids.')')->select();
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                foreach($payables_record as $k=>$v){
                    $paymentorder_record = $paymentorder->where('payables_id = %d',$v['payables_id'])->count();
                    if($paymentorder_record == 0){
                        if(!$payables->where('payables_id = %d', $v)->setField($data)){
                            actionLog($v,'t=payables');
                            alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                        }
                    }else{
                        $error_tip .= $v['name'].',';
                        actionLog($v,'t=payables');
                    }
                }
                if($error_tip){
                    alert('error',L('PARTIAL DELETION FAILED',array($error_tip)),$_SERVER['HTTP_REFERER']);
                }else{
                    if($_GET['refer']){
                        alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('success',L('DELETED SUCCESSFULLY'),U('finance/index','t='.$this->type));
                    }
                }
                break;
            case 'receivingorder' : 
                $receivingorder_ids = is_array($_REQUEST['receivingorder_id']) ? implode(',', $_REQUEST['receivingorder_id']) : $_REQUEST['id'];
                if($receivingorder_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $receivingorder = M('receivingorder');
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                if($receivingorder->where('receivingorder_id in (%s)', $receivingorder_ids)->setField($data)){
                    $receivingorder_idsArr = explode(',',$receivingorder_ids);
                    foreach($receivingorder_idsArr as $v){
                        actionLog($v,'t=receivingorder');
                    }
                    if($_GET['refer']){
                        alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('success',L('DELETED SUCCESSFULLY'),U('finance/index','t='.$this->type));
                    }
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'paymentorder' :
                $paymentorder_ids = is_array($_REQUEST['paymentorder_id']) ? implode(',', $_REQUEST['paymentorder_id']) : $_REQUEST['id'];
                if($paymentorder_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $paymentorder = M('paymentorder');
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                if($paymentorder->where('paymentorder_id in (%s)', $paymentorder_ids)->setField($data)){
                    $paymentorder_idsArr = explode(',',$paymentorder_ids);
                    foreach($paymentorder_idsArr as $v){
                        actionLog($v,'t=paymentorder');
                    }
                    if($_GET['refer']){
                        alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('success',L('DELETED SUCCESSFULLY'),U('finance/index','t='.$this->type));
                    }
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'otherrevenue' :
                $otherrevenue_ids = is_array($_REQUEST['otherrevenue_id']) ? implode(',', $_REQUEST['otherrevenue_id']) : $_REQUEST['id'];
                if($otherrevenue_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $otherrevenue = M('otherrevenue');
                $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
                if($otherrevenue->where('otherrevenue_id in (%s)', $otherrevenue_ids)->setField($data)){
                    $otherrevenue_idsArr = explode(',',$otherrevenue_ids);
                    foreach($otherrevenue_idsArr as $v){
                        actionLog($v,'t=otherrevenue');
                    }
                    if($_GET['refer']){
                        alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('success',L('DELETED SUCCESSFULLY'),U('finance/index','t='.$this->type));
                    }
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
        }
    }

    public function revert(){
        $id = intval($_GET['id']);
        if($id == 0) alert('error',L('NOT CHOOSE ANY'),$_SERVER['HTTP_REFERER']);
        switch ($this->type) {
            case 'receivables' :
                $receivables = M('receivables');
                $info = $receivables->where('receivables_id = %s', $id)->find();
                if (session('?admin') || $info['delete_role_id'] == session('role_id')) {
                    if($receivables->where('receivables_id = %s', $id)->setField('is_deleted', 0)){
                        M('r_order_product')->where('order_id = %d', $id)->setField('is_deleted', 0);
                        actionLog($id,'t=receivables');
                        alert('success',L('RESTORE SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error',L('RESTORE FAILURE'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'payables' :
                $payables = M('payables');
                $info = $payables->where('payables_id = %s', $id)->find();
                if (session('?admin') || $info['delete_role_id'] == session('role_id')) {
                    if($payables->where('payables_id = %s', $id)->setField('is_deleted', 0)){
                        actionLog($id,'t=payables');
                        alert('success',L('RESTORE SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error',L('RESTORE FAILURE'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'receivingorder' :
                $receivingorder = M('receivingorder');
                $info = $receivingorder->where('receivingorder_id = %s', $id)->find();
                if (session('?admin') || $info['delete_role_id'] == session('role_id')) {
                    if($receivingorder->where('receivingorder_id = %s', $id)->setField('is_deleted', 0)){
                        actionLog($id,'t=receivingorder');
                        alert('success',L('RESTORE SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error',L('RESTORE FAILURE'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'paymentorder' :
                $paymentorder = M('paymentorder');
                $info = $paymentorder->where('paymentorder_id = %s', $id)->find();
                if (session('?admin') || $info['delete_role_id'] == session('role_id')) {
                    if($paymentorder->where('paymentorder_id = %s', $id)->setField('is_deleted', 0)){
                        actionLog($id,'t=paymentorder');
                        alert('success',L('RESTORE SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error',L('RESTORE FAILURE'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'otherrevenue' :
                $otherrevenue = M('otherrevenue');
                $info = $otherrevenue->where('otherrevenue_id = %s', $id)->find();
                if (session('?admin') || $info['delete_role_id'] == session('role_id')) {
                    if($otherrevenue->where('otherrevenue_id = %s', $id)->setField('is_deleted', 0)){
                        actionLog($id,'t=otherrevenue');
                        alert('success',L('RESTORE SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                    }else{
                        alert('error',L('RESTORE FAILURE'),$_SERVER['HTTP_REFERER']);
                    }
                }else{
                    alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
                }
                break;
        }
    }

    public function completedelete(){
        switch ($this->type) {
            case 'receivables' :
                $receivables_ids = is_array($_REQUEST['receivables_id']) ? implode(',', $_REQUEST['receivables_id']) : $_REQUEST['id'];
                if($receivables_ids == '') alert('error',L('NOT CHOOSE ANY'),$_SERVER['HTTP_REFERER']);
                $receivables = M('receivables');
                if($receivables->where('receivables_id in (%s)', $receivables_ids)->delete()){
                    M('r_order_product')->where('order_id in (%s)', $receivables_ids)->delete();
                    $receivables_idsArr = explode(',',$receivables_ids);
                    foreach($receivables_idsArr as $v){
                        actionLog($v,'t=receivables');
                    }
                    alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'payables' :
                $payables_ids = is_array($_REQUEST['payables_id']) ? implode(',', $_REQUEST['payables_id']) : $_REQUEST['id'];
                if($payables_ids == '') alert('error','没有选中任何信息',$_SERVER['HTTP_REFERER']);
                $payables = M('payables');
                if($payables->where('payables_id in (%s)', $payables_ids)->delete()){
                    $payables_idsArr = explode(',',$payables_ids);
                    foreach($payables_idsArr as $v){
                        actionLog($v,'t=payables');
                    }
                    alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'receivingorder' :
                $receivingorder_ids = is_array($_REQUEST['receivingorder_id']) ? implode(',', $_REQUEST['receivingorder_id']) : $_REQUEST['id'];
                if($receivingorder_ids == '') alert('error',L('NOT CHOOSE ANY'),$_SERVER['HTTP_REFERER']);
                $receivingorder = M('receivingorder');
                if($receivingorder->where('receivingorder_id in (%s)', $receivingorder_ids)->delete()){
                    $receivingorder_idsArr = explode(',',$receivingorder_ids);
                    foreach($receivingorder_idsArr as $v){
                        actionLog($v,'t=receivingorder');
                    }
                    alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'paymentorder' :
                $paymentorder_ids = is_array($_REQUEST['paymentorder_id']) ? implode(',', $_REQUEST['paymentorder_id']) : $_REQUEST['id'];
                if($paymentorder_ids == '') alert('error',L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
                $paymentorder = M('paymentorder');
                if($paymentorder->where('paymentorder_id in (%s)', $paymentorder_ids)->delete()){
                    $paymentorder_idsArr= explode(',',$paymentorder_ids);
                    foreach($paymentorder_idsArr as $v){
                        actionLog($v,'t=paymentorder');
                    }
                    $otherrevenue_images = M('paymentorder_image')->where('paymentorder_id in (%s)', $paymentorder_ids)->getField('paymentorder_image', true);
                    M('paymentorder_image')->where('paymentorder_id in (%s)', $paymentorder_ids)->delete();
                    foreach ($otherrevenue_images as $otherrevenue_image){
                        unlink($otherrevenue_image);
                    }
                    alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'otherrevenue' :
                $otherrevenue_ids = is_array($_REQUEST['otherrevenue_id']) ? implode(',', $_REQUEST['otherrevenue_id']) : $_REQUEST['id'];

                if($otherrevenue_ids == '') alert('error',L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
                $otherrevenue = M('otherrevenue');
                if($otherrevenue->where('otherrevenue_id in (%s)', $otherrevenue_ids)->delete()){
                    $otherrevenue_idsArr= explode(',',$otherrevenue_ids);
                    foreach($otherrevenue_idsArr as $v){
                        actionLog($v,'t=otherrevenue');
                    }
                    $otherrevenue_images = M('otherrevenue_image')->where('otherrevenue_id in (%s)', $otherrevenue_ids)->getField('otherrevenue_image', true);
                    M('otherrevenue_image')->where('otherrevenue_id in (%s)', $otherrevenue_ids)->delete();
                    foreach ($otherrevenue_images as $otherrevenue_image){
                        unlink($otherrevenue_image);
                    }
                    alert('success',L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
                }
                break;
        }
    }

    public function listdialog(){
        $receivables = D('ReceivablesView');
        $all_ids = implode(',', getSubRoleId());
        switch ($this->type) {
            case 'receivables' :
                $list = $receivables->where("receivables.is_deleted = 0 and receivables.status <> 2 and receivables.owner_role_id in($all_ids)")->order('receivables.update_time desc')->limit(10)->select();
                $count = $receivables->where("receivables.is_deleted = 0 and receivables.status <> 2 and receivables.owner_role_id in($all_ids)")->count();
    
                $this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
                $this->count_num = $count;
                $this->assign('receivablesList',$list);
                $this->display('receivableslistdialog');
                break;
            case 'payables' :
                $payables = D('PayablesView');
                
                $this->payablesList = $payables->where("payables.is_deleted = 0 and payables.status <> 2 and payables.owner_role_id in($all_ids)")->order('payables.update_time desc')->limit(10)->select();
                $count = $payables->where("payables.is_deleted = 0 and payables.status <> 2 and payables.owner_role_id in($all_ids)")->count();
                $this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
                $this->count_num = $count;
                $this->display('payableslistdialog');
                break;
        }
    }

    public function adddialog(){
        $contract_id = $this->_get('contract_id','intval',0);
        if($contract_id == 0){
            $id = $this->_get('id','intval',0);
            $this->assign('id',$id);
        }else{
            $contract_id = intval($_GET['contract_id']);
            $this->assign('contract_id',$contract_id);
            $business_id = M('contract')->where(array('contract_id'=>$contract_id))->getField('business_id');
            $customer_id = M('business')->where(array('business_id'=>$business_id))->getField('customer_id');
            $this->assign('customer_id',$customer_id);
        }
        switch ($this->type) {
            case 'receivables' :
                $this->refer_url = $_SERVER['HTTP_REFERER'];
                $this->display('receivablesadddialog');
                break;
            case 'payables' :
                $this->refer_url = $_SERVER['HTTP_REFERER'];
                $this->display('payablesadddialog');
                break;
            case 'receivingorder' :
                $m_receivables = M('Receivables');
                $m_receivingorder = M('Receivingorder');
                $receivables = $m_receivables->where('is_deleted <> 1 and receivables_id = %d',$id)->find();
                $receivingorder = $m_receivingorder->where('is_deleted <> 1 and receivables_id = %d',$receivables['receivables_id'])->select();
                
                $receivables_money = 0;//已收款总计
                foreach($receivingorder as $v){
                    $receivables_money += $v['money'];
                }
                $this->assign('receivables_money',$receivables_money);
                $this->assign('receivables',$receivables);
                $this->display('receivingorderadddialog');
                break;
            case 'paymentorder' :
                $m_payables = M('Payables');
                $m_paymentorder = M('Paymentorder');
                $payables = $m_payables->where('is_deleted <> 1 and payables_id = %d',$id)->find();
                $paymentorder = $m_paymentorder->where('is_deleted <> 1 and payables_id = %d',$payables['payables_id'])->select();
                
                $payables_money = 0;//已收款总计
                foreach($paymentorder as $v){
                    $payables_money += $v['money'];
                }
                $this->assign('payables_money',$payables_money);
                $this->assign('payables',$payables);
                $this->display('paymentorderadddialog');
                break;
        }
    }

    public function editdialog(){
        $id = $this->_get('id','intval',0);
        if($id == 0){
            alert('参数错误');
        }
        switch ($this->type) {
            case 'receivables' :
                $receivables = M('receivables')->where('receivables_id=%d',$id)->find();
                $receivables['owner_name'] = M('user')->where('role_id=%d',$receivables['owner_role_id'])->getField('name');
                $this->refer_url = U('contract/view', 'id='.$receivables['contract_id']);
                $this->receivables = $receivables;
                $this->display('receivableseditdialog');
                break;
            case 'payables' :
                $payables = M('payables')->where('payables_id=%d',$id)->find();
                $payables['owner_name'] = M('user')->where('role_id=%d',$payables['owner_role_id'])->getField('name');
                $this->refer_url = U('contract/view', 'id='.$payables['contract_id']);
                $this->payables = $payables;
                $this->display('payableseditdialog');
                break;
        }
    }

    public function checkout(){
        switch ($this->type) {
            case 'receivingorder' :
                $receivingorder_ids = is_array($_REQUEST['receivingorder_id']) ? implode(',', $_REQUEST['receivingorder_id']) : $_REQUEST['id'];
                if($receivingorder_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $receivingorder = M('receivingorder');
                $data = array('status'=>1);
                if($receivingorder->where('receivingorder_id in (%s)', $receivingorder_ids)->setField($data)){
                    alert('success',L('SUCCESSFUL OPERATION'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('success',L('OPERATION FAILED'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'paymentorder' :
                $paymentorder_ids = is_array($_REQUEST['paymentorder_id']) ? implode(',', $_REQUEST['paymentorder_id']) : $_REQUEST['id'];
                if($paymentorder_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $paymentorder = M('paymentorder');
                $data = array('status'=>1);
                if($paymentorder->where('paymentorder_id in (%s)', $paymentorder_ids)->setField($data)){
                    alert('success',L('OPERATION SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('success',L('OPERATION FAILED'),$_SERVER['HTTP_REFERER']);
                }
                break;
            case 'otherrevenue' :
                $otherrevenue_ids = is_array($_REQUEST['otherrevenue_id']) ? implode(',', $_REQUEST['otherrevenue_id']) : $_REQUEST['id'];
                if($otherrevenue_ids == '') alert('error',L('NOT CHOOSE ANY'),U('finance/index','t='.$this->type));
                $otherrevenue = M('otherrevenue');
                $data = array('status'=>1);
                if($otherrevenue->where('otherrevenue_id in (%s)', $otherrevenue_ids)->setField($data)){
                    alert('success',L('OPERATION SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
                }else{
                    alert('success',L('OPERATION FAILED'),$_SERVER['HTTP_REFERER']);
                }
                break;
        }
    }
    
    /**
     * 根据receivables_id获取应收金额
     *
     **/
    public function getreceivablesmoney(){
        $id = $_GET['id'];
        if($id){
            $m_receivables = M('receivables');
            //应收款总额
            $receivables = $m_receivables->where('receivables_id = %d', $id)->getField('price');
            if(empty($receivables)){
                $receivables = 0;
            }
            //已收款金额
            $m_receivingorder = M('receivingorder');
            $receivingorder = $m_receivingorder->where('receivables_id = %d and status = 1', $id)->sum('money');
            if(empty($receivingorder)){
                $receivingorder = 0;
            }
            $this->ajaxReturn(array('total'=>$receivables, 'receivingorder'=>$receivingorder),'',1);
        }
    }
    
    /**
     * 根据payables_id获取应付金额
     *
     **/
    public function getpayablesmoney(){
        $id = $_GET['id'];
        if($id){
            $m_payables = M('payables');
            //应收款总额
            $payables = $m_payables->where('payables_id = %d', $id)->getField('price');
            if(empty($payables)){
                $payables = 0;
            }
            //已收款金额
            $m_paymentorder = M('paymentorder');
            $paymentorder = $m_paymentorder->where('payables_id = %d and status = 1', $id)->sum('money');
            if(empty($paymentorder)){
                $paymentorder = 0;
            }
            $this->ajaxReturn(array('total'=>$payables, 'paymentorder'=>$paymentorder),'',1);
        }
    }

    /* 按时间统计 */
    public function analytics2($year = null){
        $shoukuan_moon_count = [];
        $fukuan_moon_count = [];
        $shijishoukuan_moon_count = [];
        $shijifukuan_moon_count = [];
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

        // $previous_year = $year-1;
        // $moon = 1;
        // $shoukuan_thisyear_count = array();
        // $shoukuan_previousyear_count = array();
        // $fukuan_thisyear_count = array();
        // $fukuan_previousyear_count = array();
        // while ($moon <= 12){
        //     if($moon == 12) {
        //         $where_thisyear_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
        //         $where_previousyear_shoukuan['pay_time'] = array(array('egt', strtotime($previous_year.'-'.$moon.'-1')), array('lt', strtotime(($previous_year+1).'-1-1')), 'and');
        //     } else {
        //         $where_thisyear_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
        //         $where_previousyear_shoukuan['pay_time'] = array(array('egt', strtotime($previous_year.'-'.$moon.'-1')), array('lt', strtotime($previous_year.'-'.($moon+1).'-1')), 'and');
        //     }
            
        //     $thisyear_shoukuanList = $m_shoukuan->where($where_thisyear_shoukuan)->select();
        //     $previousyear_shoukuanList = $m_shoukuan->where($where_previousyear_shoukuan)->select();
        //     $thisyear_fukuanList = $m_fukuan->where($where_thisyear_shoukuan)->select();
        //     $previousyear_fukuanList = $m_fukuan->where($where_previousyear_shoukuan)->select();
            
        //     $total_thisyear_shoukuan_count = 0;
        //     $total_previousyear_shoukuan_count = 0;
        //     foreach($thisyear_shoukuanList as $v){
        //         $total_thisyear_shoukuan_count += $v['price'];
        //     }
        //     foreach($previousyear_shoukuanList as $v){
        //         $total_previousyear_shoukuan_count += $v['price'];
        //     }
        //     $shoukuan_thisyear_count[] = $total_thisyear_shoukuan_count;
        //     $shoukuan_previousyear_count[] = $total_previousyear_shoukuan_count;
            
        //     $total_thisyear_fukuan_count = 0;
        //     $total_previousyear_fukuan_count = 0;
        //     foreach($thisyear_fukuanList as $v){
        //         $total_thisyear_fukuan_count += $v['price'];
        //     }
        //     foreach($previousyear_fukuanList as $v){
        //         $total_previousyear_fukuan_count += $v['price'];
        //     }
        //     $fukuan_thisyear_count[] = $total_thisyear_fukuan_count;
        //     $fukuan_previousyear_count[] = $total_previousyear_fukuan_count;
            
        //     $moon ++; 
        // }


        
        $year_count['shoukuan_previousyear'] = '['.implode(',', $shoukuan_previousyear_count).']';
        $year_count['shoukuan_thisyear'] = '['.implode(',', $shoukuan_thisyear_count).']';
        $year_count['fukuan_previousyear'] = '['.implode(',', $fukuan_previousyear_count).']';
        $year_count['fukuan_thisyear'] = '['.implode(',', $fukuan_thisyear_count).']';
        $this->year_count = $year_count;

        $this->ajaxReturn([ 'moon_count' => $moon_count, 'type_total' => $type_total]);
    }
    /* 按用户统计 */
    public function analytics(){
        header("Content-type: text/html; charset=utf-8"); 
        
        $m_shoukuan = M('receivables');
        $m_shoukuandan = M('receivingorder');
        $m_fukuan = M('payables');
        $m_fukuandan = M('paymentorder');

        if($_GET['role']) {
            $role_id = intval($_GET['role']);
        }else{
            $role_id = 'all';
        }

        if($_GET['department'] && $_GET['department'] != 'all'){
            $department_id = intval($_GET['department']);
        }else{
            $department_id = D('RoleView')->where('role.role_id = %d', session('role_id'))->getField('department_id'); 
        }

        $start_time = empty($_GET['start_time']) ? strtotime(Date('Y-m-01')): strtotime($_GET['start_time']);

        $end_time = (empty($_GET['end_time']) ?  strtotime(Date('Y-m-d'))  : strtotime($_GET['end_time'])) + 86400;

        if($role_id == "all") {
            $roleList = getRoleByDepartmentId($department_id);
            $role_id_array = array_column($roleList, 'role_id');

            $where_role_id = array('in', implode(',', $role_id_array));
            $where_shoukuan['receivables.owner_role_id'] = $where_role_id;
        }else{
            $where_shoukuan['receivables.owner_role_id'] = $role_id;
        }

        //统计表内容
        $role_id_array = array();
        if($role_id == "all"){
            if($department_id != "all"){
                $roleList = getRoleByDepartmentId($department_id);
                $role_id_array = array_column($roleList, 'role_id');
            }else{
                $role_id_array = getSubRoleId();
            }
        }else{
            $role_id_array[] = $role_id;
        }

        if($start_time){
            $create_time= array(array('elt',$end_time),array('egt',$start_time), 'and');
        }else{
            $create_time = array('elt',$end_time);
        }
        
        //应收款数 未收款 部分收款 应收金额 实际收款金额 应付款数 未付款 部分付款 应付金额 实际付款金额   
        $reportList = array();
        
        $shoukuan_count_total= 0; $weishou_count_total = 0; $bufenshoukuan_count_total = 0; $shoukuan_money_total = 0; $yishou_money_total = 0; $shoukuandan_count_total = 0;
        $fukuan_count_total= 0;  $weifu_count_total = 0; $bufenfukuan_count_total = 0; $fukuan_money_total = 0; $yifu_money_total = 0; $fukuandan_count_total = 0;

        $user_shoukuan_item_list= $m_shoukuan
            ->where(array('is_deleted'=>0, 'pay_time'=>$create_time, 'owner_role_id' => ['in', $role_id_array]))
            ->group('status,owner_role_id')
            ->field("owner_role_id, status, ifnull(count('status'), 0) as amount")
            ->select();
        $user_shoukuan_list = [];

        foreach($user_shoukuan_item_list as $user_shoukuan_item){
            $user_shoukuan_list[$user_shoukuan_item['owner_role_id']][$user_shoukuan_item['status']] = $user_shoukuan_item['amount'];
        }

        $user_receviables_list = M('receivables')->alias('r')
            ->where(array('r.is_deleted'=>0, 'r.pay_time'=>$create_time, 'r.owner_role_id' => ['in', $role_id_array] ))
            ->group('r.owner_role_id')
            ->field('r.`owner_role_id`, sum(r.price) as total_price')
            ->select();

        
        $user_receving_list = M('receivingorder')->alias('receving')
        ->where(array('receving.is_deleted'=>0, 'receving.pay_time'=>$create_time, 'receving.owner_role_id' => ['in', $role_id_array] ))
        ->group('receving.owner_role_id')
        ->getField('receving.`owner_role_id`, sum(receving.money) as received', true); 

        foreach($user_receviables_list as $user_receviables){
            $owner_role_id = $user_receviables['owner_role_id'];
            $user_shoukuan_list[$owner_role_id] = $user_shoukuan_list[$owner_role_id] + $user_receviables;
            $user_shoukuan_list[$owner_role_id]['total_received'] = $user_receving_list[$owner_role_id];
        }
        
        foreach($role_id_array as $v){
            $user = getUserByRoleId($v);

            $shoukuan_money = 0; $yishou_money = 0; $shoukuandan_count = 0;
            $fukuan_count= 0;  $weifu_count = 0; $bufenfukuan_count = 0; $fukuan_money = 0; $yifu_money = 0;
            $fukuandan_count = 0;

            $weishou_count = $user_shoukuan_list[$v][0] + 0;

            $bufenshoukuan_count = $user_shoukuan_list[$v][1] + 0;
            
            $yishoukuan_count = $user_shoukuan_list[$v][2] + 0;

            $shoukuan_count = $weishou_count + $bufenshoukuan_count + $yishoukuan_count;

//            $shoukuan_money = '￥'.number_format($user_shoukuan_list[$v]['total_price'], 2);
//            $yishou_money = '￥'.number_format($user_shoukuan_list[$v]['total_received'], 2);
            $shoukuan_money = $user_shoukuan_list[$v]['total_price'];
            $yishou_money = $user_shoukuan_list[$v]['total_received'];

            $shoukuandan_count = $user_shoukuan_list[$v]['total_receving_count'] + 0;
            //exit;
            // $shoukuandan_count = $m_shoukuandan->where(array('is_deleted'=>0,'owner_role_id'=>$v, 'pay_time'=>$create_time))->count();
            // $shoukuan_money = round($m_shoukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->sum('price'),2);
            // $shoukuan_id_array = $m_shoukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->getField('receivables_id', true);
            // $shijishoukuan_money = 0;
            // foreach($shoukuan_id_array as $v2){
            //  $shoukuandan_list = $m_shoukuandan->where('status = 1 and is_deleted=0 and receivables_id = %d', $v2)->getField('money', true);
            //  foreach($shoukuandan_list as $v3) {
            //      $shijishoukuan_money += $v3;
            //  }
            // }
            // $yishou_money =round($shijishoukuan_money,2);
            
//             $fukuan_count = $m_fukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->count();
//             $weifu_count = $m_fukuan->where(array('is_deleted'=>0, 'status'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->count();
//             $bufenfukuan_count = $m_fukuan->where(array('is_deleted'=>0, 'status'=>1, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->count();
//             $fukuandan_count = $m_fukuandan->where(array('is_deleted'=>0,'owner_role_id'=>$v, 'pay_time'=>$create_time))->count();
//             $fukuan_money = $n=round($m_fukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->sum('price'),2);
//             $fukuan_id_array = $m_fukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->getField('payables_id', true);
//             $shijifukuan_money = 0;
//             foreach($fukuan_id_array as $v4){
//              $fukuandan_list = $m_fukuandan->where('status = 1 and is_deleted=0 and payables_id = %d', $v4)->getField('money', true);
//              foreach($fukuandan_list as $v5) {
//                  $shijifukuan_money += $v5;
//              }
//             }
//             $yifu_money = round($shijifukuan_money,2);
            
            $reportList[] = [
                "user" => $user,
                "shoukuan_count" => $shoukuan_count,
                "shoukuan_money" => $shoukuan_money,
                "weishou_count" => $weishou_count,
                "bufenshoukuan_count" => $bufenshoukuan_count, 
                "yishou_money" => $yishou_money,
                "shoukuandan_count" => $shoukuandan_count,
                "fukuan_count" => $fukuan_count,
                'weifu_count' => $weifu_count,
                "bufenfukuan_count" => $bufenfukuan_count,
                "fukuan_money" => $fukuan_money,
                "yifu_money" => $yifu_money,
                "fukuandan_count" => $fukuandan_count,
                'yishoukuan_count' => $yishoukuan_count
            ];

            $shoukuan_count_total += $shoukuan_count; $weishou_count_total += $weishou_count; $bufenshoukuan_count_total += $bufenshoukuan_count;
            $shoukuan_money_total += $shoukuan_money; $yishou_money_total += $yishou_money; $shoukuandan_count_total += $shoukuandan_count;
            $fukuan_count_total += $fukuan_count;  $weifu_count_total += $weifu_count;
            $bufenfukuan_count_total += $bufenfukuan_count; 
            $fukuan_money_total += $fukuan_money; $yifu_money_total += $yifu_money; 
            $fukuandan_count_total += $fukuandan_count;
            $yishoukuan_count_total += $yishoukuan_count;
        }
    
        $total_report = array(
            "shoukuan_count"=>$shoukuan_count_total,
            "weishou_count" => $weishou_count_total,
            "bufenshoukuan_count"=>$bufenshoukuan_count_total,
            "shoukuan_money"=>$shoukuan_money_total,
            "yishou_money"=>$yishou_money_total,
            "shoukuandan_count"=>$shoukuandan_count_total,
            "fukuan_count"=>$fukuan_count_total,
            "weifu_count"=>$weifu_count_total,
            "bufenfukuan_count"=>$bufenfukuan_count_total,
            "fukuan_money"=>$fukuan_money_total,
            "yifu_money"=>$yifu_money_total,
            "fukuandan_count"=>$fukuandan_count_total,
            'yishoukuan_count_total'=>$yishoukuan_count_total 
        );
        $this->reportList = $reportList;
        $this->total_report = $total_report;
        if (session('?admin')){
            //$idArray = M('role')->where('user_id <> 0')->getField('role_id',true); (修改旧)
            $idArray = getSubRoleId();  //(修改新增加)
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
        $this->display();
    }
    
    /**
     * 首页应收款月度统计
     * @ level 0:自己的数据  1:自己和下属的数据
     **/
    public function getmonthlyreceive(){
        $m_receivables = M('receivables');
        $m_payables = M('payables');
        $dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
        $widget = unserialize($dashboard);
        $where['creator_role_id'] = array('in',getSubRoleId());
        
        $year = date('Y');
        $moon = 1;
        $not_receive = array();//应收款
        $have_received = array();//实际收款
        $not_pay = array();//应付款
        $have_paid = array();//实际付款
        $where['is_deleted'] = array('eq', 0);
        while ($moon <= 12){
            if($moon == 12) {
                $where['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
            } else {
                $where['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
            }
    
            $not_receiveList = $m_receivables->where($where)->select();//应收款数组
            $monthly_not_receive = 0;
            foreach($not_receiveList as $v){
                $monthly_not_receive = floatval(bcadd($monthly_not_receive, $v['price'], 2));//单月应收款总额
            }
            $not_receive[] = $monthly_not_receive;
            
            $condition = $where;
            $condition['status'] = array('neq', 0);
            $have_receivedList = $m_receivables->where($condition)->select();//(部分)已收款数组
            $monthly_have_received = 0;
            foreach($have_receivedList as $v){
                $monthly_have_received += M('receivingorder')->where('receivables_id = %d and is_deleted = 0',$v['receivables_id'])->sum('money');//单月实收款总额
            }
            $have_received[] = $monthly_have_received;
            
            $not_payList = $m_payables->where($where)->select();//应付款数组
            $monthly_not_pay = 0;
            foreach($not_payList as $v){
                $monthly_not_pay = floatval(bcadd($monthly_not_pay, $v['price'], 2));//单月实收款总额
            }
            $not_pay[] = $monthly_not_pay;
            
            $have_paidList = $m_payables->where($condition)->select();//(部分)已收款数组
            $monthly_have_paid = 0;
            foreach($have_paidList as $v){
                $monthly_have_paid += M('paymentorder')->where('payables_id = %d and is_deleted = 0',$v['payables_id'])->sum('money');//单月实收款总额
            }
            $have_paid[] = $monthly_have_paid;
            
            $moon ++;
        }
        $financeDate['not_receive'] = $not_receive;
        $financeDate['have_received'] = $have_received;
        $financeDate['not_pay'] = $not_pay;
        $financeDate['have_paid'] = $have_paid;
        $this->ajaxReturn($financeDate,'success',1);
    }
    
    /**
     * 首页应收款年度对比统计
     * @ level 0:自己的数据  1:自己和下属的数据
     **/
    public function getYearReceiveComparison(){
        $m_receivables = M('receivables');
        $dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
        $widget = unserialize($dashboard);
        $where['creator_role_id'] = array('in',getSubRoleId());

        $year = date('Y');
        $prev_year = $year-1;
        $moon = 1;
        $receive_this_year_money = array();
        $receive_prev_year_money = array();
        $where['is_deleted'] = array('eq', 0);
        $where_this_year = $where;
        $where_prev_year = $where;
        while ($moon <= 12){
            if($moon == 12) {
                $where_this_year['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
                $where_prev_year['pay_time'] = array(array('egt', strtotime($prev_year.'-'.$moon.'-1')), array('lt', strtotime(($year).'-1-1')), 'and');
            } else {
                $where_this_year['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
                $where_prev_year['pay_time'] = array(array('egt', strtotime($prev_year.'-'.$moon.'-1')), array('lt', strtotime($prev_year.'-'.($moon+1).'-1')), 'and');
            }

            $receive_this_year_price = $m_receivables->where($where_this_year)->sum('price');//今年月度收款金额总和
            $receive_prev_year_price = $m_receivables->where($where_prev_year)->sum('price');//去年月度收款金额总和
            $receive_this_year_money[] = empty($receive_this_year_price) ? 0 : round($receive_this_year_price,2);
            $receive_prev_year_money[] = empty($receive_prev_year_price) ? 0 : round($receive_prev_year_price,2);
            $moon ++; 
        }
        
        $total_money = array('this_year'=>$receive_this_year_money, 'prev_year'=>$receive_prev_year_money);
        $this->ajaxReturn($total_money,'success',1);
    }


    public function orderprint($order_id = null, $receivingorder_id = null){
        if ($order_id){
            $order_info = M('receivables')->alias('orders')
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = orders.customer_id')
                ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->field('orders.price,orders.order_number, customer.name as customer_name, contacts.name as contacts_name, contacts.telephone as telephone, orders.pay_time')
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
            $order_info['money_cn'] = Utils::number_2_cn($received);
            for ($i=0;$i<3;$i++){
                $arr.=rand(0,9);
            }
            $order_info['order_number'] = date("Ymd").'-'.$arr;
            $this->assign('order_info' , $order_info);
            $this->assign('receiving_list' , $receiving_list);



        }elseif ($receivingorder_id){
            $receivingorder_ids = explode(',',$receivingorder_id);
            $data['receivingorder_id']=['in',$receivingorder_ids];
            $order_info = M('receivingorder')->alias('receivingorder')
                ->join(C('DB_PREFIX').'receivables orders on orders.receivables_id = receivingorder.receivables_id')
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = orders.customer_id')
                ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
                ->field('orders.price,orders.order_number, customer.name as customer_name, contacts.name as contacts_name, contacts.telephone as telephone,orders.receivables_id,orders.pay_time')
                ->where($data)
                ->find();
            for ($i=0;$i<3;$i++){
                $arr.=rand(0,9);
            }
            $order_info['order_number'] = date("Ymd").'-'.$arr;
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
            $order_info['money_cn'] = Utils::number_2_cn($received);
            $this->assign('order_info',$order_info) ;
            $this->assign('receiving_list',$receiving_list) ;
        }
        $font_family = M('config')->getFieldByName('order_font_family', 'value');
        $this->assign('font_family', $font_family);
        $this->display();
    }

    /**
     * 付款单收据发票删除
     */
    public function paymentorder_image_delete()
    {
        var_dump(I('get.id'));
        $id = I('get.id');
        $paymentorder_image = M('paymentorder_image');
        $image_url = $paymentorder_image -> where('id = %d', $id) -> getField('paymentorder_image');
        if ($paymentorder_image -> where('id = %d', $id) -> delete()){
            unlink($image_url);
            alert('success', '删除成功', $_SERVER['HTTP_REFERER']);
        }else{
            alert('alert', '删除失败', $_SERVER['HTTP_REFERER']);
        }
    }

    /**
     * 其他收入收据发票删除
     */
    public function otherrevenue_image_delete()
    {
        var_dump(I('get.id'));
        $id = I('get.id');
        $otherrevenue_image = M('otherrevenue_image');
        $image_url = $otherrevenue_image -> where('id = %d', $id) -> getField('otherrevenue_image');
        if ($otherrevenue_image -> where('id = %d', $id) -> delete()){
            unlink($image_url);
            alert('success', '删除成功', $_SERVER['HTTP_REFERER']);
        }else{
            alert('alert', '删除失败', $_SERVER['HTTP_REFERER']);
        }
    }

    public function getCostItem($level = 2){
        $costItem = M('cost_item')->where('level=%d', $level)->select();
        $this->ajaxReturn($costItem, '', 1);
    }

    public function cost_item_add()
    {
        $data = [
            'cost_item' => I('post.cost_item'),
            'level' => I('post.level'),
        ];
        if (M('cost_item')->add($data)){
            alert('success',L('添加成功'), $_SERVER['HTTP_REFERER']);
        }else{
            alert('error',L('添加失败'), $_SERVER['HTTP_REFERER']);
        }
    }
//    public function cost_item_edit()
//    {
//        $cost_item_id = I('get.cost_item_id');
////        $bool = M('cost_item')->where('cost_item_id=%d', $cost_item_id)->save(['cost_item'=>]);
//
//    }

//    public function cost_item_delete()
//    {
//        $cost_item_id = I('get.cost_item_id');
//        $bool = M('cost_item')->where('cost_item_id=%d', $cost_item_id)->delete();
//        if ($bool) {
//            alert('success',L('删除成功'), $_SERVER['HTTP_REFERER']);
//        }else{
//            alert('error',L('删除失败'), $_SERVER['HTTP_REFERER']);
//        }
//    }

    public function uploadImage()
    {
        $id = I('post.paymentorder_id');
        if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
            import('@.ORG.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 20000000;
            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
            $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
            if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                alert('error',L("ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE"),U('finance/index','t=paymentorder'));
            }
            $upload->savePath = $dirname;

            if ($upload->upload()){
                $info =  $upload->getUploadFileInfo();
            }
            if(is_array($info[0]) && !empty($info[0])){
                $paymentorder_image = M('paymentorder_image');
                foreach ($info as $imageInfo){
                    $paymentorder_image_data['paymentorder_id'] = $id;
                    $paymentorder_image_data['paymentorder_image'] = $imageInfo['savepath'].$imageInfo['savename'];
                    $paymentorder_image->add($paymentorder_image_data);
                }
            }
        }
        alert('success',L('添加成功'), $_SERVER['HTTP_REFERER']);
    }
    /**
     * 财务导出
     * @param bool $list
     * @param $type
     * @throws Exception
     */
    public function excelExport($list=false, $type, $columns = [], $actionName = ''){
        C('OUTPUT_ENCODE', false);
        import("ORG.PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("LYFZ");
        $objProps->setLastModifiedBy("LYFZ");
        $objProps->setTitle("LYFZ Service");
        $objProps->setSubject("LYFZ Service Data");
        $objProps->setDescription("LYFZ Service Data");
        $objProps->setKeywords("LYFZ Service Data");
        $objProps->setCategory("LYFZ");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();

        switch ($type){
            case 'paymentorder': 
                $objActSheet->setTitle('支出');

                $objActSheet->getColumnDimension('A')->setWidth(15);
                $objActSheet->getColumnDimension('B')->setWidth(20);
                $objActSheet->getColumnDimension('C')->setWidth(15);
                $objActSheet->getColumnDimension('D')->setWidth(15);
                $objActSheet->getColumnDimension('E')->setWidth(40);
                $objActSheet->getColumnDimension('F')->setWidth(15);

                $objPHPExcel->getActiveSheet()->setCellValue('A1',  '团队');//这里是设置A1单元格的内容
                $objPHPExcel->getActiveSheet()->setCellValue('B1',  '支出单号');////这里是设置B1单元格的内容
                $objPHPExcel->getActiveSheet()->setCellValue('C1',  '金额');
                $objPHPExcel->getActiveSheet()->setCellValue('D1',  '项目名称');
                $objPHPExcel->getActiveSheet()->setCellValue('E1',  '描述');
                $objPHPExcel->getActiveSheet()->setCellValue('F1',  '付款时间');
                foreach ($list as $key => $value) {
                    $i=$key+2;//表格是从1开始的
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,  $value['dept']);//这里是设置A1单元格的内容
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,  $value['name']);////这里是设置B1单元格的内容
                    $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,  $value['money']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,  $value['cost_item']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,  $value['description']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,  date('Y-m-d', $value['pay_time']));
                }
                break;
            default: 
                $objActSheet->setTitle($actionName);
                $columnIndex = 0x41;
                foreach ($columns as $column) {
                    $objActSheet->setCellValue(chr($columnIndex).'1', $column->getTitle());
                    $column->getWidth() && $objActSheet->getColumnDimension(chr($columnIndex ++))->setWidth($column->getWidth());
                }

                foreach ($list as $key => $value) {
                    $i=$key+2;//表格是从1开始的
                    $columnIndex = 0x41;
                    foreach ($columns as $column) {
                        $objActSheet->setCellValue(chr($columnIndex ++).$i, $column->formatter($value[$column->getKey()]));//这里是设置A1单元格的内容
                    }
                }

                break;
        }


        $objActSheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1:F1')->getAlignment()->setWrapText(true);

        //        //设置背景色
        $objActSheet->getStyle('A1:F1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objActSheet->getStyle('A1:F1')->getFill()->getStartColor()->setARGB('F5DEB3');

        $current_page = intval($_GET['current_page']);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".$type.date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
        session('export_status', 0);
    }
}