<?php

namespace App\Lib\Model;

class OrderProduct {

    public static function getMessageAccount($order_product_id)
    {
        return M('ROrderProductExtra')->where(['order_product_id'=>$order_product_id, 'extra_key'=>'message_account'])->order('id desc')->getField('extra_value');
    }
}