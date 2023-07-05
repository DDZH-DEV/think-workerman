<?php


use Workerman\Protocols\Http;

class Web extends \Workerman\WebServer
{

    public function onWorkerStart()
    {
        if (IS_CLI) {
            // Init mimeMap.
            $this->initMimeTypeMap();
            //var_dump($this->onMessage);
            $this->onMessage = function ($connection, $request) {
                //开启session
                \Workerman\Protocols\Http::sessionStart();

                g('IP', $connection->getRemoteIp());
                g('POST', $_POST);
                g('GET', $_GET);
                g('FILES', $_FILES);
                g('SESSION', $_SESSION);
                g('SERVER', $_SERVER);
                g('COOKIE', $_COOKIE);
                g('DEBUG', file_exists(APP_PATH . '/debug'));

                \system\WebServer::dispatchHttp($connection, $this, $request);
            };
        }
    }

       /**
     * Workerman3.*和FPM输出
     * @param $connection
     * @return mixed
     */
    static function response($connection,$message='')
    {
        //兼容处理
        $add_headers = g('HEADER');
        if ($add_headers) {
            foreach ($add_headers as $k => $v) {
                self::header($k . ':' . $v);
            }
        }
        $_SESSION = g('SESSION');
        $add_cookies = arrayRecursiveDiff(g('COOKIE'), $_COOKIE);
        $remove_cookies = arrayRecursiveDiff($_COOKIE, g('COOKIE'));
        if ($add_cookies) {
            foreach ($add_cookies as $name => $val) {
                self::setcookie($name, $val);
            }
        }


        if ($remove_cookies) {
            foreach ($remove_cookies as $name => $val) {
                if (!in_array($name, array_keys(g('COOKIE')))) self::setCookie($name, '', 1);
            }
        }
        $content = ob_get_clean();
        //释放变量
        g(null);

        if (IS_CLI) {
            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                return $connection->send($content);

            } else {
                return $connection->close($content);
            }
        }
    }


    /**
     * Emit when http message coming.
     *
     * @param Connection\TcpConnection $connection
     * @return void
     */
    public function handleFileRequest($connection, $file,$request=null)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        // Request php file.
        if ($extension === 'php') {

            ini_set('display_errors', 'off');
            ob_start();
            // Try to include php file.
            $_SERVER['REMOTE_ADDR'] = $connection->getRemoteIp();
            $_SERVER['REMOTE_PORT'] = $connection->getRemotePort();
            $content=exec_php_file($file);
            ini_set('display_errors', 'on');

            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                $connection->send($content);
            } else {
                $connection->close($content);
            }

        } elseif (in_array($extension, ['html', 'js', 'css', 'txt', 'htm'])) {
            return $connection->send(file_get_contents($file));
        }else {
            // Send file to client.
            return self::sendFile($connection, $file);
        }

    }




    /**
     * @param $name
     * @param $value
     * @return void
     */
    protected static function setCookie($name, $value)
    {
        if (IS_CLI && IS_LOW_WORKERMAN) {
            \Workerman\Protocols\Http::setcookie($name, $value);
        } else {
            setcookie($name, $value);
        }
    }


    /**
     * Workerman3.*和FPM的header输出
     * @param $str
     * @return void
     */
    protected static function header($str = '')
    {
        if (IS_CLI && IS_LOW_WORKERMAN) {
            \Workerman\Protocols\Http::header($str);
        } else {
            header($str);
        }

    }


}