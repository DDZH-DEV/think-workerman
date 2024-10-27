<?php

namespace app\__TEMPLATE__\controller;

use GatewayClient\Gateway;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;


class Index
{
    function index(){
        echo '<h1>hello world!</h1><p>你可以访问<a href="'.url("__TEMPLATE__/Index/test").'">/test</a>来查看相关功能</p>';
    }

    /**
     * 所有功能演示
     */
    function test()
    {
        p('输入所有输入项');
        $params = input();
        p($params);

        p('<hr>数据库演示,请先配置数据库连接文件');
        // $model = Db::table('user');
        // $list = $model->where(['status' => 1])->find();
        // p($list);

        p('<hr>session 演示');
        //事先打印session
        p('session改变前time的值:' . session('time'));
        //设置session
        session('time', time());
        //印session
        p('session改变后time的值:' . session('time'));
        //销毁session
        //session('time',null);
        //session(null);

        p('<hr>队列演示,后端处理逻辑请在start_queue.php中处理,注意：若要使用队列，请将Config.php中的$cache 模式改成memcache 或者redis');
        addToQueue('demo', ['aaa', 'bbb']);

        p('<hr>日志演示,配置在apps/config.php中修改log,储存在/server/runtime/logs下');
        Log::info('Log-info:' . time());
        Log::error('Log-error:' . time());
        Log::debug('Log-debug:' . time());
        Log::error('Log-error:' . time());


        p('<hr>缓存演示,配置在apps/config.php中修改cache');
        p('cache改变前cache_time的值:'.Cache::get('cache_time'));
        Cache::set('cache_time', time());
        p('cache改变前cache_time的值:'.Cache::get('cache_time'));
    }



    /**
     * websocket_test
     * http接口形式向所有用户发送消息,需要开启socket功能
     */
    function websocket_test()
    {
        //注册地址
        Gateway::$registerAddress = config('register.address');

        //打印所有客户端
        p(Gateway::getAllClientSessions());


        //请使用http://tool.hibbba.com/websocket/ 输入ws://127.0.0.1:8282 自行监控数据 在请求本控制器
        Gateway::sendToAll(json_encode(['message' => date('Y-m-d H:i:s')]));

    }
}