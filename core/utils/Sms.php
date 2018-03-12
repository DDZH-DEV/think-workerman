<?php

namespace utils;

use \Exception;
use think\Cache;
use utils\sms\YunPian;

class Sms{

     static $driver=null;

     static $error='';
    /**
     * getDriver
     * @param array $config
     * @return YunPian
     * @throws Exception
     * @Author: zaoyongvip@gmail.com
     */
     static function getDriver($config=[]){

         if(self::$driver) return self::$driver;

         $config=$config?$config:\Config::$sms;

         $class='utils\\sms\\'.$config['class'];

         if(!class_exists($class)){
             throw new Exception($class.' not exist !');
         }

         self::$driver=new $class($config['params']);

         return self::$driver;

     }


    /**
     * 发送短信
     * @param $mobile
     * @param $code
     * @param $content
     * @param string $type
     * @return bool
     * @Author: zaoyongvip@gmail.com
     */
     static function send($mobile,$code,$content,$type='REGISTER'){

         self::$error='';

         $key='sms_'.date('Ymd').$_SERVER['REMOTE_ADDR'];

         $num=Cache::get($key);


         if(\Config::$sms['day_limit'] && $num>=\Config::$sms['day_limit']){
             self::$error='当日限额已用完!';
             return false;
         }


         if(!Verify::isMobile($mobile)){
             self::$error='手机号码不正确!';
             return false;
         }

         if(!$code && !$content){
             self::$error='短信内容不能为空!';
             return false;
         }

         $res= self::getDriver()->send($mobile,$code,$content);


         $mime=session('user');

         //底层日志记录
         $log=[
             'mobile'=>$mobile,
             'content'=>$content,
             'type'=>$type,
             'code'=>$code,
             'result'=>$res?'success':'fail',
             'response'=>self::getDriver()->getResult(),
             'ip'=>$_SERVER['REMOTE_ADDR'],
             'uid'=>isset($mime['id'])?$mime['id']:0
         ];

         addToQueue('sms_log',$log);

         if($res===true){

            Cache::inc($key,1);
            return true;
         }else{
             self::$error=self::getDriver()->getError();
         }

         return false;

     }



}