<?php

require_once "./vendor/autoload.php";

define ('APP_NAME','App');
define ('APP_PATH','./App/');
define ('APP_TYPE','Api');
define ('UPLOAD_PATH','./Uploads/');
define ('RUNTIME_PATH',APP_PATH.'Runtime/Api/');
define ('TMPL_PATH',APP_PATH.'Tpl/Api/');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: content-type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

require APP_PATH."/Conf/app_debug.php";
require 'Base/ThinkPHP.php';
