<?php

namespace app\index\controller;

use GatewayClient\Gateway;
use think\Db;
 

class Index{

    function session(){
        p(session(''));
    }


    function  index(){
    	p()
        echo "hello !";
    }

}