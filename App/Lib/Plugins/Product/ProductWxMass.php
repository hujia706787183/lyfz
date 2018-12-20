<?php

namespace App\Lib\Plugins\Product;

use App\Lib\Abstracts\ProductPlugin;

class ProductWxMass extends ProductPlugin {
    
    public function renderForm()
    {
        return [[
                'label' => '账号',
                'name'  => 'account',
                'type'  => 'text',
            ], [
                'label' => '密码',
                'name'  => 'password',
                'type'  => 'text',
        ]];
    }

    public function renderButton() {
        return [];
    }
    
    public function echoButtons() {
        return '';
    }
}

