<?php

namespace system;

!defined('ROOT_PATH') && define('ROOT_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
!defined('APP_PATH') && define('APP_PATH', ROOT_PATH . 'apps' . DIRECTORY_SEPARATOR);
!defined('IS_WIN') && define('IS_WIN', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

/**
 * 打印数据
 * @param mixed ...$data
 * */
function p(...$data)
{
    $arg_list = func_get_args();

    $arg_list = func_num_args() == 1 ? $arg_list[0] : $arg_list;

    echo '<pre>' . print_r($arg_list, true) . '</pre>' . "\r\n\r\n";
}

/**
 * 这个文件一般不会用到,只有在开发环境下初始化才会用到
 */
class Dev
{
    public static function run($argv)
    {
        // 解析命令行参数
        $appName = $argv[1] ?? null;
        $depends = [];
        $onlyProject = null;
        
        // 遍历参数寻找 --depends 和 --only
        foreach ($argv as $arg) {
            if (strpos($arg, '--depends=') === 0) {
                $depends = explode(',', substr($arg, 10));
            } elseif (strpos($arg, '--only=') === 0) {
                $onlyProject = substr($arg, 7);
            }
        } 

        

        if ($appName && !$onlyProject) {
            return self::initApp($appName, $depends);
        } else {

            if ($onlyProject) {
                self::updateAppConfigs($onlyProject);
            }
            
            return self::initCliFiles();
        }
    }

    public static function initCliFiles()
    {

        $appConfigs = self::scanAppConfigs();


        $from_dir = ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . '__template__' . DIRECTORY_SEPARATOR . 'client_service';
        $to_dir = ROOT_PATH . 'server';
        self::copy_dir($from_dir, $to_dir);

        $funs = [];

        foreach ($appConfigs as $appName => $appConfig) {
            if (isset($appConfig['depends']) && is_array($appConfig['depends'])) {
                $funs = array_merge($funs, $appConfig['depends']);
            }
        }

        $funs = array_unique(array_filter($funs));

        $start_files = self::get_bat_files($funs);

        // 删除不需要的原装启动文件
        $files = glob(ROOT_PATH . '/server/start*.php');

        foreach ($files as $file) {
            if (!in_array(basename($file), $start_files) && in_array(
                basename($file),
                [
                    'start_businessworker.php',
                    'start_gateway.php',
                    'start_register.php',
                    'start_web.php',
                    'start_queue.php',
                    'start_timer.php',
                    'start_cron.php',
                    'start_global_data.php'
                ]
            )) {
                unlink($file);
            }
        }


        // 生成启动文件
        self::build_start_file();
    }

    protected static function scanAppConfigs()
    {
        $appConfigs = [];
        $appDirs = glob(APP_PATH . '*', GLOB_ONLYDIR);

        foreach ($appDirs as $appDir) {
            $appName = basename($appDir);
            $jsonPath = $appDir . DIRECTORY_SEPARATOR . 'app.json';
            $template_json = ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . '__template__'.DIRECTORY_SEPARATOR.'app.json';
            
            if (!file_exists($jsonPath)) {
                file_put_contents($jsonPath, str_replace('__TEMPLATE__', $appName, file_get_contents($template_json)));
            }

            $appConfigs[$appName] = json_decode(file_get_contents($jsonPath), true);
        }

        return $appConfigs;
    }

    /**
     * 文件夹复制
     * @param $src
     * @param $dst
     * @return void
     */
    protected static function copy_dir($src, $dst)
    {  // 原目录，复制到的目录
        $dir = opendir($src);
        !is_dir($dst) && mkdir($dst, 0777, true);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::copy_dir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    if (!file_exists($dst . '/' . $file)) {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
    }

    /**
     * 根据配置获取需要的启动文件
     * @param $config
     * @return array
     */
    protected static function get_bat_files($config): array
    {

        $type = implode('', $config);

        $files = [];

        if (strpos($type, 'socket') !== false) {
            $files[] = 'businessworker';
            $files[] = 'gateway';
            $files[] = 'register';
        }

        if (strpos($type, 'http') !== false) {
            $files[] = 'web';
        }

        if (strpos($type, 'queue') !== false) {
            $files[] = 'queue';
        }


        if (strpos($type, 'cron') !== false || strpos($type, 'timer') !== false) {
            $files[] = 'cron';
        }


        if (strpos($type, 'data') !== false) {
            $files[] = 'global_data';
        }


        foreach ($files as &$file) {
            $file = "start_" . $file . ".php";
        }

        return $files;
    }

    /**
     * 生成windows bat文件
     * @param string $name
     */
    protected static function build_start_file(string $name = 'apps')
    {

        global $argv;
 
        $watch = isset($argv) && $argv && strpos(implode('', $argv), 'nodemon') !== false;

        $bat = ROOT_PATH . 'start_win_' . $name . ($watch ? '_with_nodemon' : '') . '.cmd';
        $sh = ROOT_PATH . 'start_linux_' . $name . ($watch ? '_with_nodemon' : '') . '.sh';


        $files = glob(ROOT_PATH . 'server' . DIRECTORY_SEPARATOR . 'start*.php');

        $SERVER_PATH = ROOT_PATH . 'server';
        $str = '';


        foreach ($files as $file) {
            if (is_file($file)) {
                $str .= '   ' . $file;
            }
        }

        $command = "";
        $linux_command = '#!/bin/bash ' . PHP_EOL;

        if ($watch) {
            //$command = 'php ' . $SERVER_PATH . DIRECTORY_SEPARATOR . 'linux_server.php start > run.log  '. PHP_EOL;
            $command .= 'nodemon  -w "' . dirname($SERVER_PATH, 2) . DIRECTORY_SEPARATOR . '*" -i "' . $SERVER_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . '**' . '" -e "php" -x "';
            $linux_command .= $command . 'php ' . $SERVER_PATH . DIRECTORY_SEPARATOR . 'linux_server.php restart;';
        } else {
            $linux_command .= $command . 'php ' . $SERVER_PATH . DIRECTORY_SEPARATOR . 'linux_server.php start';
        }


        $command .= 'php ' . $str . ' start';

        $linux_command .= ' ' . ($watch ? '"' : '') . PHP_EOL;
        $command .= ' ' . ($watch ? '"' : '') . PHP_EOL;
        $command .= 'pause;';

        if (IS_WIN) {
            file_put_contents($bat, $command);
        } else {
            file_put_contents($sh, $linux_command);
        }
    }

    /**
     * @param string $dir 初始化项目目录
     * @return void
     */
    protected static function init_app(string $dir, array $depends = [])
    {
        //创建APP目录
        self::_mkdir(ROOT_PATH . 'apps' . DIRECTORY_SEPARATOR . $dir, true);

        $from_dir = ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . '__template__';

        $to_dir = ROOT_PATH . 'apps' . DIRECTORY_SEPARATOR . $dir;

        self::copy_dir($from_dir, $to_dir);

        self::replaceTemplateStr($to_dir, $dir);

        // 更新 app.json 文件
        $appJsonPath = $to_dir . DIRECTORY_SEPARATOR . 'app.json';
        if (file_exists($appJsonPath)) {
            $appConfig = json_decode(file_get_contents($appJsonPath), true);
            $appConfig['depends'] = $depends;
            file_put_contents($appJsonPath, json_encode($appConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        self::deldir(APP_PATH . $dir . DIRECTORY_SEPARATOR . 'client_service');

        // 生成所需的启动文件
        self::initCliFiles();
    }

    /**
     * 处理命名空间替换
     * @param $path
     * @param $app
     * @return void
     */
    protected static function replaceTemplateStr($path, $app)
    {
        $files = array_merge(
            glob($path . DIRECTORY_SEPARATOR . '*.php'),
            glob($path . DIRECTORY_SEPARATOR . '*.json'),
            glob($path . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php')
        );
        if ($files) {
            array_map(function ($file) use ($app) {
                if (is_file($file) && file_exists($file)) {
                    file_put_contents($file, str_replace('__TEMPLATE__', $app, file_get_contents($file)));
                }
            }, $files);
        }
    }

    /**
     * 创建文件夹
     * @param      $path
     * @param bool $real_path
     */
    protected static function _mkdir($path, bool $real_path = false)
    {
        $path = $real_path ? $path : ROOT_PATH . 'apps' . $path;
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }

    static function deldir($path)
    {
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描文件夹内的所有文件夹和文件并返回数组
            $data = scandir($path);
            // todo 赋予文件夹权限
            chmod($path, 0777);
            foreach ($data as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    // 1,如果是目录则递归子目录，继续操作
                    if (is_dir($path . '/' . $val)) {
                        // 2,子目录中操作删除文件夹和文件
                        self::deldir($path . '/' . $val . '/');
                        // 3,目录清空后删除空文件夹
                        @rmdir($path . '/' . $val . '/');
                    } else {
                        // 4,如果是文件直接删除
                        unlink($path . '/' . $val);
                    }
                }
            }

            is_dir($path) && @rmdir($path);
        }
    }

    public static function initApp(string $appName, array $depends)
    {
        define('INIT_APP', 1);
        self::init_app($appName, $depends);
        echo "应用 {$appName} 已创建,依赖项已设置。\n";
    }

    protected static function updateAppConfigs($onlyProject)
    {
        $appDirs = glob(APP_PATH . '*', GLOB_ONLYDIR);
        
        foreach ($appDirs as $appDir) {
            $appName = basename($appDir);
            $jsonPath = $appDir . DIRECTORY_SEPARATOR . 'app.json';
            
            if (file_exists($jsonPath)) {
                $appConfig = json_decode(file_get_contents($jsonPath), true);
                $appConfig['enabled'] = ($appName === $onlyProject);
                file_put_contents($jsonPath, json_encode($appConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
