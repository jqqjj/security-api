<?php


namespace Jqqjj\SecurityApi;


class Encrypt
{
    private $method = "AES-256-CBC";
    
    public function encrypt($data, $key)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        
        $encrypted = openssl_encrypt($data, $this->method, $key, OPENSSL_RAW_DATA, $iv);
        
        return $iv . $encrypted;
    }

    public function decrypt($data, $key)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $ivSize);
        
        return openssl_decrypt(substr($data, $ivSize), $this->method, $key, OPENSSL_RAW_DATA, $iv);
    }
}