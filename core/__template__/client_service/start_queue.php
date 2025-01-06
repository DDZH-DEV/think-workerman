<?php

/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *

 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use \Workerman\Worker;

// 自动加载类
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/base.php';


//消息队列 进程
$worker = new Worker();
// worker名称
$worker->name = config('queue.name');

$worker->count = config('queue.count');


$worker->onWorkerStart = function () {

    $queue_key = str_replace(['-', '.', '*'], '_', gethostname() . '_QUEUES');

    static $Queue;

    if (!$Queue) {
        $_redis = new \Redis();
        $_redis->connect(config('queue.host'), config('queue.port'));
        $_redis->setOption(\Redis::OPT_PREFIX, $queue_key);
        $Queue = new \Phive\Queue\RedisQueue($_redis);
    }


    while (true) {

        while ($Queue->count() > 0) {

            try {
                $row = $Queue->pop();

                if (empty($row)) {
                    sleep(1);
                    continue;
                }

                $task = json_decode($row, true);

                console('[QUEUE]:' . $task['_type'] . '|' . date('H:i:s', time()));
                //自定义的回调方法
                if (isset($task['_callback']) && $task['_callback'] && is_callable($task['_callback'])) {
                    call_user_func($task['_callback'], $task);
                } else {
                    switch ($task['_type']) {
                        case 'test':
                            console('test');
                            break;
                        default:
                            break;
                    }
                }

                flush();
            } catch (\Phive\Queue\NoItemAvailableException $e) {
                // 队列为空时的特殊处理
                sleep(1);
                break;
            } catch (\Exception $e) {
                // 其他异常的处理
                console('[ERROR]: ' . $e->getMessage());
                sleep(10);
                break;
            }
        }


        sleep(1);
    }
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
