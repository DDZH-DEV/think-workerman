# think-workerman

 已经兼容了windows下workerman 3.* 和linux下 workerman 4.*的webserver,可以一套代码两端使用,同时加入了自己基于宝塔防火墙规则json写的简单的防注入防火墙。

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
#workerman全家桶,linux下请去除"-for-win"
"workerman/workerman-for-win": "*",
"workerman/gateway-worker-for-win":"*",
"workerman/gatewayclient":"*",

#thinkphp官方包
"topthink/think-orm":"^2.0",
"topthink/think-cache":"^2.0",
"topthink/think-log":"^2.0",

#队列包，使用redis服务
"rybakit/phive-queue":"*",  #配合快捷函数 addToQueue($type,$data,$callback=null);使用
```


## 初始化流程



1.拉取代码
``` git clone https://github.com/9raxdev/think-workerman.git ```

2.安装依赖(请确认你的环境,如果你是在linux下使用请将composer.json中workerman中的依赖包中的-for-win去掉)
composer update

linux下的composer.json
```json 
"workerman/workerman": "*",  //workerman 4.*版本
"workerman/gateway-worker":"*",
"workerman/gatewayclient":"*", 
```

windows下的composer.json
```json 
"workerman/workerman-for-win": "*",  //workerman 3.*版本
"workerman/gateway-worker-for-win":"*",
"workerman/gatewayclient":"*", 
```

3.根据你的项目修改配置文件 apps.config.php


4.点击dev_init_app.cmd或者执行```php init_app.php```  ,会自动根据配置生成对应的项目目录和windows与linux启动文件。 

```html
如:

'rax_im'  => ['app',['http','socket','queue','timer']]  #会生成一个rax_im开发目录和四个启动文件

start_linux_rax_im.sh
start_linux_rax_im_with_nodemon.sh
start_win_rax_im.cmd
start_win_rax_im_with_nodemon.cmd

其中with_nodemon 的启动文件基于nodejs以及nodemon,用于修改代码后自动重启

```
其中['http','socket','queue','timer']代表四种功能,需要哪种就加哪种进去。 
 
5.linux下运行方式为 ```php 你的应用目录/client_service/linux_server.php start -d```

## 目录介绍
```html
----core
    core/_template_      app默认模板文件，项目开发中不要修改这里面的文件，请修根据配置文件生成的目录中的文件
    core/utils           常用的类，你也可以将你自己的类放入此目录下，命名空间是utils
    core/GlobalData      workerman多进程共享数据
    core/rax             自己写的,目前只放了防火墙,规则不定期更新,进QQ群 940586873
    core/functions.php   常用助手函数，强烈看一眼这里面的文件，看有哪些方法
    core/WebServer3.php  workerman 3.*的简单 WebServer
    core/WebServer4.php  workerman 4.*的简单 WebServer
```
    
## 附录：nodemon（文件监控软件）安装方式 （用于windows下修改代码后自动重启服务）
第一步：```安装nodejs```

第二步：```npm install nodemon -g ```

## 附录：一些说明  
1.静态文件（js,image,css）建议走传统的nginx 或者apache 80端口进行分发

2.一个应用目录可以开发多个模块，core下面的代码是公用的，可以开发多个应用，多个应用目录时请记得修改各自应用下的Config.php文件，避免端口冲突

3.项目中队列用到了redis,所以如果有队列服务请开启redis

4.composer.json里面的包有些未必用得上,可自行删除精简某些不需要的依赖包

5.如有问题可以加群 [点击链接加入群聊【workerman第三方交流群】](https://jq.qq.com/?_wv=1027&k=5r3f8q0)

## 项目demo
1. [RaxChat](http://chat.wsxhr.com)     think-workerman的前身,先做了这个项目，后面慢慢总结才有了think-workerman

2. [Uhao帐号免密共享插件服务端](http://9rax.wsxhr.com/works/uhao.html) 一款可以进行帐号共享的谷歌插件，以前有TP做的后端，后面独立出来，用此作为后端