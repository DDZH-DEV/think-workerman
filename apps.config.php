<?php
return [
    #应用标识名与目录 => [
    #			用到的功能｛目前有http,socket,queue,timer,cron,data｝ ,
    #           绑定的域名
    #		]
    #每个应用有自己的Config.php 配置文件，多应用时，端口分配要留意，切记！！！
    #注意：如果你的项目中用到queue队列功能，对应项目中的Config.php中的缓存类型请修改为memcache或者redis,并且要有相应的服务
    'socketlog'  => [['http','socket']],  //只需要用到HTTP和定时器两种功能
];