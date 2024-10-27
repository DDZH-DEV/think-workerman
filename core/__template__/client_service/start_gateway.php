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
use \GatewayWorker\Gateway;

// 自动加载类
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'core/base.php';

// gateway 进程，这里使用Text协议，可以用telnet测试
$gateway = new Gateway('websocket://'.config('gateway.address'));
// gateway名称，status方便查看
$gateway->name = config('gateway.name');
// gateway进程数
$gateway->count = config('gateway.count');
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = config('gateway.lan_ip');
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = config('gateway.start_port');
// 服务注册地址
$gateway->registerAddress = config('register.address');

// 心跳间隔
$gateway->pingInterval = 25;
// 25秒内客户端不发来任何消息则认为客户端下线
$gateway->pingNotResponseLimit = 1;

$gateway->onWorkerStart=function(){

};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}

