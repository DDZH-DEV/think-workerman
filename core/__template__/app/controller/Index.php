<?php

namespace app\controller;

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