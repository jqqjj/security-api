<?php


namespace Jqqjj\SecurityApi;


class XmlContent
{
    private $action;
    private $params;
    
    public function __construct($action,Array $params)
    {
        $this->action = $action;
        $this->params = $params;
    }
    
    public static function parseXmlContent($content)
    {
        
    }
}