<?php

namespace CasualJson\Transpile;

use CasualJson\Interfaces\ITranspiler;


                    
                    
class CasualJsonTranspiler implements ITranspiler
{
    
    protected function mergeObjectDefintions($str)
    {
        $defs = [];
        $idx = 0;
        $start = 0;
        $end = 0;
        $len = strlen($str);
        $in_string = false;
        $block_level = 0;
        
        for($idx = 0; $idx < $len; $idx++) {
            switch($str[$idx]) {
                case '"':
                    $in_string = !$in_string;
                    break;
                case '{':
                    if ($in_string)
                        break;
                    $block_level++;
                    if ($block_level == 1) {
                        $start = $idx;
                    }
                    break;
                case '}':
                    if ($in_string)
                        break;
                    $block_level--;
                    if ($block_level == 0) {
                        $end = $idx;

                    }

                    break;
                case ',':
                    if ($in_string || $block_level > 0)
                        break;
                        
                        $def = preg_replace('/(^\{|\}$)/', '', $str);
                        $def = preg_replace('/\}\s*,\s*\{/', ',', $str);
                        
                        
                        //$def = substr($str, $start, ($end - $start));
                        //$def = substr($def, 1) . substr($str, strlen($def)-2,1);
                        $defs[] = $def;
                        
                    break;
            }
        }
        return implode('', $defs);
        
    }
    
    protected function extractProperty($str, $propname, $offset)
    {
        $in_str = false;
        $block_level = 0;
        $tmp = $str;//substr($str, $offset);
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i]=='"') {
                $in_str = !$in_str;
            }
            if ($str[$i]=='{' && !$in_str) 
                $block_level++;
            if ($str[$i]=='}' && !$in_str) 
                $block_level--;
            
            if (!$in_str && $block_level==0 && $str[$i]=='}') {
                $tmp = substr($str, $offset, $i+$offset);
                $tmp = substr($str, 0, $offset) . substr($str, $offset+$i);
                break;
            }
        }
        return $tmp;
        
    }
    
    protected function expandCompoundNames($json)
    {       
        $re = '/([a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)+)(?:\s*:)/';
        $buffer = $json;
        $length_change = 0;
        $nameCache = [];
        $nameCacheParents = [];
        $valueCache = [];
        
        if (preg_match_all($re, $buffer, $m, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)>0) {
            $length_change = 0;        
            
            //build the cache to avoid duplicate properties
            $matchIdx = -1;
            foreach($m as $match) {
                $matchIdx++;
                $str = $match[1][0];
                $parts = explode('.', $str);
                $tempbuf = '';
                $parentIdx = -1;                
                
                for($i=0; $i < count($parts); $i++) {                       
                    $tempbuf .= $parts[$i];                    
                    //if ($i < count($parts)-1) {
                        $nameCache[] = $tempbuf;
                        $parentIdx = index_of($tempbuf, $nameCache);
                        $nameCacheParents[$tempbuf] = $parentIdx; 
                        $tempbuf .= '.';
                    //} else {
                        //add value item to cache?
                   // }                    
                }
            }
            
            //populate the value cache
            
            /*
            for ($i = count($m)-1; $i >= 0; $i--) {
                $match_str = $m[$i][1][0];
                $match_len = strlen($match_str);
                $match_offset = $m[$i][1][1];
                $start = $match_offset+$match_len;
                $parts = explode('.',$match_str);
                $newarray = [];
                $tmp = '';
                $last = $newarray;
                $temp = [];
                
                $value_len = str_next_char($buffer, ',', $start, true) - $match_len - $match_offset;
                $value_str = substr($buffer, $start, $value_len);
                $complete = $match_str.$value_str;
                
                if ($i <= count($m)-1) {
                    $cache_search = $match_str;//extract_parts($match_str,'.',0,$i);
                    if (strlen($cache_search) > 0 && $cache_search[strlen($cache_search)-1]=='.')
                        $cache_search = substr($cache_search,0,strlen($cache_search)-1);
                    if (isset($nameCacheParents[$cache_search])) {
                        $atemp = (isset($valueCache[$cache_search]) ? $valueCache[$cache_search] : []);
                        //if ($cache_search != '')
                            //$valueCache[$cache_search] = array_merge($atemp, [$match_str]);
                    }
                }
                
                if (isset($cache_search) && in_array($cache_search, $nameCacheParents)) {
                    if (isset($nameCacheParents[$cache_search])) {
                        $cache_idx = $nameCacheParents[$cache_search];
                        for ($tmpidx = 0; $tmpidx <= charcount($cache_search,'.'); $tmpidx++) {
                            $tmpstr = extract_parts($cache_search,'.',0,$tmpidx);
                            $atemp = isset($valueCache[$match_str]) && is_array($valueCache[$match_str]) ? $valueCache[$match_str] : [];//$nameCache[$cache_idx]])?$valueCache[$nameCache[$cache_idx]]:[];
                            echo $tmpstr.PHP_EOL;//extract_parts($cache_search,'.',0,0).PHP_EOL;//charcount($match_str,'.')-1) .PHP_EOL;
                            
                            $atemp = isset($valueCache[$tmpstr]) && is_array($valueCache[$tmpstr]) ? $valueCache[$tmpstr] : [];//$nameCache[$cache_idx]])?$valueCache[$nameCache[$cache_idx]]:[];
                            echo extract_parts($cache_search,'.',0,0).PHP_EOL;//charcount($match_str,'.')-1) .PHP_EOL;
                            //if ($cache_search == $tmpstr)
                            if ($tmpidx+1 > charcount($cache_search,'.')) {
                                $tmpstr2 = extract_parts($cache_search,'.',0,$tmpidx+1);
                                $valueCache[$tmpstr] = array_merge($atemp, [preg_replace('/^\s*:\s* /', ', $value_str, 1)]);
                            }
                        }

                        //$atemp = isset($valueCache[$match_str]) && is_array($valueCache[$match_str]) ? $valueCache[$match_str] : [];//$nameCache[$cache_idx]])?$valueCache[$nameCache[$cache_idx]]:[];
                       // echo extract_parts($cache_search,'.',0,0).PHP_EOL;//charcount($match_str,'.')-1) .PHP_EOL;
                       // $valueCache[$match_str] = array_merge($atemp, [$value_str]);
                    }
                }
            }
            $tempcache = [];//array_ $valueCache;
            
            foreach($valueCache as $name=>$value) {
                $value =implode(',',$value);
                $tempcache[$name] = $value;//$this->mergeObjectDefintions(implode(',',$value));
               
            }
            $valueCache = $tempcache;
            */
            
             
            //finally:
            
                    

            
            echo "\n\nNAMECACHE:-========================\n";
            echo '$nameCache=';
            print_r($nameCache);
            echo '$nameCacheParents=';
            print_r($nameCacheParents);
            echo '$valueCache=';
            print_r($valueCache);
            
            $insertIndexes = [];
            
            for ($i = count($m)-1; $i >= 0; $i--) {
                $match_str = $m[$i][1][0];
                $match_len = strlen($match_str);
                $match_offset = $m[$i][1][1];
                $start = $match_offset+$match_len;
                echo 'found '.$match_str.PHP_EOL;
                
                $value_len = str_next_char($buffer, ',', $start, true) - $match_len - $match_offset;
                $value_str = substr($buffer, $start, $value_len);
                if (isset($valueCache[$match_str])) {//, array_keys($valueCache))) {// extract_parts($match_str,'.',0,0), $valueCache)) {
                    //echo '======================+++*(**********'.PHP_EOL;

                    $parts = explode('.', $match_str);
                    $class_array = nestArray($parts);
                    //$buffer = preg_replace('/'.preg_quote($match_str.$value_str).'/', '', $buffer); //$this->extractProperty($buffer, $value, 0);
                    
                   print_r($class_array );
                    //if (isset($valueCache[$match_str])) {
                    $value_str = $valueCache[$match_str];//implode(', ', $valueCache[index_of($match_str,$valueCache)]);
                    $value_len = strlen($value_str);
                    
                    //$class_array 
                    if (!isset($class_array[$match_str]))
                       // $insertIndexes[$match_str] = $match_offset;
                    //$buffer = preg_replace('/'.$match_str.$value_str.'/', $valueCache[$match_str], $buffer);
                    
                    echo '$buffer='.$buffer."\n";
                   // }
                }
                $complete = $match_str.$value_str;
                
                /*
                if ($i <= count($m)-1) {
                    $cache_search = $match_str;//extract_parts($match_str,'.',0,$i);
                    if (strlen($cache_search) > 0 && $cache_search[strlen($cache_search)-1]=='.')
                        $cache_search = substr($cache_search,0,strlen($cache_search)-1);
                    if (isset($nameCacheParents[$cache_search])) {
                        $atemp = (isset($valueCache[$cache_search]) ? $valueCache[$cache_search] : []);
                        //if ($cache_search != '')
                            //$valueCache[$cache_search] = array_merge($atemp, [$match_str]);
                    }
                }
                
                if (isset($cache_search) && in_array($cache_search, $nameCacheParents)) {
                    if (isset($nameCacheParents[$cache_search])) {
                    $cache_idx = $nameCacheParents[$cache_search];
                    $atemp = isset($valueCache[$match_str]) && is_array($valueCache[$match_str]) ? $valueCache[$match_str] : [];//$nameCache[$cache_idx]])?$valueCache[$nameCache[$cache_idx]]:[];
                    $valueCache[$match_str][] = $value_str;// = array_merge($atemp, [$value_str]);
                    
                    for ($o = 0; $o < count($m); $o++) {
                        $match_str = $m[$o][1][0];
                        $match_len = strlen($match_str);
                        $match_offset = $m[$o][1][1];
                        $start = $match_offset+$match_len;
                        
                        $value_len = str_next_char($buffer, ',', $start, true) - $match_len - $match_offset;
                        $value_str = substr($buffer, $start, $value_len);
                        $complete = $match_str.$value_str;
                        
                        //if (in_array($match_str, $valueCache))
                        //foreach($valueCache as $name=>$value) {
                            for ($n = 0; $n < charcount($match_str,'.')+1; $n++) {
                                $name = extract_parts($match_str,'.',0,$n);
                                echo '$name='.$name.PHP_EOL;
                                if (isset($valueCache[$name])) {
                                $value_str = implode(', ', $valueCache[$name]);
                                //for($j = 0; $j < count($value); $j++) {
                                  //  $value_str = $value[$j];
                                    $suffix = str_repeat(' }', charcount($name, '.'));            
                                    $replacement = preg_replace('/("?[a-z]+"?)\./', '"$1": { ', $name);
                                    $replacementvalue = insert_spaces($value_str,strpos($value_str,':')+1);
                                    $replacement = trim(preg_replace('/("?[a-z]+"?)$/', '"$1" '.$replacementvalue.$suffix, $replacement));
                                    
                                    $buffer = preg_replace('/'.preg_quote($complete).'/', $replacement, $buffer, 1);
                                }
                            //}
                            }
                    }
                    
                    //echo 'cache_idx = '.$cache_idx.PHP_EOL;
                    }
                } else {
                */
                $suffix = str_repeat(' }', charcount($match_str, '.'));            
                $replacement = preg_replace('/("?[a-z]+"?)\./', '$1: { ', $match_str);
                $replacementvalue = insert_spaces($value_str,strpos($value_str,':')+1);
                $replacement = trim(preg_replace('/("?[a-z]+"?)$/', '$1 '.$replacementvalue.$suffix, $replacement));
                
                $buffer = preg_replace('/'.preg_quote($complete).'/', $replacement, $buffer, 1);
                //}
            }        
        // } --original end
        

        }
        
        
        print_r($valueCache);
        
        return $buffer;
    }
    
    protected function processBooleanValues($json)
    {
        $result = $json;
        //convert non-numeric truthy values to boolean names
        $result = preg_replace('/\b(yes|on|enabled|"true")\b/', 'true', $result);
        $result = preg_replace('/(no|off|disnabled|"false")/', 'false', $result);
        
        return $result;
    }
    
    protected function ensureWrapped(string $json, string $prefix, string $suffix)
    {
        //ensure that $json has surrounding $prefix and $suffix, adding them only
        //if the prefix/suffix is missing.
        if (substr($json,0,strlen($prefix)) == $prefix)
            $prefix = '';
        if (substr($json,(strlen($json)-1)-strlen($suffix), strlen($suffix)) == $suffix)
            $suffix = '';
        return $prefix . $json . $suffix;        
    }
    
    protected function quoteNames($json)
    {
        $result = $json;
        //quote names
        $result = preg_replace('/("[a-zA-Z0-9\-_]+"|[a-zA-Z0-9\-_]+)\s*:/', '"$1":', $result);
        //fix names like ""name""
        $result = preg_replace('/"{2,}([a-zA-Z0-9\-_]+)"{2,}\s*:/', '"$1":', $result);
        
        return $result;
    }
    
    public function transpile($json)
    {
        $result = trim($json);
        $result = $this->expandCompoundNames($result);
        $result = $this->processBooleanValues($result);        
        $result = $this->quoteNames($result);
        $result = $this->ensureWrapped($result, '{', '}');
        
        //format result?
        //$format_options = new \json_format_options;
        //json_format($result);
        
        return $result;
    }
    
}