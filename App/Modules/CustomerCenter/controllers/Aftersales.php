<?php

namespace App\Modules\CustomerCenter\controllers;

class Aftersales extends \Action{

    public function handle() {
        $provider = 'WechatProvider'; // 暂时写死, 后期可考虑根据环境自动判断

        $providerClassStr = $this->namespace . '\providers\\' . $provider;

        $this->provider = new $providerClassStr;

        if (method_exists($this, $op = I('get.op'))) {
            return $this->$op();
        }

        return $this->getAftersales();

    }

    public function getAftersales(){

        $contacts = $this->provider->getContact();

        $customer_id = $contacts['customer_id'];

        // $aftersales = M('onsiteservice')->where(['customer_id' => $customer_id])->select() ?? [];
        $aftersales = M('service')->alias('service')
        ->where(['customer_id' => $customer_id])
        ->join(C('DB_PREFIX').'user user on service.service_personal_id = user.user_id')
        ->field('service.*, user.name as server_name')
        ->select() ?? [];
        foreach ($aftersales as &$Aftersale) {
            $order['create_time'] = date('Y-m-d', $order['create_time']);
        }

        $this->ajaxReturn($aftersales);
    }
}