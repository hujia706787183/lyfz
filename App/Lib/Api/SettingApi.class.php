<?php

use App\Lib\Api\ApiBase;
use App\Common\Modules;
use App\Common\Utils;

class SettingApi extends ApiBase
{
    public function get_config($config_name = null)
    {
        if (empty($config_name)){
            $configs = M('config')->getField('name,value');
            foreach ($configs as $key=>$config){
                $configs[$key] = is_serialized($config) ? unserialize($config) : $config;
            }
        } elseif(strpos($config_name,',')===false) {
            $configs = M('config')->getFieldByName($config_name, 'value');
            $configs = is_serialized($configs) ? unserialize($configs) : $configs;
        }else {
            $config_name = explode(',', $config_name);
            $configs = M('config')->where(['name'=>['in',$config_name]])->getField('name,value');
            foreach ($configs as $key=>$config){
                $configs[$key] = is_serialized($config) ? unserialize($config) : $config;
            }
        }
        $this->ajaxReturn($configs);
    }

    public function set(){
        $allowFields = [];

        if ($module = I('get.module', null)){
            $allowFields = array_merge($allowFields, $this->moduleSettingsFields($module));
        }
        $postData = Utils::arrayWhiteList(I('post.'), $allowFields);

        $this->saveSettingsToDb($postData);

        $this->ajaxReturn();
    }

    protected function saveSettingsToDb($postData) {
        $exist_fields = M('config')->where([
            'name' => ['in', array_keys($postData)]
        ])->getField('name', true);

        $need_update_data = Utils::arrayWhiteList($postData, $exist_fields);
        if (count($need_update_data) > 0) {
            $need_update_data = Utils::array2DbItem($need_update_data, ['name', 'value']);
            foreach($need_update_data as $item) {
                M('config')->where(['name' => $item['name']])->save($item);
            }
        }

        $need_add_data = Utils::arrayBlackList($postData, $exist_fields);
        if (count($need_add_data) > 0) {
            $need_add_data = Utils::array2DbItem($need_add_data, ['name', 'value']);
            M('config')->addAll($need_add_data);
        }
    }

    protected function moduleSettingsFields($module_key) {
        $module = Modules::get($module_key);
        return $module['setting']['fields'];
    }
}