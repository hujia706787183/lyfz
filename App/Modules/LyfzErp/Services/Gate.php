<?php

namespace App\Modules\LyfzErp\Services;

use App\Extend\Http;

class Gate
{
    const ACCOUNT  = 'admin';
    const PASSWORD = '123';
    const BASE_URL = 'http://msg.lyfz.net:8600/ISmsService/';

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
            return $information['data'];
        }else{
            throw new \Exception('获取token失败', -10001);
        }
    }

    /**
     * 设置铂金版软件运行有效期
     * @param $domainName
     * @param $validateRuns
     * @return mixed
     * @throws \Exception
     */
    public static function setValidDateRuns($domainName, $validateRuns)
    {

        if (empty($domainName)){
            throw new \Exception('加密狗域名不能为空', -10001);
        }

        if (empty($validateRuns)){
            throw new \Exception('有效期不能为空', -10001);
        }

        $params = [
            'DomainName'        =>  $domainName,
            'ValidDateRuns'     =>  $validateRuns,
            'PermanentRegister' =>  false
        ];

        return Http::PostJson(self::BASE_URL.'SetValidDateRuns/'.self::getToken()['token'], $params);
    }
}