<?php
namespace App\Lib\Plugins\Product;

use App\Lib\Abstracts\ProductPlugin;

class ProductClassic extends ProductPlugin
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
            [
                'label'   =>  '服务费充值',
                'action'  =>  'serviceCharge',
            ],
            [
                'label'   =>  '更换铂金版',
                'action'  =>  'changePlatinum',
            ],
        ];
    }

    public function renderForm()
    {
        return [
            [
                'label' => '下次交服务费时间',
                'name'  => 'next_service_fee_date',
                'type'  => 'text',
            ],
            [
                'label' => '服务费',
                'name'  => 'service_fee',
                'type'  => 'text',
            ],
            [
                'label' => '域名信息',
                'name'  => 'domain',
                'type'  => 'text',
            ],
            // [
            //     'label' => '提成发放时间',
            //     'name'  => 'bonus_time',
            //     'type'  => 'text',
            // ],
            // [
            //     'label' => '上门人员',
            //     'name'  => 'door_username',
            //     'type'  => 'text',
            // ],
            // [
            //     'label' => '上门时间',
            //     'name'  => 'door_time',
            //     'type'  => 'text',
            // ],
            [
                'label' => '产品备注',
                'name'  => 'product_remark',
                'type'  => 'text',
            ],
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
