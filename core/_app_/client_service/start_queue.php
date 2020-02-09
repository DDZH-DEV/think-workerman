<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use \Workerman\Worker;
use \utils\Queue;

// 自动加载类
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'core/init.php';


//消息队列 进程
$worker = new Worker();
// worker名称
$worker->name = 'Queue';

$worker->count = 1;


$worker->onWorkerStart = function () {

    $queue_key=md5(json_encode(Config::$app));


    static $Queue;

    if(!$Queue){
        $_redis=new \Redis();
        $_redis->connect('127.0.0.1');
        $_redis->setOption(\Redis::OPT_PREFIX, $queue_key);
        $Queue = new \Phive\Queue\RedisQueue($_redis);
    }


    while (true) {

        while($Queue->count()>0){

            $row = $Queue->pop();

            $task = json_decode($row, true);

            console('[QUEUE]:' . $task['_type'] . '|' . date('H:i:s', time()));

            switch ($task['_type']) {
                case 'test':
                    console('test');
                    break;
                default:
                    break;
            }
            flush();
        }


        sleep(3);
    }

};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}