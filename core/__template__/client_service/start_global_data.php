<?php

use Workerman\Worker;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'base.php';


if (!config('global_data.server_port')) {
    return false;
}
// 监听端口
$worker = new data\Server('0.0.0.0', config('global_data.server_port'));

$worker->name = config('global_data.name') ? config('global_data.name') : 'GlobalDataServer';


if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
