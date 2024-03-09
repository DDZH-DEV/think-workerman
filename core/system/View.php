<?php

namespace system;

use think\Facade;

/**
 * @method static assign($var, $value = null)
 * @method static load
 * @method static display($file_name, $returnpath = false)
 * @method static set_templates_path($dir)
 * return system\Qstyle
 */
class View extends Facade
{

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     */
    protected static function getFacadeClass()
    {
        return 'system\Qstyle';
    }


}