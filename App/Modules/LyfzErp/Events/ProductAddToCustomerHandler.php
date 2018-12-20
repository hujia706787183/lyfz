<?php

namespace App\Modules\LyfzErp\Events;

use App\Modules\LyfzErp\Services\Manager;

class ProductAddToCustomerHandler {
    
    public function handle ($product_info) {

        if (in_array( $product_info['product_id'] , Manager::getProductIds())){
            M('r_order_product_extra')->add([
                'order_product_id' => $product_info['order_product_id'],
                'extra_key' => 'next_service_fee_date',
                'extra_value' => strtotime('next year'),
                'extra_type' => 'timestamp'
            ]);
        }

    }
    
}