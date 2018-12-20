<?php

use App\Lib\Api\ApiBase;

class ServiceApi extends ApiBase{

    //故障提交人发送微信模板消息
    //$target_userid 目标的微信id  $creator_name 创建者名  $contacts_name 首要联系人名称 $problem故障问题
    private function sendWxEnterpriseMessage($target_userid, $content){
        import('@.ORG.WeixinEnterprise');
        $WeixinEnterprise = new WeixinEnterprise();
        return $WeixinEnterprise->sendTextMessage('user', $target_userid, $content);
    }

    private function sendOrderProductContactingInfo($target_userid, $creator_name, $customer_name, $product_name, $customer_contacts_name, $customer_contacts_phone){
        $content = $creator_name.'安排了你对接'.$customer_name.'的'.$product_name.',联系方式:'.$customer_contacts_name.' '.$customer_contacts_phone.',请及时处理, 详情可登录CRM查看';
        if ($target_weixin_id = M('user')->where(['user_id' => $target_userid])->getField('weixinid')){
            return $this->sendWxEnterpriseMessage($target_weixin_id, $content);
        }
    }
    
    /** 设置订单商品后续服务对接人 */
    public function setOrderProductExtraInfo($order_product_id){
        $contacting_role_id = I('post.contacting_role_id');
        $contacting_status = I('post.contacting_status');

        $order_product = M('r_order_product')->alias('order_product')
        ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = order_product.customer_id')
        ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id = customer.contacts_id')
        ->field(
            'customer.name as customer_name,
            contacts.name as contacts_name,
            contacts.telephone as contacts_telephone,
            order_product.product_name as product_name')
        ->where(['id' => $order_product_id])
        ->find();

        if (!$order_product){
            $this->ajaxReturn(null,-1 , 'CAN`T FIND ORDER PRODUCT');
            return;
        }

        $order_product_receive_info = M('ROrderProduct')->alias('order_product')
            ->join('inner join '.C('DB_PREFIX').'receivables receivables on receivables.receivables_id = order_product.order_id and receivables.is_deleted = 0')
            ->join(C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id = order_product.order_id')
            ->field('receivables.price, sum(receiving.money) as received')
            ->where(['order_product.id' => $order_product_id])
            ->find();

        if ($contacting_status == 'FINISH' && $order_product_receive_info['price']!=0 && $order_product_receive_info['received']<$order_product_receive_info['price'])//如果选择已完成且实收款小于应收款则不给提交,排除应收款为0的项目
            $this->ajaxReturn(null, -1, '收款未结清，不能选择已完成！');


        $order_product_info = ['order_product_id' => $order_product_id];

        $has_extends = M('r_order_product_extend')->where($order_product_info)->count();

        $extends_info = [];

        if ($contacting_role_id){
            $extends_info['contacting_role_id'] = $contacting_role_id;
        }

        if ($contacting_status){
            $extends_info['contacting_status'] = $contacting_status;
        }
        
        if (!$has_extends){
            M('r_order_product_extend')->add(array_merge($order_product_info, $extends_info));
        } else {
            M('r_order_product_extend')->where($order_product_info)->save($extends_info);
        }
        if ($contacting_status == 'UNSERVED' || empty($contacting_status)){
            $result = $this->sendOrderProductContactingInfo($contacting_role_id, session('name'),
                $order_product['customer_name'],
                $order_product['product_name'],
                $order_product['contacts_name'],
                $order_product['contacts_telephone']);

            if ($result['errcode']){
                $this->ajaxReturn(null, $result['errcode'], '通知对接人失败，可能对方没有关注企业号');
                return;
            }
        }

        $this->ajaxReturn($result);
    }

}