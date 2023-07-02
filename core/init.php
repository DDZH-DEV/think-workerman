<?php

date_default_timezone_set("PRC");
ini_set("display_errors", 1);

//服务路径
!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
!defined('CORE_PATH') && define('CORE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
//根目录
!defined('ROOT_PATH') && define('ROOT_PATH', (dirname(CORE_PATH) . DIRECTORY_SEPARATOR));
!defined('APP_PATH') && define('APP_PATH', ROOT_PATH . 'apps' . DIRECTORY_SEPARATOR);
!defined('PUBLIC_PATH') && define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);
!defined('RUNTIME_PATH') && define('RUNTIME_PATH', ROOT_PATH . 'server' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR);
!defined('IS_CLI') && define('IS_CLI',php_sapi_name()==='cli');


//加载系统函数文件
require_once(CORE_PATH . DIRECTORY_SEPARATOR . 'functions.php');

$Loader = require(ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

bind('loader', $Loader);
//根据配置注册命名空间为app
$Loader->setPsr4('app\\', APP_PATH);


//加载默认配置文件
is_file($file = APP_PATH . 'config.php') && \rax\Config::load($file);
//加载各应用下的文件
foreach (glob(dirname(__DIR__, 1) . '/apps/*') as $dir) {
    if (is_dir($dir) && file_exists($config_file = $dir . '/config.php')) {
        \rax\Config::load($config_file);
    }
    is_file($dir . '/functions.php') && include_once $dir . '/functions.php';
}
//初始化一些配置
\rax\App::init();