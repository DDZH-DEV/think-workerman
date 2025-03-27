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
    static $isProcessing = false;
    static $taskStartTime = 0;
    static $lastMemoryCheck = 0;
    
    define('TASK_TIMEOUT', 3600); // 1小时超时
    define('MEMORY_LIMIT', 1024 * 1024 * 512); // 512MB 内存限制
    define('MEMORY_CHECK_INTERVAL', 300); // 5分钟检查一次内存

    if (!$Queue) {
        $_redis = new \Redis();
        $_redis->connect(config('queue.host'), config('queue.port'));
        $_redis->setOption(\Redis::OPT_PREFIX, $queue_key);
        $Queue = new \Phive\Queue\RedisQueue($_redis);
    }

    // 使用定时器替代死循环
    \Workerman\Timer::add(1, function() use ($Queue, &$isProcessing, &$taskStartTime, &$lastMemoryCheck) {
        // 检查内存使用情况
        if (time() - $lastMemoryCheck > MEMORY_CHECK_INTERVAL) {
            $memory = memory_get_usage(true);
            if ($memory > MEMORY_LIMIT) {
                console('[QUEUE]: Memory usage exceeded limit (' . round($memory / 1024 / 1024) . 'MB), restarting worker...', 'warning');
                Worker::stopAll();
                return;
            }
            $lastMemoryCheck = time();
        }

        // 检查任务是否超时
        if ($isProcessing && (time() - $taskStartTime > TASK_TIMEOUT)) {
            console('[QUEUE]: 任务执行超时，强制释放锁', 'error');
            error_log(sprintf(
                "[QUEUE TIMEOUT] Task has been running for %d seconds",
                time() - $taskStartTime
            ));
            $isProcessing = false;
            $taskStartTime = 0;
            return;
        }

        // 如果上一个任务还在处理中，直接返回
        if ($isProcessing) {
            return;
        }

        try {
            $count = $Queue->count();
            
            if ($count <= 0) {
                return;
            }

            $isProcessing = true;
            $taskStartTime = time();
            
            $row = $Queue->pop();
            
            if (empty($row)) {
                $isProcessing = false;
                $taskStartTime = 0;
                return;
            }

            $task = json_decode($row, true);
            if (!$task) {
                console('[QUEUE]: Invalid JSON data', 'error');
                $isProcessing = false;
                $taskStartTime = 0;
                return;
            }

            console('[QUEUE]:' . $task['_type'] . '|' . date('H:i:s', time()));
            
            // 使用 try-catch 包装任务执行
            try {
                if (isset($task['_callback']) && $task['_callback'] && is_callable($task['_callback'])) {
                    call_user_func($task['_callback'], $task);
                } else {
                    switch ($task['_type']) {
                        case 'test':
                            console('test');
                            break;
                        default:
                            console('[QUEUE]: Unknown task type ' . $task['_type'], 'warning');
                            break;
                    }
                }
            } catch (\Exception $e) {
                console('[TASK ERROR]: ' . $e->getMessage(), 'error');
                // 记录错误日志
                error_log(sprintf(
                    "[QUEUE ERROR] Task: %s, Error: %s, Stack: %s",
                    $task['_type'],
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));
            }

            // 清理内存
            gc_collect_cycles();
            
        } catch (\Phive\Queue\NoItemAvailableException $e) {
            // 队列为空，正常情况
        } catch (\Exception $e) {
            console('[QUEUE ERROR]: ' . $e->getMessage(), 'error');
            error_log(sprintf(
                "[QUEUE SYSTEM ERROR] Error: %s, Stack: %s",
                $e->getMessage(),
                $e->getTraceAsString()
            ));
        } finally {
            $isProcessing = false;
            $taskStartTime = 0;
        }
    });
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
