<?php
$modulesPath = __DIR__.'/../Modules';
$dirList = scandir($modulesPath);
$configs = [];
foreach ($dirList as $dir) {
    $fullDir = $modulesPath . DIRECTORY_SEPARATOR . $dir;
    if (!in_array($dir, ['.', '..']) && is_dir($fullDir)) 
    { 
        $moduleConfigFile = $fullDir . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($moduleConfigFile)){
            $module_config = require $moduleConfigFile;
            if (is_array($module_config)){
                if (empty($module_config['key'])){
                    $module_config['key'] = $dir;
                }
                array_push($configs, $module_config);
            }
        }
    }
}

return $configs;