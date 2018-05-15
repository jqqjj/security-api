<?php


namespace Jqqjj\SecurityApi;

class Encrypt
{
    private $method = "AES-256-CBC";
    private $token = null;
    
    public function __construct($token)
    {
        $this->token = $token;
    }
    
    public function encrypt($data)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        
        $encrypted = openssl_encrypt($data, $this->method, $this->getToken(), OPENSSL_RAW_DATA, $iv);
        
        return $iv . $encrypted;
    }
    
    public function decrypt($data)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $ivSize);
        
        return openssl_decrypt(substr($data, $ivSize), $this->method, $this->getToken(), OPENSSL_RAW_DATA, $iv);
    }
    
    public function getToken()
    {
        return $this->token;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
}