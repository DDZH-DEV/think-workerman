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
    private const APP_SOCKET_EVENT_PATH = '/service/AppSocketEvent.php';
    private static array $socket_apps = [];

    public static function onWorkerStart(BusinessWorker $businessWorker) {
        self::initSocketApps();
        self::callAppMethod('onWorkerStart', [$businessWorker]);
    }

    public static function onWebSocketConnect($client_id, $data) {
        self::log('Socket client onWebSocketConnect : ' . $client_id);
        $data['params'] = self::parseRequestUri($data['server']['REQUEST_URI']);
        self::cache('socket_request_' . $client_id, $data);
        self::callAppMethod('onWebSocketConnect', [$client_id, $data]);
    }

    /**
     * 当客户端连接到Gateway时触发
     * @param $client_id
     * @return void
     */
    public static function onConnect($client_id) {
        self::callAppMethod('onConnect', [$client_id]);
    }

    /**
     * 当客户端发来数据时触发
     * @param int $client_id 连接id
     * @param mixed $data 具体消息
     */
    public static function onMessage($client_id, $message) {
        $data = self::cache('socket_request_' . $client_id) ?? [];
        $data['message'] = $message;
        self::callAppMethod('onMessage', [$client_id, $data]);
    }

    /**
     * 当客户端关闭时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id) {
        self::log('socket client onClose : ' . $client_id);
        self::cache('socket_request_' . $client_id, null);
        self::callAppMethod('onClose', [$client_id]);
    }

    /**
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次
     * @param int $client_id 连接id
     */
    public static function onWorkerStop($businessworker) {
        // 实现 onWorkerStop 逻辑
    }

    private static function initSocketApps() {
        if (empty(self::$socket_apps)) {
            self::$socket_apps = array_filter(scandir(APP_PATH), fn($item) =>
                !in_array($item, ['.', '..']) &&
                is_dir(APP_PATH . $item) &&
                file_exists(APP_PATH . $item . self::APP_SOCKET_EVENT_PATH)
            );
        }
    }

    private static function callAppMethod(string $method, array $params) {
        foreach (self::$socket_apps as $app) {
            $className = "app\\{$app}\\service\\AppSocketEvent";
            if (method_exists($className, $method)) {
                call_user_func_array([$className, $method], $params);
            }
        }
    }

    private static function parseRequestUri(string $uri): array {
        $parse = preg_split('/[\/?]/', $uri);
        return $parse ? array_values(array_filter($parse)) : [];
    }

    private static function cache($key, $value = null) {
        // 实现缓存逻辑
    }

    private static function log($message) {
        // 实现日志逻辑
        console($message);
    }
}

