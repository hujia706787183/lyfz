<?php

namespace App\Extend;

class Dingding
{
//https://oapi.dingtalk.com/checkin/record?access_token=5b59c16a6e4c3825a19f1e49a86b492c&department_id=1&start_time=1512057600000&end_time=1512662400000&offset=0&size=100&order=asc
//https://oapi.dingtalk.com/gettoken?corpid=ding3926092e218bc5d0&corpsecret=zQWpR2McKbh6owJmUAyPllulq9zfe9QddZjVrnspisqbhExuEE3zELEN7Jp0aCWh
    protected static $BASE_URL = "https://oapi.dingtalk.com" ;
    protected static $corp_id = 'ding3926092e218bc5d0';

    protected static $corp_secret = 'zQWpR2McKbh6owJmUAyPllulq9zfe9QddZjVrnspisqbhExuEE3zELEN7Jp0aCWh';

//    public static function getToken(){
//        return Http::sendRequest(self::$BASE_URL.'/gettoken', [
//            'corpid' => self::$corp_id,
//            'corpsecret' => self::$corp_secret
//        ]);
//    }

    public static function getToken(){
        return Http::sendRequest(self::$BASE_URL.'/gettoken?corpid='.self::$corp_id.'&corpsecret='.self::$corp_secret)['access_token'];
    }

//    public static function getSignData($department_id = 1, $start_time = 1483200000000, $end_time = null, $offset = 0, $size = 100, $order = 'asc'){
//        return Http::sendRequest(self::$BASE_URL.'/checkin/record?access_token='.self::getToken().'&department_id='.$department_id.
//            '&start_time='.$start_time.'&end_time='.$end_time.'&offset='.$offset.'&size='.$size.'&order='.$order);
//    }
    public static function getSignData($department_id = 1, $start_time = 1512057600000){
        return Http::sendRequest(self::$BASE_URL.'/checkin/record?access_token='.self::getToken().'&department_id='.$department_id.'&start_time='.$start_time);
    }
}