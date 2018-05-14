<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Exceptions\RequestNetworkException;
use Jqqjj\SecurityApi\Exceptions\RequestParamsException;

class HttpReuest
{
    /* @var array $fixedCurlOptions */
    protected $fixedCurlOptions = array(
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_HEADER        => true,
    );
    //request info
    protected $scheme = '';
    protected $host = '';
    protected $url = '';
    protected $method = '';
    protected $timeout = 30;
    protected $requestHeader = [];
    protected $requestBody = null;
    protected $userAgent = '';
    protected $redirectDepth = 0;
    //response info
    protected $responseStatusCode = 0;
    protected $ResponseHeader = [];
    protected $responseHeaderSize = 0;
    protected $responseBody = null;
    protected $responseBodySize = 0;
    
    
    public function to($url)
    {
        $url_info = parse_url($url);
        if(empty($url_info['scheme']) || !in_array($url_info['scheme'],['http','https']) || empty($url_info['host'])){
            throw new RequestParamsException("Request URL is invalid.");
        }
        $this->url = $url;
        $this->scheme = $url_info['scheme'];
        $this->host = $url_info['host'];
        return $this;
    }

    public function withHeader(array $headers)
    {
        $this->requestHeader = $headers;
        return $this;
    }
    
    public function withData($data)
    {
        $this->requestBody = $data;
        return $this;
    }
    
    public function withTimeout($second)
    {
        $this->timeout = intval($second);
        return $this;
    }
    
    public function RedirectDepth($depth=1)
    {
        $this->redirectDepth = intval($depth);
        return $this;
    }
    
    public function withAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        return $this;
    }
    
    public function get()
    {
        if(is_array($this->requestBody) || is_object($this->requestBody)){
            $url = $this->url . (strpos($this->url, '?')===false?"?":"&") . http_build_query($this->requestBody);
        }else{
            $url = $this->url . (strpos($this->url, '?')===false?"?":"&") . $this->requestBody;
        }
        $this->method = 'GET';
        
        return $this->send($url,null);
    }
    
    public function post()
    {
        $this->method = 'POST';
        
        return $this->send($this->url,$this->requestBody);
    }
    
    protected function send($url,$content)
    {
        $depth = $this->redirectDepth;
        do{
            if(in_array($this->responseStatusCode, [301,302]) && (empty($this->ResponseHeader) || empty($this->ResponseHeader['Location']))){
                throw new RequestNetworkException("Curl get a bad response.");
            }
            if(in_array($this->responseStatusCode, [301,302])){
                $url_info = parse_url($this->ResponseHeader['Location']);
                $scheme = empty($url_info['scheme']) ? $this->scheme : $url_info['scheme'];
                $host = empty($url_info['host']) ? $this->host : $url_info['host'];
                $query = empty($url_info['query']) ? "" : ("?".$url_info['query']);
                $url = $scheme . "://" . $host .'/'. ltrim($url_info['path'],'/') . $query;
            }
            $curlErrno = $this->execute($url, $content);
            if($curlErrno){
                throw new RequestNetworkException("Curl Request Error:{$curlErrno}");
            }
        }while(in_array($this->responseStatusCode, [301,302]) && $depth-- > 0);
        
        if(in_array($this->responseStatusCode, [301,302])){
            throw new RequestNetworkException("Redirection times is greater than:{$this->redirectDepth}");
        }
        
        return $this;
    }
    
    protected function execute($url,$content)
    {
        $ch = curl_init();
        //固定的选项
        foreach ($this->fixedCurlOptions as $k=>$v){
            curl_setopt($ch, $k, $v);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        switch ($this->method){
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
                break;
            default :
                
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);//超时秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);//超时秒数
        if($this->requestHeader){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeader);//自定义header头
        }
        if($this->userAgent){
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        if(strpos($url, 'https')!==false){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        
        $output = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        if(!$curlErrno){
            list($header, $body) = explode("\r\n\r\n", $output, 2);
            $this->responseStatusCode = $httpCode>0 ? $httpCode : 0;
            $this->responseBody = $body;
            $this->responseBodySize = strlen($body);
            $this->responseHeaderSize = strlen($header);
            $this->ResponseHeader = $this->parseHeaderFromString($header);
        }
        
        return $curlErrno;
    }
    
    protected function parseHeaderFromString($string)
    {
        $header = [];
        foreach (explode("\r\n", $string) as $value){
            if(strpos($value,":")!==false){
                list($header_name,$header_value) = explode(":", $value, 2);
                $header[trim($header_name)] = trim($header_value);
            }
        }
        return $header;
    }
}