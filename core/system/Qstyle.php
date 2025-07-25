<?php

namespace system;
 

/*
# @copyright: 分享工作室 Yuan 2020 12 24
# @filename; Qstyle.class.php
# @version: Qstyle 8.0.0;
*/

class Qstyle
{
    public $templates_dir = array(ROOT_PATH . 'template/');       //模板路径,支持数组叠代多层目录,最后面的优先搜索;
    public $templates_cache = RUNTIME_PATH . 'tpl/';            //缓存模板路径;
    public $templates_postfix = '.html';                        //模板后缀;
    public $templates_caching = '.php';                            //缓存后缀;
    public $templates_var = 'All';                            //变量获取模式, All,ASSIGN;
    public $templates_auto = true;                            //自动更新模板;
    public $templates_new = false;                            //设置当次更新, 系统更新可强制配置为true;
    public $templates_space = false;                            //清除无意义字符
    public $templates_ankey = '';                                // 加密模板文件名,避免被猜测到.
    public $templates_isdebug = false;
    public $templates_replace = array();                        // 全局替块,区分大小写.
    public $cssname = '';

    //结果集,请不要修改以下内容;
    protected $templates_lang = array();          // 语言数组.
    protected $templates_autofile = array();      // 自动匹配文件数组.
    protected $templates_file = array();          //模板文件
    protected $templates_cache_file = array();    //缓存文件;
    protected $templates_name = null;             //标识名
    protected $templates_message = null;          //html内容;
    protected $templates_update = 0;              //更新次数
    protected $templates_assign = array();        //用户用smarty模式;
    protected $templates_static_assign = array(); // 静态变量数组. 用于css.
    protected $templates_debug = array();         //错误信息;
    protected $templates_blockreplace = array();      // block替换数组.
    protected $templates_css_assign = array();
    protected $templates_viewcount = 0;          // 视图次数.
    protected $templates_writecount = 0;         // 写入次数
    protected $templates_host = '';

    const _STATIC = 'STATIC';
    const _LISTTPL = 'LISTTPL';

    public function __construct()
    {
        $dirags = func_get_args();
        $this->templates_dir = (array)$this->templates_dir;
        foreach ($this->templates_dir as $key => $val) {
            $this->templates_dir[$val] = $val;
            unset($this->templates_dir[$key]);
        }

        if (isset($dirags[0]) && is_array($dirags[0])) {
            $dirags = $dirags[0];
            foreach ($dirags as $val) {
                $this->set_templates_path($val);
            }
        }

        $this->templates_host = md5($this->preg__urlhost());
        return $this;
    }

    public  function release()
    {
        // 释放变量
        $this->templates_assign = array();
        $this->templates_static_assign = array();
        $this->templates_css_assign = array();
        $this->templates_blockreplace = array();
        $this->templates_debug = array();

        // 重置计数器
        $this->templates_update = 0;
        $this->templates_viewcount = 0;
        $this->templates_writecount = 0;
    }

    //公共方法: 文件名, 是否返回缓存文件.
    public function display($PHPnew_file_name, $returnpath = false)
    {
        static $once = 0;

        if ($once === 0) {
            $this->templates_postfix = '.' . ltrim($this->templates_postfix, '.');

            if (!$this->templates_cache || is_dir($this->templates_cache) == false) {
                @mkdir($this->templates_cache, 0777, true);
            }

            if ($this->templates_isdebug) {
                $tplnotice = $this->templates_dir ? '板录已经被指定:' . implode(', ', $this->templates_dir) . ' (验证存在)' : '未指模板目录, 系统将从自目录中寻找模板';
                $this->preg__debug($tplnotice);
                $autodir = $this->set_auto_path(self::_STATIC);
                $this->preg__debug('静态文件匹配自动目录: ' . implode(', ', $autodir) . ' (验证存在)');
            }
            $once = 1;
        }

        $this->templates_viewcount += 1;
        $this->preg__debug($this->templates_viewcount . ' 次模板调用开始.....', E_NOTICE);
        if (isset($this->templates_debug[$PHPnew_file_name]) === true || !$PHPnew_file_name) {
            $this->preg__debug('参数为空 或者 重复模板调用:' . var_export($PHPnew_file_name, true) . ' 函数停止前进', E_WARNING);
            return false;
        }

        // 支持字符串解析
        if (strpos($PHPnew_file_name, "\n") !== false || strpos($PHPnew_file_name, "{") !== false || strpos($PHPnew_file_name, "<?php") !== false) {
            $this->templates_name = str_shuffle(md5($PHPnew_file_name) . microtime(true));
            $this->templates_message = $PHPnew_file_name;
            $phpnew_phpcode_log_phpcode = $this->__parse_html($this->templates_message);
            // 一直在释放变量.

            if ($returnpath === false) {
                unset($this->templates_message);
                $this->__parse_var(true);
                extract($this->templates_assign);
                @eval('?>' . $phpnew_phpcode_log_phpcode);
            } else {
                return $phpnew_phpcode_log_phpcode;
            }
            return true;
        } else {
            strpos($PHPnew_file_name, $this->templates_postfix) === false && $PHPnew_file_name .= $this->templates_postfix;
            $this->templates_name = $PHPnew_file_name;
            $tplcache = $this->__get_path($PHPnew_file_name);
            $true_check = $this->__check_update($tplcache);
        }

        $this->templates_cache_file[$PHPnew_file_name] = $tplcache['cache'] ?? '';
        $this->templates_file[$PHPnew_file_name] = $tplcache['tpl'] ?? '';
        $this->templates_debug[$PHPnew_file_name] = array();

        // 支持对模板block独立调用
        if (!is_bool($returnpath)) {
            $this->parse_tpl_block($this->templates_file[$PHPnew_file_name], $returnpath);
            throw new \system\JumpException('jump_exit');
        }

        $htmlname = basename($PHPnew_file_name);
        $PHPnew_path = false;

        if ($true_check === true) {
            if (isset($tplcache['cache']) && $tplcache['cache'])
                $PHPnew_path = $this->templates_cache_file[$PHPnew_file_name];
        } else {
            if (!$this->templates_file[$PHPnew_file_name] || !$this->templates_message = $this->preg__file($this->templates_file[$PHPnew_file_name])) {
                throw new \Exception('模板文件' . $PHPnew_file_name . ' 读取失败' . '当前匹配目录:' . implode(',', $this->templates_dir));
            }

            if ($this->templates_message) {
                $this->templates_message = $this->__parse_html($this->templates_message);
                $PHPnew_path = $this->templates_cache_file[$PHPnew_file_name];

                if (stripos($this->templates_message, '[qstyle debug]') !== false || stripos($this->templates_message, '{qstyle debug}') !== false) {
                    echo highlight_string($this->templates_message);
                    throw new \system\JumpException('jump_exit');
                }

                if ($this->templates_message && !$this->preg__file($PHPnew_path, $this->templates_message, true)) {
                    $this->preg__debug('模板文件无法写入: ' . $htmlname . ' | ' . $PHPnew_path);
                }
                $this->templates_message = null;
                $this->templates_update += 1;
            }
        }

        unset($tplcache, $PHPnew_file_name);
        if (IS_CLI || ($this->templates_viewcount === 1 && $returnpath === false && $PHPnew_path)) {
            $this->__parse_var();
            $this->preg__debug("第" . $this->templates_viewcount . "次输出: " . $htmlname . ' & ' . $PHPnew_path);
            include $PHPnew_path;
        } else {
            $this->__parse_var();
            if ($returnpath !== false) {
                $this->preg__debug("第" . $this->templates_viewcount . "次强制返回路径: " . $htmlname . ' & ' . $PHPnew_path);
            } else if (!$PHPnew_path) {
                $this->preg__debug("第" . $this->templates_viewcount . "次错误的模板: " . $htmlname);
            } else {
                $this->preg__debug("第" . $this->templates_viewcount . "次返回路径: " . $htmlname . ' & ' . $PHPnew_path);
            }
        }

        return $PHPnew_path;
    }

    protected function parse_tpl_block($path, $blockname)
    {
        $data = $this->preg__file($path);
        $data = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $data);
        if (preg_match("/\{block\s+{$blockname}\}(.*?)\{\/block\}/is", $data, $Reg)) {
            $data = $Reg[1];
        } else {
            $data = null;
        }

        if ($data) {
            $this->templates_name = str_shuffle(md5($path) . microtime(true));
            $this->templates_message = $data;
            $phpnew_phpcode_log_phpcode = $this->__parse_html($data);
            // 一直在释放变量.
            if ($phpnew_phpcode_log_phpcode) {
                unset($data);
                $this->__parse_var(true);
                extract($this->templates_assign);
                @eval('?>' . $phpnew_phpcode_log_phpcode);
            } else {
                echo (htmlspecialchars($data));
            }
        } else {
            echo '无法找到的block块:' . $blockname;
        }
    }

    public function load()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'display'), $args);
    }

    //公共方法: 用户用强制性变量赋值;
    public function assign($phpnew_var, $phpnew_value = null)
    {
        if (!$phpnew_var) return false;
        if ($phpnew_var === true)
            return $this->templates_assign;
        $i = 0;
        if ($phpnew_value === null && is_array($phpnew_var) === true) {
            foreach ($phpnew_var as $php_key => $php_val) {
                $this->templates_assign[$php_key] = $php_val;
                $i++;
            }
        } else {
            $this->templates_assign[$phpnew_var] = $phpnew_value;
            $i++;
        }
        return $this->templates_assign;
    }

    public function set_templates_type($parema = '变量模式[All,ASSIGN]')
    {
        if ($parema !== true) {
            $this->templates_var = $parema;
        }
        return $this->templates_var;
    }

    public function set_templates_suffix($parema = '', $paremb = '')
    {
        if ($parema) {
            $this->templates_postfix = $parema;
        }

        if ($paremb != '')
            $this->templates_caching = $paremb;

        return array('templates_postfix' => $this->templates_postfix, 'templates_caching' => $this->templates_caching);
    }

    public function set_templates_auto($parem = '设置自动更新[bool]')
    {
        $this->templates_auto = (bool)$parem;
        return $this->templates_auto;
    }

    public function set_templates_space($parem = '清除多余空白[bool]')
    {
        $this->templates_space = (bool)$parem;
        return $this->templates_space;
    }

    public function set_templates_isdebug($parem = '启用调试[bool]')
    {
        $this->templates_isdebug = (bool)$parem;
        return $this->templates_isdebug;
    }

    public function set_templates_oncenew($parem = '当次更新[bool]')
    {
        $this->templates_new = (bool)$parem;
        return $this->templates_new;
    }

    public function set_templates_ankey($parem = '安全码')
    {
        if ($parem !== true) {
            $this->templates_ankey = $parem;
        }
        return $this->templates_ankey;
    }

    public function set_templates_path($path = '模板路径')
    {
        if (!$path) return false;
        if ($path === true)
            return $this->templates_dir;

        $path = $this->__exp_path($path);

        if (!isset($this->templates_dir[$path]) && is_dir($path) === true) {
            $this->templates_dir[$path] = $path;
        } else {
            $this->preg__debug('set_templates_path 模板目录不存在, 自动忽略:' . htmlspecialchars($path));
        }
        return $this->templates_dir;
    }

    public function set_templates_replace($phpnew_var = '关键值,替换值', $phpnew_value = null)
    {
        if ($phpnew_var === true)
            return $this->templates_replace;

        $i = 0;
        if ($phpnew_value === null && is_array($phpnew_var) === true) {
            foreach ($phpnew_var as $php_key => $php_val) {
                $this->templates_replace[$php_key] = $php_val;
                $i++;
            }
        } else {
            $this->templates_replace[$phpnew_var] = $phpnew_value;
            $i++;
        }
        return $this->templates_replace;
    }

    public function set_cache_path($dir = '缓存目录路径')
    {
        if ($dir !== true && is_dir($dir)) {
            $this->templates_cache = $dir;
        }
        return $this->templates_cache;
    }

    //公共方法: 定义静态变量, 主要用于css, js.
    public function set_static_assign($var1 = null, $var2 = null)
    {
        if (!$var1) return false;
        if ($var1 === true)
            return $this->templates_static_assign;

        if ($var2 === null && is_array($var1) === true) {
            foreach ($var1 as $key => $var) {
                $this->templates_static_assign[$key] = $var;
            }
        } else {
            $this->templates_static_assign[$var1] = $var2;
        }
        return $this->templates_static_assign;
    }

    //公共方法: 设置语言数组, 模板中就可以用{lang str}
    public function set_language($var1 = null, $var2 = null)
    {
        if (!$var1) return false;
        if ($var1 === true)
            return $this->templates_lang;

        if ($var2 === null && is_array($var1) === true) {
            foreach ($var1 as $key => $var) {
                $this->templates_lang[$key] = $var;
            }
        } else {
            $this->templates_lang[$var1] = $var2;
        }
        return $this->templates_lang;
    }

    //公共方法: 设置自动匹配的路径, 默认先不工, 等有此语法再读取目录.
    public function set_auto_path($set_path = '自动搜目录路径')
    {
        if (in_array($set_path, array(self::_STATIC, self::_LISTTPL))) {
            if ($set_path === self::_STATIC) {
                return array_reverse($this->templates_autofile);
            } else {
                return array_reverse($this->templates_dir);
            }
        } else if (strpos($set_path, '/') !== false) {
            $set_path = $this->__exp_path($set_path);
            if (!isset($this->templates_autofile[$set_path]) && is_dir($set_path) === true) {
                $this->templates_autofile[$set_path] = $set_path;
            } else {
                $this->preg__debug("set_auto_path 设置自动搜索目录失败 , {$set_path} 目录不存在!", true);
            }
        }
        return $this->templates_autofile;
    }

    //私有方法: 定位域名, 以此来影响部分文件.
    protected function preg__urlhost()
    {
        $server = g('SERVER');
        return '//' . $server['HTTP_HOST'] . dirname($server['REQUEST_URI']);
    }

    protected function __exp_path($path)
    {
        return trim(str_replace(["//"], ["/"], $path . '/'));
    }

    protected function __exp_file($filepath)
    {
        $filepath = trim($filepath);
        return ltrim(strtr($filepath, array('\\' => '/', '\\\\' => '/', '//' => '/')), './');
    }

    //方: 法有自匹配功时, 此方法会被调用.
    protected function __real_alldir($dir = array(), $filename = '')
    {
        if (!$dir)
            return array();
        $dirlist = array();
        $paths = false;

        foreach ($dir as $key => $val) {
            $paths = $val . $filename;
            if (!is_file($paths)) {
                $paths = false;
                $temp = array();
                $temp = @glob($val . '*', GLOB_ONLYDIR); // 4.3.3 GLOB_ONLYDIR  在 Windows 或者其它不使用 GNU C 库的系统上开始可用
                foreach ($temp as $vals) {
                    $vals = $this->__exp_path($vals);
                    $dirlist[$vals] = $vals;
                }
            } else {
                unset($dirlist);
                break;
            }
        }

        if ($paths) {
            return $paths;
        } else {
            return $this->__real_alldir($dirlist, $filename);
        }
    }

    // 内部方法: 检查是否应该更新, 参数:当前配置数组.
    protected function __check_update($html_array)
    {
        if (is_dir($this->templates_cache) === false)
            $this->preg__debug('缓存目录不存在: ' . $this->templates_cache, E_WARNING);
        if (empty($html_array['tpl']) === true)
            $this->preg__debug('模板文件不存在: ' . $this->templates_name . '当前匹配目录:' . implode(',', $this->templates_dir), E_WARNING);
        if ($this->templates_new === true) {
            $this->preg__debug('templates_new 自动更新已经开启!');
            return false;
        }

        if (isset($html_array['cache']) && (!$html_array['cache'] || is_file($html_array['cache']) === false)) {
            $this->preg__debug(var_export($html_array['cache'], true) . '缓存文件不存在, 解析更新已开启!');
            return false;
        }
        return true;
    }

    // 内部方法: 取得路径信息.
    protected function __get_path($htmlfile)
    {
        $rename = false;
        if (stripos($htmlfile, '/') !== false) {
            if (is_file($htmlfile) === false) {
                if (strpos($htmlfile, $this->templates_postfix) === false) {
                    $htmlfile .= $this->templates_postfix;
                }
            } else {
                $rename = $htmlfile;
            }
        }

        if ($rename === false) {
            $rename = $this->__search_tpl($htmlfile);
        }

        if ($rename) {
            $this->preg__debug('模板文件自动搜索到路径: ' . $rename);
        } else {
            throw new \Exception('模板文件不存在:' . $htmlfile . '当前匹配目录:' . implode(',', $this->templates_dir));
        }

        $htmlfile = $rename;
        $retruans = array();
        if ($htmlfile !== false) {
            $md5 = $this->templates_auto === true ? md5_file($htmlfile) : md5($htmlfile . $this->templates_ankey . $this->templates_host);
            $temname = trim($this->templates_name, './\\');
            $temname = strtr($temname, array($this->templates_postfix => '', '/' => '_', '.' => '', ' ' => ''));
            $retruans = array(
                'tpl' => $htmlfile,
                'cache' => $this->templates_cache . $temname . '_' . $md5 . $this->templates_caching
            );
        }
        return $retruans;
    }

    protected function __search_tpl($htmlfile)
    {
        $dir = $this->set_auto_path(self::_LISTTPL);
        $htmlfile = $this->__exp_file($htmlfile);
        $paths = false;

        if (stripos($htmlfile, '__') === 0) {
            $htmlfile = trim($htmlfile, '_');
            $paths = $this->__real_alldir($dir, $htmlfile);
        } else {
            // 默认只搜索一层, 跟静态文件不一样.
            foreach ($dir as $val) {
                if (is_file($val . $htmlfile) === true) {
                    $paths = $val . $htmlfile;
                    break;
                }
            }

            if (!$paths) {
                $htmlfile = basename($htmlfile);
                // 默认只搜索一层, 跟静态文件不一样.
                foreach ($dir as $val) {
                    if (is_file($val . $htmlfile) === true) {
                        $paths = $val . $htmlfile;
                        break;
                    }
                }
            }
        }
        return $paths;
    }

    // 内部方法: 取得全局变量并且赋予模板.
    protected function __parse_var($isrun = false)
    {
        static $savevar = 0;

        if ($isrun === true)
            $savevar = 0;

        if ($savevar === 0 && $this->templates_var !== 'ASSIGN') {
            $allvar = array_diff_key($GLOBALS, array('GLOBALS' => 0, '_ENV' => 0, 'HTTP_ENV_VARS' => 0, 'ALLUSERSPROFILE' => 0, 'CommonProgramFiles' => 0, 'COMPUTERNAME' => 0, 'ComSpec' => 0, 'FP_NO_HOST_CHECK' => 0, 'NUMBER_OF_PROCESSORS' => 0, 'OS' => 0, 'Path' => 0, 'PATHEXT' => 0, 'PROCESSOR_ARCHITECTURE' => 0, 'PROCESSOR_IDENTIFIER' => 0, 'PROCESSOR_LEVEL' => 0, 'PROCESSOR_REVISION' => 0, 'ProgramFiles' => 0, 'SystemDrive' => 0, 'SystemRoot' => 0, 'TEMP' => 0, 'TMP' => 0, 'USERPROFILE' => 0, 'VBOX_INSTALL_PATH' => 0, 'windir' => 0, 'AP_PARENT_PID' => 0, 'uchome_loginuser' => 0, 'supe_cookietime' => 0, 'supe_auth' => 0, 'Mwp6_lastvisit' => 0, 'Mwp6_home_readfeed' => 0, 'Mwp6_smile' => 0, 'Mwp6_onlineindex' => 0, 'Mwp6_sid' => 0, 'Mwp6_lastact' => 0, 'PHPSESSID' => 0, 'HTTP_ACCEPT' => 0, 'HTTP_REFERER' => 0, 'HTTP_ACCEPT_LANGUAGE' => 0, 'HTTP_USER_AGENT' => 0, 'HTTP_ACCEPT_ENCODING' => 0, 'HTTP_HOST' => 0, 'HTTP_CONNECTION' => 0, 'HTTP_COOKIE' => 0, 'PATH' => 0, 'COMSPEC' => 0, 'WINDIR' => 0, 'SERVER_SIGNATURE' => 0, 'SERVER_SOFTWARE' => 0, 'SERVER_NAME' => 0, 'SERVER_ADDR' => 0, 'SERVER_PORT' => 0, 'REMOTE_ADDR' => 0, 'DOCUMENT_ROOT' => 0, 'SERVER_ADMIN' => 0, 'SCRIPT_FILENAME' => 0, 'REMOTE_PORT' => 0, 'GATEWAY_INTERFACE' => 0, 'SERVER_PROTOCOL' => 0, 'REQUEST_METHOD' => 0, 'QUERY_STRING' => 0, 'REQUEST_URI' => 0, 'SCRIPT_NAME' => 0, 'PHP_SELF' => 0, 'REQUEST_TIME' => 0, 'argv' => 0, 'argc' => 0, '_POST' => 0, 'HTTP_POST_VARS' => 0, '_GET' => 0, 'HTTP_GET_VARS' => 0, '_COOKIE' => 0, 'HTTP_COOKIE_VARS' => 0, '_SERVER' => 0, 'HTTP_SERVER_VARS' => 0, '_FILES' => 0, 'HTTP_POST_FILES' => 0, '_REQUEST' => 0));
            foreach ($allvar as $key => $val) {
                $this->templates_assign[$key] = $val;
            }
            $savevar = 1;
            unset($allvar);
        }
    }

    // 内部方法: 读文件与写文件的公用法.
    protected function preg__file($path, $lock = 'rb', $cls = false)
    {
        $mode = $cls === true ? 'wb+' : 'rb';

        if ($cls === false && is_file($path) === false) return false;
        if (!@$fp = fopen($path, $mode))
            return false;

        $ints = 0;
        if ($cls === true) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (!$ints = fwrite($fp, $lock))
                    return 0;
                $this->preg__debug('文件写入成功: ' . $path);
                $this->templates_writecount++;
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        } else {
            $ints = '';
            if (flock($fp, LOCK_SH | LOCK_NB)) {
                while (!feof($fp)) {
                    $ints .= fread($fp, 4096);
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
        return $ints;
    }

    // 内部方法: css,js静态文件解析方法.
    protected function __preg_source_parse($template)
    {
        static $savefile = array();
        if (isset($savefile[$template]))
            return $savefile[$template];

        if (!$template || is_file($template) === false)
            return $template;

        $this->cssname = $template;
        $static_file = $template;

        $template = $this->preg__file($static_file);

        # 增加todo bug标注支持.
        $template = preg_replace_callback("/(?:#|\/\/)(\s*)(?:TODO|BUG|INFO):(.*?)([^\n\r]*)/is", array($this, 'preg__todobug'), $template, -1, $regint);

        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this, 'preg__const'), $template, -1, $regintb);

        //替换直接变量输出
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $template);
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", '<?=$1?>', $template);
        $template = preg_replace_callback("/$varRegexp2/s", array($this, 'preg__var'), $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s", array($this, 'preg__var'), $template);

        $template = preg_replace_callback("/<\?\=$varRegexp\?\>/s", array($this, 'preg_cssjs_var'), $template);
        $template = preg_replace_callback("/\{$const_regexp\}/s", array($this, 'preg_cssjs_var'), $template);

        $template = preg_replace_callback("/\{__([^\s]*?\.[^\s]*?)\}/s", array($this, 'preg_static_autofile'), $template);
        # 处理base加密的内容
        $template = preg_replace_callback('/\{\#(.*)\}/isU', array($this, 'preg__parse_database'), $template);
        $template = strtr($template, array('Qstyle~~<~~' => '{', 'Qstyle~~>~~' => '}', 'Qstyle~~<<~~' => '$'));
        $tem = explode('.', $static_file);
        $postfix = end($tem);
        $caename_file = $this->templates_cache . $postfix . '_' . md5(basename($static_file) . $this->templates_ankey . $this->templates_host) . '.' . $postfix;
        $template = "/* {$static_file} */\n" . $template;
        $this->preg__file($caename_file, $template, true);
        $savefile[$static_file] = $caename_file;
        return $caename_file;
    }

    // 内部方法: css,js静态文件路径计算方法, 跟preg__autofile有小小区别.
    protected function preg_static_autofile($math)
    {
        static $reals = '';
        $args = func_get_args();
        if ($args)
            $file = call_user_func_array(array($this, 'preg__autofile'), $args);
        if (!$reals) {
            # 计算回调多少层.
            $tem = explode('/', rtrim($this->templates_cache, '/'));
            foreach ($tem as $key => $val) {
                if ($val !== '.' && $val) {
                    if ($key !== 0) {
                        $tem[$key] = '..';
                    } else {
                        if ($val !== '..')
                            $tem[$key] = '.';
                    }
                } else {
                    if (!$val)
                        unset($tem[$key]);
                }
            }
            $reals = implode('/', $tem) . '/';
        }

        if ($file && is_file($file) === true) {
            if (strpos($this->cssname, '.css') !== false) {
                return $reals . ltrim($file, './');
            } else {
                return $file;
            }
        }
    }

    // 内部方法: css,js静态文件变量计算方法.
    protected function preg_cssjs_var($math)
    {
        if (is_string($math) === false)
            $math = $math[1];
        $redata = $math;
        if ($math && strpos($math, '$') !== false) {
            $math = strtr($math, array('"' => '', "'" => ''));
            # 直接返回变量的值.
            $math = strtr(ltrim($math, '$'), array('][' => '.'));
            $math = strtr(ltrim($math, '$'), array(']' => '', '[' => '.'));

            $tem = explode('.', $math);
            if (!$this->templates_css_assign) {
                $this->__parse_var();
                $this->templates_css_assign = $this->templates_assign;
            }

            $redata = $this->templates_css_assign;
            foreach ($tem as $val) {
                if (isset($redata[$val]))
                    $redata = $redata[$val];
            }
            if (!is_string($redata))
                $redata = '';
        } else {
            #常量替换
            $redata = '';
            $tem = get_defined_constants(true);
            $tem = $tem['user'];
            if (isset($tem[$math]))
                $redata = $tem[$math];
        }
        return $redata;
    }

    // 内部方法: html代码自动匹配路径方法
    protected function preg__autofile($math)
    {
        if (is_string($math) === false) {
            $mathfile = $math[1];
        } else {
            $mathfile = $math;
        }

        // 带变量的?
        if (strpos($mathfile, '$') !== false || substr_count($mathfile, '{') > 0) {
            //替换直接变量输出
            $template = $mathfile;
            unset($mathfile);
            $template = $this->__parse_htmlvar($template);
            if (strpos($template, '<?=') !== false)
                $template = strtr($template, array('<?=' => '{', '?>' => '}'));
            $returns = $this->preg__base('<?php echo $this->preg__autofile(' . "\"$template\"" . ');?>');
        } else {
            $returns = $this->__real_alldir($this->set_auto_path(self::_STATIC), $mathfile); // 文件搜索,算法不一样了.
            if (!$returns)
                $returns = $mathfile;
        }
        return $returns;
    }

    // 处理变量与常量.
    protected function __parse_htmlvar($template)
    {
        if (!$template)
            return '';

        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this, 'preg__const'), $template, -1, $regintb);

        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", '<?=$1?>', $template);
        $template = preg_replace_callback("/$varRegexp2/s", array($this, 'preg__var'), $template);
        $template = preg_replace_callback("/$varRegexp/s", array($this, 'preg__var'), $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s", array(&$this, 'preg__var'), $template);
        $template = preg_replace("/\{$const_regexp\}/sU", "<?=$1?>", $template);

        $template = strtr($template, array('Qstyle~~<~~' => '{', 'Qstyle~~>~~' => '}', 'Qstyle~~<<~~' => '$'));
        return $template;
    }

    protected function preg__binary($math)
    {
        if (!$math) return '';

        if (is_string($math)) {
            $parts = explode('|', $math);
        } else {
            $parts = explode('|', $math[1]);
        }

        // 处理变量部分(支持点语法)
        $var = trim($parts[0]);
        if (strpos($var, '.') !== false) {
            $var = $this->__parse_dot_notation($var);
        }

        // 处理default值
        $default = isset($parts[1]) ? trim($parts[1]) : '';
        if (strpos($default, 'default=') === 0) {
            $default = substr($default, 8);
            // 如果default值是字符串,需要处理引号
            if (!is_numeric($default) && strpos($default, '$') !== 0) {
                $default = "'" . trim($default, "'\"") . "'";
            }
            return "<?php echo isset($var) ? $var : $default; ?>";
        }

        // 其他二元运算保持不变
        $true_value = isset($parts[1]) ? trim($parts[1]) : '';
        $false_value = isset($parts[2]) ? trim($parts[2]) : '';

        if (!$true_value) {
            $true_value = $var;
        }

        if ($true_value && strpos($true_value, '$') !== 0) {
            $true_value = "'" . addslashes($true_value) . "'";
        }

        if ($false_value && strpos($false_value, '$') !== 0) {
            $false_value = "'" . addslashes($false_value) . "'";
        }

        return "<?php echo isset($var) && $var ? $true_value : $false_value; ?>";
    }

    // TODO: 核心代码开始
    //内部函数: 模板语法处理替换
    protected function __parse_html($template)
    {   

        
        // 在preg__parse方法中添加对{php}标签的处理
        $template = preg_replace_callback(
            '/\{php\}(.*?)\{\/php\}/s',
            function($matches) {
                return "<?php " . trim($matches[1]) . " ?>";
            },
            $template
        );
        
        // 首先处理switch结构
        $template = preg_replace_callback(
            "/\{switch\s+(.+?)\}([\s\S]*?)\{\/switch\}/is",
            function ($matches) {
                return $this->processSwitchStatement($matches[1], $matches[2], $this->templates_isdebug);
            },
            $template
        );



        // 特别处理 date 函数的情况，使用更精确的匹配模式
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_\[\]\'\".]+)(?:\.[a-zA-Z0-9_]+)?\s*\?\s*date\([\'\"](Y\-m\-d\s+H\s*:\s*i(?:\s*:\s*s)?)[\'\"]\s*,\s*(\$[a-zA-Z0-9_\[\]\'\".]+(?:\.[a-zA-Z0-9_]+)?)\)\s*:\s*[\'\"]([^\}]+)[\'\"]\}/s',
            function ($matches) {
                return $this->__parse_date_expression(
                    $matches[1],  // condition_var
                    $matches[3],  // timestamp_var
                    $matches[2],  // format
                    $matches[4]   // default_value
                );
            },
            $template
        );

        // 处理简单形式的日期表达式
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_\[\]\'\"]+)\s*\?\s*date\([\'\"](Y\-m\-d\s+H:i:s)[\'\"]\s*,\s*(\$[a-zA-Z0-9_\[\]\'\"]+)\)\s*:\s*[\'\"]([^\}]+)[\'\"]\}/s',
            function ($matches) {
                return $this->__parse_date_expression(
                    $matches[1],  // condition_var
                    $matches[3],  // timestamp_var
                    $matches[2],  // format
                    $matches[4]   // default_value
                );
            },
            $template
        );

        // 处理点语法形式的日期表达式
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_\.]+)\.([a-zA-Z0-9_]+)\s*\?\s*date\([\'\"](.*?)[\'\"],\s*\$[a-zA-Z0-9_\.]+\.[a-zA-Z0-9_]+\)\s*:\s*[\'\"]([^\}]+)[\'\"]\}/',
            function ($matches) {
                $condition_var = $matches[1] . "['" . $matches[2] . "']";  // 转换点语法为数组访问
                return $this->__parse_date_expression(
                    $condition_var,
                    $condition_var,  // 在这种情况下，条件变量和时间戳变量相同
                    $matches[3],     // format
                    $matches[4]      // default_value
                );
            },
            $template
        );

        // 原有的解析逻辑继续...
        $this->preg__debug('模板解析开始... 内容共计: ' . strlen($template) . ' 字节');

        if ($this->templates_replace) {
            $template = strtr($template, $this->templates_replace);
            $this->preg__debug('解析模板细节: templates_replace 全局替换数据次数:' . count($this->templates_replace));
        }

        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $template);
        $template = preg_replace_callback("/\{html\s+(.+?)\}/s", array($this, 'preg__static'), $template);
        $template = str_ireplace(array('{loads', '{load'), array('{templatesub', '{template'), $template);

        $template = preg_replace_callback("/(?:#|\/\/)(\s*)(?:TODO|BUG|INFO):(.*?)([^\r\n]*)/is", array($this, 'preg__todobug'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: // TODO|BUG TODO,BUG 描述解析次数:' . ($regint));

        $template = preg_replace_callback("/\{templatesub\s+([^\s]+?)\}[\n\r\t]*/is", array($this, 'preg__contents'), $template, -1, $regints);
        $template = preg_replace_callback("/\{template\s+([^\s]+?)\}([\n\r\t]*)/is", array($this, 'preg__template'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: {load name} 解析次数:' . ($regint + $regints));
        $template = preg_replace_callback("/\{block\s+([^\s]*)\}(.*?)\{\/block\}([\n\r\t]*)/is", array($this, 'preg__stripblock'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: {block name} block块解析次数:' . ($regint));

        if ($this->templates_blockreplace) {
            $ri = 0;
            foreach ($this->templates_blockreplace as $keys => $vals) {
                $r2 = strtr($keys, array('{' => '{block '));
                if (strpos($template, $r2) !== false) {
                    $ri++;
                    $template = strtr($template, array($r2 => $vals));
                } else if (strpos($template, $keys) !== false) {
                    $ri++;
                    $template = strtr($template, array($keys => $vals));
                }
            }
            $this->preg__debug('解模板节: block 入块换次数:' . ($ri));
        }

        //处理自动搜索文件路径
        $template = preg_replace_callback("/\{__(.*)\}/sU", array($this, 'preg__autofile'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: {__name} 自动匹配路径解析次数:' . ($regint));

        //替换语言包/静态变量/php代码.
        $template = preg_replace_callback("/\{([\:\!]+)(.+?)\}([\n\r\t]*)/is", array($this, 'preg__evaltags'), $template, -1, $regint);

        $this->preg__debug('解析模板细节: {eval phpcode} eval运行php代码解析次数:' . ($regint));
        $template = preg_replace_callback("/\<\?php\s+(.+?)\?\>/is", array($this, 'preg__base'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: <?php code ?> 原生态php代码解析次数:' . ($regint));
        $template = preg_replace_callback("/\{lang\s+(.+?)\}/is", array($this, 'preg__language'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: {lang name} 语言包代码解析次数:' . ($regint));
        $template = str_replace("{LF}", '<?="\\n"?>', $template);

        // 二元判断
        $template = preg_replace_callback("/\{([\!]*\\$[^}\n]*\|[^\n]*)\}/isU", array($this, 'preg__binary'), $template, -1, $regint);
        $this->preg__debug('解析模板细节: {reg|1|0} 二元判断代码解析次数:' . ($regint));

        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this, 'preg__const'), $template, -1, $regintb);

        // 普通变量数组转化
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)([\.|\[][a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+[\]]*)*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

        // 直接使用回调处理所有变量
        $template = preg_replace_callback("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", array($this, 'preg__var'), $template);
        $template = preg_replace_callback("/$varRegexp2/s", array($this, 'preg__var'), $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s", array($this, 'preg__var'), $template);

        //替换特定函数
        $template = preg_replace_callback("/\{if\s+(.+?)\}/is", array($this, 'preg__if'), $template);

        $template = preg_replace_callback("/\{else[ ]*if\s+(.+?)\}/is", array($this, 'preg__ifelse'), $template);
        $template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/is", "<? } ?>", $template, -1, $regint);

        $template = preg_replace_callback("/\{foreach\s+(\S+)\s+(\S+)\}/is", array($this, 'preg__loopone'), $template, -1, $reginta);
        $template = preg_replace_callback("/\{foreach\s+(\S+)\s+(\S+)\s+(\S+)\}/is", array($this, 'preg__looptwo'), $template, -1, $regintb);
        $template = preg_replace("/\{\/foreach\}/is", "<? }} ?>", $template);

        $template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\}/is", array($this, 'preg__loopone'), $template, -1, $reginta);
        $template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/is", array($this, 'preg__looptwo'), $template, -1, $regintb);
        $template = preg_replace("/\{\/loop\}/is", "<? }} ?>", $template);
        $this->preg__debug('解析模板细节: {if else /if} if流程判断代码解析次数:' . ($regint));
        $this->preg__debug('解析模板细节: {loop all} 循环输出代码解析次数:' . ($reginta + $regintb));

        // 常量替换
        $template = preg_replace("/\{$const_regexp\}/sU", "<?=$1?>", $template, -1, $regint);
        $this->preg__debug('解析模板细节: {CONST} 常量代码解析次数:' . ($regint));

        //其他替换
        $template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/is", array($this, 'preg__transamp'), $template);
        $template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\".*?\>\s*\<\/script\>/is", array($this, 'preg__stripscriptamp'), $template);

        if ($this->templates_space === true) {
            $template = preg_replace(array('/\r\n/isU', '/<<<EOF/isU'), array('', "\r\n<<<EOF\r\n"), $template);
        }

        $template = strtr($template, array('<style>' => '<style type="text/css">', '<script>' => '<script type="text/javascript">'));

        $filename = isset($this->templates_file[$this->templates_name]) ? $this->templates_file[$this->templates_name] : '';
        $template = '<?php /* ' . $filename . ' */ if(is_object($this) === false){echo(\'Hacking!\');throw new \system\JumpException("jump_exit");}else if(!g(\'Qextract\') || IS_CLI){g(\'Qextract\',1);extract($this->templates_assign);}?>' . $template;

        $template = strtr($template, array('<?php' => '<?', '<?php echo' => '<?=', '?><?php' => ' '));
        $template = strtr($template, array('<?' => '<?php', '<?=' => '<?php echo '));

        # input 修复兼容
        if (stripos($template, '<input') !== false) {
            $template = preg_replace_callback('/<input.*type="([^"]*)".*\/>/isU', array($this, 'preg__input'), $template, -1, $regint);
            $this->preg__debug('解析模板细节: <input> 标签注入默认class次数:' . $regint);
        }

        # 处理base加密的内容
        $template = preg_replace_callback('/\{\#(.*)\}/isU', array($this, 'preg__parse_database'), $template);

        # 最终再释放所有的php代码.
        $template = preg_replace_callback('/\[qstylebase\](.*)\[\/qstylebase\]/isU', array($this, 'preg__debase'), $template);

        if ($this->templates_replace) {
            $template = strtr($template, $this->templates_replace);
            $this->preg__debug('解析模板细节: templates_replace 全局替换数据次数:' . count($this->templates_replace));
        }
        $template = strtr($template, array('Qstyle~~<~~' => '{', 'Qstyle~~>~~' => '}', 'Qstyle~~<<~~' => '$'));
        $this->preg__debug('模板解析结束... 内容共计: ' . strlen($template) . ' 字节');



        // 处理其他变量和达式
        $template = preg_replace_callback("/\\{(\\[\\!]*\\$[^}\\n]*\\|[^\\n]*)\\}/isU", array($this, 'preg__binary'), $template);


// 1. 修改处理等值比较的三元运算符
$template = preg_replace_callback(
    '/\{(\$[a-zA-Z0-9_\[\]\'\"\-\.]+)\s*(==|===|!=|!==|>=|<=|>|<)\s*([^\s\?\}]+)\s*\?\s*[\'\"]?([^\:]+?)[\'\"]?\s*:\s*[\'\"]?([^\}]+?)[\'\"]?\}/s',
    function ($matches) {
        $var = $matches[1];
        $operator = $matches[2];
        $compare_value = $matches[3];
        $true_value = trim($matches[4], "'\"");
        $false_value = trim($matches[5], "'\"");
        
        // 处理点语法
        if (strpos($var, '.') !== false) {
            $var = $this->__parse_dot_notation($var);
        }
        
        // 处理比较值：如果是变量引用，不要加引号
        if (strpos($compare_value, '$') === 0) {
            // 如果是变量，检查是否需要处理点语法
            if (strpos($compare_value, '.') !== false) {
                $compare_value = $this->__parse_dot_notation($compare_value);
            }
        } else if (!is_numeric($compare_value) && $compare_value !== 'true' && $compare_value !== 'false') {
            $compare_value = "'$compare_value'";
        }
        
        // 处理返回值：如果不是变量（不以$开头）且不是数字，则加上引号
        if (!is_numeric($true_value) && strpos($true_value, '$') !== 0) {
            $true_value = "'" . addslashes($true_value) . "'";
        }
        if (!is_numeric($false_value) && strpos($false_value, '$') !== 0) {
            $false_value = "'" . addslashes($false_value) . "'";
        }
        
        return "<?php echo isset($var) && $var $operator $compare_value ? $true_value : $false_value; ?>";
    },
    $template
);

        // 2. 处理带点语法的简单三元运算符
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)\s*\?\s*([^:]+?)\s*:\s*([^\}]+)\}/s',
            function ($matches) {
                return $this->__parse_ternary(
                    $matches[1],
                    trim($matches[2]),
                    trim($matches[3])
                );
            },
            $template
        );

        // 3. 处理数组访问形式的三元运算符
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_]+(?:\[[\'"][a-zA-Z0-9_]+[\'"]\])+)\s*\?\s*(\$[a-zA-Z0-9_\[\]\'\"\-]+|\d+|\'[^\']*\')\s*:\s*(\$[a-zA-Z0-9_\[\]\'\"\-]+|\d+|\'[^\']*\')\}/s',
            function ($matches) {
                return $this->__parse_ternary(
                    $matches[1],
                    $matches[2],
                    $matches[3]
                );
            },
            $template
        );

        // 4. 处理带函数调用的三元运算符
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_\.]+)\s*\?\s*([a-zA-Z0-9_]+\([^\)]*\))\s*:\s*[\'\"]?([^\}]+?)[\'\"]?\}/s',
            function ($matches) {
                return $this->__parse_ternary(
                    $matches[1],
                    $matches[2],
                    trim($matches[3])
                );
            },
            $template
        );

        // 5. 处理其他一般的三元运算符
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_\.]+)\s*\?\s*([^\:]+?)\s*\:\s*[\'\"]?([^\}]+?)[\'\"]?\}/s',
            function ($matches) {
                return $this->__parse_ternary(
                    $matches[1],
                    trim($matches[2]),
                    trim($matches[3])
                );
            },
            $template
        );




        // 在 __parse_html 方法中添加对空合并运算符的处理
        $template = preg_replace_callback(
            '/\{(\$[a-zA-Z0-9_]+(?:\[[^\]]+\])+)\s*\?\?\s*[\'\"]([^\}]*)[\'\"]\}/s',
            function ($matches) {
                $var = $matches[1];       // $option_values[$option.id][$version.id]
                $default = $matches[2];   // ''

                // 处理数组访问中的点语法
                if (strpos($var, '.') !== false) {
                    $var = preg_replace_callback('/\[[^\]]*\.[^\]]*\]/', function ($m) {
                        $inner = trim($m[0], '[]');
                        return '[' . $this->__parse_dot_notation($inner) . ']';
                    }, $var);
                }

                return "<?php echo $var ?? '$default'; ?>";
            },
            $template
        );





        // 1. 首先处理所有的点语法表达式
        $template = preg_replace_callback(
            '/\$[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+/',
            array($this, '__parse_dot_notation'),
            $template
        );








        return $template;
    }

    protected function preg__parse_database($math)
    {
        $fname = trim($math[1]);
        if (!$fname)
            return null;

        if (strpos($fname, '__') !== false) {
            // 有自动搜索过程.
            $fname = trim($fname, '_');
            $fname = $this->preg__autofile(array(0, $fname));
        } else {
            $fname = $this->__exp_file($fname);
        }

        if (is_file($fname)) {
            // 判断后缀
            $ext = strtolower(substr($fname, strrpos($fname, '.') + 1));
            $datastr = '';
            if (in_array($ext, array('jpg', 'jpeg'))) {
                $datastr = 'image/jpeg';
            } else if (in_array($ext, array('gif'))) {
                $datastr = 'image/gif';
            } else if (in_array($ext, array('png'))) {
                $datastr = 'image/png';
            } else if (in_array($ext, array('ico'))) {
                $datastr = 'image/x-icon';
            } else if (in_array($ext, array('js'))) {
                $datastr = 'text/javascript';
            } else if (in_array($ext, array('css'))) {
                $datastr = 'text/css';
            } else if (in_array($ext, array('html', 'htm'))) {
                $datastr = 'text/html';
            }

            if ($datastr) {
                return 'data:' . $datastr . ';base64,' . base64_encode($this->preg__file($fname));
            } else {
                return $fname;
            }
        } else {
            return $fname;
        }
    }

    protected function preg__parse_ahref($math)
    {
        $hrefdata = preg_replace('/&(?!amp;)/isU', '&amp;', $math[1]);
        return 'href="' . $hrefdata . '"';
    }

    protected function preg__static($math)
    {
        if (is_string($math) === false)
            $math = $math[1];
        if ($math) {
            $this->__parse_var();
            $varname = ltrim(trim($math), '$');
            $varname = $this->templates_assign[$varname];
            if (!$varname)
                $varname = $math[0];

            if (is_string($varname)) {
                return $varname;
            } else {
                return '';
            }
        }
    }

    protected function preg__evaltags($match)
    {
        $php = rtrim(trim($match[2]), ';');
        $lf = $match[3];
        $php = str_replace('\"', '"', $php);

        // 处理函数参数中的点语法
        if (strpos($php, '.') !== false) {
            preg_match_all('/\$[a-zA-Z0-9_]+\.[a-zA-Z0-9_\.]+/', $php, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $var) {
                    $parts = explode('.', $var);
                    $varName = array_shift($parts);
                    $replacement = $varName;
                    foreach ($parts as $part) {
                        $replacement .= "['$part']";
                    }
                    $php = str_replace($var, $replacement, $php);
                }
            }
        }

        if ($match[1] == ':') {
            return $this->preg__base("<?php echo $php;?>$lf");
        } else {
            return $this->preg__base("<?php $php;?>$lf");
        }
    }

    protected function preg__todobug($math)
    {
        if (strpos($math[1], "\n") !== false && strpos($math[3], "\n") !== false) {
            return "\n";
        }
        return ''; //默认todo, bug全部隐藏.
    }

    protected function preg__if($math)
    {
        // 处理条件表达式中的点语法
        $condition = $this->__parse_condition($math[1]);
        $expr = "<? if({$condition}){ ?>";
        return $this->preg__stripvtags($expr);
    }

    protected function preg__ifelse($math)
    {
        // 处理条件表达式中的点语法
        $condition = $this->__parse_condition($math[1]);
        $expr = "<? }else if({$condition}){ ?>";
        return $this->preg__stripvtags($expr);
    }

    // 新增方法：处理条件表达式
    protected function __parse_condition($condition)
    {
        // 处理点语法访问
        if (strpos($condition, '.') !== false) {
            // 匹配所有的变量引用（包含点语法）
            preg_match_all('/\$[a-zA-Z0-9_]+(?:\.[a-zA-Z0-9_]+)+/', $condition, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $var) {
                    $parts = explode('.', $var);
                    $varName = array_shift($parts);
                    $replacement = $varName;
                    foreach ($parts as $part) {
                        $replacement .= "['$part']";
                    }
                    $condition = str_replace($var, $replacement, $condition);
                }
            }
        }
        return $condition;
    }

    protected function preg__loopone($math)
    {
        $expr = "<? if(is_array({$math[1]})===true){foreach({$math[1]} as {$math[2]}){ ?>";
        return $this->preg__stripvtags($expr);
    }

    protected function preg__looptwo($math)
    {
        if (in_array($math[2], ['as', '=>'])) {
            $expr = "<? if(is_array({$math[1]})===true){foreach({$math[1]} as {$math[3]}){ ?>";
        } else {
            $expr = "<? if(is_array({$math[1]})===true){foreach({$math[1]} as {$math[2]} => {$math[3]}){ ?>";
        }
        return $this->preg__stripvtags($expr);
    }

    protected function preg__template($math)
    {
        $lf = $math[2];
        if (is_string($math) === false)
            $math = trim($math[1]);
        if ($math) {
            if (strpos($math, '$') !== false) {
                $math = $this->__parse_htmlvar($math);
                $math = strtr($math, array('<?=' => '', '?>' => ''));
                $retunrstr = '<?php require_once($this->load(' . $math . '));?>' . $lf;
            } else {
                $retunrstr = '<?php require_once($this->load(\'' . $math . '\'));?>' . $lf;
            }
            $this->preg__debug('解析模板细节: 引入文件: ' . $math);
            return $this->preg__base($retunrstr);
        } else {
            $this->preg__debug('解析模板细节: 无法解析的引入: ' . var_export($math[0], true));
        }
        return false;
    }

    protected function preg__language($math)
    {
        if (is_string($math) === false) {
            $math = $math[1];
            return $this->preg__base("<?php echo \$this->preg__language('$math'); ?>");
        } else {
            $varname = ltrim($math, '$');
            $returnstr = $varname;

            if ($this->templates_lang[$varname])
                $returnstr = $this->templates_lang[$varname];

            if (is_string($returnstr)) {
                return $returnstr;
            } else {
                return '';
            }
        }
    }

    protected function preg__const($math)
    {
        if (strpos($math[2], '$') !== false) {
            $math[2] = strtr($math[2], array('$' => 'Qstyle~~<<~~'));
        }

        if ($math[2]) {
            $returnstr = $math[1] . str_replace("\\\"", "\"", preg_replace_callback("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", function ($s) {
                if ($s[1]) {
                    if (preg_match('/[a-z]+/s', $s[1]) > 0) {
                        return "['{$s[1]}']";
                    } else {
                        return "[{$s[1]}]";
                    }
                }
            }, $math[2]));

            return '<?=isset(' . $returnstr . ') && ' . $returnstr . '?>';
        }
    }

    protected function preg__var($math)
    {
        if (!is_string($math)) {
            $math = trim($math[1]);
        }

        if (!$math) return '';

        $math = trim(trim($math), '<>?=');

        // 先处理数组中的点语法
        if (strpos($math, '[') !== false && strpos($math, '.') !== false) {
            // 匹配数组中的点语法，如 $status_list[$order.status]
            $math = preg_replace_callback(
                '/\[(.*?)\]/',
                function ($matches) {
                    $inner = $matches[1];
                    if (strpos($inner, '.') !== false) {
                        return '[' . $this->__parse_dot_notation($inner) . ']';
                    }
                    return $matches[0];
                },
                $math
            );
        }

        // 再处理普通的点语法
        if (strpos($math, '.') !== false) {
            $math = $this->__parse_dot_notation($math);
        }

        return "<?php echo isset($math) ? $math : ''; ?>";
    }

    protected function preg__base($math)
    {
        if (is_string($math) === false)
            $math = $math[0];
        if ($math) {
            $returnstr = '[qstylebase]' . base64_encode($math) . '[/qstylebase]';
            return $returnstr;
        }
    }

    protected function preg__debase($math)
    {
        if (is_string($math) === false)
            $math = $math[1];
        $returnstr = '';
        if ($math) {
            $returnstr = base64_decode($math);
            return $returnstr;
        }
    }

    protected function preg__stripvtags($math)
    {
        if (is_string($math) === false)
            $math = $math[1];
        $returnstr = '';
        if ($math) {
            $returnstr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $math));
        }
        return $returnstr;
    }

    protected function preg__input($math)
    {
        $inputvar = trim($math[0]);
        $type = trim($math[1]);
        if (stripos($inputvar, 'id=') === false) {
            if (stripos($inputvar, 'class=') !== false) {
                $inputvar = preg_replace('/class="([^"]*)"/isU', 'class="$1 input' . $type . '"', $inputvar);
            } else {
                $inputvar = strtr($inputvar, array('type=' => "class=\"input{$type}\" type="));
            }
        }
        return $inputvar;
    }

    protected function preg__contents($math)
    {
        static $savearray = array();
        $filename = trim($math[1]);
        if ($savearray[$filename] >= 2) {
            return '';
        }

        strpos($filename, '.') === false && $filename .= $this->templates_postfix;
        $html_array = $this->__get_path($filename);
        if (empty($html_array['tpl']) === false) {
            $filedata = $this->preg__file($html_array['tpl']);
            $filedata = str_ireplace(array('{loads', '{load'), array('{templatesub', '{template'), $filedata);
            // 让叠加数据也兼容模板化处理.
            $filedata = preg_replace("/\<\!\-\-\{(.*?)\}\-\-\>/s", '{$1}', $filedata);
            if (stripos($filedata, '{templatesub') !== false) {
                $savearray[$filename] += 1;
                $this->preg__debug('解析细节: 静态引入文件:' . $filedata);
                $filedata = preg_replace_callback("/{templatesub\s+(.+?)\}/is", array($this, 'preg__contents'), $filedata);
            }
            return $filedata;
        }

        return '';
    }

    protected function preg__transamp($math)
    {
        $s = trim($math[0]);
        if ($s) {
            $s = str_replace('&', '&amp;', $s);
            $s = str_replace('&amp;amp;', '&amp;', $s);
            $s = str_replace('\"', '"', $s);
            return $s;
        }
    }

    protected function preg__stripscriptamp($math)
    {
        $s = trim($math[1]);
        if ($s) {
            $s = str_replace('&amp;', '&', $s);
            return "<script src=\"$s\" type=\"text/javascript\"></script>";
        }
        return false;
    }

    protected function preg__stripblock($math)
    {
        $var = $math[1];
        $text = trim($math[2]);
        if ($var && $text)
            $this->templates_blockreplace["{{$var}}"] = $text;
        return '';
    }

    protected function preg__debug($mess, $cls = E_NOTICE)
    {
        if (($this->templates_isdebug || $cls === true) && $mess) {
            $mess = htmlspecialchars($mess);
            if ($cls === true || in_array($cls, array('0', E_NOTICE)) === true) {
                $cls = 'Notice';
            } else {
                $cls = 'Warn';
            }

            $this->templates_debug[][$cls] = $mess;
        }
        return $this->templates_debug;
    }

    //公共方法: 删除模板缓存,假如不传入参数, 将默认删除缓存目录的所有文件.;
    public function cache_delete($path = null)
    {
        if ($path === null) {
            $path = $this->templates_cache;
            $file_arr = scandir($path);
            foreach ($file_arr as $val) {
                if ($val === '.' || $val === '..') {
                    continue;
                }
                if (is_dir($path . $val) === true)
                    $this->cache_delete($path . $val . '/');
                if (is_file($path . $val) === true && $val !== 'inde1x.html')
                    unlink($path . $val);
            }
        } else {
            if (is_file($path) === true)
                unlink($path);
        }
    }

    public function __destruct()
    {
        if ($this->templates_isdebug) {
            $this->templates_debug[]['Notice'] = 'Qstyle 所有工作已经结束.....';

            # 植入几个全局统计.
            $newarrr = array();
            foreach ($this->templates_debug as $key => $val) {
                $newarrr[] = $val;
                if ($key === 1) {
                    $newarrr[] = array('Notice' => '模板文件信息: ' . implode(',', $this->templates_file));
                    $newarrr[] = array('Notice' => '缓存文件信息:<br /> 　' . implode('<br /> 　', $this->templates_cache_file));
                    $newarrr[] = array('Notice' => '自动匹配路径: ' . implode(',', $this->set_auto_path(self::_STATIC)) . ' * 在此目录或者子目录的文件都可以直接匹配');
                    $newarrr[] = array('Notice' => '语言数组数据: ' . implode(',', array_keys($this->templates_lang)));
                    $newarrr[] = array('Notice' => '变量数组数据: ' . count($this->templates_assign));
                    $newarrr[] = array('Notice' => '静态变量数据: ' . count($this->templates_static_assign) . ' * 主要用于CSS, JS等');
                    $newarrr[] = array('Notice' => 'block解析数据: ' . count($this->templates_blockreplace));
                    $newarrr[] = array('Notice' => "\n");

                    $newarrr[] = array('Notice' => "模板更新数: " . $this->templates_update);
                    $newarrr[] = array('Notice' => "加载视图次数: " . $this->templates_viewcount);
                    $newarrr[] = array('Notice' => "写入文件次数: " . $this->templates_writecount);
                    $newarrr[] = array('Notice' => "全局替换次数: " . count($this->templates_replace));

                    $newarrr[] = array('Notice' => "全局设置: 模板后缀:" . var_export($this->templates_postfix, true) . '; 缓存后缀: ' . var_export($this->templates_caching, true) . '; 变量模式: ' . $this->templates_var . '; 自动更新: ' . var_export($this->templates_auto, true) . '; 当次强制更新: ' . var_export($this->templates_new, true) . '; 清除无意义字符: ' . var_export($this->templates_space, true) . '; 安全码: ' . var_export($this->templates_ankey, true));
                }
            }

            $this->templates_debug = $newarrr;
            foreach ($this->templates_debug as $key => $val) {
                $trues = false;
                if (isset($val['Notice'])) {
                    $cls = 'Notice';
                    $val = $val['Notice'];
                    $trues = true;
                } else if (isset($val['Warn'])) {
                    $cls = 'Warning';
                    $val = $val['Warn'];
                    $trues = true;
                }

                if ($trues) {
                    $clstr = '<strong style="color:#BAE7DD">' . $cls . ':</strong>';
                    if ($cls === 'Warning') {
                        $clstr = '<strong style="color:#FF8040">' . $cls . ':</strong>';
                        $val = '<span style="color:#FF8040">' . $val . '<span>';
                    }
                    if ($val === "\n") {
                        $val = '<br />';
                        $clstr = '';
                    }
                    echo ('<div style="background-color: #498BBC; text-align: left; border-bottom: 1px solid #F2F8FB; padding: 2px 6px; font-size:13px; color: white;">' . $clstr . ' ' . $val . '</div>');
                }
            }
        }
    }

    // 新增辅助方法：处理点语法
    protected function __parse_dot_notation($var)
    {
        if (is_array($var)) {
            $var = $var[0];
        }

        if (strpos($var, '.') !== false) {
            $parts = explode('.', trim($var));
            $varName = array_shift($parts);
            $result = $varName;
            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    $result .= "[$part]";
                } else {
                    $result .= "['$part']";
                }
            }
            return $result;
        }
        return $var;
    }

    // 1. 提取一个统一的switch处理方法
    protected function processSwitchStatement($var, $content, $debug = false)
    {
        $var = $this->__parse_dot_notation($var);
        $processed_content = '';

        if ($debug) {
            var_dump("Switch content before processing: " . $content);
        }

        // 处理case标签
        $pattern = "/\{(case\s+([^}]+)|default)\}([\s\S]*?)\{\/(?:case|default)\}/i";
        if (preg_match_all($pattern, $content, $parts, PREG_SET_ORDER)) {
            if ($debug) {
                var_dump("Found parts: " . print_r($parts, true));
            }

            foreach ($parts as $part) {
                if ($debug) {
                    var_dump("Processing part: " . print_r($part, true));
                }

                if (trim($part[1]) === 'default') {
                    $processed_content .= "default: echo '" . addslashes(trim($part[3])) . "'; break;\n";
                } else {
                    $values = explode('|', $part[2]); // 支持多值匹配
                    $cases = [];
                    foreach ($values as $value) {
                        $value = trim($value);
                        if (!is_numeric($value) && !preg_match('/^[\'"].*[\'"]$/', $value)) {
                            $value = "'" . str_replace("'", "\\'", $value) . "'";
                        }
                        $cases[] = "case $value:";
                    }
                    $processed_content .= implode(" ", $cases) . " echo '" . addslashes(trim($part[3])) . "'; break;\n";
                }
            }
        } else if ($debug) {
            var_dump("No case/default matches found!");
            var_dump("Pattern used: " . $pattern);
        }

        $result = "<?php \$_switch_var = $var; switch(\$_switch_var) { \n$processed_content\n } ?>";

        if ($debug) {
            var_dump("Final result: " . $result);
        }

        return $result;
    }

    // 定义一个统一的处理date函数的方法
    protected function processDateTernary($matches)
    {
        $condition_var = $matches[1];  // $vo['expire_time']
        $date_format = preg_replace('/\s+/', '', $matches[2]);  // 移除日期格式中的多余空格
        $timestamp_var = $matches[3];  // $vo['expire_time']
        $false_value = $matches[4];    // 永久

        return "<?php echo isset($condition_var) && $condition_var ? date('$date_format', $timestamp_var) : '$false_value'; ?>";
    }

    // 新增辅助方法：处理三元运算符的值
    protected function __process_ternary_value($value, $allow_path = false)
    {
        if (strpos($value, '$') === 0) {
            // 如果是变量，检查是否包含点语法
            if (strpos($value, '.') !== false) {
                return $this->__parse_dot_notation($value);
            }
            return $value;
        }

        if (is_numeric($value) || $value === 'true' || $value === 'false') {
            return $value;
        }

        // 如果是路径且允许路径
        if ($allow_path && strpos($value, '/') === 0) {
            return $value;
        }

        // 如果已经有引号就不添加
        if (!preg_match('/^[\'"].*[\'"]$/', $value)) {
            return "'" . trim($value, "'\"") . "'";
        }

        return $value;
    }

    // 新增辅助方法：处理数组访问
    protected function __process_array_access($value)
    {
        return preg_replace('/\[([\'"])(.*?)\1\]/', "['$2']", $value);
    }

    // 修改后的三元运算符处理逻辑
    protected function __parse_ternary($condition, $true_value, $false_value, $operator = null, $compare_value = null)
    {
        // 处理条件
        if (strpos($condition, '.') !== false) {
            $condition = $this->__parse_dot_notation($condition);
        }

        // 处理比较值
        if ($operator && $compare_value) {
            $compare_value = $this->__process_ternary_value($compare_value);
            return "<?php echo isset($condition) && $condition $operator $compare_value ? " .
                $this->__process_ternary_value($true_value) . " : " .
                $this->__process_ternary_value($false_value) . "; ?>";
        }

        // 处理函数调用
        if (preg_match('/^[a-zA-Z0-9_]+\([^\)]*\)$/', $true_value)) {
            return "<?php echo isset($condition) && $condition ? $true_value : " .
                $this->__process_ternary_value($false_value) . "; ?>";
        }

        // 处理数组访问
        if (strpos($condition, '[') !== false) {
            $condition = $this->__process_array_access($condition);
            $true_value = strpos($true_value, '[') !== false ? $this->__process_array_access($true_value) : $this->__process_ternary_value($true_value);
            $false_value = strpos($false_value, '[') !== false ? $this->__process_array_access($false_value) : $this->__process_ternary_value($false_value);
            return "<?php echo isset($condition) && $condition ? $true_value : $false_value; ?>";
        }

        // 普通三元运算符
        return "<?php echo isset($condition) && $condition ? " .
            $this->__process_ternary_value($true_value, true) . " : " .
            $this->__process_ternary_value($false_value, true) . "; ?>";
    }

    // 统一处理日期格式化的函数
    protected function __parse_date_expression($condition_var, $timestamp_var, $format, $default_value) {
        // 处理点语法的变量
        if (strpos($condition_var, '.') !== false) {
            $parts = explode('.', $condition_var);
            $condition_var = $parts[0] . "['" . $parts[1] . "']";
        }

        if (strpos($timestamp_var, '.') !== false) {
            $parts = explode('.', $timestamp_var);
            $timestamp_var = $parts[0] . "['" . $parts[1] . "']";
        }

        // 标准化日期格式（移除多余空格）
        $format = preg_replace('/\s+/', '', $format);

        return "<?php echo isset($condition_var) && $condition_var ? date('$format', $timestamp_var) : '$default_value'; ?>";
    }

    // 公共方法: 获取模板渲染后的内容
    public function fetch($PHPnew_file_name)
    {
        ob_start();
        try {
            // 获取模板内容
            $html_array = $this->__get_path($PHPnew_file_name);
            
            // 检查是否需要更新缓存
            if ($this->__check_update($html_array) === false) {
                $template = $this->preg__file($html_array['tpl']);
                $template = $this->__parse_html($template);
                $this->preg__file($html_array['cache'], $template, true);
                $this->templates_update++;
            }
            
            // 解析变量
            $this->__parse_var(true);
            extract($this->templates_assign);
            
            // 包含缓存文件
            if (isset($html_array['cache'])) {
                include $html_array['cache'];
            }
            
            // 获取输出内容
            $content = ob_get_clean();
            echo $content;
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

 
}
