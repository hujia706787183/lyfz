<?php

namespace App\Modules\Weixin\Services;

use EasyWeChat\Factory;

class Manager {

    public static function getOfficialAccount () {

        $config = [
            'app_id' => 'wxe30d2c612847beeb',
            'secret' => '21fda8e506bccc505349faa8f4602ce5',
        
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
        
            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];
        
        return Factory::officialAccount($config);
    }

}