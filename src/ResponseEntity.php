<?php


namespace Jqqjj\SecurityApi;

use DOMDocument;
use Jqqjj\SecurityApi\Exceptions\ParamsException;
use Exception;

class ResponseEntity
{
    private $ret;
    private $message;
    private $params;
    private $xmlDom;
    private $xmlEntity;
    
    public function __construct($ret,$message,Array $params)
    {
        $this->ret = $ret;
        $this->message = $message;
        $this->params = $params;
    }
    
    public static function loadFromString($string)
    {
        /*$entity_content = preg_replace('/^<\?xml.+?\?>/', '', $string);*/
        $xml = new DOMDocument('1.0','UTF-8');
        try{
            $xml->loadXML($string);
        } catch (Exception $ex) {
            throw new ParamsException('XML structure error.');
        }
        
        $response_dom = $xml->getElementsByTagName('response');
        if(count($response_dom)!=1){
            throw new ParamsException('XML structure error.');
        }
        $ret_node = $response_dom->item(0)->getElementsByTagName('ret');
        $message_node = $response_dom->item(0)->getElementsByTagName('message');
        $params_node = $response_dom->item(0)->getElementsByTagName('params');
        if(count($ret_node)!=1 || count($message_node)!=1 || count($params_node)!=1){
            throw new ParamsException('XML structure error.');
        }
        
        $ret = $ret_node->item(0)->nodeValue;
        $message = $message_node->item(0)->nodeValue;
        $params = [];
        foreach ($params_node->item(0)->childNodes as $node){
            $params[$node->nodeName] = self::parseParams($node);
        }
        
        return new static($ret,$message,$params);
    }
    
    protected static function parseParams($node)
    {
        if(!$node->hasAttribute('type')){
            throw new ParamsException('XML structure error.');
        }
        
        $type = $node->getAttribute('type');
        switch (strtolower($type)){
            case "array":
                $value = [];
                foreach ($node->childNodes as $node){
                    //检查数字索引是否完整
                    if($node->hasAttribute('item') && !$node->hasAttribute('index')){
                        throw new ParamsException('XML structure error.');
                    }
                    $index_name = $node->hasAttribute('item') ? intval($node->getAttribute('index')) : $node->nodeName;
                    $value[$index_name] = self::parseParams($node);
                }
                break;
            case "integer":
                $value = intval(base64_decode($node->nodeValue));
                break;
            case "boolean":
                $value = boolval(base64_decode($node->nodeValue));
                break;
            case "double":
                $value = doubleval(base64_decode($node->nodeValue));
                break;
            case "float":
                $value = floatval(base64_decode($node->nodeValue));
                break;
            case "null":
                $value = null;
                break;
            default:
                $value = base64_decode($node->nodeValue);
        }
        
        return $value;
    }
    
    public function getRet()
    {
        return $this->ret;
    }
    
    public function getMessage()
    {
        return $this->message;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getXmlEntity()
    {
        if(empty($this->xmlEntity)){
            $this->xmlEntity = $this->createXmlEntity();
        }
        
        return $this->xmlEntity;
    }
    
    protected function createXmlEntity()
    {
        $xmlDom = $this->getXmlDom(true);
        $response = $xmlDom->createElement('response');
        $xmlDom->appendChild($response);
        
        $ret = $xmlDom->createElement('ret', $this->ret);
        $message = $xmlDom->createElement('message', $this->message);
        $response->appendChild($ret);
        $response->appendChild($message);
        $params = $xmlDom->createElement('params');
        $response->appendChild($params);
        
        foreach ($this->params as $node_name=>$node_value){
            if(!preg_match('/^[a-zA-Z_](\w)*/', $node_name)){
                throw new ParamsException("Node name must follow the rule of variable's naming.[{$node_name}] presents.");
            }
            $params->appendChild($this->createParamsElement($node_name,$node_value));
        }
        
        return $this->getXmlDom()->saveXML();
    }
    
    protected function createParamsElement($node_name,$node_value)
    {
        if(!preg_match('/^[a-zA-Z_](\w)*/', $node_name) && !preg_match('/^([0-9]|[1-9]\d+)$/', $node_name)){
            throw new ParamsException("Node name must follow the rule of variable's naming.[{$node_name}] presents.");
        }
        if(preg_match('/^[a-zA-Z_](\w)*/', $node_name)){
            $element = $this->getXmlDom()->createElement($node_name);
        }else{
            $element = $this->getXmlDom()->createElement('item');
            $element->setAttribute('item','true');
            $element->setAttribute('index',$node_name);
        }
        if(is_array($node_value)){
            foreach ($node_value as $k=>$v){
                $child_element = $this->createParamsElement($k, $v);
                $element->appendChild($child_element);
            }
        }else{
            $element->nodeValue = base64_encode($node_value);
        }
        
        $element->setAttribute('type', strtolower(gettype($node_value)));
        
        return $element;
    }
    
    protected function getXmlDom($force=false)
    {
        if(empty($this->xmlDom) || $force){
            $this->xmlDom = new DOMDocument('1.0','UTF-8');
        }
        return $this->xmlDom;
    }

    public function __toString()
    {
        return $this->getXmlEntity();
    }
}