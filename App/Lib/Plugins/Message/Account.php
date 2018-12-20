<?php

namespace App\Lib\Plugins\Message;

use App\Extend\Http;

class Account {
    const BASE_URL = 'http://msg.lyfz.net:8600/ISmsService/';

    public static function find($accountName) {
        $account = new Account();

        $account->setAccount($accountName);

        return $account;
    }

    public function setAccount($account) {
        $this->account = $account;
    }

    public function setToken($token) {
        $this->token = $token;
    }
    /** 充值 */
    public function smsRecharge($money, $rechargeCount, $remark)
    {
        $params = [
            'Account'       =>  $this->account,
            'Money'         =>  $money,
            'RechargeCount' =>  $rechargeCount,
            'Remarks'       =>  $remark,
        ];
        return Http::PostJson(self::BASE_URL.'SmsRecharge/'.$this->token, $params);
    }
}