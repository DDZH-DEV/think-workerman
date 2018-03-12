<?php

namespace utils\sms;


class YunPian {

    protected $apikey='';

    protected $result;

    protected $error;

    protected $url='http://sms.yunpian.com/v2/sms/single_send.json';



    public function __construct($config)
    {
        $this->apikey=$config['apikey'];
    }


    /**
     * 发送验证码
     * @param $mobile
     * @param $code
     * @param $content
     * @return bool
     * @Author: zaoyongvip@gmail.com
     */
    public function send($mobile,$code,$content){

        $content=$content?$content:'您的验证码是'.$code;

        $data=[
            'apikey'=>$this->apikey,
            'mobile'=>$mobile,
            'text'=>$content
        ];

        $data=http_build_query($data);

        $this->result = _post($this->url,$data);

        $res=json_decode($this->result,true);

        if(isset($res['code']) && $res['code']==0){
            return true;
        }elseif(isset($res['msg'])){
            $this->error=$res['msg'];
        }

        return false;
    }



    public function getResult(){
        return $this->result;
    }


    public function getError(){
        return $this->error;
    }


}