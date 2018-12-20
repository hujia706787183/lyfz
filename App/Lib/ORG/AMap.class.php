<?php

class AMap
{
    protected static $key = '6aa768ed9bdd9a52295295df030729c3';

    public static function getGeoCodes($address){
        return Http::sendRequest("http://restapi.amap.com/v3/geocode/geo?key=".self::$key."&address=$address");
    }

    public static function getRegeoCodes($locations){
        return Http::sendRequest("http://restapi.amap.com/v3/geocode/regeo?key=".self::$key."&location=".$locations);
    }
}