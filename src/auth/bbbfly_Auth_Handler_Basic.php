<?php
require_once(dirname(__FILE__).'/bbbfly_Auth_Handler.php');
require_once(dirname(__FILE__).'/bbbfly_Auth_UserData.php');

class bbbfly_Auth_Handler_Basic extends bbbfly_Auth_Handler
{
  public function authenticate(){
    if(isset($_SERVER['PHP_AUTH_USER'])){
      $name = $_SERVER['PHP_AUTH_USER'];

      if(is_string($name) && ($name !== '')){
        $data = new bbbfly_Auth_UserData();
        $data->Id = $name;
        $data->Name = $name;
        $data->IPAddr = $this->getClientIPAddress();
        return $data;
      }
    }
    return null;
  }
}