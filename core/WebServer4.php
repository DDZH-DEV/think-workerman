<?php
/**
 * 适用于workerman 4.*
 *
 * @Author  : 9rax.dev@gmail.com
 * @DateTime: 2020/7/5 22:05
 * @Notice  : 这是(九锐团队)旗下的软件之一，如果您未经授权从其他途径获得它，使用它并带来了利益，请记得支持我们。如果不能为您带来利益，请您不要扩散它，因为每个软件从0到1都经历了一个漫长的过程，并且需要经费继续维护它。
 */
use rax\RaxWaf;

class WebServer extends \Workerman\Worker
{
    function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name , $context_option);


        $this->onWorkerStart=function (){
            $global = new \GlobalData\Client('127.0.0.1:'.\Config::$global_data['port']);
            if(\Config::$waf['enable']) {
                RaxWaf::init(\Config::$waf);
                $global->deny_ips=$global->deny_ips?:RaxWaf::getDenyIps();
            }
        };

        $this->onMessage = function (\Workerman\Connection\TcpConnection $connection, \Workerman\Protocols\Http\Request $request) {

            global $global;

            if(!defined('WEB_SERVER')) define('WEB_SERVER',true);

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
                if (isset($request->header()['origin'])) {
                    $headers=[
                        'Access-Control-Allow-Credentials'=>'true',
                        'Access-Control-Allow-Origin'=>$request->header()['origin']
                    ];

                } else {
                    $headers=[
                        'Access-Control-Allow-Credentials'=>'true',
                        'Access-Control-Allow-Origin'=>\Config::$http['api_url']
                    ];
                }

                ob_start();
                try {
                    call_user_func_array([new $controller, $_['action']], [$request,$connection]);
                } catch (\Exception $e) {
                    //如果是调试模式，直接输出
                    if (_G('DEBUG')) {
                        p($e->getMessage());
                        p($e->getTraceAsString());
                    }
                    log_exception($e);

                }catch (\Error $error){
                    log_exception($error);
                }

                $session=_G('_SESSION');
                $all_session=$request->session()->all();
                //设置新的session
                $request->session()->put($session);
                //删除旧的session
                if($diff_session=array_diff($all_session,$session)){
                    foreach ($diff_session as $k=>$v){
                        if(in_array($k,array_keys(_G('_SESSION')))){
                            unset($diff_session[$k]);
                        }
                    }
                    $request->session()->forget(array_keys($diff_session));
                }

                $content = ob_get_clean();
                $response = new \Workerman\Protocols\Http\Response(200, $headers, $content);
                $add_cookies=array_diff(_G('_COOKIE'),$request->cookie());
                $remove_cookies=array_diff($request->cookie(),_G('_COOKIE'));

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
                return $connection->send('404');
            }

        };

    }
}