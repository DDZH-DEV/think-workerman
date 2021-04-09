<?php
use rax\RaxWaf;

class WebServer extends \Workerman\WebServer
{
    public function onWorkerStart (){

        $global = new \GlobalData\Client('127.0.0.1:'.\Config::$global_data['port']);
        if(\Config::$waf['enable']) {
            RaxWaf::init(\Config::$waf);
            $global->deny_ips=$global->deny_ips?:RaxWaf::getDenyIps();
        }
    }

    public function onMessage($connection)
    {

        global $global;

        if(!defined('WEB_SERVER')) define('WEB_SERVER',true);

        //判断防火墙是否开启
        if(\Config::$waf['enable']){
            $ip=ip2long($connection->getRemoteIp());
            //判断是否在防火墙中
            if(isset($global->deny_ips[$ip])){
                return $connection->send(RaxWaf::$config['deny_message'].' deny ip !');
            }
            $deny=RaxWaf::check($connection->getRemoteIp(),$_SERVER['REQUEST_URI'],['GET'=>$_GET,'POST'=>$_POST,'COOKIE'=>$_COOKIE]);
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

        \Workerman\Protocols\Http::sessionStart();

        _G('IP', $connection->getRemoteIp());
        _G('_POST', $_POST);
        _G('_GET', $_GET);
        _G('_FILES', $_FILES);
        _G('_SESSION', $_SESSION);
        _G('_COOKIE', $_COOKIE);
        _G('DEBUG', file_exists(APP_PATH . '/debug'));

        //开始分发请求
        $params = str_replace(['.html', '.htm', '.shtml'], [''], preg_split('/(\/|\?)/', $_SERVER['REQUEST_URI']));

        $params = array_filter($params, function ($item) {
            //c($item);
            return $item && strpos($item, '=') == false ? $item : false;
        });

        //最多两种模式
        $params = array_slice($params, 0, 2);

        $total = count($params);

        $params = $total < 2 ? array_merge(array_fill(0, 2 - $total, 'index'), $params) : $params;
        
        
        @list( $_['controller'], $_['action']) = $params;

        $controller = 'app\\controller\\' . ucfirst($_['controller']);

        if (class_exists($controller) && method_exists($controller, $_['action'])) {
            //全局设置
            _G('IS_MOBILE', is_mobile($_SERVER['HTTP_USER_AGENT']));
//            p($_SERVER['HTTP_ORIGIN']);
            //跨域问题
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                \Workerman\Protocols\Http::header('Access-Control-Allow-Credentials:true');
                \Workerman\Protocols\Http::header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_ORIGIN']);
                //\Workerman\Protocols\Http::header('Access-Control-Allow-Origin:*');

            } else {
                \Workerman\Protocols\Http::header('Access-Control-Allow-Credentials:true');
                \Workerman\Protocols\Http::header('Access-Control-Allow-Origin:' . \Config::$http['api_url']);
            }

            ob_start();

            try {
                call_user_func_array([new $controller, $_['action']],[$connection]);
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
                    \Workerman\Protocols\Http::header($k.':' . $v);
                }
            }

            $_SESSION=_G('_SESSION');

            $add_cookies=arrayRecursiveDiff(_G('_COOKIE'),$_COOKIE);
            $remove_cookies=arrayRecursiveDiff($_COOKIE,_G('_COOKIE'));

            if($add_cookies){
                foreach ($add_cookies as $name=>$val){
                    \Workerman\Protocols\Http::setcookie($name,$val);
                }
            }
            if($remove_cookies){
                foreach ($remove_cookies as $name=>$val){
                    if(!in_array($name,array_keys(_G('_COOKIE')))) \Workerman\Protocols\Http::setcookie($name,'',1);
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
            $this->addRoot('127.0.0.1',PUBLIC_PATH);
            return parent::onMessage($connection);
        }


    }
}