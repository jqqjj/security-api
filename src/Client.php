<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Encrypt;
use Jqqjj\SecurityApi\RequestEntity;
use Jqqjj\SecurityApi\ResponseEntity;
use Jqqjj\SecurityApi\HttpReuest;
use Exception;

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
    
    public function callApi($command, Array $params)
    {
        $request_entity = new RequestEntity($command,$params);
        $encrypted_content = $this->encrypt->encrypt($request_entity->getXmlEntity());
        
        $a = RequestEntity::loadFromString($request_entity->getXmlEntity());
        
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
        
        try{
            $request = new HttpReuest();
            $request->to($this->api_url."?".http_build_query($pulbic_query))->withHeader([
                'Content-type: application/octet-stream',
                'Content-length: '. strlen($encrypted_content),
            ])->withData($encrypted_content)->RedirectDepth(5)->post();
        } catch (Exception $ex) {
            $response_entity = new ResponseEntity($command, [
                'ret'=>1,
                'message'=>"Request Fail.",
            ]);
        }
        //解密
        $decrypt_content = $this->encrypt->decrypt($request->getResponseBody());

        $response_entity = ResponseEntity::loadFromString($decrypt_content);
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
