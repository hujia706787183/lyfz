<?php

namespace App\Extend\Weixin;

use EasyWeChat\Factory;

class Pay {
    public static function getPayment () {
        $config = [
            'app_id' => 'wx4a14a2375e93cb7b',
            'mch_id'        => '1433774502',
            'key'                => 'xiaomeihaomeilyfz075223988650000',
            'notify_url'         => 'api.php/finance/wx_qrcode_pay_callback',
        ];

        return Factory::payment($config);;
    }
}