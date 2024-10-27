<?php

use Workerman\Worker; 

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/base.php'; 

define('WORKER_VERSION',Worker::VERSION);

\system\WebServer::run();  

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
