# think-workerman


不需要在考虑workerman 3.*的问题了,WEBSERVER已经兼容CLI模式和FPM模式,目前个人自己完全抛弃tp了,只用他的包,个人习惯包依赖越少越好,纯自用,已经支持视图MVC,路由,http,socket,queue,timer,cron,data几项功能个人习惯http业务走FPM

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

由于数据库层，日志，缓存用的都是TP官方的包，所以开发形式上是差不多的，TP用户可以快速过度，相关演示请查看Demo.php。

## 所依赖的包
```text 
#thinkphp官方包
"topthink/think-orm":"^2.0",
"topthink/think-cache":"^2.0",
"topthink/think-log":"^2.0",
"topthink/think-validate": "^2.0"

#队列包，使用redis服务
"bardoqi/php-hive-queue":"*",  #配合快捷函数 addToQueue($type,$data,$callback=null);使用 
"jbzoo/event": "^4.0.1" #事件包,hook钩子依赖
```


## 初始化流程

1.拉取代码
``` git clone https://github.com/9raxdev/think-workerman.git ```

2.安装依赖composer.json(最好知道自己需要什么,里面的包并不一定都是必要的)
```text
composer update
```
以下包时不一定需要的
```text
"topthink/think-validate": "^2.0", #验证器
"wikimedia/minify": "*",    #JS css压缩
"gregwar/captcha": "*",     #验证码
"matomo/device-detector": "^6.1",
"zoujingli/wechat-developer": "^1.2" #公众号开发
``` 

3.根据你的项目修改配置文件 apps.config.php


4.点击dev_init_app.cmd或者执行```php public/default.php ```  ,会自动根据配置生成对应的项目目录和windows与linux启动文件。 

 
 
5.linux下运行方式为 ```php ./server/linux_server.php start ```


