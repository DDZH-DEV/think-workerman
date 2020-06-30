<?php
use Workerman\Worker;

require_once dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'core/init.php';
include_once CORE_PATH. '/WebServer.php';

// 启动一个webserver，用于吐html css js，方便展示
// 这个webserver服务不是必须的，可以将这些html css js文件放到你的项目下用nginx或者apache跑

$web = new WebServer('http://0.0.0.0:'.Config::$http['http_port']);

$web->count=Config::$http['count'];

//TP5 public 文件夹判断
$web->addRoot('localhost',PUBLIC_PATH);

$web->onWorkerStart=function (){
    //黑名单
    //model('chat/Deny')->initDenyIps();
};


if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
