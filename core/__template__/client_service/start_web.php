<?php

use Workerman\Worker;


require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/init.php';



define('WORKER_VERSION',Worker::VERSION);

$web = new \system\WebServer(config('http.http_server'));
$web->name  =  config('http.name')?config('http.name'):'WebServer';
$web->count = config('http.count');


if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
