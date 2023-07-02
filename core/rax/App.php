<?php

namespace rax;

use ErrorException;
use Exception;
use think\Facade;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class App extends Facade
{



    protected static function init()
    {

        //初始化数据库
        Db::setConfig(config('database'));
        //缓存设置
        Cache::config(config('cache'));
        //设置日志
        Log::init(config('log'));

        self::init_error_log();
        self::init_public_dir();

    }

    static function init_public_dir()
    {
        //项目对外路径
        if (!defined('PUBLIC_PATH') && !config('http.public_dir')) {
            define('PUBLIC_PATH', ROOT_PATH . 'public');
        } elseif (config('http.public_dir')) {
            define('PUBLIC_PATH', config('http.public_dir'));
        }

        //项目上传文件夹,项目对外路径
        !defined('UPLOAD_PATH')  && define('UPLOAD_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . config('http.upload_dir') . DIRECTORY_SEPARATOR);
        if (!is_dir(UPLOAD_PATH)) {
            @mkdir(UPLOAD_PATH, 0777, true);
        }

    }

    protected static function run()
    {
        if (defined('FPM_MODE') || defined('WEB_SERVER')) {
            WebServer::dispatchHttp();
        }
    }


    /**
     * 获取默认访问的APP
     * @return array|int|mixed|string
     */
    static function getDefaultApp($bind = false)
    {
        //加载配置文件
        $apps_configs = include(ROOT_PATH . '/apps.config.php');
        $app = '';
        $bind_app = '';
        if ($apps_configs) {
            $http_apps = [];
            foreach ($apps_configs as $name => $app) {
                //根据域名找应用
                $domain_filter = isset($app[0]) ? (is_string($app[0]) ? [$app[0]] : $app[0]) : [];

                if ($domain_filter && in_array(_G('SERVER')['SERVER_NAME'], $domain_filter)) {
                    $bind_app = $name;
                    config('bind_app', $name);
                }
                //有http业务的应用
                in_array('http', $app[0]) && $http_apps[] = $name;
            }
            $app = $bind ? $bind_app : ($bind_app ?: $http_apps[0]);
        }

        return $app;
    }

    /**
     * Uncaught exception handler.
     */
    static function log_exception($e, $type = 'exception')
    {
        if (strpos($e->getMessage(), 'stream_select') === false && $e->getMessage()!=='jump_exit' ) {
            $message = "\r\nMessage: {$e->getMessage()}; \r\nFile: {$e->getFile()} => Line: {$e->getLine()};" . "\r\n" . $e->getTraceAsString();
            Log::write($message . PHP_EOL, $type);
        }
    }


    /**
     * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
     */
    static function check_for_fatal()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log_error($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    /**
     * Error handler, passes flow over the exception logger with new ErrorException.
     */
    static function log_error($num, $str, $file, $line)
    {
        self::log_exception(new ErrorException($str, 0, $num, $file, $line));
    }


        /**
     * 初始化错误日志
     * @return void
     */
    static function init_error_log()
    {

        $log_path = config('log.channels.file.path');

        if (!is_dir($log_path)) {
            mkdir($log_path, 0777, true);
        }

        //设置错误信息输出到文件
        ini_set('log_errors', 1);
        //设置错误输出文件
        ini_set("error_log", $log_path);
        //设置日志
        register_shutdown_function("rax\App::check_for_fatal");
        set_error_handler("rax\App::log_error");
        set_exception_handler("rax\App::log_exception");
    }


}