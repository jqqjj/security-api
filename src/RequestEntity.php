<?php


namespace Jqqjj\SecurityApi;

use DOMDocument;
use Jqqjj\SecurityApi\Exceptions\RequestParamsException;

class RequestEntity
{
    private $name;
    private $params;
    private $xml;
    
    public function __construct($name,Array $params)
    {
        $this->name = $name;
        $this->params = $params;
        $this->xml = new DOMDocument('1.0','UTF-8');
    }
    
    public static function loadFromString($content)
    {
        
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getContent()
    {
        $request = $this->xml->createElement('request');
        $this->xml->appendChild($request);
        
        $name = $this->xml->createElement('name', $this->name);
        $request->appendChild($name);
        $params = $this->xml->createElement('params');
        $request->appendChild($params);
        
        foreach ($this->params as $node_name=>$node_value){
            if(!preg_match('/^[a-zA-Z_]/', $node_name)){
                throw new RequestParamsException("Index name of each param must be a letter.[{$node_name}] presents.");
            }
            $params->appendChild($this->createParamsElement($node_name,$node_value));
        }
        
        return $this->xml->saveXML();
    }
    
    private function createParamsElement($node_name,$node_value)
    {
        if(is_array($node_value)){
            $element = $this->xml->createElement($node_name);
            foreach ($node_value as $k=>$v){
                if(preg_match('/^[a-zA-Z_]/', $k)){
                    $child_element = $this->createParamsElement($k, $v);
                }elseif(preg_match('/^([0-9]|[1-9]\d+)$/', $k)){
                    $child_element = $this->createParamsElement($k, $v);
                }else{
                    throw new RequestParamsException("Prefix of node name must be a letter.[{$k}] presents.");
                }
                $element->appendChild($child_element);
            }
        }else{
            if(!empty($node_name) && is_string($node_name)){
                $element = $this->xml->createElement($node_name,base64_encode($node_value));
            }else{
                $element = $this->xml->createElement('item',base64_encode($node_value));
                $element->setAttribute('item','true');
                $element->setAttribute('index',$node_name);
            }
        }
        
        $element->setAttribute('type', strtolower(gettype($node_value)));
        
        return $element;
    }
    
    public function __toString()
    {
        return $this->getContent();
    }
}