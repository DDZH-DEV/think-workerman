<?php
/**
 * Upload.php
 *
 * @Author: zaoyongvip@gmail.com
 * @Date  Time: 2018/1/10 0:24
 */

namespace app\common\service;

use think\Db;

class Upload
{

    protected $mime = [
        'image' => 'jpg,png,gif,jpeg',
        'office' => 'doc,docx,xls,xlsx,ppt,pptx,html',
        'excel'  => 'xls,xlsx,csv',
        'attach' => 'zip,rar,tar,pdf,crx,txt,sql,gz',
        'video' => 'avi,mov,mp4,wmv,mkv,flv,rmvb,asf',
        'audio' => 'mp3,aac,amr,ogg,wma,wav'
    ];

    protected $type=false;

    protected $error;

    protected $save_full;

    protected $file;

    protected $file_md5;

    protected $rule;


    protected $subdir_rules = [
        '_USER_' => 'getUserDirs',
        '_DATE_' => 'getDateDirs',
        '_FILE_' => 'getFileDirs',
    ];

    public function __construct()
    {

        $key = array_keys($_FILES)[0];
        $this->file = $_FILES[$key];

        if ($this->file['error']) {
            switch ($this->file['error']) {
                case 1:
                    $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                    break;
                case 2:
                    $this->error = '上传的文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                    break;
                case 3:
                    $this->error = '文件只有部分被上传';
                    break;
                case 4:
                    $this->error = '没有文件被上传';
                    break;
                case 6:
                    $this->error = '找不到临时文件夹';
                    break;
                case 7:
                    $this->error = '文件写入失败';
                    break;
            }
            return false;
        }

        $this->file_md5 = md5_file($_FILES[$key]['tmp_name']);
        //后缀获取
        $this->ext = strtolower(substr(strrchr($_FILES[$key]['name'], '.'), 1));


        if ($this->ext == 'php') {
            $this->error = '当前文件格式不允许上传!';
            return false;
        }

        $this->original_name = $_FILES[$key]['name'];
    }


    /**
     * 分片上传
     *
     * @param $save_dir
     *
     * @return array|bool
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2018/10/25 13:29
     */
    public function chuncked($save_dir)
    {

        $type = $this->filter();

        if (!$type) {
            $this->error = '当前文件格式不允许上传!';
            return false;
        }

        $this->file_md5 = md5($this->original_name . session('user.id'));

        $sub_dir_rule = '_FILE_';

        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR . $save_dir . DIRECTORY_SEPARATOR . $dir;


        if (!is_dir($save_path) && @(false == mkdir($save_path, 0777, true))) {
            $this->error = '文件夹无读写权限!';
            return false;
        }

        $this->save_full = $save_path . DIRECTORY_SEPARATOR . $this->file_md5 . '.' . $this->ext;

        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        //分片总数量
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        //打开临时文件
        if (!$out = @fopen("{$save_path}_{$chunk}.parttmp", "wb")) {
            $this->error = ('Failed to open output stream.');
            return false;
        }

        //读取片段
        if (!empty($this->file)) {

            //读取二进制输入流并将其附加到临时文件
            if (!$in = @fopen($this->file["tmp_name"], "rb")) {
                $this->error = ('Failed to open input stream.');
                return false;
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                $this->error = ('Failed to open input stream.');
                return false;
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$save_path}_{$chunk}.parttmp", "{$save_path}_{$chunk}.part");
        $index = 0;
        $done = true;
        for ($index = 0; $index < $chunks; $index++) {
            if (!file_exists("{$save_path}_{$index}.part")) {
                $done = false;
                break;
            }
        }

        if ($done) {

            if (!$out = @fopen($this->save_full, "wb")) {
                $this->error = ('Failed to open output stream.');
                return false;
            }
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$save_path}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$save_path}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);

            $res = [
                'ext' => $this->ext,
                'type' => $type,
                'org_name' => $this->original_name,
                'url' => str_replace([PUBLIC_PATH, '\\'], ['', '/'], $this->save_full),
                'md5' => $this->file_md5,
                'size' => filesize($this->save_full),
            ];



            return $res;
        }

    }


    /**
     * 根据已定义的规则获取子目录
     *
     * @param string $rule
     *
     * @return mixed|string
     * @Author: zaoyongvip@gmail.com
     */
    public function getSubdir($rule = '')
    {

        $rule = $rule ? $rule : $this->rule;

        if (in_array($rule, array_keys($this->subdir_rules))) {
            $call = $this->subdir_rules[$rule];
            return $this->$call();
        }

        if (is_callable($rule)) {
            return call_user_func($rule);
        }


        $hash = md5($rule);

        //此处是有参数的文件夹创建方式
        return implode('/', array_slice(str_split($hash, 2), 0, 3));

    }

    /**
     * 根据用户设置唯一图片
     *
     * @return string
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/8/18 8:50
     */
    protected function getUserDirs()
    {
        $hash = md5(session('user.id') . config('app.app_secret'));
        $this->file_md5 = $hash;
        return implode('/', array_slice(str_split($hash, 2), 0, 3));
    }

    /**
     * 根据文件md5设置唯一路径文件
     *
     * @return string
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/8/18 8:50
     */
    protected function getFileDirs()
    {
        return implode('/', array_slice(str_split(md5($this->file_md5 . config('app.app_secret')), 2), 0, 3));
    }


    /**
     * getDateDirs
     *
     * @return false|string
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/8/18 8:51
     */
    protected function getDateDirs()
    {
        return date('Y/md', $_SERVER['REQUEST_TIME']);
    }


    /**
     * 上传文件
     *
     * @param string $save_dir
     * @param string $sub_dir_rule
     *
     * @return array|bool
     * @Author: zaoyongvip@gmail.com
     */
    function upload($save_dir, $sub_dir_rule = '',$filter=false)
    {

        if ($filter && !$this->filter($filter)) {
            $this->error = '您上传的文件后缀不合法!';
            return false;
        }

        $sub_dir_rule = $sub_dir_rule ? $sub_dir_rule : '_DATE_';

        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR . $save_dir . DIRECTORY_SEPARATOR . $dir;


        if (!is_dir($save_path) && @(false == mkdir($save_path, 0777, true))) {
            $this->error = '文件夹无读写权限!';
            return false;
        }

        $this->save_full = $save_path . DIRECTORY_SEPARATOR . $this->file_md5 . '.' . $this->ext;

        $res = move_uploaded_file($this->file['tmp_name'], $this->save_full);


        if ($res && file_exists($this->save_full)) {
            $res = [
                'ext' => $this->ext,
                'org_name' => $this->original_name,
                'url' => str_replace([PUBLIC_PATH, '\\'], ['', '/'], $this->save_full),
                'md5' => $this->file_md5,
                'size' => $this->file['size'],
                'type' => $this->type
            ];


            return $res;
        } else {
            // 上传失败获取错误信息
            $this->error = '保存文件失败';
            return false;
        }
    }


    /**
     * 上传图片快捷调用
     *
     * @param $save_dir
     * @param $sub_dir_rule
     *
     * @return array|bool
     * @Author: zaoyongvip@gmail.com
     */
    public function image($save_dir, $sub_dir_rule = '')
    {

        if (!$this->filter('image')) {
            $this->error = '请上传图片文件';
            return false;
        }

        return $this->upload($save_dir, $sub_dir_rule);
    }


    /**
     * 文件上传调用
     *
     * @param string $save_dir
     * @param string $sub_dir_rule
     *
     * @return array|bool
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2018/8/15 1:12
     */
    public function files($save_dir = 'files', $sub_dir_rule = '')
    {

        if (!$this->filter()) {
            $this->error = '不允许上传该文件类型';
            return false;
        }

        return $this->upload($save_dir, $sub_dir_rule);

    }


    /**
     * 文件校验
     *
     * @param $type
     *
     * @return bool
     * @Author: zaoyongvip@gmail.com
     */
    protected function filter($type = true)
    {

        $this->type = false;

        if ($type !== true && in_array($type, array_keys($this->mime))) {

            $filter = $this->mime[$type];

            $exts = is_string($filter) ? explode(',', $filter) : $filter;

            $this->type = in_array($this->ext, $exts) ? $type : false;

        } elseif ($this->mime) {

            foreach ($this->mime as $type => $filter) {

                $exts = is_string($filter) ? explode(',', $filter) : $filter;

                if (is_array($exts) && in_array($this->ext, $exts)) {
                    $this->type = $type;
                    break;
                }

            }
        }

        return $this->type;
    }


    /**
     * 缩图
     *
     * @param     $file
     * @param int $width
     * @param int $height
     *
     * @Author: zaoyongvip@gmail.com
     */
    public static function resizeImage($file, $width = null, $height = null)
    {
        if (file_exists($file)) {
            //ImageManagerStatic::make($file)->resize($width, $height)->save($file);
        }
    }


    /**
     * 输出错误
     *
     * @return mixed
     * @Author: zaoyongvip@gmail.com
     */
    public function getError()
    {
        return $this->error;
    }


}