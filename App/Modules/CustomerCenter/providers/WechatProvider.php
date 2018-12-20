<?php

namespace App\Modules\CustomerCenter\providers;

class WechatProvider {

    protected $userinfo = '';

    public function currentUrl() {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'; 
        return $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
    }

    protected $app = null;

    public function __construct () {

        $this->app = \App\Modules\Weixin\Services\Manager::getOfficialAccount();

        if (!($this->wxuser = session('wxuser'))) {
            if (I('get.code')){
                $this->wxuser = $this->app->oauth->user();
                session('wxuser', $this->wxuser);
            } else {
                $response = $this->app->oauth->scopes(['snsapi_base'])->redirect($this->currentUrl());
                $response->send();
            }
        }
    }

    public function bind ($contactsId) {
        return M('contacts')->where(['contacts_id' => $contactsId])->save([
            'wxopenid' => $this->wxuser->getId()
        ]) !== false;
    }

    public function cancel($contactsId) {
        $this->app->template_message->send([
            'touser' => 'oF_-jjvDOHuILAzB2V5eHW2I2jys',
            'template_id' => 'ccJXeVNS6pssAVlxHuOmS-h58M3ElvRClHP3XPNqpd8',
            'url' => 'https://mcrm.lyfz.net',
            'data' => [
                'first' => '情况不对',
                'content' => '客户信息不正确, 联系人编号'.$contactsId,
                'occurtime' => date('Y-m-d H:i:s')
            ],
        ]);
    }
    
    public function getContact() {
        $openid = $this->wxuser->getId();
 
        return M('contacts')->alias('contacts')->where(['wxopenid' => $openid])
            ->join(C('DB_PREFIX').'r_contacts_customer contacts_customer on contacts.contacts_id = contacts_customer.contacts_id')
            ->join(C('DB_PREFIX').'customer customer on customer.customer_id = contacts_customer.customer_id')
        ->field('contacts.name as contacts_name, customer.name as customer_name, contacts.contacts_id, customer.customer_id')->find();
    }

}