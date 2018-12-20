<?php

namespace App\Extend;


class VarifyCode {

    public static function generate($identifier){
        if ($identifier){
            $code = cache($identifier);
            if (!$code) {
                $code = \rand(100000,999999);
                cache($identifier, $code, 300);
            } 
        } else {
            throw new \Exception("Parameters Error");
        }
        return $code;
    }

    public static function varify($identifier, $code){
        if (cache($identifier) == $code) {
            return true;
        }
        return false;
    }
}
