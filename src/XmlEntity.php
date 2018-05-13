<?php


namespace Jqqjj\SecurityApi;


class XmlEntity
{
    private $action;
    private $params;
    
    public function __construct($action,Array $params)
    {
        $this->action = $action;
        $this->params = $params;
    }
    
    public static function loadFromString($content)
    {
        
    }
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getContent()
    {
        
    }
    
    public function __toString()
    {
        return $this->getContent();
    }
}