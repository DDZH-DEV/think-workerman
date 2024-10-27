<?php

use Workerman\Crontab\Crontab;
use Workerman\Worker;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/base.php';

$worker = new Worker();
$worker->name = 'Cron';
$worker->count= 1;

// 设置时区，避免运行结果与预期不一致
date_default_timezone_set('PRC');

$worker->onWorkerStart = function ($worker) {
        $app_crons = glob(APP_PATH . '*/cron.php');
//        p($app_crons,APP_PATH . '*/cron.php');

        if ($app_crons) {
            array_map(function ($crons_file) {
                $crons= include $crons_file;
                if ($crons) {
                    array_map(function ($cron) {
                        if(is_callable($cron['callback'])){
                            console('Add CronJob '.$cron['name'],'success');
                            new Crontab($cron['time'],$cron['callback']);
                        }
                    }, $crons);
                }

            }, $app_crons);
        }

};

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
