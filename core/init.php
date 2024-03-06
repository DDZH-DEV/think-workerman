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
!defined('IS_CLI') && define('IS_CLI', php_sapi_name() === 'cli');
!defined('APP_DEBUG') && define('APP_DEBUG', file_exists(APP_PATH . '/debug'));

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
//加载系统函数文件
require_once(CORE_PATH . DIRECTORY_SEPARATOR . 'functions.php');

is_file(APP_PATH . 'provider.php') && bind(include APP_PATH . 'provider.php');
//加载默认配置文件
is_file($file = APP_PATH . 'config.php') && system\Config::load($file);
//加载各应用下的文件
foreach (glob(dirname(__DIR__, 1) . '/apps/*') as $dir) {
    $dir_name = basename($dir);
    if (is_dir($dir) && file_exists($config_file = $dir . '/config.php')) {
        system\Config::load($config_file, $dir_name);
    }
    //钩子机制
    if (is_dir($dir) && file_exists($hook_file = $dir . '/hook.php')) {

        $hooks = require_once $hook_file;

        foreach ($hooks as $hook_name => $hook) {

            if ($hook['status']) {
                app('hook')->on($hook['hook'], function ($hook_params = [], &$return, $single) use ($hook) {

                    //$hook_params 是调用时传的参数  如hook('demo',$hook_params=[])
                    //$config 是配置的参数
                    $config = $hook['config'] ?? [];
                    if (is_array($hook['event']) && isset($hook['event'][1]) && method_exists($hook['event'][0], $hook['event'][1])) {
                        $return=call_user_func_array($hook['event'], [$hook_params,$config,$return]);
                    } else if (is_callable($hook['event'])) {
                        $return=call_user_func($hook['event'], $hook_params,$config,$return);
                    }

                    if($return===false || ($return && $single)){
                        throw new \JBZoo\Event\ExceptionStop($hook['name'].'运行结束');
                    }

                },$hook['sort']);
            }
        }
    }

    is_dir($dir) && is_file($dir . '/functions.php') && include_once $dir . '/functions.php';
}

error_reporting(E_ALL & ~E_NOTICE);
//初始化一些配置
system\App::init();
