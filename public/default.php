<?php

!defined('FPM_MODE') && define('FPM_MODE' , true);

if (!defined('IS_CLI') || 1) {

    session_start();
    include dirname(__DIR__) . "/core/init.php";

    _G('IP', getIP());
    _G('POST', $_POST);
    _G('GET', $_GET);
    _G('FILES', $_FILES);
    _G('SESSION', $_SESSION);
    _G('SERVER', $_SERVER);
    _G('COOKIE', $_COOKIE);
    _G('DEBUG', file_exists(APP_PATH . '/debug'));
    \rax\App::run();
}

