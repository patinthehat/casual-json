<?php

namespace CasualJson;

use CasualJson\Transpile\CasualJsonTranspiler;

class CasualJson
{
    protected $code;
    protected $transpiler;
    
    public function __construct($code = false)
    {
        $this->setCode( ((!is_string($code) || empty($code)) ? '' : $code) ) ;
        $this->transpiler = new CasualJsonTranspiler;
    }
    
    public function toJson()
    {
        if (empty($this->code))
            return '{}';
        
        return $this->transpiler->transpile($this->code);
    }
    
    public function fromJson($json)
    {
        $result = $json;
        
        //remove surrounding braces
        if (substr($result,0,1)=='{' && substr($result,strlen($result)-1,1)=='}') {
            $result = substr($result,1); 
            $result = substr($result,0, strlen($result)-2);
        }
        
        //unquote names
        $result = preg_replace('/"([a-zA-Z0-9_\-]+)"\s*:/', '$1:', $result);
        
        $this->setCode($result);
        return $this;
    }
    
    public function toObject()
    {
        $json = $this->toJson();
        return json_decode($json);
    }
    
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
}
