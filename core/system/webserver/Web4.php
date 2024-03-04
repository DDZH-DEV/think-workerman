<?php

use Workerman\Worker;
use \Workerman\Connection\TcpConnection;
use \Workerman\Protocols\Http\Request;
use \Workerman\Protocols\Http\Response;

class Web extends Worker
{


    function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name, $context_option);

        if (IS_CLI) {
            $this->onMessage = function ($connection, $request) {
                $this->initForWorker4($connection, $request);
                \system\WebServer::dispatchHttp($connection, $this, $request);
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

        g('SERVER', $_SERVER);
        g('IP', isset($request->header()['x-real-ip']) ? $request->header()['x-real-ip'] : $connection->getRemoteIp());
        g('POST', $request->header() && isset($request->header()['content-type']) && strpos($request->header()['content-type'], 'json') != false ? json_decode($request->rawBody(), true) : $request->post());
        g('GET', $request->get());
        g('FILES', $request->file());
        g('SESSION', array_filter($request->session()->all()));
        g('COOKIE', array_filter($request->cookie()));
    }


    static function response($connection, $request)
    {
        $_SERVER = g('SERVER');
        //跨域问题
        $headers = [
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Origin' => (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : config('http.cross_url'))
        ];

        $add_headers = g('HEADER');
        if ($add_headers) {
            foreach ($add_headers as $k => $v) {
                $headers[$k] = $v;
            }
        }
        $session = g('SESSION');
        $all_session = $request->session()->all();
        //设置新的session
        $request->session()->put($session);
        //删除旧的session

        if ($diff_session = arrayRecursiveDiff($all_session, $session)) {
            foreach ($diff_session as $k => $v) {
                if (is_array(g('SESSION')) && in_array($k, array_keys(g('SESSION')))) {
                    unset($diff_session[$k]);
                }
            }
            $request->session()->forget(array_keys($diff_session));
        }

        $content = ob_get_clean();

        !APP_DEBUG && $content =preg_replace("/\>[\s]+?\</",'><',$content);

        $response = new \Workerman\Protocols\Http\Response(200, $headers, $content);
        $add_cookies = arrayRecursiveDiff(g('COOKIE'), $request->cookie());
        $remove_cookies = arrayRecursiveDiff($request->cookie(), g('COOKIE'));

        if ($add_cookies) {
            foreach ($add_cookies as $name => $val) {
                $response->cookie($name, $val);
            }
        }

        if ($remove_cookies) {
            foreach ($remove_cookies as $name => $val) {
                if (!in_array($name, array_keys(g('COOKIE')))) $response->cookie($name, '', 1);
            }
        }

        //释放变量
        g(null);

        return $connection->send($response);
    }


    /**
     * @param $connection
     * @param $request
     * @param $message
     * @return void
     */
    public function handleFileRequest($connection, $file, $request)
    {
        $not_found = '<h3>404 Not Found</h3>';
        if (!$request) {
            echo $not_found;
            return;
        }

        if (strpos($file, PUBLIC_PATH) !== 0) {
            $connection->send(new Response(400));
            return;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

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

        if ($extension === 'php') {
            $connection->send(exec_php_file($file));
        } else {
            $connection->send((new Response())->withFile($file));
        }
    }

}