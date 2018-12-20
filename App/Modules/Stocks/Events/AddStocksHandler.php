<?php

namespace App\Modules\Stocks\Events;


class AddStocksHandler
{
    public function handle ($order_id)
    {
        $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8,2']])->getField('product_id', true);//获取铂金版,黄金版,标准版,经典版产品id
        $applet_ids = [82, 33, 32, 36];
        $user = M('user');
        $order_products = M('rOrderProduct')->where(['order_id' => $order_id])->select();// 一个订单可能有多个产品
        foreach ($order_products as $order_product){
            $isNotExisted = !M('stocksRecord')->where(['type'=>'OrderProduct', 'type_id'=>$order_product['product_id']])->find();
            if ($isNotExisted && in_array($order_product['product_id'], $platinum_ids)){ // 数据库里面没有该订单产品id才进行添加，防止数据加重
                $user->where(['role_id' => $order_product['owner_role_id']])->setInc('stocks', 10); // 10股
                M('stocksRecord')->add(['stocks'=>10, 'role_id'=>$order_product['owner_role_id'], 'type'=>'OrderProduct', 'type_id'=>$order_product['id'], 'description'=>$order_product['product_name'].'收清余款', 'created_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')]);
            }
            if ($isNotExisted && in_array($order_product['product_id'], $applet_ids)){// 数据库里面没有该订单产品id才进行添加，防止数据加重
                $user->where(['role_id' => $order_product['owner_role_id']])->setInc('stocks', 20); // 20股
                M('stocksRecord')->add(['stocks'=>20, 'role_id'=>$order_product['owner_role_id'], 'type'=>'OrderProduct', 'type_id'=>$order_product['id'], 'description'=>$order_product['product_name'].'收清余款', 'created_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')]);
            }
        }
    }
}