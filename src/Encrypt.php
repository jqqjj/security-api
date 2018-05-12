<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Exception\SecurityApiException;

class Encrypt
{
    private $method = "AES-256-CBC";
    private $key = null;
    
    public function __construct($key = null)
    {
        if(!empty($key)){
            $this->key = $key;
        }
    }
    
    public function encrypt($data)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        
        $encrypted = openssl_encrypt($data, $this->method, $this->getKey(), OPENSSL_RAW_DATA, $iv);
        
        return $iv . $encrypted;
    }

    public function decrypt($data)
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $ivSize);
        
        return openssl_decrypt(substr($data, $ivSize), $this->method, $this->getKey(), OPENSSL_RAW_DATA, $iv);
    }
    
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    public function getKey()
    {
        if(empty($this->key)){
            throw new SecurityApiException("Encrypt key is empty");
        }
        
        return $this->key;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
}