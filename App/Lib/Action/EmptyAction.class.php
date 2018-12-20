<?php

use App\Common\Modules;

class EmptyAction {
    public function __call($method, $arguments){
        if ($config = Modules::get(MODULE_NAME)){

            C($config['tp_config']);

            if (empty($method)) {
                $method = 'Index';
            }

            $targetStr = "App\Modules\\".ucfirst(MODULE_NAME)."\\controllers\\".ucfirst($method);
            if (!class_exists($targetStr)) {
                $targetStr = "App\Modules\\".ucfirst(MODULE_NAME)."\\controllers\\Index";
            }

            C('TEMPLATE_NAME',APP_PATH . 'Modules/'. ucfirst(MODULE_NAME) . '/views/'.$method.C('TMPL_TEMPLATE_SUFFIX'));

            $target = new $targetStr;

            $target->namespace = "App\Modules\\".ucfirst(MODULE_NAME);

            $target->handle();
        }
    }
}

