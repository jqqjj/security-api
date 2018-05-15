<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Encrypt;
use Jqqjj\SecurityApi\RequestEntity;
use Jqqjj\SecurityApi\ResponseEntity;
use Jqqjj\SecurityApi\HttpReuest;
use Jqqjj\SecurityApi\Exceptions\ParamsException;
use Exception;

class Client
{
    private $api_url;
    private $appid;
    private $encrypt;
    
    public function __construct($api_url,$appid,$token)
    {
        $this->api_url = $api_url;
        $this->appid = $appid;
        $this->encrypt = new Encrypt($token);
    }
    
    public function getApiUrl()
    {
        return $this->api_url;
    }
    
    public function callApi($command, Array $params)
    {
        $request_entity = new RequestEntity($command,$params);
        $encrypted_content = $this->encrypt->encrypt($request_entity->getXmlEntity());
        
        $pulbic_query = [
            'appid'=> $this->appid,
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
        
        //发送请求
        try{
            $request = new HttpReuest();
            $request->to($this->api_url."?".http_build_query($pulbic_query))->withHeader([
                'Content-type: application/octet-stream',
                'Content-length: '. strlen($encrypted_content),
            ])->withData($encrypted_content)->RedirectDepth(5)->post();
        } catch (Exception $ex) {
            return [
                'ret'=>1,
                'message'=>$ex->getMessage(),
                'data'=>[],
            ];
        }
        //判断响应结果是否正确
        try{
            $response_entity = ResponseEntity::loadFromString($request->getResponseBody());
        } catch (ParamsException $ex) {
            return [
                'ret'=>1,
                'message'=>$ex->getMessage(),
                'data'=>[],
            ];
        }
        if($response_entity->getRet()){
            return [
                'ret'=>$response_entity->getRet(),
                'message'=>$response_entity->getMessage(),
                'data'=>$response_entity->getParams(),
            ];
        }
        
        //通过初检则进行解密内容
        $response_params = $response_entity->getParams();
        if(empty($response_params['encrypt'])){
            return [
                'ret'=>1,
                'message'=>"Response params structure error.",
                'data'=>$response_params,
            ];
        }
        
        //解密失败处理
        $decrypt_content = $this->encrypt->decrypt(base64_decode($response_params['encrypt']));
        if(empty($decrypt_content)){
            return [
                'ret'=>1,
                'message'=>"Response decrypt error.",
                'data'=>[],
            ];
        }
        
        //处理真正的响应结果
        try{
            $real_entity = ResponseEntity::loadFromString($decrypt_content);
        } catch (ParamsException $ex) {
            return [
                'ret'=>1,
                'message'=>"Response params structure error.",
                'data'=>[],
            ];
        }
        
        return [
            'ret'=>$real_entity->getRet(),
            'message'=>$real_entity->getMessage(),
            'data'=>$real_entity->getParams(),
        ];
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
