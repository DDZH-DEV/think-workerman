<?php
/**
 * run with command 
 * php start.php start
 */

ini_set('display_errors', 'on');

use Workerman\Worker;

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'core/init.php';

// 加载所有Applications/*/start.php，以便启动所有服务
foreach(glob(__DIR__.'/start*.php') as $start_file)
{
    require_once $start_file;
}

// 服务停止时更新所有用户不在线
/*Worker::$onMasterStop = function(){
    \GatewayWorker\Lib\Db::instance('laychat')->update('user')->col('status', 'offline')->query();
};*/

// 运行所有服务
Worker::runAll();
