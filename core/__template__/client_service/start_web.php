<?php

use Workerman\Worker;


require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'core/init.php';

if(Config::$waf['enable']){
    \rax\RaxWaf::init(Config::$waf);
}

if (version_compare(Worker::VERSION, '4.0.0', '<')) {
    include_once CORE_PATH . '/WebServer3.php'; //windows版本
} else {
    include_once CORE_PATH . '/WebServer4.php'; //linux最新版
}

$web = new WebServer(Config::$http['http_server']);
$web->name  =  Config::$http['name']?Config::$http['name']:'WebServer';
$web->count = Config::$http['count'];


if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
