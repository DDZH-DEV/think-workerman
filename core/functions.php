<?php


/**
 * 前端打印
 * @param $obj
 * @Author: zaoyongvip@gmail.com
 */
function p($obj)
{
    echo '<pre>' . print_r($obj, true) . '</pre>';
}


/**
 * 字节大小转换
 * @param $size
 * @return string
 * @Author: zaoyongvip@gmail.com
 */
function convert($size)
{
    $size=$size===true?memory_get_usage():$size;
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

/**
 * 前端json
 * @param $data
 * @param $status
 * @return string json
 * @Author: zaoyongvip@gmail.com
 */
function json($data, $code = 200, $msg = null,$debug=[])
{
    //p($msg);
    if (is_string($data) && $msg === null) {
        $msg = $data;
        $data='';
    }

    if ($code === true) {
        $result = $data;
    } else {
        $result = [
            'data' => $data,
            'code' => $code,
            'msg' => $msg
        ];
    }

    //开发模式
    if (_G('DEBUG')) {
        $result['memory'] = convert(memory_get_usage());
        $result['files'] = count(get_included_files());
        $result['session'] = session();
        $result['session_id'] = \Workerman\Protocols\HttpCache::$instance->sessionFile;
        $result['server'] = $_SERVER;
        $result['_G']=_G();
        $result['register_address']=\GatewayClient\Gateway::$registerAddress;
        if($debug){
            $result['debug']=$debug;
        }
    }

    \Workerman\Protocols\Http::header('content-type: application/json');
    \Workerman\Protocols\Http::end(json_encode($result, JSON_UNESCAPED_UNICODE));
}


/**
 * 发送日志
 * @param string $message
 * @param string $level
 * @param string $listen
 * @param bool $write
 * @return bool
 * @Author: zaoyongvip@gmail.com
 */
function slog($message = '', $level = 'log', $listen = '')
{
    if (!$message) {
        return true;
    }

    $address = '/' . 'rnm' . $listen;

    $console = false;
    if (strpos($level, '@') > 1) {
        $params = explode('@', $level);
        $level = $params[0];
        $console = true;
    }

    $content = array(
        'client_id' => 'rnm',
        'content' => $message,
        'level' => $level,
        'console' => $console
    );

    static $Curl, $_flag;


    $url = 'http://127.0.0.1:1116' . $address;
    $Curl = $Curl ? $Curl : curl_init();

    if ($_flag == 1) {

        curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($content, JSON_UNESCAPED_UNICODE));
        curl_exec($Curl);
        return true;
    } else {
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_POST, true);
        curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($content, JSON_UNESCAPED_UNICODE));
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);
        $headers = array(
            "Content-Type: application/json;charset=UTF-8"
        );
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $headers);//设置header
        curl_exec($Curl);
        $_flag = 1;
        return true;
    }


}


/**
 * 文件夹复制
 * @param $src
 * @param $dst
 * @Author: zaoyongvip@gmail.com
 */
function copy_dir($src, $dst)
{  // 原目录，复制到的目录
    $dir = opendir($src);
    !is_dir($dst) && mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_dir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}


if (!function_exists('session')) {
    /**
     * Session管理
     * @param string|array $name session名称，如果为数组表示进行session设置
     * @param mixed $value session值
     * @param string $prefix 前缀
     * @return mixed
     */
    function session($name = '', $value = '', $prefix = null)
    {

        if (is_array($name)) {
            try {
                foreach ($name as $sessionName => $sessionValue) {
                    $_SESSION[$sessionName] = json_encode($sessionValue, true);
                }
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        } elseif (is_null($name)) {
            // 清除,奇葩的workmanSession机制
            $_SESSION=['destroy'=>date('YmdHis')];
            return true;
        } elseif ($name && !$value) {
            // 判断或获取
            return (isset($_SESSION[$name]) && json_decode($_SESSION[$name], true)) ? json_decode($_SESSION[$name], true) : @$_SESSION[$name];
        } elseif (is_null($value)) {
            // 删除
            unset($_SESSION[$name]);
            return true;

        } elseif ($name ==='') {
            // 设置
            $tmp=[];
            if(is_array($_SESSION)){
                foreach ($_SESSION as $k=>$v){
                    $tmp[$k]=json_decode($v, true)?json_decode($v, true):$v;
                }
            }
            return $tmp;

        } else {
            $_SESSION[$name] = json_encode($value);
        }
    }
}


if (!function_exists('_G')) {
    /**
     * 全局变量共享,每次控制器请求结束后释放
     * @param string $name
     * @param string $value
     * @return mixed|null
     * @Author: zaoyongvip@gmail.com
     */
    function _G($name = '', $value = '',$long=false)
    {
        if (is_null($name)) {
            // 清除
            utils\G::clear();
        } elseif ($name && $value) {
            // 设置
            utils\G::set($name, $value,$long);
        } elseif ($name == '') {
            return utils\G::all();
        } else {
            //echo \utils\Console::info('GET');
            $long=$value=='_G'?true:$long;
            return utils\G::get($name,$long);
        }
    }
}

/**
 * 快捷获取参数
 * @param string $keys
 * @param string $type
 * @return array|string|null
 * @Author: zaoyongvip@gmail.com
 */
function getParams($keys = null)
{
    return utils\Request::params($keys);
}


/**
 * 模型类
 * @param string $name
 * @param string $layer
 * @return \think\Model
 * @Author: zaoyongvip@gmail.com
 */
function model($name = '', $layer = 'Model')
{
    static $_models;

    $guid = md5($name . $layer);

    if (isset($_models[$guid])) {
        return $_models[$guid];
    }

    $mvc = _G('MVC');


    if(strpos($name,'/')){
        list($mvc['module'],$name)=explode('/',$name);
    }

    $class = '\app\\' . $mvc['module'] . '\model\\' . ucfirst($name);

    if (class_exists($class)) {
        $model = new $class();
    } else {
        throw new \Exception('class not exist :'.$class);
    }
    $_models[$guid] = $model;
    return $model;
}


/**
 * mongodb 直接连接
 * @param string $table
 * @return \think\db\Connection|\think\db\Query
 * @Author: zaoyongvip@gmail.com
 */
function mongodb($table = '')
{
    ini_set('mongo.native_long', 1);
    $connect = null;
    $connect = \think\Db::connect(Config::$mongodb, 'mongo');
    return $table ? $connect->name($table) : $connect;
}


/**
 * 判断是否是手机
 * @return int
 * @Author: zaoyongvip@gmail.com
 */
function is_mobile(){
    // returns true if one of the specified mobile browsers is detected
    // 如果监测到是指定的浏览器之一则返回true

    $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";

    $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";

    $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";

    $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";

    $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";

    $regex_match.=")/i";

    // preg_match()方法功能为匹配字符，既第二个参数所含字符是否包含第一个参数所含字符，包含则返回1既true
    return preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']));
}


/**
 * CLI端调试输出
 * @param $message
 * @param string $type
 * @Author: zaoyongvip@gmail.com
 */
function console($message,$type='info'){
    $message=is_string($message)?$message:json_encode($message);
    echo \utils\Console::$type($message);
}


/**
 * 图片输出
 * @param $url
 * @return string
 * @Author: zaoyongvip@gmail.com
 */
function img_fix($url){
    return ($url && strpos($url,'http')===false)?Config::$app['static_url'].$url:$url;
}


/**
 * 加入处理队列
 * @param $type
 * @param $data
 * @Author: zaoyongvip@gmail.com
 */
function addToQueue($type,$data){
    $Queue=\utils\Queue::instance();
    $data['_type']=$type;
    @$Queue->put(\Config::$app['name'],json_encode($data));
}





/**
 * 快捷发送post
 * @param $url
 * @param $data
 * @return mixed
 * @Author: zaoyongvip@gmail.com
 */
function _post($url,$data){

    static $_ch;

    if(!$_ch){
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $url);
        curl_setopt($_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($_ch, CURLOPT_POST, 1);
        curl_setopt($_ch, CURLOPT_HEADER, 0);
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
    }else{
        curl_setopt($_ch, CURLOPT_URL, $url);
    }

    curl_setopt($_ch,CURLOPT_POSTFIELDS,$data);

    if(false === $result=curl_exec($_ch)) {
        return curl_error($_ch);
    }

    return $result;
}


/**
 * 获取微信操作对象（单例模式）
 * @staticvar array $wechat 静态对象缓存对象
 * @param string $type 接口名称 ( Card|Custom|Device|Extend|Media|Oauth|Pay|Receive|Script|User )
 * @return \Wechat\WechatReceive 返回接口对接
 */
function & load_wechat($type = '') {
    static $wechat = array();
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {

        \Wechat\Loader::config(Config::$wechat);
        $wechat[$index] = \Wechat\Loader::get($type);
    }
    return $wechat[$index];
}