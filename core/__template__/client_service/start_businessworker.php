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
use \GatewayWorker\BusinessWorker;

// 自动加载类
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'core/base.php';

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = config('businessworker.name');
// bussinessWorker进程数量
$worker->count = config('businessworker.count');
// 服务注册地址
$worker->registerAddress = config('register.address');

//自定义设置处理业务的类。
$worker->eventHandler='app\SocketEvent';

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}