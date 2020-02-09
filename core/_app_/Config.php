<?php



class Config{
    
    //微信开发配置
    static $wechat=[
        'token'           => '', // 填写你设定的key
        'appid'           => '', // 填写高级调用功能的app id, 请在微信开发模式后台查询
        'appsecret'       => '', // 填写高级调用功能的密钥
        'encodingaeskey'  => '', // 填写加密用的EncodingAESKey（可选，接口传输选择加密时必需）
        'mch_id'          => '', // 微信支付，商户ID（可选）
        'partnerkey'      => '', // 微信支付，密钥（可选）
        'ssl_cer'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
        'ssl_key'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
        'cachepath'       => '', // 设置SDK缓存目录（可选，默认位置在Wechat/Cache下，请保证写权限）
    ];


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
                'path'   => './runtime/cache/',
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
                'path'	=>	ROOT_PATH.DS.'runtime/logs',
            ],
        ],
    ];


    //可以不修改
    static $businessworker=[
        'name'=>'BusinessWorker',
        'count'=>4
    ];


    //多个应用时需要修改
    static $app=[ 
        'upload_dir'=>'uploads',
        'http_port'=>'909',
        'ws_address'=>'127.0.0.1:8282',
        'api_url'=>'http://127.0.0.1:909',
        'static_url'=>'http://127.0.0.1:909' 
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
