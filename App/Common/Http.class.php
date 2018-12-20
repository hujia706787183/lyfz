<?php

class Http {
    public static function sendRequest($url, $params = array() , $headers = array(), $jsonfmt = false){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($params)) {
            if ($jsonfmt) {
                $header = array(
                    'Content-Type: application/json; charset=UTF-8',
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $params = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);//严格校验2
        $txt = curl_exec($ch);
        if (curl_errno($ch)) {
            $array[0] = 0;
            $array[1] = L("CONNECT TO A SERVER ERROR");
            $array[2] = -1;
            $return = $array;
        } else {
            $return = json_decode($txt, true);
            if (!$return) {
                $array[0] = 0;
                $array[1] = L("THE SERVER RETURNS DATA ANOMALIES");
                $array[2] = -1;
                $return = $array;
            }
        }
        return $return;
    }

    public static function PostJson($url, $params = [], $header = []){
        return self::sendRequest($url, $params, $header, true);
    }
}