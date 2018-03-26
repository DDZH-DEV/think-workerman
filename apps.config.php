<?php

return [
    #应用标识名 => [
    #			目录｛应用开发目录,单个应用支持多个模块｝ ，
    #			用到的功能｛目前有http,socket,queue｝ ,
    #		]
    #每个应用有自己的Config.php 配置文件，多应用时，端口分配要留意，切记！！！		
    'UhaoA'  => ['app',['http']]
];