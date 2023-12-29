<?php

namespace app;

use GatewayWorker\BusinessWorker;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class SocketEvent
{
    static $app_socket_events;

    static $socket_apps;

    public static function onWorkerStart(BusinessWorker $businessWorker) {

        self::$socket_apps=self::$socket_apps?:array_filter(scandir(APP_PATH),function ($item){
            return !in_array($item,['.','..'])
                && is_dir(APP_PATH.$item) &&
                file_exists(APP_PATH.$item.'/service/AppSocketEvent.php');
        });

        if(self::$socket_apps){
            array_map(function ($app)use ($businessWorker){
                //var_dump('app\\'.$app.'\\service\\AppSocketEvent',method_exists('app\\'.$app.'\\service\\AppSocketEvent','onWorkerStart'));
                if(method_exists('app\\'.$app.'\\service\\AppSocketEvent','onWorkerStart')){
                    call_user_func_array(['app\\'.$app.'\\service\\AppSocketEvent','onWorkerStart'],[$businessWorker]);
                }
            },self::$socket_apps);
        }

    }


    public static function onWebSocketConnect($client_id, $data) {
        //如何判别socket要走哪个应用？
        console('Socket client onWebSocketConnect : '.$client_id );
        $parse=preg_split('/[\/?]/',$data['server']['REQUEST_URI']);
        $data['params']=$parse?array_values(array_filter(preg_split('/[\/?]/',$data['server']['REQUEST_URI']))):[];
        cache('socket_request_'.$client_id,$data);
        if(self::$socket_apps){
            array_map(function ($app)use ($client_id, $data){
                //var_dump('app\\'.$app.'\\service\\AppSocketEvent',method_exists('app\\'.$app.'\\service\\AppSocketEvent','onWebSocketConnect'));
                if(method_exists('app\\'.$app.'\\service\\AppSocketEvent','onWebSocketConnect')){
                    call_user_func_array(['app\\'.$app.'\\service\\AppSocketEvent','onWebSocketConnect'],[$client_id, $data]);
                }
            },self::$socket_apps);
        }
    }

    /**
     * 当客户端连接到Gateway时触发
     * @param $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
        //console('socket client onConnect : '.$client_id );
        if(self::$socket_apps){
            array_map(function ($app)use ($client_id){
                if(method_exists('app\\'.$app.'\\service\\AppSocketEvent','onConnect')){
                    call_user_func_array(['app\\'.$app.'\\service\\AppSocketEvent','onConnect'],[$client_id]);
                }
            },self::$socket_apps);
        }
    }


    /**
     * 当客户端发来数据时触发
     * @param int $client_id 连接id
     * @param mixed $data 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        //console('socket client onMessage : '. $message);
        $data=cache('socket_request_'.$client_id);
        $data['message']=$message;
        if(self::$socket_apps){
            array_map(function ($app)use ($client_id, $data){
//                p('app\\'.$app.'\\service\\AppSocketEvent',method_exists('app\\'.$app.'\\service\\AppSocketEvent','onMessage'));
                if(method_exists('app\\'.$app.'\\service\\AppSocketEvent','onMessage')){
                    call_user_func_array(['app\\'.$app.'\\service\\AppSocketEvent','onMessage'],[$client_id, $data]);
                }
            },self::$socket_apps);
        }

    }

    /**
     * 当客户端关闭时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        console('socket client onClose : '.$client_id);
        cache('socket_request_'.$client_id,null);
        if(self::$socket_apps){
            array_map(function ($app)use ($client_id){
                //var_dump('app\\'.$app.'\\service\\AppSocketEvent',method_exists('app\\'.$app.'\\service\\AppSocketEvent','onClose'));
                if(method_exists('app\\'.$app.'\\service\\AppSocketEvent','onClose')){
                    call_user_func_array(['app\\'.$app.'\\service\\AppSocketEvent','onClose'],[$client_id]);
                }
            },self::$socket_apps);
        }
    }

    /**
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次
     * @param int $client_id 连接id
     */
    public static function onWorkerStop($businessworker)
    {


    }


}

