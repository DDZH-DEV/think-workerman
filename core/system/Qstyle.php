<?php
namespace system;
/*
# @copyright: 分享工作室 Yuan 2020 12 24
# @filename; Qstyle.class.php
# @version: Qstyle 8.0.0;
*/
class Qstyle{
	public $templates_dir         = array(PUBLIC_PATH.'template');       //模板路径,支持数组叠代多层目录,最后面的优先搜索;
    public $templates_cache       = RUNTIME_PATH.'cache_tpl/';            //缓存模板路径;
	public $templates_postfix     = '.html';						//模板后缀;
    public $templates_caching     = '.php';							//缓存后缀;
    public $templates_var         = 'All';							//变量获取模式, All,ASSIGN;
	public $templates_auto        = true;							//自动更新模板;
	public $templates_new         = false;							//设置当次更新, 系统更新可强制配置为true;
	public $templates_space       = false;							//清除无意义字符
    public $templates_ankey       = '';							    // 加密模板文件名,避免被猜测到.
    public $templates_isdebug     = false;
    public $templates_replace     = array();                        // 全局替换块,区分大小写.
    
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
    protected $templates_viewcount  = 0;          // 视图次数.
    protected $templates_writecount  = 0;         // 写入次数  
    protected $templates_host  = '';
	protected $PHPnew = 'Qstyle 8.0.0';
    
    const _STATIC ='STATIC';
    const _LISTTPL ='LISTTPL';
	
    public function __construct(){
        $this->preg__debug('Qstyle 初始化开始.....', true);
        $this->preg__debug("\n", true);
        $dirags = func_get_args();
        
        $this->templates_dir = (array) $this->templates_dir;
        foreach($this->templates_dir AS $key => $val){
            $this->templates_dir[$val] = $val;
            unset($this->templates_dir[$key]);
        }

        if(isset($dirags[0]) && is_array($dirags[0])){
            $dirags = $dirags[0];
            foreach($dirags AS $val){
                $this->set_templates_path($val);
            }
        }

        $this->templates_host = md5($this->preg__urlhost());
        return $this;
    }
        
	//公共方法: 文件名, 是否返回缓存文件.
	public function display($PHPnew_file_name, $returnpath = false){
	    static $once = 0;

        if($once === 0){
            $this->templates_postfix = '.'.ltrim($this->templates_postfix, '.');
            
            if(!$this->templates_cache || is_dir($this->templates_cache) == false){
                @mkdir($this->templates_cache, true);
            }
            
            if($this->templates_isdebug){
                $tplnotice = $this->templates_dir?'模板目录已经被指定:'.implode(', ',$this->templates_dir).' (验证存在)':'未指定模板目录, 系统将从自动目录中寻找模板';
                $this->preg__debug($tplnotice);
                $autodir = $this->set_auto_path(self::_STATIC);
                $this->preg__debug('静态文件匹配自动目录: '.implode(', ',$autodir).' (验证存在)');
            }
            $once = 1;
        }
        
        $this->templates_viewcount += 1;
        $this->preg__debug("\n");
        $this->preg__debug($this->templates_viewcount.' 次模板调用开始.....', E_NOTICE);
	    if(isset($this->templates_debug[$PHPnew_file_name]) === true || !$PHPnew_file_name){
            $this->preg__debug('参数为空 或者 重复模板调用:'. var_export($PHPnew_file_name, true).' 函数停止前进',E_WARNING);
            return false;
        }
        
        // 支持字符串解析
        if (strpos($PHPnew_file_name,"\n") !== false || strpos($PHPnew_file_name,"{") !== false || strpos($PHPnew_file_name,"<?php") !== false){
            $this->templates_name =  str_shuffle(md5($PHPnew_file_name).microtime(true));
            $this->templates_message = $PHPnew_file_name;
            $phpnew_phpcode_log_phpcode = $this->__parse_html($this->templates_message);
            // 一直在释放变量.
            if ($returnpath === false){
                unset($this->templates_message);
                $this->__parse_var(true);
                extract($this->templates_assign);          
                @eval('?>'.$phpnew_phpcode_log_phpcode);
            }else{
               return $phpnew_phpcode_log_phpcode;  
            }
            return true;
        }else{
            strpos($PHPnew_file_name,$this->templates_postfix) === false && $PHPnew_file_name .= $this->templates_postfix;
            $this->templates_name = $PHPnew_file_name;
            $tplcache = $this->__get_path($PHPnew_file_name);
            $true_check = $this->__check_update($tplcache);
        }
          
        $this->templates_cache_file[$PHPnew_file_name] = $tplcache['cache']??'';
        $this->templates_file[$PHPnew_file_name] = $tplcache['tpl']??'';
        $this->templates_debug[$PHPnew_file_name] = array();
        
        // 支持对模板block独立调用
        if (!is_bool($returnpath)){
            $this->parse_tpl_block($this->templates_file[$PHPnew_file_name], $returnpath);
            exit();
        }
        
        $htmlname = basename($PHPnew_file_name);
        $PHPnew_path = false;
        if($true_check === true){
            if(isset($tplcache['cache']) && $tplcache['cache'])
                $PHPnew_path = $this->templates_cache_file[$PHPnew_file_name];
        }else{
            if(!$this->templates_file[$PHPnew_file_name] || !$this->templates_message = $this->preg__file($this->templates_file[$PHPnew_file_name])){
                $this->preg__debug('模板文件'.$PHPnew_file_name.' 读取失败,请检查模板是否存在',E_WARNING);
            }
            
            if($this->templates_message){
                $this->templates_message = $this->__parse_html($this->templates_message);
                $PHPnew_path = $this->templates_cache_file[$PHPnew_file_name];
                
                if(stripos($this->templates_message,'[qstyle debug]') !== false || stripos($this->templates_message,'{qstyle debug}') !== false){
                   echo highlight_string($this->templates_message);
                   exit();
                }
                
                if($this->templates_message && !$this->preg__file($PHPnew_path,$this->templates_message,true)){
                    $this->preg__debug('模板文件无法写入: '. $htmlname);
                }
                $this->templates_message = null;
                $this->templates_update += 1;
            }
        }
        
        unset($tplcache , $PHPnew_file_name);
        if($this->templates_viewcount === 1 && $returnpath === false && $PHPnew_path){
            $this->__parse_var();
            $this->preg__debug("第".$this->templates_viewcount."次输出: ".$htmlname.' & '. $PHPnew_path);
            include_once $PHPnew_path;
        }else{
            if($returnpath !== false){
                $this->preg__debug("第".$this->templates_viewcount."次强制返回路径: ".$htmlname.' & ' .$PHPnew_path);
            }else if(!$PHPnew_path){
                $this->preg__debug("第".$this->templates_viewcount."次错误的模板: ".$htmlname);
            }else{
                $this->preg__debug("第".$this->templates_viewcount."次返回路径: ".$htmlname.' & ' .$PHPnew_path);
            }
        }
        
        return $PHPnew_path;
	}
    
    protected function parse_tpl_block($path, $blockname){
        $data = $this->preg__file($path);
        $data = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $data);
        if (preg_match("/\{block\s+{$blockname}\}(.*?)\{\/block\}/is", $data, $Reg)){
            $data = $Reg[1];
        }else{
            $data = null;
        }
        
        if($data){
            $this->templates_name =  str_shuffle(md5($path).microtime(true));
            $this->templates_message = $data;
            $phpnew_phpcode_log_phpcode = $this->__parse_html($data);
            // 一直在释放变量.
            if ($phpnew_phpcode_log_phpcode){
                unset($data);
                $this->__parse_var(true);
                extract($this->templates_assign);
                @eval('?>'.$phpnew_phpcode_log_phpcode);
            }else{
               echo(htmlspecialchars($data));
            }
        }else{
            echo '无法找到的block块:'.$blockname;
        }
    }
    
    public function load(){
        if($args = func_get_args()){
            return call_user_func_array(array($this, 'display'), $args);
        }
    }
    
	//公共方法: 用户用强制性变量赋值;
	public function assign($phpnew_var, $phpnew_value = null){
		if(!$phpnew_var) return false;
        if($phpnew_var === true)
            return $this->templates_assign;
        $i = 0;
		if($phpnew_value === null && is_array($phpnew_var) === true){
			foreach ($phpnew_var as $php_key => $php_val){
                $this->templates_assign[$php_key] = $php_val;
                $i ++;
            }
		} else{
            $this->templates_assign[$phpnew_var] = $phpnew_value;
            $i ++;
		}
        return $this->templates_assign;
	}
    
    public function set_templates_type($parema='变量模式[All,ASSIGN]'){
        if($parema !== true){
            $this->templates_var = $parema;
        }
        return $this->templates_var;
    }
    
    public function set_templates_suffix($parema='', $paremb=''){
        if($parema){
            $this->templates_postfix = $parema;
        }
        
        if($paremb != '')
            $this->templates_caching = $paremb;
        
        return array('templates_postfix'=>$this->templates_postfix,'templates_caching'=>$this->templates_caching);
    }
    
    public function set_templates_auto($parem='设置自动更新[bool]'){
        $this->templates_auto = (bool) $parem;
        return $this->templates_auto;
    }
    
    public function set_templates_space($parem='清除多余空白[bool]'){
        $this->templates_space = (bool) $parem;
        return $this->templates_space;
    }
    
     public function set_templates_isdebug($parem='启用调试[bool]'){
        $this->templates_isdebug = (bool) $parem;
        return $this->templates_isdebug;
    }
    
    public function set_templates_oncenew($parem='当次更新[bool]'){
        $this->templates_new = (bool) $parem;
        return $this->templates_new;
    }
    
    public function set_templates_ankey($parem='安全码'){
        if($parem !== true){
            $this->templates_ankey = $parem;
        }
        return $this->templates_ankey;
    }
    
    public function set_templates_path($path='模板路径'){
        if(!$path) return false;
        if($path === true)
            return $this->templates_dir;
        
        $path = $this->__exp_path($path);
        if(!isset($this->templates_dir[$path]) && is_dir($path) === true){
            $this->templates_dir[$path] = $path;
        }else{
            $this->preg__debug('set_templates_path 模板目录不存在, 自动忽略:'.htmlspecialchars($path));
        }
        return $this->templates_dir;
    }
    
    public function set_templates_replace($phpnew_var='关键值,替换值', $phpnew_value = null){
        if($phpnew_var === true)
            return $this->templates_replace;
        
        $i = 0;
		if($phpnew_value === null && is_array($phpnew_var) === true){
			foreach ($phpnew_var as $php_key => $php_val){
                $this->templates_replace[$php_key] = $php_val;
                $i ++;
            }
		} else{
            $this->templates_replace[$phpnew_var] = $phpnew_value;
            $i ++;
		}
        return $this->templates_replace;
    }
    
    public function set_cache_path($dir='缓存目录路径'){
        if($dir !== true && is_dir($dir)){
            $this->templates_cache = $dir;
        }
        return $this->templates_cache;
    }
  
    //公共方法: 定义静态变量, 主要用于css, js.
    public function set_static_assign($var1=null, $var2 = null){
        if(!$var1) return false;
        if($var1 === true)
            return $this->templates_static_assign;
        
        if($var2 === null && is_array($var1) === true){
            foreach($var1 AS $key => $var){
                $this->templates_static_assign[$key] = $var;
            }
        }else{
            $this->templates_static_assign[$var1] = $var2;
        }
        return $this->templates_static_assign;
    }
    
    //公共方法: 设置语言数组, 模板中就可以用{lang str}
    public function set_language($var1=null, $var2 = null){
        if(!$var1) return false;
        if($var1 === true)
            return $this->templates_lang;
        
        if($var2 === null && is_array($var1) === true){
            foreach($var1 AS $key => $var){
                $this->templates_lang[$key] = $var;
            }
        }else{
            $this->templates_lang[$var1] = $var2;
        }
        return $this->templates_lang;
    }
    
    //公共方法: 设置自动匹配的路径, 默认先不工作, 等有此语法再读取目录.
    public function set_auto_path($set_path = '自动搜索目录路径'){
        if( in_array($set_path,array(self::_STATIC,self::_LISTTPL))){
           if ($set_path === self::_STATIC){
              return array_reverse($this->templates_autofile);
           }else{
              return array_reverse($this->templates_dir);
           }
        }else if (strpos($set_path, '/') !== false){
            $set_path = $this->__exp_path($set_path);
            if(!isset($this->templates_autofile[$set_path]) && is_dir($set_path) === true){
                $this->templates_autofile[$set_path] = $set_path;
            }else{
                $this->preg__debug("set_auto_path 设置自动搜索目录失败 , {$set_path} 目录不存在!", true);
            }
        }
        return $this->templates_autofile;
    }
    
    //私有方法: 定位域名, 以此来影响部分文件.
    protected function preg__urlhost(){
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'::'.$_SERVER['SERVER_PORT'].dirname($_SERVER['PHP_SELF']);
    }
    
    protected function __exp_path($path){
        $path = trim($path);
        if(is_dir($path)){
           return trim(strtr($path, array('\\'=>'/','\\\\'=>'/','//'=>'/')),'./').'/';
        }else{
           return './'.trim(strtr($path, array('\\'=>'/','\\\\'=>'/','//'=>'/')),'./').'/';
        }
    }
    
    protected function __exp_file($filepath){
        $filepath = trim($filepath);
        return ltrim(strtr($filepath, array('\\'=>'/','\\\\'=>'/','//'=>'/')),'./');
    }
    
    //保护的方法: 当语法有自动匹配功能时, 此方法会被调用. 
    protected function __real_alldir($dir=array(),$filename =''){
        if(!$dir)
            return array();
        $dirlist = array();
        $paths = false;
        
        foreach($dir AS $key => $val){
            $paths = $val.$filename;
            if (!is_file($paths)){
                $paths = false;
                $temp = array();
                $temp = @glob($val.'*', GLOB_ONLYDIR); // 4.3.3 GLOB_ONLYDIR  在 Windows 或者其它不使用 GNU C 库的系统上开始可用 
                foreach($temp AS $vals){
                    $vals = $this->__exp_path($vals); 
                    $dirlist[$vals] = $vals;
                }
            }else{
                unset($dirlist);
                break;
            }
        }
        
        if($paths){
            return $paths;
        }else{
            return $this->__real_alldir($dirlist, $filename);
        }
    }
      
    // 内部方法: 检查是否应该更新, 参数:当前配置数组.
    protected function __check_update($html_array){
        if(is_dir($this->templates_cache) === false)
            $this->preg__debug('缓存目录不存在: '. $this->templates_cache,E_WARNING);    
        if(empty($html_array['tpl']) === true)
            $this->preg__debug('模板文件不存在: '. $this->templates_name,E_WARNING);
        if($this->templates_new === true){
            $this->preg__debug('templates_new 自动更新已经开启!');
            return false;
        }
        
        if(isset($html_array['cache']) && ( !$html_array['cache'] || is_file($html_array['cache']) === false )){
            $this->preg__debug(var_export($html_array['cache'], true).'缓存文件不存在, 解析更新已开启!');
            return false;
        }
        return true;
    }
    
	// 内部方法: 取得路径信息.
	protected function __get_path($htmlfile){
	    $rename = false;
	    if(stripos($htmlfile,'/') !== false){
            if(is_file($htmlfile) === false){ 
                if(strpos($htmlfile, $this->templates_postfix) === false){
                    $htmlfile .= $this->templates_postfix;
                }
            }else{
                $rename = $htmlfile;
            }
        }

        if ($rename === false){
            $rename = $this->__search_tpl($htmlfile);
        }
        
        if($rename){
            $this->preg__debug('模板文件自动搜索到路径: '. $rename);
        }else{
            $this->preg__debug('模板文件搜索不到路径: '. $htmlfile, E_WARNING);
        }
        
        $htmlfile = $rename;
        $retruans = array();
        if($htmlfile !== false){
            $md5 = $this->templates_auto === true? md5_file($htmlfile):md5($htmlfile.$this->templates_ankey.$this->templates_host);
            $temname = trim($this->templates_name,'./\\');
            $temname = strtr($temname, array($this->templates_postfix=>'','/'=>'_','.'=>'',' '=>''));
            $retruans = array(
                'tpl'=>$htmlfile,
                'cache'=>$this->templates_cache.$temname.'_'.$md5.$this->templates_caching
            );
       }
       return $retruans;
	}
    
    protected function __search_tpl($htmlfile){
        $dir = $this->set_auto_path(self::_LISTTPL);
        $htmlfile = $this->__exp_file($htmlfile);
        $paths = false;
               
        if(stripos($htmlfile,'__') === 0){
            $htmlfile = trim($htmlfile,'_');
            $paths = $this->__real_alldir($dir, $htmlfile);
        }else{
            // 默认只搜索一层, 跟静态文件不一样.
            foreach($dir AS $val){
                if(is_file($val.$htmlfile) === true){
                    $paths = $val.$htmlfile;
                    break;
                }
            }
            
            if(!$paths){ 
                $htmlfile = basename($htmlfile);
                // 默认只搜索一层, 跟静态文件不一样.
                foreach($dir AS $val){
                    if(is_file($val.$htmlfile) === true){
                        $paths = $val.$htmlfile;
                        break;
                    }
                }
            }
        }
        return $paths;
    }
    
    // 内部方法: 取得全局变量并且赋予模板.
	protected function __parse_var($isrun=false){
		static $savevar = 0;
        
        if ($isrun === true)
            $savevar = 0;
        
        if($savevar === 0 && $this->templates_var !== 'ASSIGN'){
            $allvar = array_diff_key($GLOBALS, array ('GLOBALS'=>0,'_ENV'=>0,'HTTP_ENV_VARS'=>0,'ALLUSERSPROFILE'=>0,'CommonProgramFiles'=>0,'COMPUTERNAME'=>0,'ComSpec'=>0,'FP_NO_HOST_CHECK'=>0,'NUMBER_OF_PROCESSORS'=>0,'OS'=>0,'Path'=>0,'PATHEXT'=>0,'PROCESSOR_ARCHITECTURE'=>0,'PROCESSOR_IDENTIFIER'=>0,'PROCESSOR_LEVEL'=>0,'PROCESSOR_REVISION'=>0,'ProgramFiles'=>0,'SystemDrive'=>0,'SystemRoot'=>0,'TEMP'=>0,'TMP'=>0,'USERPROFILE'=>0,'VBOX_INSTALL_PATH'=>0,'windir'=>0,'AP_PARENT_PID'=>0,'uchome_loginuser'=>0,'supe_cookietime'=>0,'supe_auth'=>0,'Mwp6_lastvisit'=>0,'Mwp6_home_readfeed'=>0,'Mwp6_smile'=>0,'Mwp6_onlineindex'=>0,'Mwp6_sid'=>0,'Mwp6_lastact'=>0,'PHPSESSID'=>0,'HTTP_ACCEPT'=>0,'HTTP_REFERER'=>0,'HTTP_ACCEPT_LANGUAGE'=>0,'HTTP_USER_AGENT'=>0,'HTTP_ACCEPT_ENCODING'=>0,'HTTP_HOST'=>0,'HTTP_CONNECTION'=>0,'HTTP_COOKIE'=>0,'PATH'=>0,'COMSPEC'=>0,'WINDIR'=>0,'SERVER_SIGNATURE'=>0,'SERVER_SOFTWARE'=>0,'SERVER_NAME'=>0,'SERVER_ADDR'=>0,'SERVER_PORT'=>0,'REMOTE_ADDR'=>0,'DOCUMENT_ROOT'=>0,'SERVER_ADMIN'=>0,'SCRIPT_FILENAME'=>0,'REMOTE_PORT'=>0,'GATEWAY_INTERFACE'=>0,'SERVER_PROTOCOL'=>0,'REQUEST_METHOD'=>0,'QUERY_STRING'=>0,'REQUEST_URI'=>0,'SCRIPT_NAME'=>0,'PHP_SELF'=>0,'REQUEST_TIME'=>0,'argv'=>0,'argc'=>0,'_POST'=>0,'HTTP_POST_VARS'=>0,'_GET'=>0,'HTTP_GET_VARS'=>0,'_COOKIE'=>0,'HTTP_COOKIE_VARS'=>0,'_SERVER'=>0,'HTTP_SERVER_VARS'=>0,'_FILES'=>0,'HTTP_POST_FILES'=>0,'_REQUEST'=>0));
            foreach($allvar as $key => $val){
                $this->templates_assign[$key] = $val;
			}
            $savevar = 1;
            unset($allvar);
		}
	}
 
    // 内部方法: 读文件与写文件的公用方法.
    protected function preg__file($path, $lock='rb' ,$cls = false){
        $mode = $cls === true?'wb+':'rb';
        if($cls === false && is_file($path) === false) return false;
        if(!@$fp = fopen($path, $mode))
            return false;
        
        $ints = 0;
        if($cls === true){
            if(flock($fp, LOCK_EX | LOCK_NB)){
                if(!$ints = fwrite($fp, $lock))
                    return 0;
                $this->preg__debug('文件写入成功: '.$path);
                $this->templates_writecount ++;
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }else{
            $ints = '';
            if(flock($fp, LOCK_SH | LOCK_NB)){
                while(!feof($fp)){
                    $ints .= fread($fp, 4096);
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
         return $ints;
    }
    
    // 内部方法: css,js静态文件解析方法.
    protected function __preg_source_parse($template){
        static $savefile = array();
        if(isset($savefile[$template]))
            return $savefile[$template];
        
        if(!$template || is_file($template) === false)
            return $template;
        
        $this->cssname = $template;
        $static_file = $template;
        
        $template = $this->preg__file($static_file);
        
        
        # 增加todo bug标注支持.
        $template = preg_replace_callback("/(?:#|\/\/)(\s*)(?:TODO|BUG|INFO):(.*?)([^\n\r]*)/is",array($this, 'preg__todobug'),$template,-1,$regint);
        
        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this,'preg__const'), $template,-1,$regintb);
        
  		//替换直接变量输出
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $template);
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
	    $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", '<?=$1?>', $template);
	    $template = preg_replace_callback("/$varRegexp2/s", array($this,'preg__var'), $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s",array($this,'preg__var'), $template);
        
		$template = preg_replace_callback("/<\?\=$varRegexp\?\>/s",array($this,'preg_cssjs_var'), $template);
		$template = preg_replace_callback("/\{$const_regexp\}/s", array($this,'preg_cssjs_var'), $template);
                
        $template = preg_replace_callback("/\{__([^\s]*?\.[^\s]*?)\}/s", array($this, 'preg_static_autofile'), $template);
        # 处理base加密的内容 
        $template = preg_replace_callback('/\{\#(.*)\}/isU',array($this, 'preg__parse_database'), $template);
        $template = strtr($template, array('Qstyle~~<~~'=>'{', 'Qstyle~~>~~'=>'}','Qstyle~~<<~~'=>'$'));
        $tem = explode('.',$static_file);
        $postfix = end($tem);
        $caename_file = $this->templates_cache.$postfix.'_'.md5(basename($static_file).$this->templates_ankey.$this->templates_host).'.'.$postfix;
        $template = "/* {$static_file} */\n".$template;
        $this->preg__file($caename_file,$template,true);
        $savefile[$static_file] = $caename_file;
        return $caename_file;
    }
    
    // 内部方法: css,js静态文件路径计算方法, 跟preg__autofile有小小区别.
    protected function preg_static_autofile($math){
        static $reals = '';
        $args = func_get_args();
        if($args)
            $file = call_user_func_array(array($this,'preg__autofile'),$args);
        if(!$reals){
            # 计算回调多少层.
            $tem = explode('/', rtrim($this->templates_cache,'/'));
            foreach($tem AS $key => $val){
                if($val !== '.' && $val){
                    if($key !== 0){
                        $tem[$key] = '..';
                    }else{
                        if($val !== '..')
                        $tem[$key] = '.';
                    }
                }else{
                    if(!$val)
                        unset($tem[$key]);
                }
            }
            $reals = implode('/', $tem).'/';
        }
        
        if($file && is_file($file) === true){
            if(strpos($this->cssname,'.css') !== false){
                return $reals.ltrim($file,'./');
            }else{
                return $file;
            }
        }
    }
    
     // 内部方法: css,js静态文件变量计算方法.
   	protected function preg_cssjs_var($math){
	    if(is_string($math) === false)
            $math = $math[1];
        $redata = $math;
        if($math && strpos($math,'$') !== false){
            $math = strtr($math, array('"'=>'',"'"=>''));
            # 直接返回变量的值.
            $math = strtr(ltrim($math,'$'),array(']['=>'.'));
            $math = strtr(ltrim($math,'$'),array(']'=>'','['=>'.'));
            
            $tem = explode('.',$math);
            if(!$this->templates_css_assign){
                $this->__parse_var();
                $this->templates_css_assign = $this->templates_assign;
            }
            
            $redata = $this->templates_css_assign;
            foreach($tem AS $val){
                if(isset($redata[$val]))
                    $redata = $redata[$val];
            }
            if(!is_string($redata))
                $redata = '';
        }else{
            #常量替换
            $redata = '';
            $tem = get_defined_constants(true);
            $tem = $tem['user'];
            if(isset($tem[$math]))
                $redata = $tem[$math];
        }
        return $redata;
	}
    // 内部方法: css文件引用规范方法.
    protected function preg__css($math){
        if(!$math[1])
            return false;
        $css_file_path  = '';
        if(strpos($math[0],'link') !== false){
            if(strpos($math[0],'/php') !== false){
                $css_file_path = $this->__preg_source_parse(trim($math[1]));
                $math[0] = preg_replace('/ href="[^"]*"/is'," href=\"$css_file_path\"", $math[0]);
            }

            $this->preg__debug('CSS 自动匹配: '.$css_file_path);
            return $math[0];
        }else{
            $css_file_path = $this->__preg_source_parse($math[1]);
            $this->preg__debug('CSS 自动匹配: '.$css_file_path);
            return '<link rel="stylesheet" type="text/css" href="'.$css_file_path.'" />';
        }
    }

    // 内部方法: js文件引用规范方法.
    protected function preg__js($math){
        $js_file_path = '';
        if(strpos($math[0],'src') !== false){
            if(strpos($math[0],'/php') !== false){
                $js_file_path = $this->__preg_source_parse(trim($math[1]));
                $math[0] = preg_replace('/ src="[^"]*"/is'," src=\"$js_file_path\"", $math[0]);
            }

            $this->preg__debug('JS 自动匹配: '.$js_file_path);
            return $math[0];
        }else{
            $js_file_path = $this->__preg_source_parse(trim($math[1]));
            $this->preg__debug('JS 自动匹配: '.$js_file_path);
            return '<script type="text/javascript" src="'.$js_file_path.'"></script>';
        }
    }

    // 内部方法: html代码自动匹配路径方法
    protected function preg__autofile($math){
        if(is_string($math) === false){
            $mathfile = $math[1];
        }else{
            $mathfile = $math;
        }

        // 带变量的?
        if(strpos($mathfile,'$') !== false || substr_count($mathfile,'{') >0){
            //替换直接变量输出
            $template = $mathfile;
            unset($mathfile);
            $template = $this->__parse_htmlvar($template);
            if(strpos($template, '<?=') !== false)
                $template = strtr($template,array('<?='=>'{','?>'=>'}'));            
            $returns = $this->preg__base('<?php echo $this->preg__autofile('."\"$template\"".');?>');
        }else{
            $returns = $this->__real_alldir($this->set_auto_path(self::_STATIC), $mathfile); // 文件搜索,算法不一样了.
            if(!$returns)
                $returns = $mathfile;
        }
        return $returns;
    }
    
    // 处理变量与常量.
    protected function __parse_htmlvar($template){
        if(!$template)
            return '';
        
        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this,'preg__const'), $template,-1,$regintb);
            
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
	    $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", '<?=$1?>', $template);
	    $template = preg_replace_callback("/$varRegexp2/s", array($this,'preg__var'), $template);
  	    $template = preg_replace_callback("/$varRegexp/s", array($this,'preg__var'), $template);
	    $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s",array(&$this,'preg__var'), $template);
        $template = preg_replace("/\{$const_regexp\}/sU", "<?=$1?>", $template);
        
        $template = strtr($template, array('Qstyle~~<~~'=>'{', 'Qstyle~~>~~'=>'}','Qstyle~~<<~~'=>'$'));
        return $template;
    }
    
    protected function preg__binary($math){
        if($math)
            $math = explode('|',$math[1]);
    
        $var0 = $var1 =$var2 = '';
        if (isset($math[0]))
        $var0 = $this->__parse_htmlvar($math[0]);
        
        if (isset($math[1]))
        $var1 = $this->__parse_htmlvar($math[1]);
        
        if (isset($math[2]))
        $var2 = $this->__parse_htmlvar($math[2]);
        
        if(strpos($var0,'$') !== false)
            $var = trim($var0, '<?=>');
        if(strpos($var1,'$') !== false)
            $var1 = trim($var1, '<?=>');
        if(strpos($var2,'$') !== false)
            $var2 = trim($var2, '<?=>');
    
        if(isset($math[2]) === false){
            $math[1] = $var;
            $var2 = $var1;
            $var1 = ltrim($var,'!');
        }
        
        if($var1 != '' && strpos($var1, '$') !== 0){
            $var1 = strtr($var1, array('\''=>'\\\''));
            $var1 = "'{$var1}'";
        }else{
            if(!$var1){
                $var1 = strtr($var1, array('\''=>'\\\''));
                $var1 = "'{$var1}'";
            }
        }
        
         if($var2 != '' && strpos($var2, '$') !== 0){
            $var2 = strtr($var2, array('\''=>'\\\''));
            $var2 = "'{$var2}'";
         }else{
            if(!$var2){
                $var2 = strtr($var2, array('\''=>'\\\''));
                $var2 = "'{$var2}'";
            }
         }
         return $this->preg__base("<?php echo (isset($var) AND $var)?{$var1}:{$var2};?>");
    }
    
    // TODO: 核心代码开始
	//内部函数: 模板语法处理替换
	protected function __parse_html($template){
	    $template = strtr($template, array('\{'=>'Qstyle~~<~~', '\}'=>'Qstyle~~>~~','\$'=>'Qstyle~~<<~~'));
	    static $savefile = array();
        if(isset($savefile[$this->templates_name]))
            return false;
        
	    if(empty($template) === true)
            return $template;
        
        $savefile[$this->templates_name] = 1;
        
        $this->preg__debug('模板解析开始... 内容共计: '. strlen($template).' 字节');
        
        if($this->templates_replace){
		  $template = strtr($template, $this->templates_replace);
          $this->preg__debug('解析模板细节: templates_replace 全局替换数据次数:'.count($this->templates_replace));
        }
        
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", '{$1}', $template);
        $template = preg_replace_callback("/\{html\s+(.+?)\}/s",array($this,'preg__static'), $template);
        $template = str_ireplace(array('{loads','{load'),array('{templatesub','{template'),$template);
        
        $template = preg_replace_callback("/(?:#|\/\/)(\s*)(?:TODO|BUG|INFO):(.*?)([^\r\n]*)/is",array($this, 'preg__todobug'),$template,-1,$regint);
        $this->preg__debug('解析模板细节: // TODO|BUG TODO,BUG 描述解析次数:'.($regint));
        
		$template = preg_replace_callback("/\{templatesub\s+([^\s]+?)\}[\n\r\t]*/is", array($this,'preg__contents'), $template,-1,$regints);
		$template = preg_replace_callback("/\{template\s+([^\s]+?)\}([\n\r\t]*)/is", array($this,'preg__template'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {load name} 解析次数:'.($regint+$regints));
        $template = preg_replace_callback("/\{block\s+([^\s]*)\}(.*?)\{\/block\}([\n\r\t]*)/is", array($this, 'preg__stripblock'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {block name} block块解析次数:'.($regint));
  
        if($this->templates_blockreplace){
          $ri = 0;
          foreach($this->templates_blockreplace AS $keys => $vals){
             $r2 = strtr($keys , array('{'=>'{block '));
             if(strpos($template, $r2) !== false){
                $ri ++;
                 $template = strtr($template, array($r2=>$vals));
             }else if(strpos($template, $keys) !== false){
                $ri ++;
                $template = strtr($template, array($keys=>$vals));
             }
          }
          $this->preg__debug('解析模板细节: block 注入块替换次数:'.($ri));
        }
        
        //处理自动搜索文件路径
        $template = preg_replace_callback("/\{__(.*)\}/sU", array($this, 'preg__autofile'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {__name} 自动匹配路径解析次数:'.($regint));
        
        // 处理掉所有的路径问题.
        $template = preg_replace_callback("/\<link[^>]*?href=\"([^\s]*)\".*?\/\>/is",array($this,'preg__css'), $template,-1,$regint);
        $template = preg_replace_callback("/\<style[^>]*?\>([^\s]+?\.*?)\<\/style\>/is",array($this,'preg__css'), $template,-1,$regints);
        $this->preg__debug('解析模板细节: <link><style> CSS路径自动匹配路径解析次数:'.($regint+$regints));
        $template = preg_replace_callback("/\<script[^>]*?src=\"([^\s]*)\".*?\>\<\/script\>/is",array($this,'preg__js'), $template,-1,$regint);
        $template = preg_replace_callback("/\<script[^>]*?\>([^\s]*\.*?)\<\/script\>/is",array($this,'preg__js'), $template,-1,$regints);
        $this->preg__debug('解析模板细节: <script> JS路径自动匹配路径解析次数:'.($regint+$regints));
        
        //替换语言包/静态变量/php代码.
        $template = preg_replace_callback("/\{eval\s+(.+?)\}([\n\r\t]*)/is",array($this,'preg__evaltags'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {eval phpcode} eval运行php代码解析次数:'.($regint));
        $template = preg_replace_callback("/\<\?php\s+(.+?)\?\>/is", array($this,'preg__base'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: <?php code ?> 原生态php代码解析次数:'.($regint));
        $template = preg_replace_callback("/\{lang\s+(.+?)\}/is", array($this,'preg__language'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {lang name} 语言包代码解析次数:'.($regint));
		$template = str_replace("{LF}", '<?="\\n"?>', $template);
        
        // 二元判断
        $template = preg_replace_callback("/\{([\!]*\\$[^}\n]*\|[^\n]*)\}/isU",array($this,'preg__binary'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {reg|1|0} 二元判断代码解析次数:'.($regint));
        
        // php7 常量.
        $const_regexp2 = "([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])+";
        $template = preg_replace_callback("/\{$const_regexp2\}/s", array($this,'preg__const'), $template,-1,$regintb);
        
        // 普通变量数组转化.
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $varRegexp2 = "\{((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\}";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
	    $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", '<?=$1?>', $template);
	    $template = preg_replace_callback("/$varRegexp2/s", array($this,'preg__var'), $template);
  	    $template = preg_replace_callback("/$varRegexp/s", array($this,'preg__var'), $template);
	    $template = preg_replace_callback("/\<\?\=\<\?\=$varRegexp\?\>\?\>/s",array($this,'preg__var'), $template,-1,$regint);
        $this->preg__debug('解析模板细节: {$var} 变量,数组代码解析次数:'.($regint));
        
		//替换特定函数
		$template = preg_replace_callback("/\{if\s+(.+?)\}/is",array($this,'preg__if'), $template);
        
		$template = preg_replace_callback("/\{else[ ]*if\s+(.+?)\}/is",array($this,'preg__ifelse'), $template);
		$template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
		$template = preg_replace("/\{\/if\}/is", "<? } ?>", $template,-1,$regint);
        
		$template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\}/is", array($this,'preg__loopone'), $template,-1,$reginta);
		$template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/is",array($this,'preg__looptwo'), $template,-1,$regintb);
		$template = preg_replace("/\{\/loop\}/is", "<? }} ?>", $template);
        $this->preg__debug('解析模板细节: {if else /if} if流程判断代码解析次数:'.($regint));
        $this->preg__debug('解析模板细节: {loop all} 循环输出代码解析次数:'.($reginta+$regintb));
 
        // 常量替换
        $template = preg_replace("/\{$const_regexp\}/sU", "<?=$1?>", $template,-1,$regint);
        $this->preg__debug('解析模板细节: {CONST} 常量代码解析次数:'.($regint));
              
		//其他替换
		$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/is", array($this, 'preg__transamp'), $template);
		$template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\".*?\>\s*\<\/script\>/is",array($this, 'preg__stripscriptamp'), $template);
		
		if($this->templates_space === true){
			$template = preg_replace(array('/\r\n/isU', '/<<<EOF/isU'), array('', "\r\n<<<EOF\r\n"), $template);
		}
        
		$template = strtr($template, array('<style>' => '<style type="text/css">', '<script>' => '<script type="text/javascript">'));
        
        $filename = isset($this->templates_file[$this->templates_name])?$this->templates_file[$this->templates_name]:'';
        $template = '<?php /* '.$filename.' */ if(is_object($this) === false){exit(\'Hacking!\');}else if(!isset($_SERVER[\'Qextract\'])){$_SERVER[\'Qextract\']=1;extract($this->templates_assign);}?>'.$template;
        
		$template = strtr($template, array('<?php' => '<?', '<?php echo' => '<?=','?><?php'=>' '));
		$template = strtr($template, array('<?' => '<?php', '<?=' => '<?php echo '));
        
        # input 修复兼容
        if(stripos($template, '<input') !== false){
           $template = preg_replace_callback('/<input.*type="([^"]*)".*\/>/isU',array($this,'preg__input'), $template,-1, $regint);
           $this->preg__debug('解析模板细节: <input> 标签注入默认class次数:'.$regint);
        }
        
        # 处理base加密的内容 
        $template = preg_replace_callback('/\{\#(.*)\}/isU',array($this, 'preg__parse_database'), $template);
        
        # 最终再释放所有的php代码.
        $template = preg_replace_callback('/\[qstylebase\](.*)\[\/qstylebase\]/isU',array($this, 'preg__debase'), $template);
                
        if($this->templates_replace){
		  $template = strtr($template, $this->templates_replace);
          $this->preg__debug('解析模板细节: templates_replace 全局替换数据次数:'.count($this->templates_replace));
        }
        $template = strtr($template, array('Qstyle~~<~~'=>'{', 'Qstyle~~>~~'=>'}','Qstyle~~<<~~'=>'$'));
        $this->preg__debug('模板解析结束... 内容共计: '.strlen($template).' 字节');
                
        return $template;
	}
    protected function preg__parse_database($math){
        $fname = trim($math[1]);
        if(!$fname)
            return null;
        
        if(strpos($fname, '__') !== false){
           // 有自动搜索过程. 
           $fname = trim($fname,'_');
           $fname = $this->preg__autofile(array(0,$fname));
        }else{
           $fname = $this->__exp_file($fname); 
        }
        
        if(is_file($fname)){
           // 判断后缀
           $ext = strtolower(substr($fname, strrpos($fname, '.')+1));
           $datastr = '';
           if(in_array($ext, array('jpg','jpeg'))){
              $datastr = 'image/jpeg';
           }else if(in_array($ext, array('gif'))){
              $datastr = 'image/gif';
           }else if(in_array($ext, array('png'))){
              $datastr = 'image/png';
           }else if(in_array($ext, array('ico'))){
              $datastr = 'image/x-icon';
           }else if(in_array($ext, array('js'))){
              $datastr = 'text/javascript';
           }else if(in_array($ext, array('css'))){
              $datastr = 'text/css';
           }else if(in_array($ext, array('html','htm'))){
              $datastr = 'text/html';
           }
           
           if($datastr){
             return 'data:'.$datastr.';base64,'.base64_encode($this->preg__file($fname));
           }else{
             return $fname;
           }
        }else{
           return $fname;
        }
    }
    
    protected function preg__parse_ahref($math){
        $hrefdata = preg_replace('/&(?!amp;)/isU','&amp;', $math[1]);
        return 'href="'.$hrefdata.'"';
    }
    
    protected function preg__static($math){
        if(is_string($math) === false)
	       $math = $math[1];
        if($math){
            $this->__parse_var();
            $varname = ltrim(trim($math),'$');
            $varname = $this->templates_assign[$varname];
            if(!$varname)
                $varname = $math[0];
            
            if(is_string($varname)){
    		   return $varname;
            }else{
               return ''; 
            }
        }
    }
    
	protected function preg__evaltags($math) {
	    $php = rtrim(trim($math[1]),';');
        $lf  = $math[2];
		$php = str_replace('\"', '"', $php);
		return $this->preg__base("<?php $php;?>$lf");
	}
    
    protected function preg__todobug($math){
        if(strpos($math[1],"\n")!== false && strpos($math[3],"\n")!== false){
            return "\n";
        }
        return ''; //默认todo, bug全部隐藏.
    }
    protected function preg__if($math){
        $expr = "<? if({$math[1]}){ ?>";
        return $this->preg__stripvtags($expr);
    }
    protected function preg__ifelse($math){
        $expr = "<? }else if({$math[1]}){ ?>";
        return $this->preg__stripvtags($expr);
    }
    protected function preg__loopone($math){
        $expr = "<? if(is_array({$math[1]})===true){foreach({$math[1]} AS {$math[2]}){ ?>";
        return $this->preg__stripvtags($expr);
    }
    protected function preg__looptwo($math){
        $expr = "<? if(is_array({$math[1]})===true){foreach({$math[1]} AS {$math[2]} => {$math[3]}){ ?>";
        return $this->preg__stripvtags($expr);
    }
    protected function preg__template($math){
        $lf = $math[2];
        if(is_string($math) === false)
            $math = trim($math[1]);
        if($math){
            if(strpos($math,'$') !== false){
                $math = $this->__parse_htmlvar($math);
                $math = strtr($math, array('<?='=>'','?>'=>''));
                $retunrstr = '<?php require($this->load('.$math.'));?>'.$lf;
            }else{
                $retunrstr = '<?php require($this->load(\''.$math.'\'));?>'.$lf;
            }
            $this->preg__debug('解析模板细节: 引入文件: '. $math);
            return $this->preg__base($retunrstr);
        }else{
            $this->preg__debug('解析模板细节: 无法解析的引入: '. var_export($math[0], true));
        }
        return false;
    }

    protected function preg__language($math){
        if(is_string($math) === false){
	       $math = $math[1];
           return $this->preg__base("<?php echo \$this->preg__language('$math'); ?>");
        }else{
            $varname = ltrim($math, '$');
            $returnstr = $varname;
            
            if($this->templates_lang[$varname])
                $returnstr = $this->templates_lang[$varname];
                
            if(is_string($returnstr)){
    		   return $returnstr;
            }else{
               return ''; 
            }
        }
    }
    
    protected function preg__const($math){
        if(strpos($math[2],'$') !== false ){
            $math[2] = strtr($math[2], array('$'=>'Qstyle~~<<~~'));
        }
        
		if($math[2]){
			$returnstr = $math[1].str_replace("\\\"", "\"", preg_replace_callback("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", function ($s){
				if($s[1]){
				  if(preg_match('/[a-z]+/s',$s[1]) > 0 ){
					 return  "['{$s[1]}']";  
				  }else{
					 return  "[{$s[1]}]"; 
				  }
				}
			} , $math[2]));
			
			return '<?=isset('.$returnstr.') && '.$returnstr.'?>';
		}
    }
    
	protected function preg__var($math){
	    if(!is_string($math))
           $math = $math[1];
        
        $returnstr = '';
        if($math){
            $math = trim(trim($math), '<>?=');
    	    $varname = "<?={$math}?>";
            $returnstr = str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $varname));
        }
        return $returnstr;
	}
    
    protected function preg__base($math){
        if(is_string($math) === false)
	       $math = $math[0];
        if($math){
            $returnstr = '[qstylebase]'.base64_encode($math).'[/qstylebase]';
            return $returnstr;
        }
    }
    protected function preg__debase($math){
        if(is_string($math) === false)
	       $math = $math[1];
        $returnstr = '';
        if($math){
            $returnstr = base64_decode($math);
		    return $returnstr;
        }
    }
	protected function preg__stripvtags($math){
	    if(is_string($math) === false)
	       $math = $math[1];
        $returnstr = '';
        if($math){
            $returnstr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $math));
        }
        return $returnstr;
	}
    
    protected function preg__input($math){
        $inputvar = trim($math[0]);
        $type = trim($math[1]);
        if(stripos($inputvar, 'id=') === false){
            if(stripos($inputvar, 'class=') !== false){
               $inputvar = preg_replace('/class="([^"]*)"/isU','class="$1 input'.$type.'"', $inputvar);
            }else{
                $inputvar = strtr($inputvar, array('type='=>"class=\"input{$type}\" type="));
            }
        }
        return $inputvar;
    }
    
	protected function preg__contents($math){
        static $savearray = array();
        $filename = trim($math[1]);
        if($savearray[$filename] >= 2){
            return '';
        }
        
        strpos($filename,'.') === false && $filename .= $this->templates_postfix;
        $html_array = $this->__get_path($filename);
		if(empty($html_array['tpl']) === false){
		    $filedata = $this->preg__file($html_array['tpl']);
            $filedata = str_ireplace(array('{loads','{load'),array('{templatesub','{template'),$filedata);
            // 让叠加数据也兼容模板化处理.
            $filedata = preg_replace("/\<\!\-\-\{(.*?)\}\-\-\>/s", '{$1}', $filedata);
            if(stripos($filedata, '{templatesub') !== false){
              $savearray[$filename] += 1;
              $this->preg__debug('解析细节: 静态引入文件:'.$filedata);
              $filedata = preg_replace_callback("/{templatesub\s+(.+?)\}/is", array($this,'preg__contents'),$filedata);
            }
			return $filedata;
		}
        
		return '';
	}

	protected function preg__transamp($math){
	   $s = trim($math[0]);
       if($s){
    		$s = str_replace('&', '&amp;', $s);
    		$s = str_replace('&amp;amp;', '&amp;', $s);
    		$s = str_replace('\"', '"', $s);
    		return $s;
        }
	}

	protected function preg__stripscriptamp($math){
	    $s = trim($math[1]);
        if($s){
		  $s = str_replace('&amp;', '&', $s);
		  return "<script src=\"$s\" type=\"text/javascript\"></script>";
        }
        return false;
	}
 
	protected function preg__stripblock($math){
        $var    = $math[1];
        $text   = trim($math[2]);
        if($var && $text)
            $this->templates_blockreplace["{{$var}}"] = $text;
        return '';
	}
    
    protected function preg__debug($mess, $cls = E_NOTICE){
        if(($this->templates_isdebug || $cls === true) && $mess){
            $mess = htmlspecialchars($mess);
            if($cls === true || in_array($cls, array('0',E_NOTICE)) === true){
                $cls = 'Notice';
            }else{
                $cls = 'Warn';
            }
            
            $this->templates_debug[][$cls] = $mess;
        }
        return $this->templates_debug;
    }
    
	//公共方法: 删除模板缓存,假如不传入参数, 将默认删除缓存目录的所有文件.;
	public function cache_dele($path = null){
		if($path === null){
			$path = $this->templates_cache;
    		$file_arr = scandir($path);
    		foreach ($file_arr as $val){
    			if($val === '.' || $val === '..'){
    				continue;
    			}
    			if(is_dir($path . $val) === true)
    				$this->cache_dele($path . $val . '/');
    			if(is_file($path . $val) === true && $val !== 'index.html')
    				unlink($path . $val);
    		}
        }else{
            if(is_file($path) === true)
                unlink($path);
        }
	}
    
    public function __destruct(){
        if($this->templates_isdebug){
            $this->templates_debug[]['Notice'] = "\n";
            $this->templates_debug[]['Notice'] = 'Qstyle 所有工作已经结束.....';
            echo '<br /><hr />';
            
            # 植入几个全局统计.
            $newarrr = array();
            foreach($this->templates_debug AS $key => $val){
                $newarrr[] = $val;
                if($key === 1){
                    $newarrr[] = array('Notice'=>'模板文件信息: '. implode(',', $this->templates_file));
                    $newarrr[] = array('Notice'=>'缓存文件信息:<br /> 　'. implode('<br /> 　', $this->templates_cache_file));
                    $newarrr[] = array('Notice'=>'自动匹配路径: '. implode(',', $this->set_auto_path(self::_STATIC)).' * 在此目录或者子目录的文件都可以直接匹配');
                    $newarrr[] = array('Notice'=>'语言数组数据: '. implode(',', array_keys($this->templates_lang)));
                    $newarrr[] = array('Notice'=>'变量数组数据: '. count($this->templates_assign));
                    $newarrr[] = array('Notice'=>'静态变量数据: '. count($this->templates_static_assign).' * 主要用于CSS, JS等');
                    $newarrr[] = array('Notice'=>'block解析数据: '. count($this->templates_blockreplace));
                    $newarrr[] = array('Notice'=>"\n");
                    
                    $newarrr[] = array('Notice'=>"模板更新次数: ".$this->templates_update);
                    $newarrr[] = array('Notice'=>"加载视图次数: ".$this->templates_viewcount);
                    $newarrr[] = array('Notice'=>"写入文件次数: ".$this->templates_writecount);
                    $newarrr[] = array('Notice'=>"全局替换次数: ".count($this->templates_replace));
                    
                    $newarrr[] = array('Notice'=>"全局设置: 模板后缀:".var_export($this->templates_postfix, true).'; 缓存后缀: '.var_export($this->templates_caching, true).'; 变量模式: '.$this->templates_var.'; 自动更新: '.var_export($this->templates_auto, true).'; 当次强制更新: '.var_export($this->templates_new, true).'; 清除无意义字符: '.var_export($this->templates_space, true).'; 安全码: '.var_export($this->templates_ankey, true) );
                    $newarrr[] = array('Notice'=>"\n");
                }
            }
            
            $this->templates_debug = $newarrr;
            foreach($this->templates_debug AS $key => $val){
                $trues = false;
                if(isset($val['Notice'])){
                      $cls = 'Notice';
                      $val = $val['Notice'];
                     $trues = true;
                }else if(isset($val['Warn'])){
                    $cls = 'Warning';
                    $val = $val['Warn'];
                    $trues = true;
                }
                
                if($trues){
                    $clstr = '<strong style="color:#BAE7DD">'.$cls.':</strong>';
                    if($cls === 'Warning'){
                        $clstr = '<strong style="color:#FF8040">'.$cls.':</strong>';
                        $val = '<span style="color:#FF8040">'.$val.'<span>';
                    }
                    if($val === "\n"){
                        $val = '<br />';
                        $clstr = '';
                    }
                    echo('<div style="background-color: #498BBC; text-align: left; border-bottom: 1px solid #F2F8FB; padding: 2px 6px; font-size:13px; color: white;">'.$clstr.' '.$val.'</div>');
                }
            }
        }
    }
}