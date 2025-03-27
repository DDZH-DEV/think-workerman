<?php

return [
    'config' => system\Config::class,
    'router' => system\Router::class,
    'cache' => \think\facade\Cache::class,
    'log' => \think\facade\Log::class,
    'db'=> \think\facade\Db::class,
    // 'db'=> \system\Mysqli::class,
    'view'=>\system\Qstyle::class,
    'assets'=>\system\Assets::class,
    'hook'=>JBZoo\Event\EventManager::class
];