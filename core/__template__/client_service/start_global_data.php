<?php

use Workerman\Worker;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'core'.DIRECTORY_SEPARATOR.'init.php';

// 监听端口
$worker = new GlobalData\Server('0.0.0.0', Config::$global_data['port']);

$worker->name  =  Config::$global_data['name']?Config::$global_data['name']:'GlobalDataServer';


if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
