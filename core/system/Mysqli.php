<?php
namespace system;

use MysqliDb;
use Exception;
use think\Facade;
class Mysqli extends Facade { 
    public static $mysqli = null;
    public static $config = [];
  

    public static function setConfig($config) {   
        self::$config = $config['connections']['mysql'];  
    }

    public static function init() {
        if (self::$mysqli !== null) {
            return self::$mysqli;
        }
 
        
        if (empty(self::$config)) {
            throw new Exception('Database configuration not set');
        }

        try {
            self::$mysqli = new MysqliDb(
                self::$config['hostname'] ?? 'localhost',
                self::$config['username'] ?? '',
                self::$config['password'] ?? '',
                self::$config['database'] ?? '',
                self::$config['port'] ?? 3306,
                self::$config['charset'] ?? 'utf8mb4'
            );

            if (isset(self::$config['prefix']) && !empty(self::$config['prefix'])) {
                self::$mysqli->setPrefix(self::$config['prefix']);
            }

        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }

        return self::$mysqli;
    }

    public function __call($name, $arguments) {
        if (self::$mysqli === null) {
            self::init();
        }
        return call_user_func_array([self::$mysqli, $name], $arguments);
    }

    public static function __callStatic($name, $arguments) {
 
        return call_user_func_array([self::instance()->init(), $name], $arguments);
    }
}
