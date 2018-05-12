<?php


namespace Jqqjj\SecurityApi;

use Jqqjj\SecurityApi\Encrypt;
use Jqqjj\SecurityApi\XmlContent;

class Client
{
    private $api_uri;
    private $encrypt;
    
    public function __construct($api_uri,Encrypt $encrypt)
    {
        $this->api_uri = $api_uri;
        $this->encrypt = $encrypt;
    }
    
    public function getApiUri()
    {
        return $this->api_uri;
    }
    
    public function call($action, Array $params)
    {
        $xmlBodyManager = new XmlContent();
        $content = $xmlBodyManager->create($action,$params);
        $encrypted_content = $this->encrypt->encrypt($content);
        return $this->executeRequest($encrypted_content);
    }
}
