<?php

namespace App\Lib\Action;

class BaseAction extends \Action{

    protected function _initialize(){
        // 注入数据库的配置信息
        C(M('config')->getField('name, value'));
        
    }

}