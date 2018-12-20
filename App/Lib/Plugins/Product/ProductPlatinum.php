<?php
namespace App\Lib\Plugins\Product;
//use App\Lib\Interfaces\ProductPlugin;
use App\Lib\Abstracts\ProductPlugin;
class ProductPlatinum extends ProductPlugin
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
//            [
//                'label' => '快递公司',
//                'name'  => 'express_company',
//                'type'  => 'text',
//            ],
//            [
//                'label' => '快递单号',
//                'name'  => 'express_number',
//                'type'  => 'text',
//            ],
//             [
//                 'label' => '提成发放时间',
//                 'name'  => 'bonus_time',
//                 'type'  => 'text',
//             ],
//             [
//                 'label' => '上门人员',
//                 'name'  => 'door_username',
//                 'type'  => 'text',
//             ],
//             [
//                 'label' => '上门时间',
//                 'name'  => 'door_time',
//                 'type'  => 'text',
//             ],
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

    public static function CustomerAuthByDomain($domain) {
        $customer_info = M('ROrderProductExtra')
        ->alias('OrderProductExtra')
        ->join(C('DB_PREFIX').'r_order_product order_product on order_product.id = OrderProductExtra.order_product_id')
        ->join(C('DB_PREFIX').'customer customer on customer.customer_id = order_product.customer_id')
        ->field('customer.name as customer_name, customer.customer_id, industry')
        ->where(['extra_key' => 'domain', 'extra_value' => $domain])
        ->find();

        $current_time = time();
        $customer_info['token'] = md5($customer_info['customer_id'].$current_time);
        $auth_info = [
            'token' => $customer_info['token'],
            'create_time' => $current_time
        ];
        if (M('customer_auth')->where(['customer_id' => $customer_info['customer_id']])->find()) {
            M('customer_auth')->where(['customer_id' => $customer_info['customer_id']])->save($auth_info);
        } else {
            $auth_info['customer_id'] = $customer_info['customer_id'];
            M('customer_auth')->add($auth_info);
        }
        
        unset($customer_info['customer_id']);
        return $customer_info;
    }

    public static function createOrder($order_product_id, $service_fee, $creator_role_id, $service_time, $owner_role_id, $name, $order_describe='')
    {
        $rOrderProduct = M('ROrderProduct')->field('customer_id, owner_role_id')->where('id = %d', $order_product_id)->find();
        $order_number = 'O'.date('Ymd').mt_rand(1000,9999);
        $order_info = [
            'customer_id' => $rOrderProduct['customer_id'],
            'name' => $name,
            'order_number' => $order_number,
            'price' => $service_fee,
            'owner_role_id' => $owner_role_id ?? $rOrderProduct['owner_role_id'],//没传使用订单产品的负责人
            'creator_role_id' => $creator_role_id,
            'create_time' => time(),
            'pay_time' => time(),
            'status' => 0,
            'description' => $order_describe,
        ];
        $order_id = M('receivables')->add($order_info);

        $plugin_params = serialize(['service_fee' => $service_fee, 'service_time'=>$service_time, 'order_product_id'=>$order_product_id]);

        $OrderPlugin = [
            'order_id' => $order_id,
            'plugin_id'     => 2,
            'plugin_name'   => 'PlatinumPlugin',
            'createtime'    => time(),
            'plugin_params' => $plugin_params,
        ];

        M('OrderPlugin')->add($OrderPlugin);

        return $order_id;
    }
}
