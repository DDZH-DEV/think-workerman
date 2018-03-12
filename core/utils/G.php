<?php

namespace utils;


/**
 * 全局变量共享类
 * @package utils
 * @Author: zaoyongvip@gmail.com
 */
class G
{

    //mvc会释放的全局变量
    protected $_global_release = [];


    //mvc后不会释放的数据
    protected $_global_no_release=[];



    static $instance;


    public static function instance()
    {
        if(!self::$instance){
            self::$instance=new self();
        }
        return self::$instance;
    }



    public static function get($name,$long=false){
        $instance=self::instance();
        return $long!=='_G'?@$instance->_global_release[$name]:@$instance->_global_no_release[$name];
    }




    public static function set($name,$value,$long=false){

        if($long){
            self::instance()->_global_no_release[$name]=$value;
        }else{
            self::instance()->_global_release[$name]=$value;
        }

    }


    /**
     * 获取所有全局数据
     * @return array
     * @Author: zaoyongvip@gmail.com
     */
    public static function all()
    {
        return array_merge(self::instance()->_global_release,self::instance()->_global_no_release);
    }


    public static function clear()
    {
        self::instance()->_global_release = [];
        return null;
    }

}