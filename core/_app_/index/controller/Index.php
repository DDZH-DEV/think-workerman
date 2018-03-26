<?php

namespace app\index\controller;

class Index{


    /**
     * index
     */
    function  index(){
        p(session(''));
        p(get_included_files());
        p(convert(true));
    }

}