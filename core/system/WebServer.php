<?php

namespace system;

use Exception;
use GatewayWorker\Lib\Gateway;
use Web;

use Workerman\Worker;

define("IS_LOW_WORKERMAN", version_compare(Worker::VERSION, '3.5.3', '<'));

if (defined('FPM_MODE')) {

    include __DIR__ . '/webserver/Web3.php';

} else {
    include __DIR__ . '/webserver/Web4.php';
}


class WebServer extends Web
{
    static $debug = false;

    static function init(){
        static  $map;

        if($map) return ;

        foreach (config('map_rule') as $name=>$rule){
            app('config')::map($name,$rule);
        }

        //预设routers
        app('router')->addRoutes(config('routers'));

        $map=true;

        \GatewayClient\Gateway::$registerAddress=config('register.address');
        \GatewayWorker\Lib\Gateway::$registerAddress=config('register.address');
    }


    /**
     * @param $connection
     * @param $object
     * @param $request
     * @return void
     */
    static function dispatchHttp($connection = null, $object = null, $request = null)
    {
        self::init();

        $match = app('router')->match();

        $server = g('SERVER');

        define('IS_POST',$server['REQUEST_METHOD']==='POST');
        define('IS_GET',$server['REQUEST_METHOD']==='GET');

        //文件直接输出
        $file = PUBLIC_PATH . basename($server['REQUEST_URI']);
        if (IS_CLI && file_exists($file) && is_file($file)) {
            $object = $object ?: new self();
            $object->handleFileRequest($connection, $file, $request);
            unset($object);
            return;
        }
        $class=$match['target'];

        IS_CLI && ob_start();
        if (class_exists($class) && method_exists($class,$match['action'])) {
            g('MODULE',$match['module']);
            g('CONTROLLER',$match['controller']);
            g('ACTION',$match['action']);
            //全局设置
            g('IS_MOBILE', is_mobile($server['HTTP_USER_AGENT']));
            //跨域问题
            self::fixHttpCrossDomain($server);
            try {
                call_user_func_array([new $class,$match['action']], [$match['params'],$connection,$request]);
            } catch (Exception $e) {
                //如果是调试模式，直接输出
                if ($e->getMessage() !== 'jump_exit') {
                    if (APP_DEBUG) {
                        p($e->getMessage(), $e->getTraceAsString());
                        !IS_CLI && die();
                    }
                    Debug::log_exception($e);
                }
            }

            self::response($connection, $request);

        } else {
            $message = strpos($server['REQUEST_URI'], '.php') !== false ?
                    '当前请求的的[' . basename($server['REQUEST_URI']) . '] 文件不存在!' :
                    '当前请求的的[' . $class . '::' .$match['action'] . '] 方法不存在!';
            //此处是方法不存在的处理方法
            if (IS_CLI) {

                if (IS_LOW_WORKERMAN) {
                    $connection->send($message);
                } else {
                    $response = new \Workerman\Protocols\Http\Response(404, [], $message);
                    $connection->send($response);
                }
                return;
            }

            header ("HTTP/1.1 404 Not Found");
            echo $message;
        }
    }


        /**
     * 处理跨域问题 Workerman3.*和 FPM 的 跨域
     * @param $server
     * @return void
     */
    protected static function fixHttpCrossDomain($server)
    {

        if (IS_CLI) {
            \Workerman\Protocols\Http::header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
            \Workerman\Protocols\Http::header('Access-Control-Allow-Methods: *');
            \Workerman\Protocols\Http::header('Access-Control-Allow-Credentials:true');
            \Workerman\Protocols\Http::header('Access-Control-Allow-Origin:' . (isset($server['HTTP_ORIGIN']) ? $server['HTTP_ORIGIN'] : config('http.cross_url')));
        } else {
            header('Access-Control-Allow-Credentials:true');
            if (isset($server['HTTP_ORIGIN'])) {
                header('Access-Control-Allow-Origin:' . $server['HTTP_ORIGIN']);
            } else {
                header('Access-Control-Allow-Origin:*');
            }
        }
    }

}