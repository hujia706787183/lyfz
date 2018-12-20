<?php
namespace App\Lib\Plugins\Product;
use App\Lib\Abstracts\ProductPlugin;
use App\Lib\Model\Position;

class ProductOriginal extends ProductPlugin
{
    public function renderButton()
    {
        return [
            [
                'label'   =>  '设置产品',
                'action'  =>  'setProductId',
            ],
            [
                'label'   =>  '删除订单产品',
                'action'  =>  'deleteOrderProduct',
            ],
        ];
    }

    /**
     * 设置产品信息的表单
     * @return array
     */
    public function renderForm()
    {
        return [
            [
                'label' => '短信账号',
                'name'  => 'message_account',
                'type'  => 'text',
            ],
            [
                'label' => '短信密码',
                'name'  => 'message_account_password',
                'type'  => 'text',
            ],
            [
                'label' => '下次服务时间',
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
            [
                'label' => '快递公司',
                'name'  => 'express_company',
                'type'  => 'text',
            ],
            [
                'label' => '快递单号',
                'name'  => 'express_number',
                'type'  => 'text',
            ],
            [
                'label' => '提成发放时间',
                'name'  => 'bonus_time',
                'type'  => 'text',
            ],
            [
                'label' => '提成金额',
                'name'  => 'bonus_amount',
                'type'  => 'text',
            ],
            [
                'label' => '上门人员',
                'name'  => 'door_username',
                'type'  => 'text',
            ],
            [
                'label' => '上门时间',
                'name'  => 'door_time',
                'type'  => 'text',
            ],
        ];
    }

    public function echoButtons()
    {
        $position_id = Position::getPositionId($_SESSION['role_id']);
        $renderButtons = self::renderButton();
        $stringButtons = '';
        if (in_array($position_id, [1, 88])){
            foreach ($renderButtons as $renderButton){
                $stringButtons .= "<button type='button' onclick='{$renderButton['action']}($this->order_product_id)' class='btn btn-default btn-xs'>{$renderButton['label']}</button>&nbsp;&nbsp;&nbsp;";
            }
        }
        return $stringButtons;
    }
}
