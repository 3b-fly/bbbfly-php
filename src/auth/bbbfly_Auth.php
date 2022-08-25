<?php
require_once(dirname(__FILE__).'/bbbfly_Auth_Handler.php');
require_once(dirname(__FILE__).'/bbbfly_Auth_UserData.php');

class bbbfly_Auth
{
  const METHOD_NONE = 0;
  const METHOD_BASIC = 1;

  private static $Method = self::METHOD_NONE;
  private static $Handlers = array();

  private static $Authenticated = false;
  private static $UserData = null;

  public static function useConfig($key='Auth',$alias='default'){
    if(class_exists('bbbfly_Config',false)){
      self::setOptions(bbbfly_Config::get($key,$alias));
    }
    else{
      throw new Exception('Missing "bbbfly_Config" class.');
    }
  }

  public static function setOptions($ops){
    if(isset($ops['Method'])){self::setProp('Method',$ops['Method']);}
    return $this;
  }

  protected static function setProp($propName,$value){
    switch($propName){
      case 'Method':
        switch($value){
          case self::METHOD_NONE:
          case 'none':
            self::$Method = self::METHOD_NONE;
          case self::METHOD_BASIC:
          case 'basic':
            self::$Method = self::METHOD_BASIC;
          break;
        }
      break;
    }
  }

  protected static function getHandler(){
    $handlers =& self::$Handlers;
    $method = self::$Method;

    if(isset($handlers[$method])){
      return $handlers[$method];
    }

    $handler = null;

    switch($method){
      case self::METHOD_BASIC:
        require_once(dirname(__FILE__).'/bbbfly_Auth_Handler_Basic.php');
        $handler = new bbbfly_Auth_Handler_Basic();
      break;
    }

    if($handler instanceof bbbfly_Auth_Handler){
      $handlers[$method] = $handler;
    }
    return $handler;
  }

  protected static function getClientIPAddress(){
    if(isset($_SERVER['HTTP_CLIENT_IP'])){
      return $_SERVER['HTTP_CLIENT_IP'];
    }
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
      return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if(isset($_SERVER['REMOTE_ADDR'])){
      return $_SERVER['REMOTE_ADDR'];
    }
    return null;
  }

  public static function authenticate(){
    if(self::$Authenticated){return;}

    self::$UserData = null;
    $handler = self::gatHandler();

    if($handler instanceof bbbfly_Auth_Handler){
      $data = $handler->authenticate();

      if($data instanceof bbbfly_Auth_UserData){
        self::$UserData = $data;
      }
    }

    self::$Authenticated = true;
  }

  public static function getAuthData(){
    self::authenticate();

    $data = new stdClass();
    $data->Method = self::$Method;
    $data->Authenticated = self::$Authenticated;
    $data->UserData = self::$UserData;
    return json_encode($data);
  }

  public static function buildJS(){
    return '<script type="text/javascript">'
      .'if(bbbfly){bbbfly.AuthData = '.self::getAuthData().';}'
    .'</script>';
  }
}