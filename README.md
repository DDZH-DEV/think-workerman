#think-workerman

此项目是个人的项目总结，只是为了能快速开发异步api接口和一些socket应用而整理,目前本人的项目一般是不注重后台与SEO(api+vue提供业务,thinkadmin 快速开发后台) 一套的形式，所以目前未接入模板引擎，有兴趣者自行接入第三方引擎。

由于数据库层，日志，缓存用的都是TP官方的包，所以开发形式上是差不多的，TP用户可以快速过度，相关演示请查看Demo.php。

##与传统TP开发的区别点
*****
    传统的TP从入口到控制器,到数据层每次都要初始化各种变量，加载各种文件，每次请求都要损耗部分性能，而基于workerman的webserver 所需要加载的文件在onWorkerStart前就已经注入命名空间， 所以文件在onMessage一般只会加载一次，属于常驻内存，性能更高，请自行进行压力测试。

##初始化流程 
    拉取代码
    git clone https://github.com/Zsoner/think-workerman.git
*****
    安装依赖
    composer update
*****
    根据你的项目修改配置文件 apps.config.php
*****

    点击dev_init_app.cmd或者dev_init_app_with_nodemon.cmd ,会自动根据配置生成对应的项目目录和启动文件，两个文件的区别时 dev_init_app_with_nodemon.cmd 依赖于nodemon,在windows下如果你希望每次改完代码自动重启服务，请使用它。 
##目录介绍
    ----core
        core/_app_  app默认模板文件，项目开发中不要修改这里面的文件，请修根据配置文件生成的目录中的文件
        core/utils  一些个人常用的类，你也可以将你自己的类放入此目录下，命名空间是utils
        core/wechat-php-sdk  微信sdk,基于https://github.com/zoujingli/wechat-php-sdk有修改做兼容处理 
        core/functions.php  常用助手函数，强烈看一眼这里面的文件，看有哪些方法
        core/WebServer.php  这里面交代了怎么样走入控制器的


##附录：nodemon（文件监控软件）安装方式 
    第一步：安装nodejs
*****
    第二步：npm install nodemon -g 
##附录：一些说明  
    1.静态文件（js,image,css）建议走传统的nginx 或者apache 80端口进行分发
    2.如有问题可以加群 [点击链接加入群聊【workerman第三方交流群】](https://jq.qq.com/?_wv=1027&k=5r3f8q0)