<?php

namespace App\Modules\CustomerCenter\controllers;

class Products extends \Action{

    public function handle() {
        $provider = 'WechatProvider'; // 暂时写死, 后期可考虑根据环境自动判断

        $providerClassStr = $this->namespace . '\providers\\' . $provider;

        $this->provider = new $providerClassStr;

        if (method_exists($this, $op = I('get.op'))) {
            return $this->$op();
        }

        return $this->getProducts();

    }

    public function getProducts(){

        $contacts = $this->provider->getContact();

        $customer_id = $contacts['customer_id'];

        $products = M('r_order_product')->alias('order_product')
        ->join('inner join ' . C('DB_PREFIX').'receivables `order` on `order`.receivables_id = order_product.order_id and `order`.is_deleted = 0')
        ->where(['order_product.customer_id' => $customer_id])
        ->select() ?? [];

        foreach ($products as &$product) {
            $order['create_time'] = date('Y-m-d', $order['create_time']);
        }

        $this->ajaxReturn($products);
    }
}