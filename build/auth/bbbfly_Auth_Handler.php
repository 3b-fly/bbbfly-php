<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

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