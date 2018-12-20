<?php

// namespace App\Api;

use App\Lib\Api\ApiBase;
use App\Lib\Model\User;

class CustomerApi extends ApiBase
{
    public function _initialize(){
        parent::_initialize();
        $action = array(
            'permission' => ['customer_file'],
            'allow' => ['get_ordered_list', 'get_communicate_logs', 'add_communicate_log', 'details','order_product_list','get_list'],
            'roles' => [
                'customer' => ['get_order_product_list', 'details']
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

    public function getCategories(){
        $category = D('ProductCategory');
        $this->ajaxReturn(['categories' => $category->select()]);
    }

    /**
     * 获取客户列表
     * @param null $owner_role_id
     * @param int $page
     * @param int $page_size
     * @param string $keyword
     * @param int $subtree 0获取自己 1获取自己和下属 2获取下属
     */
    public function get_list($owner_role_id = null, $page = 1, $page_size = 15, $keyword = '', $subtree = 0){
        $db_customer = M('customer');
        if (!empty($keyword)){
            $keyword = preg_replace('/^|$|\s+/', '%', trim($keyword));

            $where['customer.name'] = ['like', $keyword];
        }
        if (!empty($owner_role_id)){
            if (1 == $subtree){
                $teamMembersRoleIds = User::getTeamMembersRoleIds($owner_role_id, 0);
                array_unshift($teamMembersRoleIds, $owner_role_id);
                $where['customer.owner_role_id'] = ['in', $teamMembersRoleIds];
            }elseif (2 == $subtree){
                $teamMembersRoleIds = User::getTeamMembersRoleIds($owner_role_id, 0);
                $where['customer.owner_role_id'] = ['in', $teamMembersRoleIds];
            }else{
                $where['customer.owner_role_id|customer.creator_role_id'] = $owner_role_id;// 创建人为自己也可以看到
            }
        }
        $customer_list = $db_customer->alias('customer')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
            ->where($where)
            ->where(['customer.is_deleted'=>0])
            ->field('customer.customer_id, customer.name customer_name, contacts.name contacts_name, contacts.telephone')
            ->order('customer.customer_id desc')
            ->page($page, $page_size)
            ->select();
        $sql = M()->getLastSql();
        $total = $db_customer->alias('customer')->where($where)->where(['customer.is_deleted'=>0])->count();

        foreach ($customer_list as $key => $customer){
            if (empty($customer['customer_name'])){
                $customer_list[$key]['customer_name'] = L('UNKNOWN');
            }
        }

        $this->ajaxReturn(['customer_list' => $customer_list, 'page_info' => [ 'size' => $page_size, 'total' => $total ], 'sql'=>$sql]);
    }

    public function get_ordered_list($page = 1, $page_size = 15, $keyword = ''){
        $db_customer = M('OrderedCustomerView');
        if (!empty($keyword)){
            $keyword = preg_replace('/^|$|\s+/', '%', trim($keyword));

            $where['customer_name|contacts_telephone'] = ['like', $keyword];
        }

        $customer_list = $db_customer
            ->where($where)
            ->where(['is_deleted'=>0])
            ->order('customer_id desc')
            ->page($page, $page_size)
            ->select();
        $sql = M()->getLastSql();
        $total = $db_customer->alias('customer')
            ->where($where)
            ->where(['is_deleted'=>0])
            ->count();

        foreach ($customer_list as $key => $customer){
            if (empty($customer['customer_name'])){
                $customer_list[$key]['customer_name'] = L('UNKNOWN');
            }
        }

        $this->ajaxReturn(['customer_list' => $customer_list, 'page_info' => [ 'size' => $page_size, 'total' => $total ], 'sql'=>$sql]);
    }


    public function details($customer_id = null, $order_product_id=null)
    {
        if (in_array('customer', $this->user['roles'])){
            $customer_id = $this->user['customer_id'];
        }

        if (empty($customer_id) && empty($order_product_id)){
            $this->ajaxReturn(null, -1, '缺失参数');
        }
        if ($order_product_id){
            $customer_id = M('ROrderProduct')->where('id = %d', $order_product_id)->getField('customer_id');
        }
        $db_ordered_customer_view = M('Customer')->alias('customer');
        $where = ['customer_id' => $customer_id];
        $customer_details = $db_ordered_customer_view
            ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = customer.contacts_id')
            ->field('customer.name as customer_name, contacts.name as contacts_name, contacts.telephone as contacts_telephone, customer.address as customer_address, contacts.qq_no')
            ->where($where)
            ->order('customer_id desc')
            ->find();

        $this->ajaxReturn(['customer_details' => $customer_details]);
    }

    /**
     * 添加客户APi
     */
    public function addCustomer()
    {
        $customerInfo = I('post.');
        $m_customer = D('Customer');
        $contacts = array();
        if ($customerInfo['con_name'])
            $contacts['name'] = $customerInfo['con_name'];
        else
            $this->ajaxReturn(null, -1, '联系人姓名信息必填');

        if ($customerInfo['con_telephone'])
            $contacts['telephone'] = $customerInfo['con_telephone'];
        else
            $this->ajaxReturn(null, -1, '联系人电话信息必填');

        $contact = M('contacts')->alias('contacts')
        ->join(C('DB_PREFIX').'r_contacts_customer contacts_customer on contacts_customer.contacts_id = contacts.contacts_id')
        ->join(C('DB_PREFIX').'customer customer on contacts_customer.customer_id = customer.customer_id')
        ->where(['telephone' => $contacts['telephone']])
        ->field('customer.name as customer_name, contacts.contacts_id, contacts.name as contacts_name')
        ->find();

        if ($customerInfo['name'])
            $m_customer->name = $customerInfo['name'];
        else
            $this->ajaxReturn(null, -1, '客户名称不能为空');


        if($customerData = $m_customer->create()){
            if (!empty($contact)){ // 客户联系电话已存在
                $contacts_id = $contact['contacts_id'];
                M('contacts')->where(['contacts_id'=>$contacts_id])->setField('name', $contacts['name']);
            }else{
                $contacts['saltname'] = isset($customerInfo['saltname'])?$customerInfo['saltname']:'';
                $contacts['email'] = isset($customerInfo['con_email'])?$customerInfo['con_email']:'';
                $contacts['post'] = isset($customerInfo['con_post'])?$customerInfo['con_post']:'';
                $contacts['qq_no'] = isset($customerInfo['con_qq'])?$customerInfo['con_qq']:'';
                $contacts['description'] = isset($customerInfo['con_description'])?$customerInfo['con_description']:'';

                $contacts['creator_role_id'] = session('role_id') ?? $this->user['role_id'];
                $contacts['create_time'] = time();
                $contacts['update_time'] = time();
                //字段设置默认值
                $contacts['department'] = '';
                $contacts['sex'] = 0;
                $contacts['address'] = '';
                $contacts['zip_code'] = '';
                $contacts['is_deleted'] = 0;
                $contacts['delete_role_id'] = 0;
                $contacts['delete_time'] = 0;
                if(!($contacts_id = M('Contacts')->add($contacts))){
                    $this->ajaxReturn(null, -1, '添加首要联系人失败');
                }

            }
            $m_customer->create_time = time();
            $m_customer->update_time = time();
            if($contacts_id) $m_customer->contacts_id = $contacts_id;
            $m_customer->creator_role_id = session('role_id') ?? $this->user['role_id'];
            $m_customer->owner_role_id = $customerInfo['owner_role_id'] ?? $this->user['role_id'];
//                $m_customer->address = $customerData['address']['state'].chr(10).$customerData['address']['city'].chr(10).$customerData['address']['area'].chr(10).$customerData['address']['street'];
            $m_customer->address = $customerInfo['address'];
            if(!($customer_id = $m_customer->add())){
                $this->ajaxReturn(null, -1 ,'添加客户失败，请联系管理员'.$m_customer->getError());
            }
            if ($customerInfo['leads_id']) {
                $leads_id = intval($customerInfo['leads_id']);
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
            $this->ajaxReturn(['customer_id'=>$customer_id], 0 , '添加客户成功');
        }else{
            $this->ajaxReturn(null, -1, '添加失败,'.$m_customer->getError());
        }

    }
    /**
     * 客户使用接口
     * 获取客户的订单产品
     */
    public function get_order_product_list ($customer_id = null) {

        if (in_array('customer', $this->user['roles'])){
            $customer_id = $this->user['customer_id'];
        }

        $order_product_list = M('ROrderProduct')->alias('order_product')
            ->join('inner join '.C('DB_PREFIX').'receivables receivables on receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
            ->join(C('DB_PREFIX').'user owner_user on owner_user.role_id = order_product.owner_role_id')
            ->field('order_product.product_name, order_product.create_time, owner_user.name as owner_role_name, order_product.product_id, order_product.id as order_product_id,order_product.price as unit_price,receivables.price as order_price')
            ->where(['order_product.customer_id' => $customer_id, 'order_product.is_deleted' => 0])
            ->select();
        $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8']])->getField('product_id', true);//获取铂金版,黄金版,标准版产品id
        foreach ($order_product_list as &$order_product) {
            $order_product['create_time'] = date('Y-m-d', $order_product['create_time']);
            $extra_props = M('ROrderProductExtra')->where(['order_product_id' => $order_product['order_product_id']])->getField('extra_key, extra_value');
            $order_product['props'] = $extra_props;
            if ($order_product['product_id'] == 19) {
                $order_product['buttons'] = [
                    [
                        'label' => 'query',
                        'front_uri' => '/message/query'
                    ],
                    [
                        'label' => 'recharge',
                        'front_uri' => '/message/recharge'
                    ]
                ];
            }
            // elseif (in_array($order_product['product_id'], $platinum_ids)){
            //     $order_product['buttons'] = [
            //         [
            //
            //             'label'   =>  'query',
            //             'front_uri'  =>  '/platinum/query',
            //         ],
            //         [
            //             'label'   =>  'serviceCharge',
            //             'front_uri'  =>  '/platinum/serviceCharge',
            //         ],
            //     ];
            // }
        }
        $this->ajaxReturn($order_product_list);
    }

    /**
     * CRM使用
     * 获取客户的订单产品
     * @param null $customer_id
     */
    public function order_product_list ($customer_id = null) {

        if (in_array('customer', $this->user['roles'])){
            $customer_id = $this->user['customer_id'];
        }

        $order_product_list = M('ROrderProduct')->alias('order_product')
            ->join('inner join '.C('DB_PREFIX').'receivables receivables on receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
            ->join(C('DB_PREFIX').'user owner_user on owner_user.role_id = order_product.owner_role_id')
            ->field('order_product.product_name, order_product.create_time, owner_user.name as owner_role_name, order_product.product_id, order_product.id as order_product_id,order_product.price as unit_price,receivables.price as order_price')
            ->where(['order_product.customer_id' => $customer_id, 'order_product.is_deleted' => 0])
            ->select();
        $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8']])->getField('product_id', true);//获取铂金版,黄金版,标准版产品id
        $hardware_ids =M('product')->where(['category_id' => 7])->getField('product_id', true);//获取硬件产品id
        foreach ($order_product_list as &$order_product) {
            $order_product['create_time'] = date('Y-m-d', $order_product['create_time']);
            $extra_props = M('ROrderProductExtra')->where(['order_product_id' => $order_product['order_product_id']])->getField('extra_key, extra_value');
            $order_product['props'] = $extra_props;
            if ($order_product['product_id'] == 19) {
                $order_product['buttons'] = [
                    [
                        'label' => 'query',
                        'front_uri' => '/message/query'
                    ],
                    [
                        'label' => 'recharge',
                        'front_uri' => '/message/recharge'
                    ]
                ];
            }elseif (in_array($order_product['product_id'], $platinum_ids)){
                $order_product['buttons'] = [
                    [
                        'label'   =>  'query',
                        'front_uri'  =>  '/platinum/query',
                    ],
                    [
                        'label'   =>  'deliverGoods',
                        'front_uri'  =>  '/platinum/deliverGoods',
                    ],
                    [
                        'label'   =>  'serviceCharge',
                        'front_uri'  =>  '/platinum/serviceCharge',
                    ],
                ];
            }elseif (in_array($order_product['product_id'], $hardware_ids)){
                $order_product['buttons'] = [
                    [
                        'label'   =>  'query',
                        'front_uri'  =>  '/platinum/query',
                    ],
                    [
                        'label'   =>  'deliverGoods',
                        'front_uri'  =>  '/platinum/deliverGoods',
                    ],
                ];
            }
            // elseif ($order_product['product_id'] == 84){
            //     $order_product['buttons'] = [
            //         [
            //             'label'   =>  'register',
            //             'front_uri'  =>  '/selling/register',
            //         ],
            //     ];
            // }
        }
        $this->ajaxReturn($order_product_list);
    }

    public function get_communicate_logs ($customer_id = null) {

        if (in_array('customer', $this->user['roles'])){
            $customer_id = $this->user['customer_id'];
        }

        $communicate_logs = M('RCustomerLog')->alias('customer_log')
        ->join(C('DB_PREFIX').'log logs on logs.log_id = customer_log.log_id')
        ->join(C('DB_PREFIX').'user user on user.role_id = logs.role_id')
        ->where(['customer_id' => $customer_id])
        ->field('logs.log_id, subject, create_date, content, user.name as user_name')
        ->select();

        foreach ($communicate_logs as &$log) {
            $log['create_date'] = date('Y-m-d h:i:s', $log['create_date']);
        }

        $this->ajaxReturn($communicate_logs);
    }

    
    public function add_communicate_log () {
        $customer_id = I('get.customer_id');
        // if (!($title = I('post.title'))) {
        //     $this->ajaxReturn(null, -1, '请输入日志标题');
        // }
        
        $title = '';

        if (!($content = I('post.content'))) {
            $this->ajaxReturn(null, -1, '请输入日志内容');
        }
        
        $log_id = M('log')->add([
            'customer_id' => $customer_id,
            'content' => $content,
            'create_date' => time(),
            'role_id' => session('role_id') ?? $this->user['role_id'],
            'subject' => $title
        ]);

        M('RCustomerLog')->add([
            'customer_id' => $customer_id,
            'log_id' => $log_id
        ]);

        $this->ajaxReturn(null);
    }

    // 客户合同文件
    public function customer_file($customer_id = null)
    {
        if (!$customer_id)
            $this->ajaxReturn(null, -1, '客户id不能为空');

        $contactPhoto = M('r_customer_file')->alias('customer_file')//合同数据查找
            ->where('customer_file.customer_id=%d', $customer_id)
            ->join(C('DB_PREFIX').'file file ON file.file_id=customer_file.file_id')
            ->field('file_path src')
            ->select();
        $this->ajaxReturn($contactPhoto);
    }
}