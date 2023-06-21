<?php

date_default_timezone_set("PRC");

ini_set("display_errors",1);

//服务路径
const CORE_PATH = __DIR__;
//根目录
define('ROOT_PATH',(dirname(CORE_PATH)));

const DS = DIRECTORY_SEPARATOR;

$Loader=require_once (ROOT_PATH.DS.'vendor'.DS.'autoload.php');

//加载系统函数
require_once (CORE_PATH.DS.'functions.php');

//加载配置文件
$app_path=dirname(dirname(debug_backtrace()[0]['file']));

define('APP_PATH',$app_path.DIRECTORY_SEPARATOR);

//console('[APP_PATH] : '.APP_PATH);
include_once APP_PATH.'Config.php';

//设置日志
$log_path=Config::$log['channels']['file']['path'];
if(!is_dir($log_path)){
    mkdir($log_path,0777,true);
}

//设置错误信息输出到文件
ini_set('log_errors', 1);
//设置错误输出文件
ini_set("error_log", $log_path);


/**
 * Error handler, passes flow over the exception logger with new ErrorException.
 */
function log_error( $num, $str, $file, $line, $context = null )
{
    log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
}


/**
 * Uncaught exception handler.
 */
function log_exception( $e )
{
    if(strpos($e->getMessage(),'stream_select')===false){
        $message = "Type: " . get_class( $e ) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};". "\r\n" . $e->getTraceAsString();
        \think\facade\Log::error($message . PHP_EOL);
    }

}
/**
 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
 */
function check_for_fatal()
{
    $error = error_get_last();

    if ( in_array($error['type'],[E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR]) ){
        log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
    }
}

register_shutdown_function( "check_for_fatal" );
set_error_handler( "log_error");
set_exception_handler( "log_exception" );


//根据配置注册命名空间为app
$Loader->setPsr4('app\\', APP_PATH.'app');

//TP项目对外路径
if(!defined('PUBLIC_PATH') && !Config::$http['public_dir']){
    define('PUBLIC_PATH',ROOT_PATH.DS.'public');
}elseif (Config::$http['public_dir']){
    define('PUBLIC_PATH',Config::$http['public_dir']);
}

//项目上传文件夹,TP项目对外路径
define('UPLOAD_PATH',PUBLIC_PATH.DS.Config::$http['upload_dir'].DS);

if(!is_dir(UPLOAD_PATH)){
    @mkdir(UPLOAD_PATH,0777,true);
}


if(file_exists(APP_PATH.'functions.php')){
    include_once APP_PATH.'functions.php';
}
