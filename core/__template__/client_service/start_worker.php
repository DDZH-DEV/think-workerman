<?php

use Workerman\Crontab\Crontab;
use Workerman\Worker;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/base.php';

$worker = new Worker();
$worker->name = '__WORKER_NAME__';
$worker->count = 1;

// 设置时区，避免运行结果与预期不一致
date_default_timezone_set('PRC');

$worker->onWorkerStart = function ($worker) {
    
    
};

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
