# think-workerman

此项目是个人的项目总结，只是为了能快速开发异步api接口和一些socket应用而整理,目前本人的项目一般是不注重后台与SEO(api+vue提供业务) 一套的形式，所以目前未接入模板引擎，有兴趣者自行接入第三方引擎。

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
"rybakit/phive-queue":"*",

#其余非必须依赖包,可以自行删除
"ahead4/captcha": "*", 
"intervention/image":"*"

```


## 初始化流程



1.拉取代码
``` git clone https://github.com/9raxdev/think-workerman.git ```

2.安装依赖(请确认你的环境,如果你是在linux下使用请将composer.json中workerman中的依赖包中的-for-win去掉)
composer update

3.根据你的项目修改配置文件 apps.config.php


4.点击dev_init_app.cmd或者dev_init_app_with_nodemon.cmd ,会自动根据配置生成对应的项目目录和启动文件，两个文件的区别时 dev_init_app_with_nodemon.cmd 依赖于nodemon,在windows下如果你希望每次改完代码自动重启服务，请使用它。 

```html
如'UhaoA'  => ['app',['http','socket','queue']]会生成一个app开发目录和一个start_windows_UhaoA.cmd启动文件，

在windows下点击start_windows_UhaoA.cmd即可启动进行运行演示
```
5.linux下运行方式为 ```php 你的应用目录/client_service/linux_server.php start -d```

## 目录介绍
```html
----core
    core/_app_  app默认模板文件，项目开发中不要修改这里面的文件，请修根据配置文件生成的目录中的文件
    core/utils  一些个人常用的类，你也可以将你自己的类放入此目录下，命名空间是utils（用不上可以删）
    core/wechat-php-sdk  微信sdk,基于https://github.com/zoujingli/wechat-php-sdk有修改做兼容处理 （用不上可以删）
    core/functions.php  常用助手函数，强烈看一眼这里面的文件，看有哪些方法
    core/WebServer.php  这里面交代了怎么样走入控制器的  
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