<?php

namespace utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Dir
{
    /**
     * 删除文件夹
     *
     * @param string $dirname 目录
     * @param bool $withself 是否删除自身
     *
     * @return boolean
     */
    static function rm_dirs($dirname, $withself = true)
    {
        if (!is_dir($dirname))
            return false;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }


    /**
     * 复制文件夹
     *
     * @param string $source 源文件夹
     * @param string $dest 目标文件夹
     */
    static function copy_dirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }


    /**
     * 创建文件夹
     *
     * @param $dir
     *
     * @return bool
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/8/5 15:43
     */
    static function mkdir($dir)
    {
        return $dir && (is_dir($dir) || mkdir($dir, 0777, true));
    }


    /**
     * 扫描文件夹
     * @param        $dir
     * @param string $ext
     * @param bool $new
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/8/21 13:39
     */
    static function scan_dir($dir, $ext = '', $new = false)
    {
        static $arr;
        static $org_dir;

        $arr = $new ? [] : $arr;

        $org_dir = ($org_dir && !$new) ? $org_dir : $dir;

        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {

                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $file;

                $file = str_replace($org_dir, '', $path);

                if (is_dir($path)) {
                    self::scan_dir($path, $ext);
                } else {

                    if ($ext && strpos($file, $ext) === false) {
                        continue;
                    }

                    array_push($arr, ltrim(str_replace('\\', '/', $file), '/'));
                }

            }

        }
        return $arr;
    }


    /**
     * 判断文件或文件夹是否可写
     *
     * @param string $file 文件或目录
     *
     * @return    bool
     */
    static function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE) {
                return FALSE;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }
        fclose($fp);
        return TRUE;
    }

}