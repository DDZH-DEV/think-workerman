<?php

/*
 * This file is part of JSON5-php.
 *
 * (c) Hiroto Kitazawa <hiro.yo.yo1610@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace utils;

/**
 * Class Parser.
 *
 * @package HirotoK\JSON5
 */
class Json5
{
    /**
     * JSON5 string.
     *
     * @var string
     */
    protected $json5;

    /**
     * Parse option.
     *
     * @var bool
     */
    protected $assoc;

    /**
     * Parser constructor.
     *
     * @param string $json5
     */
    public function __construct($json5)
    {
        $this->json5 = $json5;
    }

    /**
     * Parse.
     *
     * @param bool $assoc
     *
     * @return mixed
     */
    public function parse($assoc = false)
    {
        $this->assoc = $assoc;
        $json5       = $this->json5;

        return $this->parse_json5($json5);
    }

    /**
     * @param $obj
     *
     * @return bool
     */
    protected function returnObject($obj)
    {
        if ($this->assoc) {
            return (array) $obj;
        }

        return (object) $obj;
    }

    /**
     * Parse json5.
     *
     * @param $json5
     *
     * @return mixed
     */
    protected function parse_json5(&$json5)
    {
        $json5 = trim($json5);
        $this->parse_comment($json5);

        $c = substr($json5, 0, 1);

        if ($c === '{') {
            return $this->parse_object($json5);
        }
        if ($c === '[') {
            return $this->parse_array($json5);
        }
        if ($c === '"' || $c === "'") {
            return $this->parse_string($json5);
        }
        if (strncasecmp($json5, 'null', 4) === 0) {
            $json5 = substr($json5, 4);

            return;
        }
        if (strncasecmp($json5, 'true', 4) === 0) {
            $json5 = substr($json5, 4);

            return true;
        }
        if (strncasecmp($json5, 'false', 5) == 0) {
            $json5 = substr($json5, 5);

            return false;
        }
        if (strncasecmp($json5, 'infinity', 8) == 0) {
            $json5 = substr($json5, 8);

            return INF;
        }
        if (preg_match('/^(0x[a-zA-Z0-9]+)/', $json5, $m)) {
            $num   = $m[1];
            $json5 = substr($json5, strlen($num));

            return intval($num, 16);
        }
        if (preg_match('/^((\+|\-)?\d*\.?\d*[eE]?(\+|\-)?\d*)/', $json5, $m)) {
            $num   = $m[1];
            $json5 = substr($json5, strlen($num));

            return floatval($num);
        }
        $json5 = substr($json5, 1);

        return $this->returnObject($json5);
    }

    /**
     * Remove json5 comments.
     *
     * @param $json5
     *
     * @return string
     */
    protected function parse_comment(&$json5)
    {
        while ($json5 !== '') {
            $json5 = ltrim($json5);
            $c2    = substr($json5, 0, 2);
            if ($c2 === '/*') {
                $this->token($json5, '*/');
                continue;
            }
            if ($c2 === '//') {
                $this->token($json5, "\n");
                continue;
            }
            break;
        }

        return $json5;
    }

    /**
     * Parse json5 string.
     *
     * @param $json5
     *
     * @return mixed|string
     */
    protected function parse_string(&$json5)
    {
        $str   = '';
        $flag  = substr($json5, 0, 1);
        $json5 = substr($json5, 1);
        while ($json5 !== '') {
            $c     = mb_substr($json5, 0, 1);
            $json5 = substr($json5, strlen($c));
            if ($c === $flag) {
                break;
            }
            if ($c === '\\') {
                if (substr($json5, 0, 2) === "\r\n") {
                    $json5 = substr($json5, 2);
                    $str .= "\r\n";
                    continue;
                }
                if (substr($json5, 0, 1) === "\n") {
                    $json5 = substr($json5, 1);
                    $str .= "\n";
                    continue;
                }
            }
            $str .= $c;
        }
        $res = json_decode('"'.$str.'"');
        if (is_null($res)) {
            $json = json_decode(json_encode(compact('str')));
            $res  = $json->str;
        }

        return $res;
    }

    /**
     * Parse json5 array.
     *
     * @param string $json5
     *
     * @return array
     */
    protected function parse_array(&$json5)
    {
        $json5 = substr($json5, 1);
        $res   = [];
        while ($json5 !== '') {
            $this->parse_comment($json5);
            if (strncmp($json5, ']', 1) === 0) {
                $json5 = substr($json5, 1);
                break;
            }
            $res[] = $this->parse_json5($json5);
            $json5 = ltrim($json5);
            if (substr($json5, 0, 1) === ',') {
                $json5 = substr($json5, 1);
            }
        }

        return $this->returnObject($res);
    }

    /**
     * Parse object.
     *
     * @param $json5
     *
     * @return array
     */
    protected function parse_object(&$json5)
    {
        $json5 = substr($json5, 1);
        $res   = [];
        while ($json5 !== '') {
            $this->parse_comment($json5);
            if (strncmp($json5, '}', 1) === 0) {
                $json5 = substr($json5, 1);
                break;
            }
            $c = substr($json5, 0, 1);
            if ($c === '"' || $c === "'") {
                $key = $this->parse_string($json5);
                $this->token($json5, ':');
            } else {
                $key = trim($this->token($json5, ':'));
            }
            $value                  = $this->parse_json5($json5);
            $res[trim($key, '"\'')] = $value;
            $json5                  = ltrim($json5);
            if (strncmp($json5, ',', 1) === 0) {
                $json5 = substr($json5, 1);
            }
        }

        return $this->returnObject($res);
    }

    /**
     * Parse token.
     *
     * @param $str
     * @param $spl
     *
     * @return string
     */
    protected function token(&$str, $spl)
    {
        $i = strpos($str, $spl);
        if ($i === false) {
            $result = $str;
            $str    = '';

            return $result;
        }
        $result = substr($str, 0, $i);
        $str    = substr($str, $i + strlen($spl));

        return $result;
    }
}