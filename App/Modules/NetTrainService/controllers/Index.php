<?php

namespace App\Modules\NetTrainService\controllers;

class Index {
    public function handle () {
        $service = new Service();
        $method = ACTION_NAME;

        $service->$method();
    }
}