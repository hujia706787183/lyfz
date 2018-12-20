<?php

namespace App\Common;

class Modules {

    static $instance = null;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function __callStatic($method, $arguments){
        if (method_exists(self::getInstance(), $method)){
            return self::$instance->$method(...$arguments);
        }
    }

    protected $modules;

    public function __construct() {
        $modules = require __DIR__ . '/../Conf/modules.php';
        $this->modules = array_column($modules, null, 'key');
    }

    protected function list() {
        return $this->modules;
    }

    protected function get($module_key) {
        return $this->modules[$module_key];
    }
}