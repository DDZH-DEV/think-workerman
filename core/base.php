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

 //判断RUNTIME_PATH文件夹是否存在,不存在则创建文件夹
 if (!is_dir(RUNTIME_PATH)) {
     mkdir(RUNTIME_PATH, 0777, true);
 }


if (!APP_DEBUG) {
    $prod_config_file = RUNTIME_PATH . 'prod_config.php';
    $prod_hook_file = RUNTIME_PATH . 'prod_hook.php';
    $prod_functions_file = RUNTIME_PATH . 'prod_functions.php';

    $need_regenerate = !file_exists($prod_config_file) || 
                       !file_exists($prod_hook_file) || 
                       !file_exists($prod_functions_file);

    if ($need_regenerate) {
        list($merged_config, $merged_hooks, $merged_functions) = mergeFiles();

        file_put_contents($prod_config_file, '<?php return ' . var_export($merged_config, true) . ';');
        file_put_contents($prod_hook_file, '<?php return ' . var_export($merged_hooks, true) . ';');
        file_put_contents($prod_functions_file, $merged_functions);
    }
    
    system\Config::load($prod_config_file);
    $hooks = require_once $prod_hook_file;
    
    loadHooks($hooks);
    
    require_once $prod_functions_file;
    
    
} else {
    is_file($file = APP_PATH . 'config.php') && system\Config::load($file);
    
    foreach (glob(dirname(__DIR__, 1) . '/apps/*', GLOB_ONLYDIR) as $dir) {
        $dir_name = basename($dir);
        
        // 检查 app.json 文件
        $app_json_file = $dir . '/app.json';
        if (file_exists($app_json_file)) {
            $app_config = json_decode(file_get_contents($app_json_file), true);
            if (!isset($app_config['enable']) || $app_config['enable'] !== true) {
                continue; // 如果应用未启用,跳过此应用
            }
        }
        
        // 应用已启用,继续加载配置文件
        if (file_exists($config_file = $dir . '/config.php')) {
            system\Config::load($config_file, $dir_name);
        }
        if (file_exists($hook_file = $dir . '/hook.php')) {
            $hooks = require_once $hook_file;
            loadHooks($hooks);
        }
        is_file($dir . '/functions.php') && include_once $dir . '/functions.php';
    }
    
}
 
error_reporting(E_ALL & ~E_NOTICE);
//初始化一些配置
system\App::init();
