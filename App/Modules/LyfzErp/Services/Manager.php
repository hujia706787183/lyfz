<?php

namespace App\Modules\LyfzErp\Services;

class Manager {

    public static function getProductIds() {
        return M('product')->where(['category_id' => ['in', '1, 5, 8']])->getField('product_id', true);
    }

}