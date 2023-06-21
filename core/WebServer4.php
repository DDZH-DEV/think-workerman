<?php
/**
 * 适用于workerman 4.*
 *
 * @Author  : 9rax.dev@gmail.com
 * @DateTime: 2020/7/5 22:05
 * @Notice  : 这是(九锐团队)旗下的软件之一，如果您未经授权从其他途径获得它，使用它并带来了利益，请记得支持我们。如果不能为您带来利益，请您不要扩散它，因为每个软件从0到1都经历了一个漫长的过程，并且需要经费继续维护它。
 */
use rax\RaxWaf;
use \Workerman\Connection\TcpConnection;
use \Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

function exec_php_file($file) {
    \ob_start();
    // Try to include php file.
    try {
        include $file;
    } catch (\Exception $e) {
        echo $e;
    }
    return \ob_get_clean();
}


class WebServer extends \Workerman\Worker
{
    function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name , $context_option);

        /**
         * @Author  : 9rax.dev@gmail.com
         * @DateTime: 2021/4/9 18:32
         */
        $this->onWorkerStart=function (){
            $global = new \GlobalData\Client(\Config::$global_data['client']);
            if(\Config::$waf['enable']) {
                RaxWaf::init(\Config::$waf);
                $global->deny_ips=$global->deny_ips?:RaxWaf::getDenyIps();
            }
        };

        /**
         * @param \Workerman\Connection\TcpConnection $connection
         * @param \Workerman\Protocols\Http\Request   $request
         *
         * @return bool|void|null
         * @Author  : 9rax.dev@gmail.com
         * @DateTime: 2021/4/9 18:32
         */
        $this->onMessage = function (TcpConnection $connection,  Request $request) {

            global $global;

            if(!defined('WEB_SERVER')) define('WEB_SERVER',true);
            $_SERVER = array(
                'QUERY_STRING'         => '',
                'REQUEST_METHOD'       => '',
                'REQUEST_URI'          => '',
                'SERVER_PROTOCOL'      => '',
                'SERVER_SOFTWARE'      => 'workerman/'.\Workerman\Worker::VERSION,
                'SERVER_NAME'          => '',
                'HTTP_HOST'            => '',
                'HTTP_USER_AGENT'      => '',
                'HTTP_ACCEPT'          => '',
                'HTTP_ACCEPT_LANGUAGE' => '',
                'HTTP_ACCEPT_ENCODING' => '',
                'HTTP_COOKIE'          => '',
                'HTTP_CONNECTION'      => '',
                'REMOTE_ADDR'          => '',
                'REMOTE_PORT'          => '0',
                'REQUEST_TIME'         => time()
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
                list($key, $value)       = explode(':', $content, 2);
                $key                     = str_replace('-', '_', strtoupper($key));
                $value                   = trim($value);
                $_SERVER['HTTP_' . $key] = $value;
                switch ($key) {
                    // HTTP_HOST
                    case 'HOST':
                        $tmp                    = explode(':', $value);
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
                            $http_post_boundary      = '--' . $match[1];
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

            _G('_SERVER',$_SERVER);

            //判断防火墙是否开启
            if(\Config::$waf['enable']){
                $ip=ip2long($connection->getRemoteIp());
                //判断是否在防火墙中
                if(isset($global->deny_ips[$ip])){
                    return $connection->send(RaxWaf::$config['deny_message'].' deny ip !');
                }
                $deny=RaxWaf::check($connection->getRemoteIp(),$request->uri(),['GET'=>$request->get(),'POST'=>$request->post(),'COOKIE'=>$request->cookie()]);
                if($deny){
                    $waf_ip_key='rax_waf_ip_'.$ip;
                    $hit_num = $global->$waf_ip_key?:0;
                    $hit_num++;
                    $global->$waf_ip_key=$hit_num;
                    if($hit_num>\Config::$waf['deny_num']){
                        $deny_ips=$global->deny_ips?:[];
                        $deny_ips[$ip]=$hit_num;
                        $global->deny_ips=$deny_ips;
                        RaxWaf::saveDenyIps($deny_ips);
                    }
                    return $connection->send(RaxWaf::$config['deny_message']);
                }
            }
            _G('IP',isset($request->header()['x-real-ip'])?$request->header()['x-real-ip']: $connection->getRemoteIp());
            _G('_POST', $request->header() && isset($request->header()['content-type']) && strpos($request->header()['content-type'], 'json') != false ? json_decode($request->rawBody(), true) : $request->post());
            _G('_GET', $request->get());
            _G('_FILES', $request->file());
            _G('_SESSION', array_filter($request->session()->all()));
            _G('_COOKIE', array_filter($request->cookie()));
            _G('DEBUG', file_exists(APP_PATH . '/debug'));
            //开始分发请求
            $params = str_replace(['.html', '.htm', '.shtml'], [''], preg_split('/(\/|\?)/', $request->uri()));

            $params = array_filter($params, function ($item) {
                //c($item);
                return $item && strpos($item, '=') == false ? $item : false;
            });

            //最多两种模式
            $params = array_slice($params, 0, 2);

            $total = count($params);

            $params = $total < 2 ? array_merge(array_fill(0, 2 - $total, 'index'), $params) : $params;


            @list(  $_['controller'], $_['action']) = $params;

            $controller = 'app\\controller\\' . ucfirst($_['controller']);

            if (class_exists($controller) && method_exists($controller, $_['action'])) {

                //全局设置
                _G('IS_MOBILE', is_mobile($request->header()['user-agent']));

                //跨域问题
                $headers=[
                    'Access-Control-Allow-Credentials'=>'true',
                    'Access-Control-Allow-Methods'=>'GET, POST, PUT, OPTIONS',
                    'Access-Control-Allow-Headers'=>'*',
                    'Access-Control-Allow-Origin'=>(isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN']: \Config::$http['cross_url'])
                ];


                ob_start();
                try {
                    call_user_func_array([new $controller, $_['action']], [$request,$connection]);
                } catch (\Exception $e) {
                    //如果是调试模式，直接输出

                    if($e->getMessage()!=='jump_exit'){
                        if (_G('DEBUG')) {
                            p($e->getMessage());
                            p($e->getTraceAsString());
                        }
                        log_exception($e);
                    }

                }catch (\Error $error){
                    log_exception($error);
                }

                $add_headers=_G('_HEADER');
                if($add_headers){
                    foreach ($add_headers as $k=>$v){
                        $headers[$k]=$v;
                    }
                }

                $session=_G('_SESSION');
                $all_session=$request->session()->all();
                //设置新的session
                $request->session()->put($session);
                //删除旧的session

                if($diff_session=arrayRecursiveDiff($all_session,$session)){
                    foreach ($diff_session as $k=>$v){
                        if(is_array(_G('_SESSION')) && in_array($k,array_keys(_G('_SESSION')))){
                            unset($diff_session[$k]);
                        }
                    }
                    $request->session()->forget(array_keys($diff_session));
                }

                $content = ob_get_clean();
                $response = new \Workerman\Protocols\Http\Response(200, $headers, $content);
                $add_cookies=arrayRecursiveDiff(_G('_COOKIE'),$request->cookie());
                $remove_cookies=arrayRecursiveDiff($request->cookie(),_G('_COOKIE'));

                if($add_cookies){
                    foreach ($add_cookies as $name=>$val){
                        $response->cookie($name, $val);
                    }
                }

                if($remove_cookies){
                    foreach ($remove_cookies as $name=>$val){
                        if(!in_array($name,array_keys(_G('_COOKIE')))) $response->cookie($name,'',1);
                    }
                }

                //释放变量
                _G(null);

                return $connection->send($response);
            }else{


                $_GET = $request->get();
                $path = $request->path();
                if ($path === '/') {
                    $connection->send(exec_php_file(PUBLIC_PATH.'/index.php'));
                    return;
                }
                $file = realpath(PUBLIC_PATH. $path);
                if (false === $file) {
                    $connection->send(new Response(404, array(), '<h3>404 Not Found</h3>'));
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

        };

    }
}