<?php

!defined('FPM_MODE') && define('FPM_MODE', true);
 
if (!defined('IS_CLI')) { 
    // 添加跨域相关的 header
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: *');

    // 处理 OPTIONS 预检请求
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
    !session_id() && session_start();
    include dirname(__DIR__) . "/core/base.php";  
    g('IP', ip());
    g('POST', $_POST);
    g('RAW', file_get_contents('php://input'));
    g('GET', $_GET);
    g('FILES', $_FILES);
    g('SESSION', $_SESSION);
    g('SERVER', $_SERVER);
    g('COOKIE', $_COOKIE);

    \system\App::run();
}

