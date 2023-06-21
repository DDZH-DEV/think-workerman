<?php

use rax\RaxWaf;

const CGI_MODE = true;

include "init.php";


if (\Config::$waf['enable']) {
    $ip = ip2long(getIP());
    //判断是否在防火墙中
    if (isset($global->deny_ips[$ip])) {
        echo(RaxWaf::$config['deny_message'] . ' deny ip !');
    }

    $deny = RaxWaf::check(getIP(), $_SERVER['REQUEST_URI'], ['GET' => $_GET, 'POST' => $_POST, 'COOKIE' => $_COOKIE]);

    if ($deny) {
        $waf_ip_key = 'rax_waf_ip_' . $ip;
        /*
        $hit_num = $global->$waf_ip_key?:0;
        $hit_num++;
        $global->$waf_ip_key=$hit_num;
        if($hit_num>\Config::$waf['deny_num']){
            $deny_ips=$global->deny_ips?:[];
            $deny_ips[$ip]=$hit_num;
            $global->deny_ips=$deny_ips;
            RaxWaf::saveDenyIps($deny_ips);
        }
        */
        echo(RaxWaf::$config['deny_message']);
    }
}


_G('IP', getIP());
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
    return $item && !strpos($item, '=') ? $item : false;
});

//最多两种模式
$params = array_slice($params, 0, 2);

$total = count($params);

$params = $total < 2 ? array_merge(array_fill(0, 2 - $total, 'index'), $params) : $params;


@list($_['controller'], $_['action']) = $params;

$controller = 'app\\controller\\' . ucfirst($_['controller']);

if (class_exists($controller) && method_exists($controller, $_['action'])) {
    //全局设置
    _G('IS_MOBILE', is_mobile($_SERVER['HTTP_USER_AGENT']));
    //跨域问题
    header('Access-Control-Allow-Credentials:true');

    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header('Access-Control-Allow-Origin:*');
    }
    //var_dump(class_exists($controller) && method_exists($controller, $_['action']));die;
    try {
        call_user_func_array([new $controller, $_['action']], []);
    } catch (Exception $e) {
        //如果是调试模式，直接输出
        if ($e->getMessage() !== 'jump_exit') {
            if (_G('DEBUG') || 1) {
                p($e->getMessage());
                p($e->getTraceAsString());
                die();
            }
            log_exception($e);
        }
    } catch (Error $error) {
        log_exception($error);
    }

    //兼容处理
    $add_headers = _G('_HEADER');
    if ($add_headers) {
        foreach ($add_headers as $k => $v) {
            header($k . ':' . $v);
        }
    }

    $_SESSION = _G('_SESSION');

    $add_cookies = arrayRecursiveDiff(_G('_COOKIE'), $_COOKIE);
    $remove_cookies = arrayRecursiveDiff($_COOKIE, _G('_COOKIE'));

    if ($add_cookies) {
        foreach ($add_cookies as $name => $val) {
            setcookie($name, $val);
        }
    }
    if ($remove_cookies) {
        foreach ($remove_cookies as $name => $val) {
            if (!in_array($name, array_keys(_G('_COOKIE')))) setcookie($name, '', 1);
        }
    }

    //释放变量
    _G(null);

}