<?php


namespace Jqqjj\SecurityApi;

use DOMDocument;

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
            $params->appendChild($this->createParamsElement($node_name,$node_value));
        }
        
        return $this->xml->saveXML();
    }
    
    private function createParamsElement($node_name,$node_value)
    {
        if(is_array($node_value)){
            $element = $this->xml->createElement((string)$node_name);
            foreach ($node_value as $k=>$v){
                $child_element = $this->createParamsElement($k, $v);
                $element->appendChild($child_element);
            }
        }else{
            $element = $this->xml->createElement($node_name,$node_value);
        }
        
        $element->setAttribute('node_type',gettype($node_name));
        $element->setAttribute('value_type',gettype($node_value));
        
        return $element;
    }
    
    public function __toString()
    {
        return $this->getContent();
    }
}