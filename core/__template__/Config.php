<?php


class Config{

    static $waf=[
        //开启防火墙
        'enable'=>true,
        //开启记录
        'log'=>true,
        //日志目录
        'log_path'=>APP_PATH.'runtime'.DIRECTORY_SEPARATOR.'waf',
        //注入多少次后屏蔽
        'deny_num'=>3,
    ];

    static $error_level= E_ALL & ~E_NOTICE;
    //数据库配置
    static $database=[
        // 默认数据连接标识
        'default'     => 'mysql',
        // 数据库连接信息
        'connections' => [
            'mysql' => [
                // 数据库类型
                'type'     => 'mysql',
                // 主机地址
                'hostname' => '127.0.0.1',
                // 用户名
                'username' => 'root',
                //密码
                'password' => 'root',
                // 数据库名
                'database' => '',
                // 数据库编码默认采用utf8
                'charset'  => 'utf8',
                // 数据库表前缀
                'prefix'   => '',
                // 数据库调试模式
                'debug'    => true,
                //断线重连
                'break_reconnect'=>true
            ],
        ],
    ];


    //一般不需要修改
    static $cache=[
        'default'	=>	'file',
        'stores'	=>	[
            'file'	=>	[
                'type'   => 'File',
                // 缓存保存目录
                'path'   => APP_PATH.DS.'runtime'.DS.'cache',
                // 缓存前缀
                'prefix' => '',
                // 缓存有效期 0表示永久缓存
                'expire' => 0,
            ],
            'redis'	=>	[
                'type'   => 'redis',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'prefix' => '',
                'expire' => 0,
            ],
        ],
    ];


    static $log=[
        'default'	=>	'file',
        'channels'	=>	[
            'file'	=>	[
                'type'	=>	'file',
                'path'	=>	APP_PATH.DS.'runtime/logs',
            ],
        ],
    ];


    static $global_data=[
        'name'=>'GlobalDataServer',
        'port'=>2700
    ];

    static $queue=[
        'name'=>'Queue',
        'count'=>1,
        'host'=>'127.0.0.1',
        'port'=>6379

    ];

    //可以不修改
    static $businessworker=[
        'name'=>'BusinessWorker',
        'count'=>1
    ];


    //多个应用时需要修改
    static $http=[
        'name'=>'WebServer',
        'upload_dir'=>'uploads',
        'http_port'=>'909',
        'ws_address'=>'127.0.0.1:8282',
        'api_url'=>'http://127.0.0.1:909',
        'cdn_url'=>'http://127.0.0.1:909',
        'count'=>3
    ];

    static $sms=[
        'class'=>'YunPian',
        'params'=>[
            'apikey'=>''
        ],
        'day_limit'=>5
    ];


    //多个应用时需要修改
    static $register=[
        'address'=>'127.0.0.1:1238',
    ];

    //多个应用时需要修改
    static $gateway=[
        'name'=>'Gateway',
        'address'=>'0.0.0.0:8282',
        'count'=>1,
        'lan_ip'=>'127.0.0.1',
        'start_port'=>'2900',
    ];


}
