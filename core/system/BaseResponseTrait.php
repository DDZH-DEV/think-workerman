<?php

namespace system;

/**
 * 通用响应 Trait：统一 success/error 行为，并兼容 FPM 与 Workerman
 *
 * 使用方式：
 * - 控制器基类 use BaseResponseTrait;
 * - 如需自定义默认输出类型（JSON / View），覆盖 preferJson().
 * - 需要提供 _jump_($content, $extend, $url, $icon) 方法以支持非 JSON 场景。
 */
trait BaseResponseTrait
{
    /**
     * 当前请求是否优先使用 JSON 输出。
     * 默认：仅 AJAX 请求走 JSON，其它交给视图跳转。
     */
    protected function preferJson(): bool
    {
        return (bool)g('IS_AJAX');
    }

    /**
     * 统一响应出口，兼容原 Base::response 签名。
     */
    protected function response($msg, $code = 0, $url = '', $icon = '')
    {
        if ($this->preferJson()) {
            $data = [];
            if ($url) {
                $data['url'] = $url;
            }

            $resp = new Response($data, (int)$code, (string)$msg);
            // 通过 JumpException 让 WebServer/FPM 统一输出
            throw new JumpException($resp);
        }

        // 非 JSON 场景，走应用自定义的跳转视图
        $icon = $icon ?: ((int)$code === 200 ? 'check' : 'error');
        $url = $url ?: (g('SERVER')['HTTP_REFERER'] ?? '');

        // 由具体基类实现 _jump_ 方法
        return $this->_jump_($msg, '', $url, $icon);
    }

    /**
     * 错误响应
     */
    public function error($msg, $url = '', $code = 0)
    {
        return $this->response($msg, $code ?: 400, $url);
    }

    /**
     * 成功响应
     */
    public function success($msg, $url = '', $code = 200)
    {
        return $this->response($msg, $code, $url);
    }
}

