<?php

namespace system;


/**
 * 全局变量共享类
 * @package utils
 */
class G
{

    //mvc会释放的全局变量
    static $instance;


    //mvc后不会释放的数据
    protected $_global_release = [];
    protected $_global_no_release = [];

    public static function get($name, $long = false)
    {
        $instance = self::instance();
        return $long !== 'G' ?
            ($instance->_global_release[$name] ?? null) :
            ($instance->_global_no_release[$name] ?? null);
    }

    public static function instance(): G
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function set($name, $value, $long = false)
    {

        if ($long) {
            self::instance()->_global_no_release[$name] = $value;
        } else {
            self::instance()->_global_release[$name] = $value;
        }

    }


    /**
     * 获取所有全局数据
     * @return array
     */
    public static function all()
    {
        return array_merge(self::instance()->_global_release, self::instance()->_global_no_release);
    }


    public static function clear()
    {
        self::instance()->_global_release = [];
        return null;
    }

}