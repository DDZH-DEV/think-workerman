<?php

//初始化数据库
\think\facade\Db::setConfig(Config::$database);
//缓存设置
\think\facade\Cache::config(Config::$cache);
//设置日志
\think\facade\Log::init(Config::$log);
