<?php

namespace system;

/**
 * 一个标准的HTTP响应对象，用于替代旧的异常驱动流程
 */
class Response
{
    public $data;
    public $code;
    public $msg;
    public $debug;
    public $headers = [];

    /**
     * Response constructor.
     * @param mixed $data
     * @param int $code
     * @param string|null $msg
     * @param array $debug
     */
    public function __construct($data, int $code = 200, string $msg = null, array $debug = [])
    {
        if (is_string($data) && is_null($msg)) {
            $msg = $data;
            $data = null;
        }

        $this->data = $data;
        $this->code = $code;
        $this->msg = $msg;
        $this->debug = $debug;
    }

    /**
     * 添加一个HTTP头
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * 获取将要发送给客户端的响应体 (JSON格式)
     * @return false|string
     */
    public function getBody()
    {
        $result = [
            'data' => $this->data,
            'code' => $this->code,
            'msg' => $this->msg
        ];

        if (!empty($this->debug) && is_array($result)) {
            $result['debug'] = $this->debug;
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取HTTP状态码
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->code;
    }
} 