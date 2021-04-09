<?php


/**
 * 打印数据
 * @param mixed ...$data
 *
 * @Author  : 9rax.dev@gmail.com
 * @DateTime: 2020/7/9 11:18
 */
function p(...$data)
{
    $arg_list = func_get_args();

    $arg_list = func_num_args()==1?$arg_list[0]:$arg_list;

    echo '<pre>.' . print_r($arg_list, true) . '</pre>'."\r\n\r\n";
}


/**
 * 字节大小转换
 * @param $size
 * @return string
 * @Author: 9rax.dev@gmail.com
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
 * @Author: 9rax.dev@gmail.com
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
    if(version_compare(\Workerman\Worker::VERSION,'4.0.0','<')){
        \Workerman\Protocols\Http::header('content-type: application/json');
        \Workerman\Protocols\Http::end(json_encode($result, JSON_UNESCAPED_UNICODE));
    }else{
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        throw new Exception('jump_exit');
    }

}


/**
 * 发送日志
 * @param string $message
 * @param string $level
 * @param string $listen
 * @param bool $write
 * @return bool
 * @Author: 9rax.dev@gmail.com
 */
function slog($message = '', $level = 'log', $listen = '9rax')
{
    if (!$message) {
        return true;
    }

    $address = '/' .   $listen;

    $console = false;
    if (strpos($level, '@') > 1) {
        $params = explode('@', $level);
        $level = $params[0];
        $console = true;
    }

    $content = array(
        'client_id' => $listen,
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
 * @Author: 9rax.dev@gmail.com
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


if (!function_exists('data')) {
    /**
     * 数据操作函数
     * @param string $name
     * @param string $value
     * @param string $layer
     *
     * @return array|bool|mixed
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2020/7/8 14:54
     */
    function data($name = '', $value = '',$layer='_SESSION')
    {
        $data=_G($layer);

        if (is_array($name)) {
            try {
                foreach ($name as $dataName => $dataValue) {
                    $data[$dataName] = json_encode($dataValue, true);
                }
                _G($layer,$data);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        } elseif (is_null($name)) {
            // 清除,奇葩的workmanSession机制
            $data=['destroy'=>date('YmdHis')];
            _G($layer,$data);
//            p('clear');
            return true;
        } elseif ($name && !$value && !is_null($value)) {
            // 判断或获取
            return (isset($data[$name]) && $data[$name])
                ? (is_array($data[$name])?$data[$name]:json_decode($data[$name], true)) :
                $data[$name];
        } elseif (is_null($value)) {
            // 删除
            if (isset($data[$name]))  unset($data[$name]);
            _G($layer,$data);
//            p('delete item '.$name);
            return true;

        } elseif ($name ==='') {
            // 设置
            $tmp=[];
            if(is_array($data)){
                foreach ($data as $k=>$v){
                    $tmp[$k]=json_decode($v, true)?json_decode($v, true):$v;
                }
            }
//            p('read all');
            return $tmp;

        } else {
            $data[$name] = $value;
//            p('set item '.$name,$data);
            _G($layer,$data);
        }
    }
}

if (!function_exists('session')) {
    /**
     * session快捷操作
     * @param string $name
     * @param string $value
     *
     * @return array|bool|mixed
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2020/7/8 14:55
     */
    function session($name = '', $value = ''){
        return data($name, $value,'_SESSION');
    }
}


if (!function_exists('cookie')) {
    /**
     * cookie快捷操作
     * @param string $name
     * @param string $value
     *
     * @return array|bool|mixed
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2020/7/8 14:55
     */
    function cookie($name = '', $value = ''){
        return data($name, $value,'_COOKIE');
    }
}


if (!function_exists('_header')) {
    /**
     * _header快捷操作
     * @param string $name
     * @param string $value
     *
     * @return array|bool|mixed
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2020/7/8 14:55
     */
    function _header($name = '', $value = ''){
        return data($name, $value,'_HEADER');
    }
}


if (!function_exists('_G')) {
    /**
     * 全局变量共享,每次控制器请求结束后释放
     * @param string $name
     * @param string $value
     * @return mixed|null
     * @Author: 9rax.dev@gmail.com
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
 * 获取输入数据 支持默认值和过滤
 * @param string    $key 获取的变量名
 * @param mixed     $default 默认值
 * @param string    $filter 过滤方法
 * @return mixed
 */
function input($key ='',$default_value=null,$filter='')
{
    if (0 === strpos($key, '?')) {
        $key = substr($key, 1);
        $has = true;
    }

    if ($pos = strpos($key, '.')) {
        // 指定参数来源
        $method = substr($key, 0, $pos);
        if (in_array($method, ['get', 'post', 'session', 'cookie', 'file'])) {
            $key = substr($key, $pos + 1);
            $key = $key==='file'?'files':$key;
        } else {
            $method = 'params';
        }
    } else {
        // 默认为自动判断
        $method = 'params';
    }
    $params=array_merge((array)_G('_GET'),(array)_G('_POST'));

    if(!$key) return $method==='params'?$params:(array)_G('_'.strtoupper($method));

    if($method==='params'){
        return  isset($params[$key])?
            (is_callable($filter)?call_user_func($filter,$params[$key]):$params[$key]):
            $default_value;
    }else{
        $find=_G('_'.strtoupper($method));
        if($find && isset($find[$key])){
            return is_callable($filter)?call_user_func($filter,$find[$key]):$find[$key];
        }
        return $default_value;
    }
}




/**
 * 判断是否是手机
 * @return int
 * @Author: 9rax.dev@gmail.com
 */
function is_mobile($agent){
    // returns true if one of the specified mobile browsers is detected
    // 如果监测到是指定的浏览器之一则返回true

    $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";

    $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";

    $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";

    $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";

    $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";

    $regex_match.=")/i";

    // preg_match()方法功能为匹配字符，既第二个参数所含字符是否包含第一个参数所含字符，包含则返回1既true
    return preg_match($regex_match, strtolower($agent));
}


/**
 * CLI端调试输出
 * @param $message
 * @param string $type
 * @Author: 9rax.dev@gmail.com
 */
function console($message,$type='info'){
    if(defined('WEB_SERVER')){
        return;
    }
    $message=is_string($message)?$message:json_encode($message);
    echo \utils\Console::$type($message);
}


/**
 * 静态资源输出
 * @param $url
 * @return string
 * @Author: 9rax.dev@gmail.com
 */
function staticFix($url){
    return ($url && strpos($url,'http')===false)?Config::$http['cdn_url'].$url:$url;
}


/**
 * 加入处理队列
 * @param $type
 * @param $data
 * @Author: 9rax.dev@gmail.com
 */
function addToQueue($type,$data,$callback=null){

    static $Queue;
    static $queue_key;
    $queue_key=$queue_key?$queue_key:md5(json_encode(Config::$http));

    if(!$Queue){
        $_redis=new \Redis();
        $_redis->connect('127.0.0.1');
        $_redis->setOption(\Redis::OPT_PREFIX, $queue_key);
        $Queue = new \Phive\Queue\RedisQueue($_redis);
    }
    $data['_type']=$type;
    $data['_callback']=$callback;

    console('[add_queue]:'.$type.'|'.$queue_key);

    $Queue->push(json_encode($data,JSON_UNESCAPED_UNICODE));

}


/**
 * arrayRecursiveDiff
 * @param $aArray1
 * @param $aArray2
 *
 * @return array
 * @Author  : 9rax.dev@gmail.com
 * @DateTime: 2021/3/29 18:25
 */
function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = array();
    if($aArray1){
        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
    }else if($aArray2){
        return  $aArray2;
    }

    return $aReturn;
}
