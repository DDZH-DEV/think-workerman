<?php
namespace app\__TEMPLATE__\service;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class AppSocketEvent
{
    /**
     * 当客户端连接到Gateway时触发
     * @param $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {

    }


    /**
     * 当客户端发来数据时触发
     * @param int $client_id 连接id
     * @param mixed $data 具体消息
     */
    public static function onMessage($client_id, $data)
    {

    }

    /**
     * 当客户端关闭时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {


    }

    /**
     * 当客户端关闭时触发
     * @param int $client_id 连接id
     */
    public static function onWorkerStop($businessworker)
    {


    }


}

