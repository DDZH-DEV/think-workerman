<?php

namespace utils;
/**
 * 日志类
 * @method static info($data)
 * @method static error($data)
 * @method static notice($data)
 * @method static debug($data)
 * @Author: zaoyongvip@gmail.com
 */
class Log{

    public static $instance;


    public static function instance(){
        if(!self::$instance){
            self::$instance=new \think\Log;
            self::$instance->init([
                'type'=>'file',
                'path'=>ROOT_PATH.DS.'runtime/logs/',
                'debug'=>true
            ]);
        }
        return self::$instance;
    }


    /**
     * __callStatic
     * @param $name
     * @param string $arguments
     * @Author: zaoyongvip@gmail.com
     */
    public static function __callStatic($name, $arguments='')
    {
        if(method_exists(self::instance(),$name)){
            self::$instance->$name($arguments);
        }
    }
}