<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Encrypt;
use Jqqjj\SecurityApi\RequestEntity;
use Jqqjj\SecurityApi\ResponseEntity;
use Jqqjj\SecurityApi\HttpReuest;

class Client
{
    private $api_url;
    private $encrypt;
    
    public function __construct($api_url,$secret)
    {
        $this->api_url = $api_url;
        $this->encrypt = new Encrypt($secret);
    }
    
    public function getApiUrl()
    {
        return $this->api_url;
    }
    
    public function callApi($name, Array $params)
    {
        $entity = new RequestEntity($name,$params);
        $encrypted_content = $this->encrypt->encrypt($entity->getContent());
        
        $pulbic_query = [
            'timestamp'=>time(),
            'nonce'=> $this->randomString(6),
        ];
        $signature_query = array_merge($pulbic_query,[
            'token'=> $this->encrypt->getToken(),
        ]);
        ksort($signature_query);
        
        $signature_string = "";
        foreach ($signature_query as $key=>$value){
            $signature_string .= $key.$value;
        }
        $signature_string .= $encrypted_content;
        $pulbic_query['signature'] = md5($signature_string);
        
        $request = new HttpReuest();
        $response = $request->to($this->api_url."?".http_build_query($pulbic_query))->withHeader([
            'Content-type: application/octet-stream',
            'Content-length: '. strlen($encrypted_content),
        ])->withData($encrypted_content)->RedirectDepth(5)->post();
        
        $response_entity = ResponseEntity::loadFromString($response);
    }
    
    public function executeRequest($url,$content)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不直接输出，返回结果变量
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//自动重定向
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);//自动重定向数次限制
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);//超时秒数
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/octet-stream',
            'Content-length: '. strlen($content),
        ]);//自定义header头
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    
    private function randomString($len)
    {
        //生成连接密码
        $random = '';
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        for($i=0;$i<$len;$i++)
        {
            $random .= $chars{array_rand(str_split($chars))};
        }
        return $random;
    }
}
