<?php

use \think\Cache;

class WebServer extends \Workerman\WebServer
{

    public function onMessage($connection)
    {
        //IP黑名单
        $ips = Cache::get('deny_ips');

        if ($ips && in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            return $connection->close(json_encode(['code' => 0, 'msg' => 'waf deny ip hit !']));
        }

        _G('DEBUG', file_exists(APP_PATH . '/debug'));

        $params = str_replace(['.html', '.htm', '.shtml'], [''], preg_split('/(\/|\?)/', $_SERVER['REQUEST_URI']));


        $params = array_filter($params, function ($item) {
            //c($item);
            return $item && strpos($item, '=') == false ? $item : false;
        });

        //最多两种模式
        $params = array_slice($params, 0, 3);

        $total = count($params);

        $params = $total < 3 ? array_merge(array_fill(0, 3 - $total, 'index'), $params) : $params;


        @list($_['module'], $_['controller'], $_['action']) = $params;

        $controller = 'app\\' . $_['module'] . '\\controller\\' . ucfirst($_['controller']);


        //new $controller();

        if (class_exists($controller) && method_exists($controller, $_['action'])) {

            //全局设置
            _G('MVC', $_);
            _G('IS_MOBILE', is_mobile());

            //console($controller.':'.$_['action']);

            \Workerman\Protocols\Http::sessionStart();

            //跨域问题
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                \Workerman\Protocols\Http::header('Access-Control-Allow-Credentials:true');
                \Workerman\Protocols\Http::header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_ORIGIN']);
                //\Workerman\Protocols\Http::header('Access-Control-Allow-Origin:*');
            } else {
                \Workerman\Protocols\Http::header('Access-Control-Allow-Credentials:true');
                \Workerman\Protocols\Http::header('Access-Control-Allow-Origin:' . Config::$app['api_url']);
            }

            ob_start();

            ini_set('display_errors', 'on');

            try {
                call_user_func_array([new $controller, $_['action']], $params);
            } catch (\Exception $e) {
                //如果是调试模式，直接输出

                if ($e->getMessage() !== 'jump_exit') {

                    if (_G('DEBUG')) {
                        p($e->getMessage());
                        p($e->getTraceAsString());
                    } else {
                        //记录日志
                        \utils\Log::error($e->getMessage());
                    }

                }
            }

            //释放变量
            _G(null);

            $content = ob_get_clean();

            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                return $connection->send($content);

            } else {
                return $connection->close($content);
            }


        } else {

            parent::onMessage($connection);
        }


    }
}