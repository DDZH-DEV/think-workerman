# think-workerman

2024.10.27更新
使用workerman 5.0.0-beta.7,增加对应用的独立开启或者禁用
写了个热启动工具tw-watch,可以监听文件变化,自动重启,但保留nodemon生成脚本

```cmd
php twcli test                       //创建test应用
php twcli test --depends=cron,queue  //创建test应用,并依赖cron,queue两项功能 http,socket,queue,timer,cron,data
php twcli --only=test                //只启用test应用,其他应用不启用,其他应用的路由，定时器，websocket等都不会加载
php twcli                            //重新生成启动文件
```
 
目前个人自己完全抛弃tp了,只用他的几个包,个人习惯包依赖越少越好,纯自用,已经支持视图MVC,路由,http,socket,queue,timer,cron,data几项功能个人习惯http业务走FPM

***特别留意,如果要兼容两个平台不同的WebServer,在控制器里面接受参数时需要用快捷函数***

```php
#session操作
session('a','123');         //set session
$session_a=session('a');    //read session
session('a',null);          //delete session

#cookie操作
cookie('a','123');         //set cookie
$cookie_a=cookie('a');     //read cookie
cookie('a',null);          //delete cookie

#参数接收
$var_a=input('a');         //$_GET,$_POST,$_COOKIE中的某一项
$get_a=input('get.a');     //从workerman3.*$_GET中或者workerman4.* $request->get() 中取值
$post_a=input('post.a');   //获取post参数 


#自定义header  2021.04.08更新
_header("Content-type","text/html; charset=utf-8");
 
```

此项目是个人的项目总结，只是为了能快速开发异步api接口和一些socket应用而整理,只想用最少的包来开发自己想要的功能 , 所以没有多余的功能 ，想要完整的参考官方 https://github.com/walkor/webman 。

由于数据库层，日志，缓存用的都是TP官方的包，所以开发形式上是差不多的，TP用户可以快速过度，相关演示请查看/应用目录/index/index路径。

###tw-watch功能
一是判断应用目录是否包含view/static目录,如果有复制static目录到public/static目录
二是监听server目录,如果start_*.php,且应用目录下有修改,则重启

###伪静态
```php

location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}

```