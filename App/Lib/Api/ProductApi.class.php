<?php

// namespace App\Api;

use App\Lib\Api\ApiBase;
use App\Extend\Http;

class ProductApi extends ApiBase
{
    const BASE_URL = 'http://activity.lyfz.net/';
    public function getCategories(){
        $category = D('ProductCategory');
        $this->ajaxReturn(['categories' => $category->select()]);
    }

    public function getlist($category_id = null){
        $view_product = D('ProductView');

        if ($category_id){
            $view_product->where(['category_id'=>$category_id]);
        }
        $view_product->field(['product_id','name', 'suggested_price']);
        $this->ajaxReturn(['product_list' => $view_product->order('sort asc')->select()]);
    }

    public function getProductDetail(){
        
    }

    public function setOrderProductInfo(){
        $post = I('post.');

        if (empty($post['order_product_id'])){
            return $this->ajaxReturn('', -1, 'MISSING ARGUMENTS');
        }

        $order_product_id = $post['order_product_id'];
        unset($post['order_product_id']);
        // $save_data = [];
        // $time = time();
        // foreach($post as $key => $value){
        //     if (empty(trim($value)))continue;
        //     if ($key == 'next_service_fee_date'){//如果填写了下次上门时间，将对应的时间转化为时间戳
        //         $save_data[] = [
        //             'order_product_id' => $order_product_id,
        //             'extra_key' => $key,
        //             'extra_value' => strtotime($value),
        //             'create_time' => $time
        //         ];
        //     }else{
        //         $save_data[] = [
        //             'order_product_id' => $order_product_id,
        //             'extra_key' => $key,
        //             'extra_value' => $value,
        //             'create_time' => $time
        //         ];
        //     }
        // }
        // $result = M('r_order_product_extra')->addAll($save_data);
        foreach($post as $key => $value){
            if (empty(trim($value)))continue;
            $save_data = [
                'order_product_id' => $order_product_id,
                'extra_key' => $key,
                'extra_value' => ($key == 'next_service_fee_date' ? strtotime($value) : $value),//如果填写了下次上门时间，将对应的时间转化为时间戳
                'create_time' => time(),
                'extra_type' => ($key == 'next_service_fee_date' ? 'timestamp' : 'varchar'),
            ];
            if ($id = M('r_order_product_extra')->where(['order_product_id'=>$order_product_id, 'extra_key'=>$key])->order('id desc')->getField('id')){
                $result[] = M('r_order_product_extra')->where(['id'=>$id])->save(['extra_value' => ($key == 'next_service_fee_date' ? strtotime($value) : $value), 'extra_type' => ($key == 'next_service_fee_date' ? 'timestamp' : 'varchar')]);
            }else {
                $result[] = M('r_order_product_extra')->add($save_data);
            }
        }

        event('SetProductInfo', $post);

        return $this->ajaxReturn($result);
    }

    public function getProduct($order_product_id='')
    {
        if (empty($order_product_id)){
            $this->ajaxReturn(null, -1, 'MISSING ARGUMENTS');
        }
        $productInfo = M('r_order_product')->alias('order_product')
            ->where('id=%d', $order_product_id)
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id=order_product.customer_id')
            ->join(C('DB_PREFIX').'customer_data customer_data ON customer.customer_id=customer_data.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
            ->join(C('DB_PREFIX').'express express ON express.order_product_id='.$order_product_id)
            ->join(C('DB_PREFIX').'receivables receivables ON receivables.receivables_id=order_product.order_id')
            ->field('contacts.name contacts_name,order_product.product_name,customer.address,order_product.id order_product_id,customer.customer_id,
            express.express_company,express.express_number,express.sender,contacts.telephone contacts_telephone,customer_data.description,receivables.description order_description')
            ->order('express_id desc')
            ->find();
        if (!$productInfo['express_number']){
            $productInfoExtra = M('r_order_product_extra')->where('(extra_key="express_number" or extra_key="express_company") AND order_product_id=%d', $order_product_id)->order('id desc')->getField('extra_key, extra_value');
            if ($productInfoExtra){
                $productInfo['express_company'] = $productInfoExtra['express_company'];
                $productInfo['express_number'] = $productInfoExtra['express_number'];
            }
        }
        $productInformationExtra = M('r_order_product_extra')->where('order_product_id = %d', $order_product_id)->getField('extra_key, extra_value');
        $productInfo['extra_info'] = $productInformationExtra;
        $this->ajaxReturn($productInfo);
    }



    //*******************************   营销软件  ****************************************//

    /**
     *商户注册
     */
    public function sellingRegister()
    {
        $posts = I('post.');
        foreach ($posts['auth'] as $key=>$auth){
            if (!$auth['createtime']){
                $posts['auth'][$key]['createtime'] = time();
                $posts['auth'][$key]['valid_date'] = strtotime($auth['valid_date'])-time();
            }else{
                $posts['auth'][$key]['valid_date'] = strtotime($auth['valid_date'])-$auth['createtime'];
            }
        }
        $posts['auth'] = json_encode($posts['auth']);
        Action::ajaxReturn(Http::PostForm(self::BASE_URL.'api/register', $posts));
    }

    public function getMerchantDetail($order_product_id=null)
    {
        if ($order_product_id){
            if ($merchant_id = M('plugin_selling')->where('order_product_id = %d', $order_product_id)->order('plugin_selling_id desc')->getField('merchant_id'))
                Action::ajaxReturn(Http::PostForm(self::BASE_URL.'api/getMerchantDetail?id='.$merchant_id));
        }
        Action::ajaxReturn(Http::PostForm(self::BASE_URL.'api/getMerchantDetail'));
    }

    /**
     * 将外部服务器返回的id保存到本服务器数据库
     */
    public function savePluginSelling()
    {
        $posts = I('post.');
        if ($plugin_selling_id = M('plugin_selling')->add($posts))
            $this->ajaxReturn($plugin_selling_id);
        else
            $this->ajaxReturn(null, -1, '添加失败');
    }



    //*******************************   营销软件  ****************************************//


    public function get_order_product_list () {
        
    }

    public function get_order_product_details ($order_product_id) {
        $order_product_info = M('ROrderProduct')->alias('order_product')
        ->join('inner join '.C('DB_PREFIX').'receivables receivables on receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
        ->join(C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id = order_product.order_id and receiving.is_deleted = 0')
        ->join(C('DB_PREFIX').'user owner_user on owner_user.role_id = order_product.owner_role_id')
        ->field('receivables.order_number, receivables.price, owner_user.name as owner_role_name, product_name, sum(receiving.money) as received, order_product.customer_id')
        ->where(['order_product.id' => $order_product_id])
        ->find();
        $this->ajaxReturn($order_product_info);
    }

    /**
     * 设置产品id
     * @param null $order_product_id
     * @param null $product_id
     */
    public function setProductId($order_product_id = null, $product_id = null)
    {
        if (empty($order_product_id) || empty($product_id)){
            $this->ajaxReturn(null, -1, '您没有选择它所属的产品');
        }
        if (false !== M('ROrderProduct')->where('id = %d', $order_product_id)->setField('product_id', $product_id)){
            $this->ajaxReturn(null, 0, '修改成功');
        }else{
            $this->ajaxReturn(null, -1, '修改失败');
        }
    }

    /**
     * 更换铂金版
     */
    public function change_platinum()
    {
        $posts = I('post.');
        if (empty($posts['order_product_id']) || empty($posts['product_id'])){
            $this->ajaxReturn(null, -1, '您没有选择它所属的产品');
        }
        $trans = M();
        $trans->startTrans();//开启事务

        $orderProductExtra = M('ROrderProductExtra');
        if ($orderProductExtra->where(['order_product_id'=>$posts['order_product_id'], 'extra_key'=>'change_description'])->find()){//判断是否有该字段，有修改，没有添加（一般没有）
            $result1 = $orderProductExtra->where(['order_product_id'=>$posts['order_product_id'], 'extra_key'=>'change_description'])->save(['extra_value'=>$posts['change_description']]);
        }else{
            $result1 = $orderProductExtra->add(['order_product_id'=>$posts['order_product_id'],'extra_key'=>'change_description','extra_value'=>$posts['change_description']]);
        }
        $result2 = M('ROrderProduct')->where('id = %d', $posts['order_product_id'])->setField(['product_id'=>$posts['product_id'], 'product_name'=>$posts['product_name']]);

        if (false !== $result1 && false !== $result2){
            $trans->commit();
            $this->ajaxReturn(null, 0, '修改成功');
        }else{
            $trans->rollback();
            $this->ajaxReturn(null, -1, '修改失败');
        }
    }

    /**
     * 删除订单产品
     * @param null $order_product_id
     */
    public function deleteOrderProduct($order_product_id=null)
    {
        if (empty($order_product_id)){
            $this->ajaxReturn(null, -1, '出现意外问题，请联系管理员');
        }
        $data = [
                    'is_deleted'    => 1,
                    'delete_role_id'=> session('role_id') ?? $this->user['role_id'],
                    'delete_time'   => time()
                ];
        if (M('ROrderProduct')->where('id = %d', $order_product_id)->setField($data)){
            $this->ajaxReturn(null, 0, '删除成功！');
        }else{
            $this->ajaxReturn(null, -1, '删除失败！');
        }
    }

    /**
     * 获取订单产品负责人id
     * @param null $order_product_id
     */
    public function get_order_product_owner($order_product_id = null)
    {
        if (empty($order_product_id)){
            $this->ajaxReturn(null, -1, '参数缺失');
        }
        $owner_role = M('ROrderProduct')
            ->alias('order_product')
            ->field('user.name username,user.user_id,user.role_id,department.name department_name,department.department_id,position.name position_name,position.position_id')
            ->join(C('DB_PREFIX').'user user ON user.role_id = order_product.owner_role_id')
            ->join(C('DB_PREFIX').'role role ON role.role_id = user.role_id')
            ->join(C('DB_PREFIX').'position position ON position.position_id = role.position_id')
            ->join(C('DB_PREFIX').'role_department department ON department.department_id = position.department_id')
            ->where('id = %d', $order_product_id)->find();
        $this->ajaxReturn($owner_role);
    }

    /**
     * 添加快递信息，并发货
     */
    public function express()
    {
        $express = I('post.');
        $express_id = M('express')->add($express);
        if ($express_id){
            $message = new \App\Extend\Message();
            // $data = $message ->sendProcessMessage($express['customer_id'], \App\Extend\Message::SHIPMENTS, $express['order_product_id'], $express_id,null,$_SESSION['role_id']??$this->user['role_id']);//发货短信
            $role_id = session('role_id') ?? $this->user['role_id'];
            $data = $message->sendExpressMessage($express, $role_id);
            $this->ajaxReturn($data);
        }
        else
           $this->ajaxReturn(null, -1, '发货失败');
    }

    public function ceshi()
    {
        $message = new \App\Extend\Message();
        $this->ajaxReturn($message->ceshi());
    }
}