<?php

namespace App\Modules\CustomerCenter\controllers;

class Index extends \Action {
    public function handle () {
        $provider = 'WechatProvider'; // 暂时写死, 后期可考虑根据环境自动判断

        $providerClassStr = $this->namespace . '\providers\\' . $provider;

        $this->provider = new $providerClassStr;

        if (method_exists($this, $op = I('get.op'))) {
            return $this->$op();
        }

        $this->display('index');
    }

    public function getMine () {
        $contact = $this->provider->getContact();

        if ($contact) {
            $this->ajaxReturn($contact); 
        } else {
            $this->ajaxReturn('', '您还绑定账号, 请绑定后继续', 1001); 
        }
    }
}