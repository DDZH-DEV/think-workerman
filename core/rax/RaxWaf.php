<?php

namespace rax;
/**
 * 根据宝塔规则改写的php防火墙,后期更新规则请进群940586873
 * * @Notice  : 九锐网(9rax.com)旗下的开源作品。
 */
class RaxWaf
{

    static $config = [
        //开启防火墙
        'enable' => true,
        //开启记录
        'log' => true,
        //日志目录
        'log_path' => __DIR__ . DIRECTORY_SEPARATOR . 'waf_log' . DIRECTORY_SEPARATOR,
        //规则目录
        'rule_path' => __DIR__ . DIRECTORY_SEPARATOR . 'waf_rule' . DIRECTORY_SEPARATOR,
        //拦截消息
        'deny_message' => 'php waf hit ! ',
        //注入多少次后屏蔽
        'deny_num' => 3,
    ];

    static $is_cli;

    static $rules;

    static $instance;

    /**
     * @var callable
     */
    static $handle;

    private static $deny_ips = [];

    function __construct()
    {
        self::$rules['args'] = self::parserRule('args.json');
        self::$rules['post'] = self::parserRule('post.json');
        self::$rules['url'] = self::parserRule('url.json');
    }

    /**
     * 解析规则
     * @param $json
     * @return array
     */
    private static function parserRule($json): array
    {

        $res = [];

        $content = json_decode(file_get_contents(self::$config['rule_path'] . $json));

        foreach ($content as $k => $item) {
            if (is_string($k)) {
                $res[$k] = self::fixRegex($item);
            } elseif (isset($item[3])) {
                if ($item[0]) $res[$item[2]] = self::fixRegex($item[1]);
            }
        }

        return $res;

    }

    /**
     * 修正正则表达式
     * @param $regex
     * @return array|string|string[]|null
     */
    private static function fixRegex($regex)
    {

        return preg_replace_callback('/([\\\\]?\/)/', function ($item) {
            return $item[1] === '/' ? '\/' : '\/';
        }, $regex);
    }

    static function getDenyIps(): array
    {
        if (!self::$instance) self::init();
        return self::$deny_ips;
    }

    static function setDenyIps($ips = [])
    {
        self::$deny_ips = $ips;
    }

    static function init($config = [])
    {
        if (!self::$instance) {
            $config && self::$config = array_merge(self::$config, $config);
            if (!is_dir(self::$config['log_path'])) mkdir(self::$config['log_path'], 755, true);
            self::$instance = new self();
            self::$is_cli = PHP_SAPI === 'cli';
            if (!is_file(self::$config['rule_path'] . 'deny_ips.json')) {
                touch(self::$config['rule_path'] . 'deny_ips.json');
            }
            self::$deny_ips = json_decode(self::$config['rule_path'] . 'deny_ips.json', true) ? json_decode(self::$config['rule_path'] . 'deny_ips.json', true) : [];
        }
    }

    /**
     * saveDenyIps
     * @param $ips
     */
    static function saveDenyIps($ips)
    {

        $ips && is_array($ips) && file_put_contents(self::$config['rule_path'] . 'deny_ips.json', json_encode($ips, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 监听请求
     * @param $ip
     * @param mixed $urlOrData
     * @param array $data
     * @param string $remark
     * @return bool
     */
    static function check($ip, $urlOrData, array $data = [], string $remark = ''): bool
    {

        if (!self::$instance) {
            self::init();
        }

        if (!self::$config['enable']) {
            return false;
        }

        if (in_array($ip, self::$deny_ips)) {

            if (!self::$handle && !self::$is_cli) {
                die(self::$config['deny_message']);
            }

            return false;
        }

        $deny = false;
        $data = is_array($urlOrData) ? $urlOrData : $data;
        $url = '';
        //url地址和参数过滤
        if (is_string($urlOrData) && self::$rules['url']) {
            $url = $urlOrData;
            $rules = array_merge(self::$rules['url'], self::$rules['args']);
            foreach ($rules as $name => $regex) {
                //var_dump($name,"/{$regex}/i",$url,preg_match("/{$regex}/i",$url));
                if (preg_match("/$regex/i", $url) || preg_match("/$regex/i", urldecode($url))) {
                    self::log($url, $ip, 'URL&GET HIT', $regex, $name, $data, $remark);
                    $deny = true;
                    break;
                }
            }
        }

        if ($data) {
            $remark = is_string($data) ? $data : $remark;
            $rules = self::$rules['post'];
            $type = 'DATA HIT';

            if (is_array($data)) {

                $values = self::arr2str($data);

                foreach ($rules as $name => $regex) {
                    if (preg_match("/$regex/i", $values) || preg_match("/$regex/i", htmlspecialchars_decode($values))) {
                        self::log($url, $ip, $type, $regex, $name, $data, $remark);
                        $deny = true;
                        break;
                    }
                }
            }
        }
        //var_dump($deny,self::$handle , self::$is_cli);
        if (!self::$handle && !self::$is_cli && $deny) {
            die(self::$config['deny_message']);
        } else if (is_callable(self::$handle)) {
            call_user_func(self::$handle, $ip);
        }

        return $deny;

    }

    /**
     * log
     * @param        $url
     * @param $ip
     * @param        $type
     * @param        $rule
     * @param string $name
     * @param array $data
     * @param string $remark
     */
    private static function log($url, $ip, $type, $rule, string $name = '', array $data = [], string $remark = '')
    {
        $log = "TIME:" . date('Y-m-d H:i:s', time()) . ";TYPE:$type;RULE:$rule;IP:$ip;";
        $url && $log .= "URL:$url;";
        $name && $log .= "NAME:$name;";
        $remark && $log .= "REMARK:$remark;";
        $data && $log = $log . PHP_EOL . 'CHECK_DATA:' . json_encode($data, JSON_UNESCAPED_UNICODE);
        $log .= PHP_EOL . PHP_EOL;
        self::logOutput($log);
    }

    /**
     * 日志记录到文件
     * @param $str
     */
    private static function logOutput($str)
    {
        //数据类型检测
        $filename = self::$config['log_path'] . DIRECTORY_SEPARATOR . date("Y-m-d") . ".log";
        file_put_contents($filename, $str, FILE_APPEND);
    }

    private static function arr2str($data): string
    {
        $result = '';
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $result .= self::arr2str($v);
                } else {
                    $result .= $k . '=' . $v . ' ';
                }
            }
        } else {
            $result .= $data;
        }
        return $result;
    }
}