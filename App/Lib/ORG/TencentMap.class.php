<?php

class TencentMap
{
    protected static $key = 'Q44BZ-WZ2CS-Z4KOA-6QZBU-ICXO6-SFBZO';

    public static function getDistrictList(){
        return Http::sendRequest("http://apis.map.qq.com/ws/district/v1/list?key=".self::$key);
    }
}