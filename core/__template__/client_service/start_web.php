<?php

use Workerman\Worker;


require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'core/init.php';

if(Config::$waf['enable']){
    \rax\RaxWaf::init(Config::$waf);
}

if (version_compare(Worker::VERSION, '4.0.0', '<')) {
    include_once CORE_PATH . '/WebServer3.php';
} else {
    include_once CORE_PATH . '/WebServer4.php';
}

$web = new WebServer('http://0.0.0.0:' . Config::$http['http_port']); 
$web->name  =  Config::$http['name']?Config::$http['name']:'Web';
$web->count = Config::$http['count'];


if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
