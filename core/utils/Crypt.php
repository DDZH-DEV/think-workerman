<?php

namespace utils;

class Crypt
{


    /**
     * 加密字符串
     * @param $plain_text
     * @param $passphrase
     * @return string
     * @Author: 9rax.dev@gmail.com
     */
    public static function encrypt($plain_text, $passphrase)
    {

        $salt = openssl_random_pseudo_bytes(256);
        $iv = openssl_random_pseudo_bytes(16);
        //on PHP7 can use random_bytes() istead openssl_random_pseudo_bytes()
        //or PHP5x see : https://github.com/paragonie/random_compat

        $iterations = 999;
        $key = hash_pbkdf2("sha512", $passphrase, $salt, $iterations, 64);

        $encrypted_data = openssl_encrypt($plain_text, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);

        return base64_encode(json_encode(array("ciphertext" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "salt" => bin2hex($salt))));


    }

    /**
     * 解密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @return string
     */
    public static function decrypt($base64Str = '', $passphrase)
    {

        $jsondata = json_decode(base64_decode($base64Str), true);
        try {
            $salt = hex2bin($jsondata["salt"]);
            $iv  = hex2bin($jsondata["iv"]);
        } catch(\Exception $e) { return null; }

        $ciphertext = base64_decode($jsondata["ciphertext"]);
        $iterations = 999; //same as js encrypting

        $key = hash_pbkdf2("sha512", $passphrase, $salt, $iterations, 64);

        $decrypted= openssl_decrypt($ciphertext , 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);

        return $decrypted;
    }
}