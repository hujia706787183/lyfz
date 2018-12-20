<?php

namespace App\Lib\Plugins\Message;

use App\Extend\Http;

class Gateway {

    const ACCOUNT  = 'admin';
    const PASSWORD = '123';
    const BASE_URL = 'http://msg.lyfz.net:8600/ISmsService/';

    public static function getAccount($accountName) {
        $account = Account::find($accountName);
        $account->setToken(self::getToken());
        return $account;
    }

    /**
     * 获取Token
     * @return mixed
     * @throws \Exception
     */
    public static function getToken()
    {
        $params = [
            'account'   =>  self::ACCOUNT,
            'password'  =>  strtoupper(md5(self::ACCOUNT.self::PASSWORD))
        ];
        $information = Http::PostJson(self::BASE_URL.'GetToken', $params);
        if ($information['code'] == 200){
            return $information['data']['token'];
        }else{
            throw new \Exception($information['msg']);
        }
    }
}