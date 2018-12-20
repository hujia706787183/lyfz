<?php

namespace App\Lib\Plugins\Product;

use App\Lib\Abstracts\ProductPlugin;

class ProductHardware extends ProductPlugin
{
    public function renderButton()
    {
        return [
            [
                'label'   =>  '查询',
                'action'  =>  'query',
            ],
            [
                'label'   =>  '发货',
                'action'  =>  'deliverGoods',
            ],
        ];
    }

    public function renderForm()
    {
        return [

        ];
    }

    public function echoButtons()
    {
        $renderButtons = self::renderButton();
        $stringButtons = '';
        foreach ($renderButtons as $renderButton){
            $stringButtons .= "<button type='button' onclick='{$renderButton['action']}($this->order_product_id)' class='btn btn-default btn-xs'>{$renderButton['label']}</button>&nbsp;&nbsp;&nbsp;";
        }
        return $stringButtons;
    }
}
