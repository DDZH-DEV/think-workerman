<?php

namespace rax;

use Exception;
use \Workerman\Connection\TcpConnection;
use \Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

define("IS_WIN_WORKERMAN", version_compare(Worker::VERSION, '4.0.0', '<') && IS_CLI);

if (IS_CLI) {
    if (IS_WIN_WORKERMAN) {
        include __DIR__ . '/webserver/Web3.php';
    } else {
        include __DIR__ . '/webserver/Web4.php';
    }
}

class WebServer extends \Web
{
    static $debug = false;

    public function onWorkerStart()
    {
        if (IS_CLI && IS_WIN_WORKERMAN) {
            //var_dump($this->onMessage);
            $this->onMessage = function ($connection, $request) {
                //开启session
                \Workerman\Protocols\Http::sessionStart();

                _G('IP', $connection->getRemoteIp());
                _G('POST', $_POST);
                _G('GET', $_GET);
                _G('FILES', $_FILES);
                _G('SESSION', $_SESSION);
                _G('SERVER', $_SERVER);
                _G('COOKIE', $_COOKIE);
                _G('DEBUG', file_exists(APP_PATH . '/debug'));

                self::dispatchHttp($connection, $this, $request);
            };
        }
    }


    function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name, $context_option);

        if (IS_CLI && !IS_WIN_WORKERMAN) {
            $this->onMessage = function ($connection, $request) {

                $this->initForWorker4($connection, $request);

                self::dispatchHttp($connection, $this, $request);
            };
        }

    }

    /**
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    function initForWorker4(TcpConnection $connection, Request $request)
    {
        $_SERVER = array(
            'QUERY_STRING' => '',
            'REQUEST_METHOD' => '',
            'REQUEST_URI' => '',
            'SERVER_PROTOCOL' => '',
            'SERVER_SOFTWARE' => 'workerman/' . \Workerman\Worker::VERSION,
            'SERVER_NAME' => '',
            'HTTP_HOST' => '',
            'HTTP_USER_AGENT' => '',
            'HTTP_ACCEPT' => '',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_COOKIE' => '',
            'HTTP_CONNECTION' => '',
            'REMOTE_ADDR' => '',
            'REMOTE_PORT' => '0',
            'REQUEST_TIME' => time()
        );

        $header_data = explode("\r\n", $request->rawHead());

        list($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']) = explode(' ',
            $header_data[0]);

        unset($header_data[0]);
        foreach ($header_data as $content) {
            // \r\n\r\n
            if (empty($content)) {
                continue;
            }
            list($key, $value) = explode(':', $content, 2);
            $key = str_replace('-', '_', strtoupper($key));
            $value = trim($value);
            $_SERVER['HTTP_' . $key] = $value;
            switch ($key) {
                // HTTP_HOST
                case 'HOST':
                    $tmp = explode(':', $value);
                    $_SERVER['SERVER_NAME'] = $tmp[0];
                    if (isset($tmp[1])) {
                        $_SERVER['SERVER_PORT'] = $tmp[1];
                    }
                    break;
                // cookie
                case 'COOKIE':
                    parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                    break;
                // content-type
                case 'CONTENT_TYPE':
                    if (!preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                        if ($pos = strpos($value, ';')) {
                            $_SERVER['CONTENT_TYPE'] = substr($value, 0, $pos);
                        } else {
                            $_SERVER['CONTENT_TYPE'] = $value;
                        }
                    } else {
                        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
                        $http_post_boundary = '--' . $match[1];
                    }
                    break;
                case 'CONTENT_LENGTH':
                    $_SERVER['CONTENT_LENGTH'] = $value;
                    break;
            }
        }
        $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        $_SERVER['REMOTE_ADDR'] = $connection->getRemoteIp();
        $_SERVER['REMOTE_PORT'] = $connection->getRemotePort();

        _G('SERVER', $_SERVER);
        _G('IP', isset($request->header()['x-real-ip']) ? $request->header()['x-real-ip'] : $connection->getRemoteIp());
        _G('POST', $request->header() && isset($request->header()['content-type']) && strpos($request->header()['content-type'], 'json') != false ? json_decode($request->rawBody(), true) : $request->post());
        _G('GET', $request->get());
        _G('FILES', $request->file());
        _G('SESSION', array_filter($request->session()->all()));
        _G('COOKIE', array_filter($request->cookie()));
        _G('DEBUG', file_exists(APP_PATH . '/debug'));
    }

    /**
     * @param $connection
     * @param $object
     * @param $request
     * @return void
     */
    static function dispatchHttp($connection = null, $object = null, $request = null)
    {
        $server = _G('SERVER');
        //开始分发请求
        $params = str_replace(['.html', '.htm', '.shtml'], [''], preg_split('#([/?])#', $server['REQUEST_URI']));

        $params = array_filter($params, function ($item) {
            //c($item);
            return $item && !strpos($item, '=') ? $item : false;
        });

        //最多两种模式
        $params = array_slice($params, 0, 3);
        $url_params_total = count($params);

        $params = $url_params_total < 3 ? array_merge(array_fill(0, 3 - $url_params_total, 'index'), $params) : $params;

        @list($_['module'], $_['controller'], $_['action']) = $params;

        $bind_app = App::getDefaultApp(true);

        $app = $url_params_total < 3 ? ($bind_app ?: $_['module']) : $_['module'];

        $controller = 'app\\' . $app . '\\controller\\' . ucfirst($_['controller']);
         IS_CLI && ob_start();

        if (class_exists($controller) && method_exists($controller, $_['action'])) {
            //全局设置
            _G('IS_MOBILE', is_mobile($server['HTTP_USER_AGENT']));
            //跨域问题
            (!defined('IS_CLI') || IS_WIN_WORKERMAN) && self::fixHttpCrossDomain($server);
            try {
                call_user_func_array([new $controller, $_['action']], []);
            } catch (Exception $e) {
                //如果是调试模式，直接输出
                if ($e->getMessage() !== 'jump_exit') {
                    if (_G('DEBUG')) {
                        p($e->getMessage());
                        p($e->getTraceAsString());
                        die();
                    }
                    App::log_exception($e);
                }
            }

            if (IS_CLI) {
                if (IS_WIN_WORKERMAN) {
                    self::response($connection);
                } else {
                    self::responseForWorkerman4($connection, $request);
                }
            }

        } else {
            //此处是方法不存在的处理方法
            if (IS_CLI) {
                $message=('当前请求的的['.$controller . '::' . $_['action'] . '] 方法不存在!');
                if (IS_WIN_WORKERMAN) {
                    $object->addRoot('*', PUBLIC_PATH);
                    $object->onMessage($connection);
                    $connection->send($message);
                } else {
                    self::responseForDefaultWorkerman4($connection, $request ,$message);
                }
            }
        }

    }

    /**
     * @param $connection
     * @param $request
     * @param $message
     * @return void
     */
    static function responseForDefaultWorkerman4($connection, $request,$message)
    {
        $not_found='<h3>404 Not Found</h3><p>'.$message.'</p>';
        if(!$request){
            echo $not_found;
            return;
        }
        $_GET = $request->get();
        $path = $request->path();

        if ($path === '/') {
            if(file_exists(PUBLIC_PATH . '/index.php')){
                $connection->send(exec_php_file(PUBLIC_PATH . '/index.php'));
            }else{
                $connection->send(new Response(404, array(), $not_found));
            }
            return;
        }
        $file = realpath(PUBLIC_PATH . $path);
        if (false === $file) {
            $connection->send(new Response(404, array(), $not_found));
            return;
        }
        // Security check! Very important!!!
        if (strpos($file, PUBLIC_PATH) !== 0) {
            $connection->send(new Response(400));
            return;
        }
        if (\pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $connection->send(exec_php_file($file));
            return;
        }

        $if_modified_since = $request->header('if-modified-since');
        if (!empty($if_modified_since)) {
            // Check 304.
            $info = \stat($file);
            $modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
            if ($modified_time === $if_modified_since) {
                $connection->send(new Response(304));
                return;
            }
        }
        $connection->send((new Response())->withFile($file));
    }

    static function responseForWorkerman4($connection, $request)
    {

        //跨域问题
        $headers=[
            'Access-Control-Allow-Credentials'=>'true',
            'Access-Control-Allow-Methods'=>'GET, POST, PUT, OPTIONS',
            'Access-Control-Allow-Headers'=>'*',
            'Access-Control-Allow-Origin'=>(isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN']: config('http.cross_url'))
        ];
        $_SERVER=_G('SERVER');
        $add_headers = _G('HEADER');
        if ($add_headers) {
            foreach ($add_headers as $k => $v) {
                $headers[$k] = $v;
            }
        }
        $session = _G('SESSION');
        $all_session = $request->session()->all();
        //设置新的session
        $request->session()->put($session);
        //删除旧的session

        if ($diff_session = arrayRecursiveDiff($all_session, $session)) {
            foreach ($diff_session as $k => $v) {
                if (is_array(_G('SESSION')) && in_array($k, array_keys(_G('SESSION')))) {
                    unset($diff_session[$k]);
                }
            }
            $request->session()->forget(array_keys($diff_session));
        }

        $content = ob_get_clean();
        $response = new \Workerman\Protocols\Http\Response(200, $headers, $content);
        $add_cookies = arrayRecursiveDiff(_G('COOKIE'), $request->cookie());
        $remove_cookies = arrayRecursiveDiff($request->cookie(), _G('COOKIE'));

        if ($add_cookies) {
            foreach ($add_cookies as $name => $val) {
                $response->cookie($name, $val);
            }
        }

        if ($remove_cookies) {
            foreach ($remove_cookies as $name => $val) {
                if (!in_array($name, array_keys(_G('COOKIE')))) $response->cookie($name, '', 1);
            }
        }

        //释放变量
        _G(null);

        return $connection->send($response);
    }

    /**
     * Workerman输出
     * @param $connection
     * @return mixed
     */
    static function response($connection)
    {
        //兼容处理
        $add_headers = _G('HEADER');
        if ($add_headers) {
            foreach ($add_headers as $k => $v) {
                self::header($k . ':' . $v);
            }
        }
        $_SESSION = _G('SESSION');
        $add_cookies = arrayRecursiveDiff(_G('COOKIE'), $_COOKIE);
        $remove_cookies = arrayRecursiveDiff($_COOKIE, _G('COOKIE'));
        if ($add_cookies) {
            foreach ($add_cookies as $name => $val) {
                self::setcookie($name, $val);
            }
        }
        if ($remove_cookies) {
            foreach ($remove_cookies as $name => $val) {
                if (!in_array($name, array_keys(_G('COOKIE')))) self::setCookie($name, '', 1);
            }
        }
        $content = ob_get_clean();
        //释放变量
        _G(null);

        if (IS_CLI) {
            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                return $connection->send($content);

            } else {
                return $connection->close($content);
            }
        }

    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    static function setCookie($name, $value)
    {
        if (IS_CLI && IS_WIN_WORKERMAN) {
            \Workerman\Protocols\Http::setcookie($name, $value);
        } else {
            setcookie($name, $value);
        }
    }


    /**
     * @param $str
     * @return void
     */
    static function header($str = '')
    {
        if (IS_CLI && IS_WIN_WORKERMAN) {
            \Workerman\Protocols\Http::header($str);
        } else {
            header($str);
        }

    }

    /**
     * 处理跨域问题
     * @param $server
     * @return void
     */
    private static function fixHttpCrossDomain($server)
    {

        if (IS_CLI && IS_WIN_WORKERMAN) {
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