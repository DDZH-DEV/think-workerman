<?php

namespace system;

use think\Facade;

/**
 * Class View
 * @package system
 * @method static void assign($var, $value = null)
 * @method static void load()
 * @method static void display($file_name, $returnpath = false)
 * @method static void set_templates_path(string $dir)
 * @method static void release()
 * @see \system\Qstyle
 */
class View extends Facade
{
    protected static $instance;

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     */
    protected static function getFacadeClass()
    {
        return 'system\View';
    }

    /**
     * 释放 Qstyle 实例
     */
    public static function release()
    {
        // 清除静态实例
        static::$instance = null;

        // 重新创建一个新的 Qstyle 实例
        static::$instance = new Qstyle();
    }

    protected static function getFacadeAccessor()
    {
        return static::getFacadeClass();
    }

    /**
     * 重写 resolveFacadeInstance 方法
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (!isset(static::$instance)) {
            static::$instance = app($name);
        }

        return static::$instance;
    }
}