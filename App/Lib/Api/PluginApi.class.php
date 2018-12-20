<?php

use App\Lib\Api\ApiBase;
use App\Lib\Plugins\Product\ProductPlatinum;
use App\Lib\Plugins\Product\ProductMessage;

class PluginApi extends ApiBase
{
    public function CustomerAuthByDomain($domain, $timestamp, $sign){
        if (md5($domain.$timestamp.'lyfz2398865') == $sign) {
            $this->ajaxReturn(ProductPlatinum::CustomerAuthByDomain($domain));
        } else {
            $this->ajaxReturn(['code' => -1, 'info' => 'auth failed']);
        }
    }

    public function MessageQueryBalance ($account, $password = null) {
        if (empty($password)){
            $this->ajaxReturn(null, -1, '密码不能为空');
        }
        try {
            $result= ProductMessage::queryBalance($account, $password);
            $this->ajaxReturn($result);
        } catch (\Exception $e) {
            $this->ajaxReturn(null, $e->getCode(), $e->getMessage());
        }
    }

    public function MessageCreateOrder($order_product_id, $money){
        $this->ajaxReturn(['order_id' => ProductMessage::createOrder($order_product_id, $money, 1)]);
    }

    /**
     * 铂金版收取服务费时，创建订单
     */
    public function PlatinumCreateOrder()
    {
        $posts = I('post.');
        $creator_role_id = session('role_id') ?? $this->user['role_id'];
        $this->ajaxReturn(['order_id' => ProductPlatinum::createOrder($posts['order_product_id'], $posts['service_fee'], $creator_role_id, $posts['service_time'], $posts['owner_role_id'], $posts['name'], $posts['order_describe'])]);
    }
}