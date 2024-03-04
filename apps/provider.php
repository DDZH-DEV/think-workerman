<?php

return [
    'config' => system\Config::class,
    'router' => system\Router::class,
    'cache' => \think\facade\Cache::class,
    'log' => \think\facade\Log::class,
    'db'=> \think\facade\Db::class,
    'view'=>\system\View::class,
    'assets'=>\system\Assets::class,
    'hook'=>JBZoo\Event\EventManager::class
];