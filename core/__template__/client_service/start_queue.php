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
use \Workerman\Timer;
use \Workerman\RedisQueue\Client;

// 自动加载类
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core/base.php';


//消息队列 进程
$worker = new Worker();
// worker名称
$worker->name = config('queue.name');

$worker->count = config('queue.count');


$worker->onWorkerStart = function () {
    static $client;

    // 内存限制和超时配置
    define('TASK_TIME_CHECK', false);
    define('TASK_TIMEOUT', 3600); // 1小时超时
    define('MEMORY_LIMIT', 1024 * 1024 * 512); // 512MB 内存限制
    define('MEMORY_CHECK_INTERVAL', 300); // 5分钟检查一次内存

    // 获取队列配置
    $redis_host = config('queue.host', '127.0.0.1');
    $redis_port = config('queue.port', 6379);
    $redis_auth = config('queue.auth', '');
    $redis_db = config('queue.db', 0);
    $max_attempts = config('queue.max_attempts', 5);
    $retry_seconds = config('queue.retry_seconds', 5);

    // 构建Redis连接字符串
    $redis_address = "redis://{$redis_host}:{$redis_port}";

    // 初始化RedisQueue客户端
    $client = new Client($redis_address, [
        'auth' => $redis_auth,
        'db' => $redis_db,
        'max_attempts' => $max_attempts,
        'retry_seconds' => $retry_seconds
    ]);

    // 定义队列名
    $queue_names = config('queue.queues', ['default']);
    if (!is_array($queue_names)) {
        $queue_names = ['default'];
    }

    // 处理任务的回调函数
    $task_handler = function ($task) {
        // 记录任务开始时间
        $taskStartTime = time();
        $timeoutTimer = null;
        
        // 根据配置决定是否启用任务超时检测
        if (TASK_TIME_CHECK) {
            // 创建超时定时器
            $timeoutTimer = Timer::add(10, function() use ($taskStartTime, &$timeoutTimer, $task) {
                // 检查任务是否超时
                if (time() - $taskStartTime > TASK_TIMEOUT) {
                    console('[QUEUE TIMEOUT]: 任务 "' . ($task['_name_'] ?? 'unknown') . '" 执行超时 (' . TASK_TIMEOUT . '秒)，强制中断', 'warning');
                    
                    // 记录超时日志
                    error_log(sprintf(
                        "[QUEUE TIMEOUT] Task: %s has been running for %d seconds, execution aborted",
                        $task['_name_'] ?? 'unknown',
                        time() - $taskStartTime
                    ));
                    
                    // 清除定时器
                    if ($timeoutTimer) {
                        Timer::del($timeoutTimer);
                        $timeoutTimer = null;
                    }
                    
                    // 尝试中断处理过程
                    throw new \RuntimeException("Task execution timeout after " . TASK_TIMEOUT . " seconds");
                }
            }, [], false);  // 非持久定时器
        }

        try {
            console('[QUEUE TASK]: ' . $task['_name_'] . '|' . date('H:i:s', time())); 
            
            if (isset($task['_callback_']) && $task['_callback_'] && is_callable($task['_callback_'])) {
                $callback = $task['_callback_'];
                unset($task['_callback_'],$task['_name_']);
                call_user_func($callback, $task);
            } 
            // 清理内存
            gc_collect_cycles();
            
            // 任务成功完成，清除超时定时器(如果存在)
            if (TASK_TIME_CHECK && $timeoutTimer) {
                Timer::del($timeoutTimer);
                $timeoutTimer = null;
            }
            
        } catch (\Exception $e) {
            // 清除超时定时器(如果存在)
            if (TASK_TIME_CHECK && $timeoutTimer) {
                Timer::del($timeoutTimer);
                $timeoutTimer = null;
            }
            
            // 检查是否是任务超时异常
            if (strpos($e->getMessage(), 'Task execution timeout') !== false) {
                console('[QUEUE TIMEOUT]: ' . $e->getMessage(), 'warning');
            } else {
                console('[TASK ERROR]: ' . $e->getMessage(), 'error');
                // 记录错误日志
                error_log(sprintf(
                    "[QUEUE ERROR] Task: %s, Error: %s, Stack: %s",
                    $task['_name_'] ?? 'unknown',
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));
            }

            // 重新抛出异常，让redis-queue处理重试逻辑
            throw $e;
        }
    };

    // 订阅所有配置的队列
    foreach ($queue_names as $queue) {
        $client->subscribe($queue, $task_handler);
        console("[QUEUE]: Subscribed to queue: {$queue}");
    }

    // 消费失败的回调
    $client->onConsumeFailure(function (\Throwable $exception, $package) {
        console("[QUEUE FAILURE]: Queue {$package['queue']} consumption failed: " . $exception->getMessage(), 'error');

        // 检查是否是最后一次尝试
        if ($package['attempts'] >= $package['max_attempts']) {
            console("[QUEUE FAILURE]: Task in queue {$package['queue']} has failed {$package['attempts']} times and will not be retried.", 'error');
        }

        return $package;
    });

    // 定时检查内存使用情况
    Timer::add(MEMORY_CHECK_INTERVAL, function () {
        $memory = memory_get_usage(true);
        if ($memory > MEMORY_LIMIT) {
            console('[QUEUE]: Memory usage exceeded limit (' . round($memory / 1024 / 1024) . 'MB), restarting worker...', 'warning');
            Worker::stopAll();
        }
    });
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
