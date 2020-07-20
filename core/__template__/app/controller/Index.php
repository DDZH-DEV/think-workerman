<?php

namespace app\index\controller;

class Index{


    /**
     * index
     */
    function  index(){
        p(session());
        p(cookie());
        p(input());
    }

}