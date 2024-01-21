<?php

namespace utils;

use think\facade\Db;

class Upload
{


    protected $mime = [
        'image' => ['mime' => ['image/jpeg', 'image/gif', 'image/png'], 'ext' => 'jpg,png,gif,jpeg']
    ];

    protected $deny_ext = ['php', 'ext'];

    protected $error;

    protected $file;

    protected $tmp_file;

    protected $file_name;

    protected $file_type;

    protected $file_md5;

    protected $file_size;

    protected $rule = '_FILE_';


    protected $subdir_rules = [
        '_USER_' => 'getUserDirs',
        '_DATE_' => 'getDateDirs',
        '_FILE_' => 'getFileDirs',
    ];

    public function __construct($raw = false)
    {

        if ($raw) return $this;

        $files = function_exists('g') ? g('FILES') : $_FILES;

        if (!$files) return false;

        $key = array_keys($files)[0];

        $this->file = $files[$key];

        $this->file_name = isset($this->file['file_name']) ? $this->file['file_name'] : $this->file['name'];

        $this->tmp_file = isset($this->file['file_data']) ? $this->file['file_data'] : $this->file['tmp_name'];

        $this->file_type = isset($this->file['file_type']) ? $this->file['file_type'] : $this->file['type'];

        $this->file_size = isset($this->file['file_size']) ? $this->file['file_size'] : $this->file['size'];

        $this->file_md5 = md5_file($this->tmp_file);
        //后缀获取
        $this->ext = $ext = substr(strrchr($this->file_name, '.'), 1);
        $this->original_name = str_replace("." . $ext, '', $this->file_name);
    }


    /**
     * @param $rawString
     * @param $save_dir
     * @param $sub_dir_rule
     * @param $ext
     */
    public function saveRawFile($rawString, $save_dir = '', $sub_dir_rule = '', $ext = 'jpg', $org_name = '')
    {

        if (!$rawString) {
            return false;
        }

        $this->file_md5 = md5($rawString);

        $rawString = base64_decode(preg_replace("/data:.*?,/", '', $rawString));

        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = str_replace(['\\\\', '\\'], ['\\', '/'], UPLOAD_PATH . $save_dir . DIRECTORY_SEPARATOR . $dir);

        if (!is_dir($save_path)) {
            @mkdir($save_path, 0777, true);
        }
        $file_name = $sub_dir_rule && is_numeric($sub_dir_rule) ? md5($sub_dir_rule) : md5($this->file_md5);

        $save_path = $save_path . '/' . $file_name . '.' . $ext;

        file_put_contents($save_path, $rawString);


        $path =  '/'.str_replace(str_replace('\\', '/', PUBLIC_PATH), '', $save_path);

        $res = [
            'ext' => $ext,
            'name' => $org_name ?: $file_name . '.' . $ext,
            'path' => $path,
            'md5' => $this->file_md5,
            'size' => filesize($save_path)
        ];

        $this->record($res);

         $server = function_exists('g') ? g('SERVER') : $_SERVER;
         $url = defined('CDN_URL') ?CDN_URL: $server['HTTP_ORIGIN'] ;

         $res['path']=$url.$path;

         return $res;
    }

    public $user_id=0;
    /**
     * 记录
     * @auth false
     */
    protected function record($insert)
    {


        $insert['user_id'] = $this->user_id;
        $insert['upload_time'] = time();

        $insert['ip'] = get_ip();

        if (!Db::name('qe_files')->where('md5', $insert['md5'])->find()) {
            Db::name('qe_files')->insert( $insert);
        }
    }

    /**
     * 上传图片快捷调用
     * @param $save_dir
     * @param $sub_dir_rule
     * @return array|bool
     */
    public function uploadImage($save_dir, $sub_dir_rule = '_FILE_')
    {

        if (!$this->filter('image')) {
            $this->error = '请上传图片文件';
            return false;
        }

        return $this->upload($save_dir, $sub_dir_rule);
    }

    /**
     * 文件校验
     * @param $type
     * @return bool
     */
    protected function filter($type)
    {

        $filter = $this->mime[$type];

        $mimes = is_string($filter['mime']) ? explode(',', $filter['mime']) : $filter['mime'];

        $exts = is_string($filter['ext']) ? explode(',', $filter['ext']) : $filter['ext'];

        return in_array($this->ext, $exts) && in_array($this->file_type, $mimes);

    }


    /**
     * 上传文件
     * @param string $save_dir
     * @param string $sub_dir_rule
     * @return array|bool
     */
    public function upload($save_dir = '', $sub_dir_rule = '_FILE_')
    {
        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = UPLOAD_PATH . $save_dir . DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($save_path)) {
            @mkdir($save_path, 0777, true);
        }

        if (in_array($this->ext, ['php', 'exe'])) {
            // 上传失败获取错误信息
            $this->error = '不允许的文件格式';
            return false;
        }

        $this->save_full = $save_path . DIRECTORY_SEPARATOR . $this->file_md5 . '.' . $this->ext;

        $content = is_file($this->tmp_file) ? file_get_contents($this->tmp_file) : $this->tmp_file;

        $res = file_put_contents($this->save_full, $content);

        if ($res) {


            $path = str_replace([PUBLIC_PATH, '\\'], ['', '/'], $this->save_full);
            $server = function_exists('g') ? g('SERVER') : $_SERVER;
            $url = defined('CDN_URL') ?CDN_URL: $server['HTTP_ORIGIN'] . '/';

            $res = [
                'ext' => $this->ext,
                'name' => $this->original_name,
                'path' => $path,
                'md5' => $this->file_md5,
                'size' => $this->file_size
            ];

            $this->record($res);

            $res['path'] = $url . $res['path'];

            return $res;

        } else {
            // 上传失败获取错误信息
            $this->error = '保存文件失败';
            return false;
        }
    }

    /**
     * 根据已定义的规则获取子目录
     * @param string $rule
     * @return mixed|string
     */
    public function getSubdir($rule = '')
    {
        if (is_numeric($rule)) {
            $hash = md5($rule);
        } else {
            $rule = $rule ? $rule : $this->rule;

            if (in_array($rule, array_keys($this->subdir_rules))) {
                $call = $this->subdir_rules[$rule];
                return $this->$call();
            }

            if (is_callable($rule)) {
                return call_user_func($rule);
            }


            $hash = md5($rule);
        }

        //此处是有参数的文件夹创建方式
        return implode('/', array_slice(str_split($hash, 2), 0, 3));

    }

    /**
     * 输出错误
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    protected function getUserDirs()
    {
        $hash = md5( $this->user_id);
        $this->file_md5 = $hash;
        return implode('/', array_slice(str_split($hash, 2), 0, 3));
    }

    protected function getFileDirs()
    {
        return implode('/', array_slice(str_split($this->file_md5, 2), 0, 3));
    }

    protected function getDateDirs()
    {
        return date('Y/m-d', time());
    }


}