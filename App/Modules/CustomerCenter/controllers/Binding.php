<?php

namespace App\Modules\CustomerCenter\controllers;

use App\Extend\VarifyCode;
use App\Extend\Message;

class Binding extends \Action {
    public function handle() {

        $provider = 'WechatProvider'; // 暂时写死, 后期可考虑根据环境自动判断

        $providerClassStr = $this->namespace . '\providers\\' . $provider;

        $this->provider = new $providerClassStr;

        if (method_exists($this, $op = I('get.op'))) {
            return $this->$op();
        }

        $this->display();
    }

    public function getBindingVarifyCode(){
        $tel = I('get.tel');
        if (!$tel)$this->ajaxReturn(null, '缺少参数', -1);

        $contacts = M('contacts')->alias('contacts')
        ->where(['telephone' => $tel])
        ->count();

        if ($contacts){
            $code = VarifyCode::generate($tel);
            $message = new Message();
            $message->sendMessage($tel, '尊敬的利亚方舟用户, 您的验证码是:' . $code);
            $this->ajaxReturn(null, $code, 0);
        } else {
            $this->ajaxReturn(null, '抱歉，没有找到您的信息', 404);
        }
    }

    public function getCustomerInfo () {
        $tel = I('get.tel');
        $code = I('get.code');

        if(VarifyCode::varify($tel, $code)){
            $contacts = M('contacts')->alias('contacts')
                ->join(C('DB_PREFIX').'r_contacts_customer cc on cc.contacts_id = contacts.contacts_id')
                ->join(C('DB_PREFIX').'customer customer on customer.customer_id = cc.customer_id')
                ->where(['telephone' => $tel])
                ->field('customer.name as customer_name, contacts.name as contacts_name, contacts.contacts_id as contacts_id')
                ->find();
            $this->ajaxReturn($contacts);
        } else {
            $this->ajaxReturn(null, '验证码不正确', -2);
        }
    }
    
    public function confirm () {
        $contacts_id = I('get.contacts_id');

        if (!$contacts_id) {
            $this->ajaxReturn(null, '缺少参数', -1);
        }

        $this->provider->bind($contacts_id);

        $this->ajaxReturn('', 'OK', 0);
    }


    public function cancel () {
        $contacts_id = I('get.contacts_id');

        if (!$contacts_id) {
            $this->ajaxReturn(null, '缺少参数', -1);
        }

        $this->provider->cancel($contacts_id);
    }
}