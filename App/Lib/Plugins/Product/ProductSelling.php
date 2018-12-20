<?php
namespace App\Lib\Plugins\Product;
//use App\Lib\Interfaces\ProductPlugin;
use App\Lib\Abstracts\ProductPlugin;
use App\Lib\Model\Position;
class ProductSelling extends ProductPlugin
{
    public function renderButton()
    {
        return [
            [
                'label'   =>  '注册/授权',
                'action'  =>  'register',
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
        $position_id = Position::getPositionId($_SESSION['role_id']);
        $renderButtons = self::renderButton();
        $stringButtons = '';
        if (in_array($position_id, [1, 88])){
            foreach ($renderButtons as $renderButton){
                $stringButtons .= "<button type='button' onclick='{$renderButton['action']}($this->order_product_id, $this->customer_id)' class='btn btn-default btn-xs'>{$renderButton['label']}</button>&nbsp;&nbsp;&nbsp;";
            }
        }
        return $stringButtons;
    }
}
