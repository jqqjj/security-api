<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Encrypt;
use Jqqjj\SecurityApi\RequestEntity;
use Jqqjj\SecurityApi\ResponseEntity;
use Jqqjj\SecurityApi\Exceptions\ParamsException;

class Server
{
    private $valid=false;
    private $encrypt;
    private $command;
    private $request_params;
    private $response_ret = 0;
    private $response_message = '';
    private $response_params=[];
    
    public function __construct($token,array $query,$body)
    {
        $this->encrypt = new Encrypt($token);
        if($this->verifySignature($query,$body) && $this->parseCommandAndParams($body)){
            $this->valid = true;
        }
    }
    
    public function setResponseRet($ret)
    {
        $this->response_ret = intval($ret);
        return $this;
    }
    
    public function setResponseMessage($message)
    {
        $this->response_message = $message;
        return $this;
    }
    
    public function setResponseParams(array $params)
    {
        $this->response_params = $params;
        return $this;
    }
    
    public function valid()
    {
        return $this->valid;
    }
    
    public function getResponseBody()
    {
        if(!$this->valid()){
            $response_entity = new ResponseEntity(1, "Invalid request.", ['encrypt'=>'']);
        }else{
            $real_entity = new ResponseEntity($this->response_ret, $this->response_message, $this->response_params);
            $encrypted_content = $this->encrypt->encrypt($real_entity->getXmlEntity());
            $response_entity = new ResponseEntity(0, "Success.", ['encrypt'=> $encrypted_content]);
        }
        return $response_entity->getXmlEntity();
    }
    
    public function getCommand()
    {
        return $this->command;
    }
    
    public function getRequestParams()
    {
        return $this->request_params;
    }
    
    protected function parseCommandAndParams($string)
    {
        //解密
        $decrypt_content = $this->encrypt->decrypt($string);
        if(empty($decrypt_content)){
            return false;
        }
        
        try{
            $request_entity = RequestEntity::loadFromString($decrypt_content);
        } catch (ParamsException $ex) {
            return false;
        }
        
        $this->command = $request_entity->getCommand();
        $this->request_params = $request_entity->getParams();
        return true;
    }

    protected function verifySignature($query,$body)
    {
        if(isset($query['signature'])){
            $query_signature = $query['signature'];
            unset($query['signature']);
        }
        $signature_query = array_merge($query,[
            'token'=> $this->encrypt->getToken(),
        ]);
        ksort($signature_query);
        
        $signature_string = "";
        foreach ($signature_query as $key=>$value){
            $signature_string .= $key.$value;
        }
        $signature_string .= $body;
        return md5($signature_string) == $query_signature;
    }
}
