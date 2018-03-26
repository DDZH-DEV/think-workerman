<?php
namespace utils;


class Queue {

    private $_handler = null;
    private $_maxqueue = 1000000;//消息队列最大存储量

    public static $instance=null;

    /**
     *
     * 构造函数
     * @param handler $handler
     */
    public function __construct($handler){
        $this->_handler = $handler;
    }


    public static function instance($handler=null){

        if(self::$instance && !$handler){
            return self::$instance;
        }else{

            if(!$handler){

                $class=  ucfirst(\Config::$cache['type']);


                /**
                 * @var $handler \Memcache
                 */
                $handler= new $class();

                $handler->connect(\Config::$cache['host'],\Config::$cache['port']);
            }

            self::$instance=new self($handler);

            return self::$instance;
        }
    }



    /**
     *
     * 添加到消息队列
     * @param String $input_name
     * @param String $value
     */
    public function put($input_name, $value){
        $queue_putpos = $this->_now_putpos($input_name);
        $key = $input_name . ":" . $queue_putpos;
        if($queue_putpos){
            return $this->_handler->set($key, $value, 0);
        }
        return false;
    }

    /**
     *
     * 从消息队列中取数据
     * @param String $input_name
     */
    public function get($input_name){
        $queue_getpos = $this->_now_getpos($input_name);
        if($queue_getpos == 0){
            return false;
        }
        $key = $input_name . ":" . $queue_getpos;
        $result = $this->_handler->get($key);
        $this->_handler->delete($key);
        return $result;
    }

    /**
     *
     * 查看消息队列中的某个点的消息
     * @param String $input_name
     * @param Int $pos
     */
    public function view($input_name, $pos){
        $queue_name =  $input_name . ":"  .$pos;
        return $this->_handler->get($queue_name);
    }

    /**
     *
     * 查看消息队列状态
     * @param String $input_name 消息队列名称
     * @return
     */
    public function status($input_name){
        $maxqueue_num = $this->_read_maxqueue($input_name);
        $queue_putpos = $this->_read_putpos($input_name);
        $queue_getpos = $this->_read_getpos($input_name);

        if($queue_putpos > $queue_getpos){
            $unget = abs($queue_putpos - $queue_getpos);
        }else if($queue_putpos < $queue_getpos){
            $unget = abs($this->_maxqueue - $queue_getpos + $queue_putpos);
        }else{
            $unget = 0;
        }
        return array('maxqueue_num'=>$maxqueue_num, 'queue_putpos'=>$queue_putpos, 'queue_getpos'=>$queue_getpos, 'unget'=>$unget);
    }

    /**
     *
     * 重置消息队列
     * @param String $input_name
     */
    public function reset($input_name){
        $this->_handler->delete("putpos:".$input_name);
        $this->_handler->delete("getpos:".$input_name);
        $this->_handler->delete("maxqueue:".$input_name);
    }

    /**
     *
     * 设置消息队列的存储数
     * @param Int $maxqueue
     */
    public function maxqueue($maxqueue){
        $this->_maxqueue = intval( $maxqueue );
    }

    /**
     *
     * 获取当前入消息队列位置
     * @param String $input_name 队列名称
     */
    private function _now_putpos($input_name){
        $maxqueue_num = $this->_read_maxqueue($input_name);
        $queue_putpos = $this->_read_putpos($input_name);
        $queue_getpos = $this->_read_getpos($input_name);

        $queue_name = "putpos:".$input_name;
        //队列写入位置点加1
        $queue_putpos +=1;
        //如果队列写入ID+1之后追上队列读取ID，则说明队列已满，返回0，拒绝继续写入
        if ($queue_putpos == $queue_getpos) {
            $queue_putpos = 0;
            //如果队列写入ID大于最大队列数量，并且从未进行过出队列操作（=0）或进行过1次出队列操作（=1），返回0，拒绝继续写入
        }else if($queue_getpos <=1 && $queue_putpos > $this->_maxqueue){
            $queue_putpos = 0;
            //如果队列写入ID大于最大队列数量，则重置队列写入位置点的值为1
        }else if($queue_putpos > $this->_maxqueue){
            $queue_putpos = 1;
            $this->_handler->set($queue_name, $queue_putpos, 0);
            //队列写入位置点加1后的值，回写入数据库
        }else{
            $this->_handler->set($queue_name, $queue_putpos, 0);
        }
        return $queue_putpos;
    }

    /**
     *
     * 获取当前出消息队列位置
     * @param String $input_name
     */
    private function _now_getpos($input_name){
        $maxqueue_num = $this->_read_maxqueue($input_name);
        $queue_putpos = $this->_read_putpos($input_name);
        $queue_getpos = $this->_read_getpos($input_name);

        $queue_name = "getpos:".$input_name;
        //如果queue_get_value的值不存在，重置队列读取位置点为1
        if($queue_getpos == 0 && $queue_putpos > 0){
            $queue_getpos = 1;
            $this->_handler->set($queue_name, $queue_getpos, 0);
            //如果队列的读取值（出队列）小于队列的写入值（入队列） */
        }else if($queue_getpos < $queue_putpos){
            $queue_getpos+=1;
            $this->_handler->set($queue_name, $queue_getpos, 0);
            //如果队列的读取值（出队列）大于队列的写入值（入队列），并且队列的读取值（出队列）小于最大队列数量
        }else if($queue_getpos > $queue_putpos && $queue_getpos < $this->_maxqueue){
            $queue_getpos+=1;
            $this->_handler->set($queue_name, $queue_getpos, 0);
            //如果队列的读取值（出队列）大于队列的写入值（入队列），并且队列的读取值（出队列）等于最大队列数量
        }else if($queue_getpos > $queue_putpos && $queue_getpos == $this->_maxqueue){
            $queue_getpos = 1;
            $this->_handler->set($queue_name, $queue_getpos, 0);
        }else{
            $queue_getpos = 0;
        }
        return $queue_getpos;
    }

    /**
     *
     * 获取入消息队列位置
     * @param unknown_type $input_name
     */
    private function _read_putpos($input_name){
        $pos = 0;
        $key = "putpos:" . $input_name;

        $temp = $this->_handler->get($key);
        if($temp){
            $pos = $temp;
        }
        return $pos;
    }

    /**
     *
     * 获取出消息队列位置
     * @param unknown_type $input_name
     */
    private function _read_getpos($input_name){
        $pos = 0;
        $key = "getpos:" . $input_name;
        $temp = $this->_handler->get($key);
        if($temp){
            $pos = $temp;
        }
        return $pos;
    }

    /**
     *
     * 获取消息写入最大存储量
     * @param String $input_name
     */
    private function _read_maxqueue($input_name){
        $pos = 0;
        $temp = $this->_handler->get("maxqueue:" . $input_name);
        if($temp){
            $pos = $temp;
        }else{
            $pos = $this->_maxqueue;
        }
        return $pos;
    }

}

?>