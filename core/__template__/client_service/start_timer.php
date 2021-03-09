<?php

use Workerman\Worker;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'core/init.php';

$worker = new Worker();
$worker->name = 'Timer';


$worker->onWorkerStart = function ($worker) {

    //每隔60秒执行自定义业务
    \Workerman\Lib\Timer::add(60, function () use ($worker) {
        //在这里写业务逻辑
    });

};

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
