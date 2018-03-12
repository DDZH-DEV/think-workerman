<?php

namespace utils;


class IdCard
{

    static function check($id_card)
    {
        if (strlen($id_card) == 18) {
            return self::idcard_checksum18($id_card);
        } elseif ((strlen($id_card) == 15)) {
            $id_card = self::idcard_15to18($id_card);
            return self::idcard_checksum18($id_card);
        } else {
            return false;
        }
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    static function idcard_verify_number($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    // 将15位身份证升级到18位
    static function idcard_15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . self::idcard_verify_number($idcard);
        return $idcard;
    }

    // 18位身份证校验码有效性检查
    static function idcard_checksum18($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        if (self::idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }


    const API_URL = 'http://120.76.102.228:8083/CheckIDCardService.aspx';

    static $config = [
        'company_code' => 'YuZuox002',
        'app_secret' => 'ccc184f687fdb8583f5d4d00dfg'
    ];

    static $error = '';

    static $_Curl;


    public static function auth($id_card, $name)
    {
        self::$error = '';
        if (!self::check($id_card)) {
            self::$error = '身份证号码错误!';
            return false;
        }

        $params = [
            'CompanyCode' => self::$config['company_code'],
            'IdCardName' => $name,
            'IdCardNo' => $id_card,
            'Timestamp' => time()
        ];

        $params = self::signReturn($params);

        return self::post($params);

    }



    static function post($params){

        $params=(json_encode($params,JSON_UNESCAPED_UNICODE));

        if(!self::$_Curl){
            self::$_Curl =  curl_init();
            //设置抓取的url
            curl_setopt(self::$_Curl, CURLOPT_URL, self::API_URL);
            //设置头文件的信息作为数据流输出
            curl_setopt(self::$_Curl, CURLOPT_HEADER, 0);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt(self::$_Curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt(self::$_Curl, CURLOPT_POST, 1);
        }


        //设置post数据
        curl_setopt(self::$_Curl, CURLOPT_POSTFIELDS, $params);
        //执行命令
        $data = curl_exec(self::$_Curl);

        $res=json_decode($data,true);


        if($res['Status']!==1){
            self::$error=$res['Message'];
            return false;
        }

        return true;

    }


    protected static function signReturn($params)
    {
        $str="{CompanyCode=".$params['CompanyCode']."&IdCardName=".$params['IdCardName']."&IdCardNo=".$params['IdCardNo']."&Timestamp=".$params['Timestamp']."}".md5(self::$config['app_secret']);

        $params['Sign']=md5($str);

        return $params;
    }
}