<?php

namespace system;

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
        app('db')::setConfig(config('database'));
        //缓存设置
        app('cache')::config(config('cache'));
        //设置日志
        app('log')::init(config('log'));

        app('assets')->config(config('assets'));

        self::init_dir();
        self::init_error_log();
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
        register_shutdown_function("system\\Debug::check_for_fatal");
        set_error_handler("system\\Debug::log_error");
        set_exception_handler("system\\Debug::log_exception");
    }

    static function init_dir()
    {
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

    protected static function run()
    {
        if (defined('FPM_MODE') || defined('WEB_SERVER')) {
            WebServer::dispatchHttp();
        }

        ob_end_flush();
        session_write_close();
    }
}