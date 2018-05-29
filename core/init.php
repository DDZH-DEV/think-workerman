<?php

date_default_timezone_set("PRC");
//服务路径
define('CORE_PATH',__DIR__);
//根目录
define('ROOT_PATH',(dirname(CORE_PATH)));

define('DS',DIRECTORY_SEPARATOR);

$Loader=require_once (ROOT_PATH.DS.'vendor'.DS.'autoload.php');

//加载自定义函数
require_once (CORE_PATH.DS.'functions.php');

//加载配置文件
$app_path=dirname(dirname(debug_backtrace()[0]['file']));

define('APP_PATH',$app_path.DIRECTORY_SEPARATOR);

//console('[APP_PATH] : '.APP_PATH);
include_once APP_PATH.'Config.php';


//根据配置注册命名空间为app
$Loader->setPsr4('app\\', APP_PATH);


//TP项目对外路径
if(!defined('PUBLIC_PATH')){
    define('PUBLIC_PATH',ROOT_PATH.DS.'public');
}

//项目上传文件夹,TP项目对外路径
define('UPLOAD_PATH',PUBLIC_PATH.DS.Config::$app['upload_dir'].DS);


if(!is_dir(UPLOAD_PATH)){
    @mkdir(UPLOAD_PATH,0777,true);
}


if(file_exists(APP_PATH.'functions.php')){
    include_once APP_PATH.'functions.php';
}

//初始化数据库
\think\Db::setConfig(Config::$database);
//缓存设置
$Cache=\think\Cache::init(Config::$cache);

\think\Db::setCacheHandler($Cache);



