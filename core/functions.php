<?php

use system\Config;

if (!function_exists('app')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @param string $name 类名或标识 默认获取当前应用实例
     * @param array $args 参数
     * @param bool $newInstance 是否每次创建新的实例
     * @return \think\DbManager|\JBZoo\Event\EventManager|\system\Config|\system\Router|\think\CacheManager|\think\LogManager|\think\DbManager|\think\Template|\think\facade\Db|\think\facade\Cache|\think\facade\Log
     */
    function app(string $name = '', array $args = [], bool $newInstance = false) {
        return $name ?
            \think\Container::getInstance()->make($name, $args, $newInstance) :
            \think\Container::getInstance();
    }
}
if (!function_exists('bind')) {
    /**
     * @param $name
     * @param $concrete
     * @return void
     */
    function bind($name, $concrete = null) {
        return \think\Container::getInstance()->bind($name, $concrete);
    }
}

if (!function_exists('cache')) {
    /**
     * 缓存管理
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param mixed $options 缓存参数
     * @param string $tag 缓存标签
     * @return mixed
     */
    function cache(string $name = null, $value = '', $options = null, $tag = null) {
        if (is_null($name)) {
            return app('cache');
        }
        if ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? app('cache')::has(substr($name, 1)) : app('cache')::get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return app('cache')::delete($name);
        }
        // 缓存数据
        if (is_array($options)) {
            $expire = $options['expire'] ?? null; //修复查询缓存无法设置过期时间
        } else {
            $expire = $options;
        }
        if (is_null($tag)) {
            return app('cache')::set($name, $value, $expire);
        } else {
            return app('cache')::tag($tag)->set($name, $value, $expire);
        }
    }
}

if (!function_exists('p')) {
    /**
     * 打印数据
     * @param mixed ...$data
     * */
    function p(...$data) {
        $arg_list = func_get_args();

        $arg_list = func_num_args() == 1 ? $arg_list[0] : $arg_list;

        echo '<pre>' . print_r($arg_list, true) . '</pre>' . "\r\n\r\n";
    }
}

if (!function_exists('g')) {
    /**
     * 全局变量共享,每次控制器请求结束后释放
     * @param string $name 可选值:
     *        'MODULE'|'CONTROLLER'|'ACTION'|'IS_MOBILE'|'IP'|
     *        'POST'|'GET'|'FILES'|'SESSION'|'SERVER'|'COOKIE'|'REQUEST'
     * @param mixed $value
     * @param bool $long
     * @return mixed|null
     * @see g()
     */
    function g($name = '', $value = '', $long = false) {
        if (is_null($name)) {
            // 清除
            system\G::clear();
        } elseif ($name && (!is_null($value) && $value !== '')) {
            // 设置
            system\G::set($name, $value, $long);
        } elseif ($name == '') {
            return system\G::all();
        } else {
            //echo \system\Console::info('GET');
            $long = $value == 'G' ? true : $long;
            return system\G::get($name, $long);
        }
    }
}

if (!function_exists('convert')) {
    /**
     * 字节大小转换
     * @param $size
     * @return string
     */
    function convert($size) {
        $size = $size === true ? memory_get_usage() : $size;
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}

if (!function_exists('json')) {
    /**
     * 前端json
     * @param $data
     * @param $status
     * @return void
     */
    function json($data, $code = 200, $msg = null, $debug = []) {
        if (is_string($data) && $msg === null) {
            $msg = $data;
            $data = '';
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
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
         
    }
}

if (!function_exists('slog')) {
    /**
     * 发送日志
     * @param string|array $message
     * @param string $level
     * @param string $listen
     * @param bool $write
     * @return bool
     */
    function slog($message, $level = 'log', $listen = '') {
        return \system\Debug::slog($message, $level, $listen);
    }
}

if (!function_exists('data')) {
    /**
     * 数据操作函数
     * @param string $name
     * @param string $value
     * @param string $layer
     * @return array|bool|mixed
     */
    function data($name = '', $value = '', $layer = 'SESSION') {
        $data = g($layer);

        if (is_array($name)) {
            try {
                foreach ($name as $dataName => $dataValue) {
                    $data[$dataName] = is_array($dataValue) ? $dataValue : json_encode($dataValue, true);
                }
                g($layer, $data);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        } elseif (is_null($name)) {
            // 清除,奇葩的workmanSession机制
            $data = ['destroy' => date('YmdHis')];
            g($layer, $data);
            //            p('clear');
            return true;
        } elseif ($name && !$value && !is_null($value)) {
            // 判断或获取
            if (!isset($data[$name])) {
                return '';
            }
            // 如果已经是数组则直接返回
            if (is_array($data[$name])) {
                return $data[$name];
            }
            // 尝试 JSON 解码
            return json_decode($data[$name], true) ?: $data[$name];
        } elseif (is_null($value)) {
            // 删除
            if (isset($data[$name])) unset($data[$name]);
            g($layer, $data);
            //            p('delete item '.$name);
            return true;
        } elseif ($name === '') {
            // 读取所有
            $tmp = [];
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $tmp[$k] = is_string($v) ? json_decode($v, true) : $v;
                }
            }
            //            p('read all');
            return $tmp;
        } else {
            $data[$name] = $value;
            //            p('set item '.$name,$data);
            g($layer, $data);
        }
    }
}

if (!function_exists('session')) {
    /**
     * session快捷操作
     * @param string $name
     * @param mixed $value
     * @return array|bool|mixed
     */
    function session($name = '', $value='') {
        return data($name, $value, 'SESSION');
    }
}

if (!function_exists('cookie')) {
    /**
     * cookie快捷操作
     * @param string $name
     * @param string $value
     * @return array|bool|mixed
     */
    function cookie($name = '', $value = '') {
        if (!IS_CLI && $name && $value) {
            setcookie($name, $value, 0, '/');
        }
        return data($name, $value, 'COOKIE');
    }
}

if (!function_exists('_header')) {
    /**
     * header快捷操作
     * @param string $name
     * @param string $value
     * @return array|bool|mixed
     */
    function _header($name = '', $value = '') {
        if (!IS_CLI && $name && $value) {
            return header($name . ":" . $value);
        }
        return data($name, $value, 'HEADER');
    }
}

if (!function_exists('input')) {
    /**
     * 获取输入数据 支持默认值和过滤
     * @param string $key 获取的变量名
     * @param mixed $default 默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    function input($key = '', $default_value = null, $filter = '') {
        if (0 === strpos($key, '?')) {
            $key = substr($key, 1);
            $has = true;
        }

        if ($pos = strpos($key, '.')) {
            // 指定参数来源
            $method = substr($key, 0, $pos);
            if (in_array($method, ['get', 'post', 'session', 'cookie', 'file'])) {
                $key = substr($key, $pos + 1);
                $key = $key === 'file' ? 'files' : $key;
            } else {
                $method = 'params';
            }
        } else {
            // 默认为自动判断
            $method = 'params';
        }
        $params = array_merge((array)g('GET'), (array)g('POST'));

        if (!$key) return $method === 'params' ? $params : (array)g(strtoupper($method));

        if ($method === 'params') {
            return isset($params[$key]) ?
                (is_callable($filter) ? call_user_func($filter, $params[$key]) : $params[$key]) :
                $default_value;
        } else {
            $find = g(strtoupper($method));
            if ($find && isset($find[$key])) {
                return is_callable($filter) ? call_user_func($filter, $find[$key]) : $find[$key];
            }
            return $default_value;
        }
    }
}
if (!function_exists('ip')) {
    /**
     * @return array|false|mixed|string
     */
    function ip() {
        $server = g('SERVER');

        if (isset($server)) {
            if (isset($server["HTTP_X_FORWARDED_FOR"])) {
                $realip = $server["HTTP_X_FORWARDED_FOR"];
            } else if (isset($server["HTTP_CLIENT_IP"])) {
                $realip = $server["HTTP_CLIENT_IP"];
            } else {
                $realip = $server["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
}

if (!function_exists('is_mobile')) {
    /**
     * 判断是否是手机
     * @return int
     */
    function is_mobile($agent) {
        // returns true if one of the specified mobile browsers is detected
        // 如果监测到是指定的浏览器之一则返回true

        $regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";

        $regex_match .= "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";

        $regex_match .= "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";

        $regex_match .= "symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";

        $regex_match .= "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";

        $regex_match .= ")/i";

        // preg_match()方法功能为匹配字符，既第二个参数所含字符是否包含第一个参数所含字符，包含则返回1既true
        return preg_match($regex_match, strtolower($agent));
    }
}

if (!function_exists('console')) {
    /**
     * CLI端调试输出
     * @param $message
     * @param string $type
     */
    function console($message, $type = 'info') {
        if (defined('WEB_SERVER') || !IS_CLI) {
            return;
        }
        $message = is_string($message) ? $message : json_encode($message);
        echo system\Console::$type($message);
    }
}

/**
 * 静态资源输出
 * @param $url
 * @return string
 */
function staticFix($url) {
    $cdn_url = config('http.cdn_url');
    return ($url && strpos($url, 'http') === false) ?
        config('http.cdn_url') . $url : ($url ? $url : config('http.cdn_url') . '/default.png');
}

if (!function_exists('addToQueue')) {
    /**
     * 加入处理队列
     * @param $type
     * @param $data
     */
    function addToQueue($type, $data, $callback = null) {
        static $Queue;
        static $queue_key;
        $queue_key = $queue_key ? $queue_key : str_replace(['-', '.', '*'], '_', gethostname() . '_QUEUES');
        // console('QUEUE KEY :' . $queue_key);

        if (!$Queue) {
            /**
             * @var \Redis $_redis
             */
            $_redis = new \Redis();
            $_redis->connect('127.0.0.1');
            $_redis->setOption(\Redis::OPT_PREFIX, $queue_key);
            $Queue = new \Phive\Queue\RedisQueue($_redis);
        }
        $data['_type'] = $type;
        $callback && $data['_callback'] = $callback;

        //console('[add_queue]:'.$type.'|'.$queue_key);

        $Queue->push(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('arrayRecursiveDiff')) {
    /**
     * arrayRecursiveDiff
     * @param $aArray1
     * @param $aArray2
     *
     * @return array
     */
    function arrayRecursiveDiff($aArray1, $aArray2) {
        $aReturn = array();
        if ($aArray1) {
            foreach ($aArray1 as $mKey => $mValue) {
                if (is_array($aArray2) && array_key_exists($mKey, $aArray2)) {
                    if (is_array($mValue)) {
                        $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                        if (count($aRecursiveDiff)) {
                            $aReturn[$mKey] = $aRecursiveDiff;
                        }
                    } else {
                        if ($mValue != $aArray2[$mKey]) {
                            $aReturn[$mKey] = $mValue;
                        }
                    }
                } else {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else if ($aArray2) {
            return $aArray2;
        }

        return $aReturn;
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string|array $name 参数名
     * @param mixed $value 参数值
     * @return mixed
     */
    function config($name = '', $value = null) {
        if (is_array($name)) {
            return Config::set($name, $value);
        }
        return 0 === strpos($name, '?') ? Config::has(substr($name, 1)) : Config::get($name, $value);
    }
}

if (!function_exists('url')) {
    /**
     * @param $name
     * @param $params
     * @return string
     * '/users/[i:id]/'
     * 'i' => '[0-9]++',
     * 'a' => '[0-9A-Za-z]++',
     * 'h' => '[0-9A-Fa-f]++',
     * '*' => '.+?',
     * '**' => '.++',
     * '' => '[^/\.]++'
     *
     */
    function url($name = '', $params = [], $domain = '') {
        if ($domain === true) {
            $server = g("SERVER");
            $domain = ($server['REQUEST_SCHEME'] ?: 'http') . '://' . $server['HTTP_HOST'] . '///';
        } 
        return preg_replace("/\/{3,}/", '/', '///' . $domain . app('router')->generate($name, $params));
    }
}

/**
 * 组件快捷操作(添加或者输出静态文件)
 *
 * @param mixed|array|string $type
 * @param string $act_type
 */
function assets($type, $act_type = 'add') {
    //全部输出
    if ($type === true) {
        echo app('assets')->css();
        echo app('assets')->js();
        return app('assets')->reset();
    }

    //单次载入输出
    if ($type && $act_type === true) {
        app('assets')->add($type);
        echo app('assets')->css();
        echo app('assets')->js();
        return app('assets')->reset();
    }

    //载入
    if ($act_type === 'add') {
        app('assets')->add($type);
    } else if ($type) {
        //重置类型
        echo app('assets')->$type();
        $act = 'reset' . ucfirst($type);
        app('assets')->$act();
    }
}

/**
 * 钩子机制
 * @param $name
 * @param $params
 * @param $single
 * @return mixed
 */
function hook($name = '', $params = [], $single = false) {
    $return = [];
    app('hook')->trigger($name, [$single, $params, &$return]);
    return $single && $return && is_array($return) ? $return[0] : $return;
}

/**
 * 中断hook运行
 * @auth false
 */
function abort_hook() {
    throw new \JBZoo\Event\ExceptionStop();
}

// 提取公共函数到单独的方法中
function loadHooks($hooks) {
    foreach ($hooks as $hook) {
        if ($hook['status']) {
            app('hook')->on($hook['hook'], function ($single, $hook_params = [], &$return = null) use ($hook) {
                //$hook_params 是调用时传的参数  如hook('demo',$hook_params=[])
                //$config 是配置的参数
                $config = $hook['config'] ?? [];

                try {
                    if (is_array($hook['event']) && isset($hook['event'][1]) && method_exists($hook['event'][0], $hook['event'][1])) {
                        $result = call_user_func_array($hook['event'], [$hook_params, $config, $return]);
                    } else if (is_callable($hook['event'])) {
                        $result = call_user_func($hook['event'], $hook_params, $config, $return);
                    }

                    $return = $result ?? $return;
                } catch (\Exception $e) {
                    \system\Debug::log_exception($e);
                    $return = $e->getMessage();
                }

                if ($return === false || ($return && $single)) {
                    throw new \JBZoo\Event\ExceptionStop($hook['name'] . '运行结束');
                }

                return $return;
            }, $hook['sort']);
        }
    }
}

// 提取文件合并逻辑到单独的函数中
function mergeFiles() {
    $merged_config = [];
    $merged_hooks = [];
    $merged_functions = "<?php\n\n";

    // 首先加载 apps 根目录的 config.php
    if (file_exists($root_config_file = APP_PATH . 'config.php')) {
        $root_config = include $root_config_file;
        $merged_config = array_merge($merged_config, $root_config);
    }

    foreach (glob(dirname(__DIR__, 1) . '/apps/*', GLOB_ONLYDIR) as $dir) {
        $app_name = basename($dir);

        // 检查应用是否启用
        // 检查 app.json 文件
        $app_json_file = $dir . '/app.json';
        if (file_exists($app_json_file)) {
            $app_config = json_decode(file_get_contents($app_json_file), true);
            if (!isset($app_config['enable']) || $app_config['enable'] !== true) {
                continue; // 如果应用未启用,跳过此应用
            }
        }

        // 合并配置
        if (file_exists($config_file = $dir . '/config.php')) {
            $config = include $config_file;
            $merged_config[$app_name] = $config;
        }

        // 合并钩子
        if (file_exists($hook_file = $dir . '/hook.php')) {
            $hooks = include $hook_file;
            $merged_hooks = array_merge($merged_hooks, $hooks);
        }

        // 合并函数
        if (file_exists($functions_file = $dir . '/functions.php')) {
            $content = file_get_contents($functions_file);
            $content = preg_replace('/^<\?php/', '', $content);
            $content = preg_replace('/\?>$/', '', $content);

            $merged_functions .= "// Functions from {$app_name}\n";
            $merged_functions .= $content . "\n\n";
        }
    }

    return [$merged_config, $merged_hooks, $merged_functions];
}
