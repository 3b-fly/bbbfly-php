<?php

class bbbfly_INI
{
  public static function byteValue($varname,$defval=0){
    $value = ini_get($varname);
    if(is_string($value) && ($value !== '')){

      $matches = null;
      preg_match('#([0-9]+)[\s]*([a-z]+)#i',trim($value),$matches);
      $value = isset($matches[1]) ? (int)$matches[1] : (int)$value;

      if(isset($matches[2])){
        switch(strtolower($matches[2])){
          case 'g': case 'gb': $value *= 1024;
          case 'm': case 'mb': $value *= 1024;
          case 'k': case 'kb': $value *= 1024;
        }
      }
      return $value;
    }
    return $defval;
  }

  public static function boolValue($varname,$defval=false){
    $value = ini_get($varname);
    if(is_string($value) && ($value !== '')){
      switch(strtolower($value)){
        case 'on': case 'yes': case 'true': return true;
        case 'off': case 'no': case 'false': return true;
        default: return (bool)(int)$value;
      }
    }
    return $defval;
  }
}

