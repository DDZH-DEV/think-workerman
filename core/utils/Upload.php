<?php

namespace utils;

use think\facade\Db;

class Upload
{ 
    protected $mime = [
        'image' => ['mime' => ['image/jpeg', 'image/gif', 'image/png'], 'ext' => 'jpg,png,gif,jpeg'],
        'video' => ['mime' => ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/mpeg', 'video/mpg', 'video/m4v', 'video/webm', 'video/ogg', 'video/3gp', 'video/mkv'], 'ext' => 'mp4,avi,mov,wmv,flv,mpeg,mpg,m4v,webm,ogg,3gp,mkv'],
        'doc' => ['mime' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'ext' => 'pdf,doc,docx,xls,xlsx,ppt,pptx'],
        'audio' => ['mime' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/aac', 'audio/flac', 'audio/m4b', 'audio/m4p', 'audio/m4b', 'audio/m4p', 'audio/m4b', 'audio/m4p', 'audio/m4b', 'audio/m4p'], 'ext' => 'mp3,wav,ogg,m4a,aac,flac,m4b,m4p'],
        'zip' => ['mime' => ['application/zip', 'application/x-zip-compressed', 'application/x-7z-compressed', 'application/x-rar-compressed', 'application/x-tar', 'application/x-gzip', 'application/x-bzip2', 'application/x-7z-compressed', 'application/x-rar-compressed', 'application/x-tar', 'application/x-gzip', 'application/x-bzip2'], 'ext' => 'zip,rar,7z,tar,gz,bz2'],
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

    protected $ext;

    protected $original_name;

    protected $save_full;


    protected $ip;

    static $upload_path = UPLOAD_PATH;

    static $base_path = PUBLIC_PATH ;

    static $img_max_width=1000;

    static $img_max_height=1000;


    protected $subdir_rules = [
        '_USER_' => 'getUserDirs',
        '_DATE_' => 'getDateDirs',
        '_FILE_' => 'getFileDirs',
        '_NULL_' => 'getNullDirs',
    ];

    public function __construct($raw = false, $file = null)
    {
        $files = function_exists('g') ? g('FILES') : $_FILES;
        $key = array_keys($files)[0];

        if ($raw || is_array($files[$key]['name'])) return $this;


        if ($file) {
            $this->file = $file;
        } else {
            $this->file = $files[$key];
        }


        if (!$this->file) return $this;
 

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

        $save_path = str_replace(['\\\\', '\\'], ['\\', '/'], self::$upload_path . $save_dir . DIRECTORY_SEPARATOR . $dir);

        if (!is_dir($save_path)) {
            @mkdir($save_path, 0777, true);
        }
        $file_name = $sub_dir_rule && is_numeric($sub_dir_rule) ? md5($sub_dir_rule) : md5($this->file_md5);

        $save_path = $save_path . '/' . $file_name . '.' . $ext;

        file_put_contents($save_path, $rawString);


        $path =  '/' . str_replace(str_replace('\\', '/', self::$base_path), '', $save_path);

        $res = [
            'ext' => $ext,
            'name' => $org_name ?: $file_name . '.' . $ext,
            'path' => $path,
            'md5' => $this->file_md5,
            'size' => filesize($save_path)
        ];

        $this->record($res);

        return $res;
    }

    static $user_id = 0;

    static $record = false;
    /**
     * 记录
     * @auth false
     */
    protected function record($insert)
    {
        if (self::$record) {
            if (!self::$recordFunc) {
                $this->defaultRecord($insert);
            } else {
                if (is_callable(self::$recordFunc)) {
                    call_user_func(self::$recordFunc, $insert);
                }
            }
        }
    }
    //自定义记录函数
    static $recordFunc = '';
    static $recordTable = 'qe_files';

    /** 
     * CREATE TABLE `qe_files` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(11) unsigned DEFAULT '0' COMMENT '用户',
        `path` varchar(255) DEFAULT '' COMMENT '保存路径',
        `md5` varchar(32) DEFAULT '' COMMENT 'MD5',
        `size` int(11) unsigned DEFAULT '0' COMMENT '大小',
        `name` varchar(255) DEFAULT '' COMMENT '文件名',
        `ext` varchar(32) DEFAULT '' COMMENT '后缀',
        `ip` varchar(64) DEFAULT NULL COMMENT 'IP',
        `upload_time` bigint(11) DEFAULT NULL COMMENT '上传时间',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `md5` (`md5`) USING BTREE,
        KEY `user_id` (`user_id`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='文件列表';
     * @param mixed $insert
     * @return void
     */
    protected function defaultRecord($insert)
    {
        $insert['user_id'] = self::$user_id;
        $insert['upload_time'] = time();
        $insert['ip'] = ip();
        if (!Db::name(self::$recordTable)->where('md5', $insert['md5'])->find()) {
            Db::name(self::$recordTable)->insert($insert);
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
        // 如果是通用文件上传，只需检查是否为危险文件类型
        if ($type == 'file' || $type == 'files') {
            return !in_array(strtolower($this->ext), $this->deny_ext);
        }
        
        // 其他特定类型的文件验证保持不变
        $filter = $this->mime[$type];
        $mimes = is_string($filter['mime']) ? explode(',', $filter['mime']) : $filter['mime'];
        $exts = is_string($filter['ext']) ? explode(',', $filter['ext']) : $filter['ext'];
        return in_array($this->ext, $exts) && in_array($this->file_type, $mimes);
    }

    public function uploadFile($file, $save_dir = '', $sub_dir_rule = '_FILE_')
    {
        $this->file = $file;

        $this->file_name = isset($this->file['file_name']) ? $this->file['file_name'] : $this->file['name'];

        $this->tmp_file = isset($this->file['file_data']) ? $this->file['file_data'] : $this->file['tmp_name'];

        $this->file_type = isset($this->file['file_type']) ? $this->file['file_type'] : $this->file['type'];

        $this->file_size = isset($this->file['file_size']) ? $this->file['file_size'] : $this->file['size'];

        $this->file_md5 = md5_file($this->tmp_file);
        //后缀获取
        $this->ext = $ext = substr(strrchr($this->file_name, '.'), 1);
        $this->original_name = str_replace("." . $ext, '', $this->file_name);


        return $this->upload($save_dir, $sub_dir_rule);
    }
    /**
     * 上传文件
     * @param string $save_dir
     * @param string $sub_dir_rule
     * @return array|bool
     */
    public function upload($save_dir = '', $sub_dir_rule = '_FILE_', $type = '')
    {
        if ($type && !$this->filter($type)) {
            $this->error = '当前文件格式不允许上传!';
            return false;
        }
        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = self::$upload_path . $save_dir . DIRECTORY_SEPARATOR . $dir; 

        if (!is_dir($save_path)) {
            @mkdir($save_path, 0777, true);
        }

        if (in_array($this->ext, ['php', 'exe','sh']) || strpos($this->ext,' ')!=false || strpos($this->ext,'　')!=false) {
            // 上传失败获取错误信息
            $this->error = '不允许的文件格式';
            return false;
        }

        $this->save_full = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $save_path . DIRECTORY_SEPARATOR . $this->file_md5 . '.' . $this->ext); 

        $content = is_file($this->tmp_file) ? file_get_contents($this->tmp_file) : $this->tmp_file;

        $res = $this->saveFile($content);

        if ($res) {
            $path = '/' . str_replace([self::$base_path, '\\'], ['', '/'], $this->save_full);
            $res = [
                'ext' => $this->ext,
                'name' => $this->original_name,
                'path' => $path,
                'md5' => $this->file_md5,
                'size' => $this->file_size
            ];

            $this->record($res);

            return $res;
        } else {
            // 上传失败获取错误信息
            $this->error = '保存文件失败';
            return false;
        }
    }

    /**
     * 保存文件内容
     */
    protected function saveFile($content) {
        $res = file_put_contents($this->save_full, $content);
        
        // 如果是图片,进行压缩处理
        if($res && $this->isImage()) {
            $this->compressImage($this->save_full);
        }
        
        return $res;
    }

    /**
     * 压缩图片
     */
    protected function compressImage($file_path) {
        // 获取图片信息
        $info = getimagesize($file_path);
        if(!$info) return false;
        
        $width = $info[0];
        $height = $info[1];
        $type = $info[2];
        
        // 如果尺寸都在限制范围内,无需压缩
        if($width <= self::$img_max_width && $height <= self::$img_max_height) {
            return true;
        }
        
        // 计算压缩后的尺寸
        $ratio = min(self::$img_max_width / $width, self::$img_max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);
        
        // 创建新图像
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // 根据图片类型处理
        switch($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file_path);
                // 保持PNG透明度
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($file_path);
                break;
            default:
                return false;
        }
        
        // 调整图片大小
        imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // 保存压缩后的图片
        switch($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($new_image, $file_path, 90); // 90是质量参数
                break;
            case IMAGETYPE_PNG:
                imagepng($new_image, $file_path, 9); // 9是压缩级别
                break;
            case IMAGETYPE_GIF:
                imagegif($new_image, $file_path);
                break;
        }
        
        // 释放内存
        imagedestroy($new_image);
        imagedestroy($source);
        
        return true;
    }

    /**
     * 判断是否为图片
     */
    protected function isImage() {
        $image_types = [
            'image/jpeg',
            'image/png',
            'image/gif'
        ];
        return in_array($this->file_type, $image_types);
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
        } else if($rule == '_NULL_') {
            return '';
        } else  {
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
        $hash = md5(self::$user_id);
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
 


    /**
     * 分片上传
     *
     * @param $save_dir
     *
     * @return array|bool 
     * @DateTime: 2018/10/25 13:29
     */
    public function chuncked($save_dir, $type = 'image')
    {

        $type = $this->filter($type);

        if (!$type) {
            $this->error = '当前文件格式不允许上传!';
            return false;
        }

        $this->file_md5 = md5($this->original_name . session('user.id'));

        $sub_dir_rule = '_FILE_';

        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = self::$base_path . 'uploads' . DIRECTORY_SEPARATOR . $save_dir . DIRECTORY_SEPARATOR . $dir;


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
                'url' => '/' . str_replace([self::$base_path, '\\'], ['', '/'], $this->save_full),
                'md5' => $this->file_md5,
                'size' => filesize($this->save_full),
            ];

            $this->record($res);

            return $res;
        }
    }
}
