<?php



class Config{
    

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


    static $database=[
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '127.0.0.1',
        // 数据库名
        'database'        => 'lexi_app',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => 'root',
        // 端口
        'hostport'        => '',
        // 连接dsn
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => 'fa_',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => '',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
        // Builder类
        'builder'         => '',
        // Query类
        'query'           => '\\think\\db\\Query',
        // 是否需要断线重连
        'break_reconnect' => true,

        'paginate'               => [
            'type'      => 'bootstrap',
            'var_page'  => 'page',
            'list_rows' => 15,
        ],
    ];
 

    //一般不需要修改
    static $cache=[
        'type'   => 'memcache',
        'host'       => '127.0.0.1',
        'port'       => 11211,
        // 缓存前缀
        'prefix' => 'chat_',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ];


    //可以不修改
    static $businessworker=[
        'name'=>'BusinessWorker',
        'count'=>16
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
            'apikey'=>'13898519a92d32e6844f36269ecb1c7e'
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
