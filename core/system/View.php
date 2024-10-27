<?php

namespace system;

use think\Facade;

/**
 * @method static assign($var, $value = null)
 * @method static load
 * @method static display($file_name, $returnpath = false)
 * @method static set_templates_path($dir)
 * @method static release()
 * return system\Qstyle
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
        return 'system\Qstyle';
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

        // 如果需要重置 Qstyle 实例的某些属性,可以在这里进行
        // 例如: static::$instance->resetSomeProperties();
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
            static::$instance = new Qstyle();
        }

        return static::$instance;
    }
}