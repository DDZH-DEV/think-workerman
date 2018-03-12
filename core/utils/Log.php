<?php

namespace utils;
/**
 * Class Log
 * @package utils
 * @method static info|error|notice|info|debug
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


    public static function __callStatic($name, $arguments)
    {
        if(method_exists(self::instance(),$name)){
            self::$instance->$name($arguments);
        }
    }
}