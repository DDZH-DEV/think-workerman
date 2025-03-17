<?php

namespace system;

use think\facade\Log;

class Debug
{
    /**
     * 发送日志
     * @param string|array $message
     * @param string $level
     * @param string $listen
     * @param bool $write
     * @return bool
     */
    static function slog($message = '', $level = '', $listen = '')
    {

        $conf=config('socket_log');

        if(!$conf || !$conf['enable']){
            return true;
        }

        if (!$message || !$conf['enable']) {
            return true;
        }
        $address = ($listen?:$conf['client']);

        $console = false;
        if (strpos($level, '@') > 1) {
            $params = explode('@', $level);
            $level = $params[0];
            $console = true;
        }
        $logs[]=array(
            'type' => $level,
            'msg' => $message,
            'css' => ''
        );

        $server=g('SERVER');
        //p($server);
        array_unshift($logs, array(
            'type' => 'group',
            'msg' => $server['HTTP_HOST'].$server['REQUEST_URI'],
            'css' => 'color:#40e2ff;background:#171717;'
        ));

        if (config('socket_log.show_included_files')) {
            $logs[] = array(
                'type' => 'groupCollapsed',
                'msg' => 'included_files',
                'css' => ''
            );
            $logs[] = array(
                'type' => 'log',
                'msg' => implode("\n", get_included_files()),
                'css' => ''
            );
            $logs[] = array(
                'type' => 'groupEnd',
                'msg' => '',
                'css' => '',
            );
        }

        $logs[] = array(
            'type' => 'groupEnd',
            'msg' => '',
            'css' => '',
        );
        $content = array(
            'client_id' => $address,
            'logs' => $logs,
            'level' => $level,
            'console' => $console
        );
        static $Curl, $_flag;
        $url = $conf['server'] . $address;
        $Curl = $Curl ? $Curl : curl_init();
        if ($_flag == 1) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($content, JSON_UNESCAPED_UNICODE));
            $response=curl_exec($Curl);
            return $response;
        } else {
            curl_setopt($Curl, CURLOPT_URL, $url);
            curl_setopt($Curl, CURLOPT_POST, true);
            curl_setopt($Curl, CURLOPT_POSTFIELDS, json_encode($content, JSON_UNESCAPED_UNICODE));
            curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($Curl, CURLOPT_TIMEOUT, 3);
            $headers = array(
                "Content-Type: application/json;charset=UTF-8"
            );
            curl_setopt($Curl, CURLOPT_HTTPHEADER, $headers);//设置header
            $response=curl_exec($Curl);
            $_flag = 1;
            return $response;
        }


    }


    /**
     * Uncaught exception handler.
     */
    static function log_exception($e, $type = 'exception')
    {
        if (strpos($e->getMessage(), 'stream_select') === false && $e->getMessage()!=='jump_exit' ) {
            $message = "\r\nMessage: {$e->getMessage()}; \r\nFile: {$e->getFile()} => Line: {$e->getLine()};" . "\r\n" . $e->getTraceAsString();
            !IS_CLI && APP_DEBUG && p($message);
            Log::write($message . PHP_EOL, $type);
        }
    }


    /**
     * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
     */
    static function check_for_fatal()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log_error($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    /**
     * Error handler, passes flow over the exception logger with new ErrorException.
     */
    static function log_error($num, $str, $file, $line)
    {
        self::log_exception(new \ErrorException($str, 0, $num, $file, $line));
    }

}