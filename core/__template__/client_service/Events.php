<?php
use \GatewayWorker\Lib\Gateway;
use rax\RaxWaf;
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    public static function onConnect($client_id)
    {
        echo \utils\Console::success('[Connect]:'.$client_id);
    }



   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $data) {
       global $global;

       $message = json_decode($data, true); 

       //判断防火墙是否开启
       if(Config::$waf['enable']){
           $ip=ip2long($_SERVER['REMOTE_ADDR']);
           //判断是否在防火墙中
           if(isset($global->deny_ips[$ip])){
               return Gateway::sendToClient($client_id, RaxWaf::$config['deny_message'].' deny ip !');
           }
           $deny=RaxWaf::check($_SERVER['REMOTE_ADDR'],$message?:$data,'websocket');
           if($deny){
               $waf_ip_key='rax_waf_ip_'.$ip;
               $hit_num = $global->$waf_ip_key?:0;
               $hit_num++;
               $global->$waf_ip_key=$hit_num;
               if($hit_num>Config::$waf['deny_num']){
                   $deny_ips=$global->deny_ips?:[];
                   $deny_ips[$ip]=$hit_num;
                   $global->deny_ips=$deny_ips;
                   RaxWaf::saveDenyIps($deny_ips);
               }
               return Gateway::sendToClient($client_id, RaxWaf::$config['deny_message']);
           }
       }

       $message_type = $message['type'];

       switch($message_type) {
           case 'init':
               // 通知当前客户端初始化
               $init_message = array(
                   'message_type' => 'init',
                   'client_id'    => $client_id,
               );
               return Gateway::sendToClient($client_id, json_encode($init_message));
           case 'ping':
               return Gateway::sendToClient($client_id, json_encode(['message_type' => 'ping','client_id'    => $client_id,]));
           default:
               console($data);
       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose() {
       // 有可能多页面登录，没有全部下线
        
   }


}

