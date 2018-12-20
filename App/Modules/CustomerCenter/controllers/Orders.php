<?php

namespace App\Modules\CustomerCenter\controllers;

class Orders extends \Action{

    public function handle() {
        $provider = 'WechatProvider'; // 暂时写死, 后期可考虑根据环境自动判断

        $providerClassStr = $this->namespace . '\providers\\' . $provider;

        $this->provider = new $providerClassStr;

        if (method_exists($this, $op = I('get.op'))) {
            return $this->$op();
        }

        return $this->getOrders();

    }

    public function getOrders(){

        $contacts = $this->provider->getContact();

        $customer_id = $contacts['customer_id'];

        $orders = M('receivables')->where(['customer_id' => $customer_id, 'is_deleted' => 0])->order('pay_time desc')->select() ?? [];

        foreach ($orders as &$order) {
            $order['pay_time'] = date('Y-m-d', $order['pay_time']);
        }

        $this->ajaxReturn($orders);
    }
}