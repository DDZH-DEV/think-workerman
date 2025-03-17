<?php

namespace system;

use ErrorException;
use Exception;
use think\Facade;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class App extends Facade {
    protected static function init() {

        self::init_dir();
        self::init_error_log();
        
        
        //初始化数据库
        app('db')::setConfig(config('database'));
        //缓存设置
        app('cache')::config(config('cache'));
        //设置日志
        app('log')::init(config('log'));

        app('assets')->config(config('assets'));
 
        
    }

    /**
     * 初始化错误日志
     * @return void
     */
    static function init_error_log() {
        $log_path = config('log.channels.file.path');

        if (!is_dir($log_path)) {
            mkdir($log_path, 0777, true);
        }

        //设置错误信息输出到文件
        ini_set('log_errors', 1);
        //设置错误输出文件
        ini_set("error_log", $log_path);
        //设置日志
        register_shutdown_function("system\\Debug::check_for_fatal");
        set_error_handler("system\\Debug::log_error");
        set_exception_handler("system\\Debug::log_exception");
    }

    static function init_dir() {
        //项目对外路径
        if (!defined('PUBLIC_PATH') && !config('http.public_dir')) {
            define('PUBLIC_PATH', PUBLIC_PATH);
        } elseif (config('http.public_dir')) {
            define('PUBLIC_PATH', config('http.public_dir'));
        }

        //项目上传文件夹,项目对外路径
        !defined('UPLOAD_PATH') && define('UPLOAD_PATH', PUBLIC_PATH  . config('http.upload_dir') . DIRECTORY_SEPARATOR);
        if (!is_dir(UPLOAD_PATH)) {
            @mkdir(UPLOAD_PATH, 0777, true);
        }
    }

    protected static function run() {
        if (defined('FPM_MODE') || defined('WEB_SERVER')) {
            WebServer::dispatchHttp();
        }

        // 检查是否有活动的输出缓冲区
        if (ob_get_level() > 0) {
            ob_end_flush();
        }

        session_write_close();
    }

    static function  register_all_static_files() {
        $cache_file = RUNTIME_PATH . 'static_files_cache.php';
        // 非调试模式下，如果缓存文件存在，直接加载缓存
        if (!APP_DEBUG && file_exists($cache_file)) {
            $statics = include $cache_file;
        } else {
            $statics = [];

            // 静态文件
            foreach (glob(APP_PATH . '*') as $dir) {
                if (is_dir($dir)) {
                    $app_static_config = is_file($dir . '/view/static.php') ? include($dir . '/view/static.php') : [];
                    //获取最后文件夹名称
                    $layer = basename($dir);

                    $module_statics[$layer] = $app_static_config;
                    $statics = array_merge_recursive($statics, $app_static_config);
                }
            }
            unset($statics['STATIC_MAP']);

            // 在非调试模式下生成缓存文件
            $cache_content = "<?php\nreturn " . var_export($statics, true) . ";\n";
            file_put_contents($cache_file, $cache_content);
            cache('module_statics', $module_statics);
        }

        app('assets')->registerCollection($statics);
    }

    static function module_static_files($layer = '') {
        $module = g('MODULE');

        $layers = is_string($layer) ? [$layer] : $layer;

        $app_static_config = cache('module_statics')[$module] ?? [];

        if (!$app_static_config && file_exists(APP_PATH.$module.'/view/static.php')){
            $app_static_config =include(APP_PATH.$module . '/view/static.php') ;
        };

        if (!$app_static_config) return [];

        $statics = [];

        if($layer){
            array_map(function ($layer) use (&$statics, $app_static_config) {
                isset($app_static_config[$layer]) && $statics = array_merge_recursive($statics, $app_static_config[$layer]);
            }, $layers);
        }else{
            $statics = $app_static_config;
        }

        return $statics;
    }
}
