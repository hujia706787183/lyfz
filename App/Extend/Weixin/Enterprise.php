<?php

namespace App\Extend\Weixin;

use EasyWeChat\Factory;

class Enterprise {
    public static function getApp(){
        $config = [
            'corp_id' => C('WX_ENTERPRISE_CORPID'),
            'agent_id' => 1000028, 
            'secret' => C('WX_ENTERPRISE_CORPSECRIT'),
            'token' => C('WX_ENTERPRISE_TOKEN'),          // Token
            'aes_key' => C('WX_ENTERPRISE_ENCODING_AES_KEY')                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！
        ];
        return Factory::work($config);
    }

    /**
     * 打卡
     * @return \EasyWeChat\Work\Application
     */
    public static function getSignApp(){
        $config = [
            'corp_id' => C('WX_ENTERPRISE_CORPID'),
            'agent_id' => 3010011,
            'secret' => 'R--jDijfPqbNqD1JqQrjTCqUvYv-Nhy-KOyKYn3RZuI',
            'token' => C('WX_ENTERPRISE_TOKEN'), // Token
            'aes_key' => C('WX_ENTERPRISE_ENCODING_AES_KEY') // EncodingAESKey，兼容与安全模式下请一定要填写！！！
        ];
        return Factory::work($config);
    }

    public static function getContactsApp() {
        $config = [
            'corp_id' => C('WX_ENTERPRISE_CORPID'),
            'secret' => 'dFWPOIslJ7VNv45daUCtQsJ8T_qQn-RImz8aWgEzeos',
            'token' => 'ZYDroRxbtCXVihoPX42TFK85ocyV', // Token
            'aes_key' => 'bFHtFVFAGhSNLeHbDWXWooQrLOBoL8UfUEaQf2kHRyn' // EncodingAESKey，兼容与安全模式下请一定要填写！！！
        ];
        
        return Factory::work($config);
    }
}