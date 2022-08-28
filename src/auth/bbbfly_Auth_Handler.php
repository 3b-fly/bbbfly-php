<?php
abstract class bbbfly_Auth_Handler
{
  abstract public function authenticate();

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
}