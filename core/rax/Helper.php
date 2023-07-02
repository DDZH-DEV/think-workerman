<?php

namespace rax;

class Helper
{

    /**
     * 发送日志
     * @param string $message
     * @param string $level
     * @param string $listen
     * @param bool $write
     * @return bool
     */
    static function slog($message = '', $level = 'log', $listen = 'slog')
    {
        $conf=config('socket_log');

        if (!$message || !$conf['enable']) {
            return true;
        }
        $address = '/' . ($listen?:$conf['']);

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
        $url = $conf['server'] . $address;
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
            curl_setopt($Curl, CURLOPT_TIMEOUT, 3);
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
     */
    static function copy_dir($src, $dst)
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

    /**
     * 清空文件夹函数和清空文件夹后删除空文件夹函数的处理
     * @param $path
     * @return void
     */
    static function deldir($path)
    {
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $data = scandir($path);
            // todo 赋予文件夹权限
            chmod($path, 0777);
            foreach ($data as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    // 1,如果是目录则递归子目录，继续操作
                    if (is_dir($path . '/' . $val)) {
                        // 2,子目录中操作删除文件夹和文件
                        deldir($path . '/' . $val . '/');
                        // 3,目录清空后删除空文件夹
                        @rmdir($path . '/' . $val . '/');
                    } else {
                        // 4,如果是文件直接删除
                        unlink($path . '/' . $val);
                    }
                }
            }
        }
    }

}