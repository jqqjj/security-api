<?php


namespace Jqqjj\SecurityApi;

use DOMDocument;
use Jqqjj\SecurityApi\Exceptions\RequestParamsException;

class RequestEntity
{
    private $command;
    private $params;
    private $xmlDom;
    private $xmlEntity;
    
    public function __construct($command,Array $params)
    {
        $this->command = $command;
        $this->params = $params;
    }
    
    public static function loadFromString($string)
    {
        /*$entity_content = preg_replace('/^<\?xml.+?\?>/', '', $string);*/
        $xml = new DOMDocument('1.0','UTF-8');
        $xml->loadXML($string);
        
        $request_dom = $xml->getElementsByTagName('request');
        if(count($request_dom)!=1){
            throw new RequestParamsException('XML structure error.');
        }
        $command_node = $request_dom->item(0)->getElementsByTagName('command');
        $params_node = $request_dom->item(0)->getElementsByTagName('params');
        if(count($command_node)!=1 || count($params_node)!=1){
            throw new RequestParamsException('XML structure error.');
        }
        
        $command = $command_node->item(0)->nodeValue;
        $params = [];
        foreach ($params_node->item(0)->childNodes as $node){
            $params[$node->nodeName] = self::parseParams($node);
        }
        
        return new static($command,$params);
    }
    
    protected static function parseParams($node)
    {
        $values = [];
        if(!$node->hasAttribute('type')){
            throw new RequestParamsException('XML structure error.');
        }
        $type = $node->getAttribute('type');
        switch ($type){
            case "array":
                
                break;
            default :
                
        }
            $node_value = $node->nodeValue;
            
            $values[$node->getAttribute('index')] = $node->nodeValue;
    }

    public function getCommand()
    {
        return $this->command;
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
        $request = $xmlDom->createElement('request');
        $xmlDom->appendChild($request);
        
        $command = $xmlDom->createElement('command', $this->command);
        $request->appendChild($command);
        $params = $xmlDom->createElement('params');
        $request->appendChild($params);
        
        foreach ($this->params as $node_name=>$node_value){
            if(!preg_match('/^[a-zA-Z_](\w)*/', $node_name)){
                throw new RequestParamsException("Index name of each param must be a letter.[{$node_name}] presents.");
            }
            $params->appendChild($this->createParamsElement($node_name,$node_value));
        }
        
        return $this->getXmlDom()->saveXML();
    }
    
    protected function createParamsElement($node_name,$node_value)
    {
        if(!preg_match('/^[a-zA-Z_](\w)*/', $node_name) && !preg_match('/^([0-9]|[1-9]\d+)$/', $node_name)){
            throw new RequestParamsException("Node name must follow the rule of variable naming.[{$node_name}] presents.");
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