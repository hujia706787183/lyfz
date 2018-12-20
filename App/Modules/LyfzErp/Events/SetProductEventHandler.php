<?php

namespace App\Modules\LyfzErp\Events;

use App\Modules\LyfzErp\Services\Gate;

class SetProductEventHandler
{
    /**
     * @param $post
     * @throws \Exception
     */
    public function handle ($post) {
        if ($post['next_service_fee_date'] && $post['domain']) {
            try{
                Gate::setValidDateRuns($post['domain'], $post['next_service_fee_date']);
            }catch (\Exception $e){
                throw $e;
            }
        }
    }
}