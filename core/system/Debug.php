<?php

namespace system;

use think\facade\Log;

class Debug
{
    function slog()
    {

    }


    /**
     * Uncaught exception handler.
     */
    static function log_exception($e, $type = 'exception')
    {
        if (strpos($e->getMessage(), 'stream_select') === false && $e->getMessage()!=='jump_exit' ) {
            $message = "\r\nMessage: {$e->getMessage()}; \r\nFile: {$e->getFile()} => Line: {$e->getLine()};" . "\r\n" . $e->getTraceAsString();
            !IS_CLI && g('DEBUG') && p($message);
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