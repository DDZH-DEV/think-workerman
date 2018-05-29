<?php
/**
 * Upload.php
 * @Author: zaoyongvip@gmail.com
 * @Date Time: 2018/1/10 0:24
 */

namespace utils;



use Intervention\Image\ImageManagerStatic;

class Upload{


    protected  $mime=[
        'image'=>['mine'=>['image/jpeg','image/gif','image/png'],'ext'=>'jpg,png,gif,jpeg']
    ];


    protected $error;


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
        $this->file_md5 = md5($_FILES[$key]['file_data']);
        //后缀获取
        $this->ext = $ext =substr(strrchr($_FILES[$key]['file_name'], '.'), 1);
        $this->original_name = str_replace("." . $ext,'',$_FILES[$key]['file_name']);
    }


    /**
     * 根据已定义的规则获取子目录
     * @param string $rule
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


        $hash=md5($rule);

        //此处是有参数的文件夹创建方式
        return implode('/', array_slice(str_split($hash, 2), 0, 3));

    }


    protected function getUserDirs()
    {
        $hash = md5(session('user')['id']);
        $this->file_md5 = $hash;
        return implode('/', array_slice(str_split($hash, 2), 0, 3));
    }

    protected function getFileDirs()
    {
        return implode('/', array_slice(str_split($this->file_md5, 2), 0, 3));
    }

    protected function getDateDirs()
    {
        return date('Y/m-d', $_SERVER['REQUEST_TIME']);
    }


    /**
     * 上传文件
     * @param string $save_dir
     * @param string $sub_dir_rule
     * @return array|bool
     * @Author: zaoyongvip@gmail.com
     */
    public function upload($save_dir = '', $sub_dir_rule = '')
    {
        // 获取表单上传文件 例如上传了001.jpg
        $dir = $this->getSubdir($sub_dir_rule);

        $save_path = UPLOAD_PATH  . $save_dir . DS . $dir;

        if(!is_dir($save_path)){
            @mkdir($save_path,0777,true);
        }

        $this->save_full=$save_path.DS.$this->file_md5.'.'.$this->ext;

        $res=file_put_contents( $this->save_full, $this->file['file_data']);



        if ($res) {
            return [
                'ext' => $this->ext,
                'org_name' => $this->original_name,
                'url' => str_replace([PUBLIC_PATH,'\\'],['','/'],$this->save_full),
                'md5' => $this->file_md5,
                'size' =>$this->file['file_size']
            ];
        } else {
            // 上传失败获取错误信息
            $this->error = '保存文件失败';
            return false;
        }
    }


    /**
     * 上传图片快捷调用
     * @param $save_dir
     * @param $sub_dir_rule
     * @return array|bool
     * @Author: zaoyongvip@gmail.com
     */
    public function uploadImage($save_dir, $sub_dir_rule)
    {

        if(!$this->filter('image')){
            $this->error='请上传图片文件';
            return false;
        }

        return $this->upload($save_dir, $sub_dir_rule);
    }


    /**
     * 文件校验
     * @param $type
     * @return bool
     * @Author: zaoyongvip@gmail.com
     */
    protected function filter($type){

        $filter=$this->mime[$type];
        //p($filter);

        $mines=is_string($filter['mine'])?explode(',',$filter['mine']):$filter['mine'];

        $exts=is_string($filter['ext'])?explode(',',$filter['ext']):$filter['ext'];

        return in_array($this->ext,$exts) && in_array($this->file['file_type'],$mines);

    }



    /**
     * 缩图
     * @param $file
     * @param int $width
     * @param int $height
     * @Author: zaoyongvip@gmail.com
     */
    public static function resizeImage($file, $width = null, $height = null)
    {

        if (file_exists($file)) {

            ImageManagerStatic::make($file)->resize($width, $height)->save($file);

        }
    }


    /**
     * 输出错误
     * @return mixed
     * @Author: zaoyongvip@gmail.com
     */
    public function getError()
    {
        return $this->error;
    }


}