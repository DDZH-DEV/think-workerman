<?php

namespace system;

use Exception;
use GatewayWorker\Lib\Gateway;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Workerman\Protocols\Http\Session;
use system\Response;
use system\HttpException;

define("IS_LOW_WORKERMAN", version_compare(Worker::VERSION, '3.5.3', '<'));
class JumpException extends \Exception {
    /**
     * @var Response
     */
    public $response;

    public function __construct(Response $response)
    {
        parent::__construct();
        $this->response = $response;
    }
}
class WebServer {
    protected $worker;
    public static $debug = false;

    public function __construct($socket_name = '', array $context_option = array()) {
        if (PHP_SAPI === 'cli') {
            $this->worker = new Worker($socket_name, $context_option);
            $this->worker->onMessage = [$this, 'handleRequest'];
        }
    }

    public static function init() {
        static $init;
        if ($init) return;
        !defined('WEB_SERVER') && define('WEB_SERVER', 'true');
        self::loadAppsRouters();
        $init = true;

        if (file_exists(ROOT_PATH . 'server' . DIRECTORY_SEPARATOR . 'start_gateway.php')) {
            \GatewayClient\Gateway::$registerAddress = config('register.address');
            \GatewayWorker\Lib\Gateway::$registerAddress = config('register.address');
        }
    }

    private static function loadAppsRouters() {
        $cacheFile = RUNTIME_PATH . '/router_cache.php'; 
        if (!APP_DEBUG && file_exists($cacheFile)) {
            $routes = include $cacheFile;
        } else {
            $routes = [];
            
            foreach (glob(APP_PATH . '*', GLOB_ONLYDIR) as $dir) {
                // 检查 app.json 文件
                $app_json_file = $dir . '/app.json'; 
                if (file_exists($app_json_file)) {
                    $app_config = json_decode(file_get_contents($app_json_file), true); 
        
                    if (!isset($app_config['enable']) || !$app_config['enable']) {
                        continue; // 如果应用未启用,跳过此应用
                    }
                }

                $routerFile = $dir . '/router.php';

                if (file_exists($routerFile)) {
                    $appRoutes = include $routerFile;
                    $routes = array_merge($routes, $appRoutes);
                }
            }

            if (!APP_DEBUG) {
                file_put_contents($cacheFile, '<?php return ' . var_export($routes, true) . ';');
            }
        } 
        app('router')->addRoutes($routes);
    }

    public function handleRequest($connection, $request) {
        $this->initGlobals($connection, $request);

        $uri = $request->uri();
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $file = PUBLIC_PATH . ltrim($path, '/');

        if ($path === '/') {
            self::dispatchHttp($connection, $this, $request);
            return;
        }

        if (is_dir($file) && substr($path, -1) === '/') {
            $indexFile = rtrim($file, '/') . '/index.php';
            if (file_exists($indexFile)) {
                $file = $indexFile;
            }
        }
        if (is_file($file)) {

            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $this->executePhpFile($connection, $file, $request);
            } else {
                $this->handleFileRequest($connection, $file, $request);
            }
        } else {
            self::dispatchHttp($connection, $this, $request);
        }
    }

    protected function initGlobals($connection, $request) {
        if (PHP_SAPI === 'cli') {
            $_SERVER = array_merge($_SERVER, [
                'REQUEST_METHOD' => $request->method(),
                'REQUEST_URI' => $request->uri(),
                'QUERY_STRING' => $request->queryString(),
                'HTTP_HOST' => $request->host(),
                'HTTP_USER_AGENT' => $request->header('User-Agent'),
                'REMOTE_ADDR' => $connection->getRemoteIp(),
                'HTTP_X_REQUESTED_WITH' => $request->header('X-Requested-With'),
                'SERVER_PROTOCOL' => $request->protocolVersion(),
                'REQUEST_TIME' => time(),
                'REQUEST_TIME_FLOAT' => microtime(true),
                'HTTP_ACCEPT' => $request->header('Accept'),
                'HTTP_ACCEPT_LANGUAGE' => $request->header('Accept-Language'),
                'HTTP_ACCEPT_ENCODING' => $request->header('Accept-Encoding'),
                'HTTP_COOKIE' => $request->header('Cookie'),
                'HTTP_REFERER' => $request->header('Referer'),
                'CONTENT_TYPE' => $request->header('Content-Type'),
                'CONTENT_LENGTH' => $request->header('Content-Length'),
                'SCRIPT_NAME' => '/index.php',
                'SCRIPT_FILENAME' => $_SERVER['DOCUMENT_ROOT'] . '/index.php',
                'PATH_INFO' => $request->path(),
                'REMOTE_PORT' => $connection->getRemotePort(),
                'SERVER_NAME' => $request->host(),
                'SERVER_PORT' => $connection->getLocalPort(),
                'SERVER_ADDR' => $connection->getLocalIp(),
                'HTTPS' => ($connection->getLocalPort() == 443 || $request->header('X-Forwarded-Proto') === 'https') ? 'on' : 'off',
            ]);

            $_GET = $request->get();
            $_POST = $request->post();
            $_FILES = $request->file();
            $_COOKIE = $request->cookie();
            $_SESSION = $request->session()->all();
            $_REQUEST = array_merge($_GET, $_POST);
            // 确保session数据被正确初始化
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }

        $_SERVER = $_SERVER ?? [];
        $_GET = $_GET ?? [];
        $_POST = $_POST ?? [];
        $_FILES = $_FILES ?? [];
        $_COOKIE = $_COOKIE ?? [];
        $_SESSION = $_SESSION ?? [];

        g('SERVER', $_SERVER);
        g('IP', $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        g('POST', $_POST);
        g('RAW', file_get_contents('php://input'));
        g('GET', $_GET);
        g('FILES', $_FILES);
        g('SESSION', $_SESSION);
        g('COOKIE', $_COOKIE);
        g('REQUEST', $_REQUEST);
    }

    public static function dispatchHttp($connection = null, $object = null, $request = null) {
        self::init();

        $match = app('router')->match();
        $server = g('SERVER');

        g('IS_POST', $server['REQUEST_METHOD'] === 'POST');
        g('IS_GET', $server['REQUEST_METHOD'] === 'GET');
        g('IS_AJAX', isset($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        // 获取请求的 URI 并移除查询字符串
        $uri = $server['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
  
        // 检查是否是根目录或以 /index.php 开头
        if ($path === '/' || strpos($path, '/index.php') === 0) {
            // 根目录执行路由
            self::executeRoute($connection, $object, $request, $match);
            return;
        }
        
        // 文件直接输出
        $file = PUBLIC_PATH . ltrim($path, '/');


        if (file_exists($file) && is_file($file)) {
            $object = $object ?: new self();
            $object->handleFileRequest($connection, $file, $request);
            unset($object);
            return;
        }

        // 如果不是文件，执行路由
        self::executeRoute($connection, $object, $request, $match);
    }

    private static function executeRoute($connection, $object, $request, $match) {
        $class = $match['target'];
        if (class_exists($class) && method_exists($class, $match['action'])) {
            g('MODULE', $match['module']);
            g('CONTROLLER', $match['controller']);
            g('ACTION', $match['action']);
            g('IS_MOBILE', is_mobile(g('SERVER')['HTTP_USER_AGENT'] ?? ''));
            
            View::release();

            // 始终开启输出缓冲：避免模板/echo 直接发送导致 headers already sent
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ob_start();
            
            $response = null;
            try {
                // 执行控制器方法，并捕获其返回值
                $response = call_user_func_array([new $class, $match['action']], [$match['params'], $connection, $request]);
            } catch (\system\JumpException $e) {
                // 捕获为了提前终止流程的异常
                $response = $e->response;
            } catch (\Throwable $e) {
                // 捕获所有可抛出的错误 (包括Error和Exception)
                Debug::log_exception($e);

                $statusCode = property_exists($e, 'statusCode') ? (int)$e->statusCode : 500;
                if ($statusCode < 100 || $statusCode > 599) {
                    $statusCode = 500;
                }

                $isAjax = (bool)g('IS_AJAX');

                if ($isAjax) {
                    // AJAX：保持 JSON；调试模式下附带堆栈，便于前端/接口联调
                    $msg = APP_DEBUG ? ('服务器错误: ' . $e->getMessage()) : '服务器内部错误';
                    $debug = [];
                    if (APP_DEBUG) {
                        $debug = [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ];
                    }
                    $response = new Response(null, $statusCode, $msg, $debug);
                } else {
                    // 普通页面请求：输出完整 HTML（调试模式下含 getTraceAsString），避免浏览器只看到一段 JSON
                    if (APP_DEBUG) {
                        $esc = static function ($s) {
                            return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        };
                        $body = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
                            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                            . '<title>Application Error</title></head><body style="font-family:system-ui,sans-serif;padding:16px;">'
                            . '<h1 style="margin:0 0 12px;">服务器错误</h1>'
                            . '<p style="margin:0 0 8px;"><strong>' . $esc($e->getMessage()) . '</strong></p>'
                            . '<p style="color:#666;font-size:14px;margin:0 0 16px;">' . $esc($e->getFile()) . ':' . (int)$e->getLine() . '</p>'
                            . '<pre style="background:#f6f8fa;border:1px solid #ddd;border-radius:6px;padding:12px;overflow:auto;white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.45;">'
                            . $esc($e->getTraceAsString())
                            . '</pre></body></html>';
                    } else {
                        $body = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><title>Error</title></head><body>'
                            . '<p>服务器内部错误</p></body></html>';
                    }
                    $response = (new Response($body, $statusCode, ''))
                        ->asRaw()
                        ->withHeader('Content-Type', 'text/html; charset=utf-8');
                }
            }
            
            // 将控制器返回的结果（可能是Response对象或旧的输出）传递给response方法
            self::response($connection, $request, $response);
            
        } else {
            $message = strpos(g('SERVER')['REQUEST_URI'], '.php') !== false ?
                '当前请求的[' . basename(g('SERVER')['REQUEST_URI']) . '] 文件不存在!' :
                '当前请求的[' . $class . '::' . $match['action'] . '] 方法不存在!';

            if (IS_CLI) {
                $response = new WorkermanResponse(404, [], $message);
                $connection->send($response);
                return;
            }

            header("HTTP/1.1 404 Not Found");
            echo $message;
        }
    }

    protected static function response($connection, $request, $controllerResponse = null) {
        // 优先处理从控制器返回的Response对象
        if ($controllerResponse instanceof Response) {
            // 丢弃旧的输出缓冲，避免和 Response 重复输出/提前输出冲突
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $content = $controllerResponse->getBody();
            $statusCode = $controllerResponse->getStatusCode();
            $headers = $controllerResponse->headers;
        } else {
            // 兼容旧的输出模式，从ob缓冲和全局变量中获取
            if (ob_get_level() > 0) {
                $content = ob_get_clean();
            } else {
                $content = '';
            }

            // 兼容“直接 return 字符串内容”的控制器：仅当没有任何输出时才使用返回值作为内容
            if ($content === '' && $controllerResponse !== null && $controllerResponse !== true && (is_scalar($controllerResponse) || (is_object($controllerResponse) && method_exists($controllerResponse, '__toString')))) {
                $content = (string)$controllerResponse;
            }

            $statusCode = 200;
            $headers = g('HEADER') ?: [];
        }

        $cookies = g('COOKIE') ?: []; 
        $_SESSION = g('SESSION') ?: [];
   
        if (PHP_SAPI === 'cli') {
            $response = new WorkermanResponse($statusCode, $headers, $content);

            foreach ($cookies as $name => $value) {
                $response->cookie($name, $value);
            }

            if ($request && method_exists($request, 'session')) {
            $session = $request->session();
            $session->put($_SESSION);
            }

            $connection->send($response);
        } else {
            // 如果已经有输出导致 headers 发送，则退化为只输出内容，避免 warning
            if (!headers_sent()) {
                http_response_code($statusCode);
                foreach ($cookies as $name => $value) {
                    setcookie($name, $value, 0, '/', '', false, true);
                }
                foreach ($headers as $name => $value) {
                    header("$name: $value");
                }
            }
            echo $content;
        }

        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function handleFileRequest($connection, $file, $request) {

        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        if (file_exists($file)) {
            $content = file_get_contents($file);

            // 判断是否在 CLI 模式下
            if (PHP_SAPI === 'cli' && $connection) {
                $response = new WorkermanResponse(200, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => strlen($content),
                ], $content);
                $connection->send($response);
            } else {
                // 非 CLI 模式，直接输出内容
                header('Content-Type: ' . $mimeType);
                header('Content-Length: ' . strlen($content));
                echo $content;
            }
        } else {
            if (PHP_SAPI === 'cli' && $connection) {
                $response = new WorkermanResponse(404, [], 'File not found');
                $connection->send($response);
            } else {
                header("HTTP/1.1 404 Not Found");
                echo 'File not found';
            }
        }
    }

    protected static function fixHttpCrossDomain($server) {
        _header('Access-Control-Allow-Credentials', 'true');
        if (isset($server['HTTP_ORIGIN'])) {
            _header('Access-Control-Allow-Origin', $server['HTTP_ORIGIN'] ?? config('http.cross_url'));
        } else {
            _header('Access-Control-Allow-Origin', '*');
        }
    }

    public static function run() {
        if (PHP_SAPI === 'cli') {
            $config = config('http');
            $server = new static($config['http_server']);
            $server->worker->name = $config['name'];
            $server->worker->count = $config['worker_num'] ?? 4;
            Worker::runAll();
        }
    }

    protected function executePhpFile($connection, $file, $request) {
        ob_start();

        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['SCRIPT_NAME'] = str_replace(PUBLIC_PATH, '', $file);
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

        include $file;

        $content = ob_get_clean();

        $headers = g('HEADER') ?: [];
        $response = new Response(200, $headers, $content);
        $connection->send($response);
    }
}
