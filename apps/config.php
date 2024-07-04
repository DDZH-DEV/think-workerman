<?php

return [

    //数据库配置
    'database' => [
        // 默认数据连接标识
        'default' => 'mysql',
        // 数据库连接信息
        'connections' => [
            'mysql' => [
                // 数据库类型
                'type' => 'mysql',
                // 主机地址
                'hostname' => '127.0.0.1',
                // 用户名
                'username' => 'root',
                //密码
                'password' => 'root',
                // 数据库名
                'database' => 'xnc',
                // 数据库编码默认采用utf8
                'charset' => 'utf8',
                // 数据库表前缀
                'prefix' => '',
                // 数据库调试模式
                'debug' => false,
                //断线重连
                'break_reconnect' => true
            ],
        ],
    ],


    'socket_log' => [
        'enable' => false,
        'server' => 'http://127.0.0.1:1116/',
        'client' => 'debug',
        'show_included_files' => false
    ],
    //一般不需要修改
    'cache' => [
        'default' => 'redis',
        'stores' => [
            'file' => [
                'type' => 'File',
                // 缓存保存目录
                'path' => RUNTIME_PATH . 'cache',
                // 缓存前缀
                'prefix' => '',
                // 缓存有效期 0表示永久缓存
                'expire' => 0,
            ],
            'redis' => [
                'type' => 'redis',
                'host' => '127.0.0.1',
                'port' => 6379,
                'prefix' => '',
                'expire' => 0,
            ],
        ],
    ],


    'log' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'type' => 'file',
                'path' => RUNTIME_PATH . 'logs',
                'realtime_write' => true
            ],
        ],
    ],


    'global_data' => [
        'name' => 'GlobalDataServer',
        'client' => '127.0.0.1:2700',
        'server_port' => '2700' //可以填false 关闭当前应用不单独再开启一个server
    ],

    'queue' => [
        'name' => 'Queue',
        'count' => 1,
        'host' => '127.0.0.1',
        'port' => 6379
    ],

    //多个应用时需要修改
    'register' => [
        'address' => '127.0.0.1:1238',
    ],

    //多个应用时需要修改
    'gateway' => [
        'name' => 'Gateway',
        'address' => '0.0.0.0:8282',
        'count' => 1,
        'lan_ip' => '127.0.0.1',
        'start_port' => '2900',
    ],

    //可以不修改
    'businessworker' => [
        'name' => 'BusinessWorker',
        'count' => 1
    ],


    //多个应用时需要修改
    'http' => [
        'name' => 'WebServer',
        'upload_dir' => 'uploads',
        'cross_url' => 'http://127.0.0.1:909',
        'http_server' => 'http://0.0.0.0:909',
        'cdn_url' => '',  //静态文件分发地址 参考函数 staticFix()
        'count' => 1
    ],


    'waf' => [
        //开启防火墙
        'enable' => true,
        //开启记录
        'log' => true,
        //日志目录
        'log_path' => RUNTIME_PATH . 'waf',
        //注入多少次后屏蔽
        'deny_num' => 3,
    ],
    'error_level' => E_ALL & ~E_NOTICE,

    'map_rule' => [
        'hook'=> APP_PATH . '*' . DIRECTORY_SEPARATOR . 'hook.php',
        'routers' => APP_PATH . '*' . DIRECTORY_SEPARATOR . 'router.php',
        'cron' => APP_PATH . '*' . DIRECTORY_SEPARATOR . 'cron.php'
    ],

    'default_module' => 'index',

    'template' => [
        'view_path' => PUBLIC_PATH,
        'cache_path' => RUNTIME_PATH . 'template/',
        'view_suffix' => 'html',
    ],

    'assets' => ['public_dir' => PUBLIC_PATH, 'pipeline_dir' => 'min', 'pipeline_gzip' => true, 'pipeline' => !APP_DEBUG],

    'qurl'=>'/QEditor'

];