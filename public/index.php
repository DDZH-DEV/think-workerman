<?php

!defined('FPM_MODE') && define('FPM_MODE', true);
 
if (!defined('IS_CLI')) { 
    !session_id() && session_start();
    include dirname(__DIR__) . "/core/base.php";  
    g('IP', ip());
    g('POST', $_POST);
    g('GET', $_GET);
    g('FILES', $_FILES);
    g('SESSION', $_SESSION);
    g('SERVER', $_SERVER);
    g('COOKIE', $_COOKIE);

    \system\App::run();
}

