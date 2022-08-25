<?php
class bbbfly_Auth_UserData
{
  public $Id = null;
  public $Name = null;
  public $IPAddr = null;

  public $SurName = null;
  public $PhoneNumber = null;

  public function __set($propName,$value){
    switch($propName){
      case 'Id': $this->Id = $value;
      case 'Name': $this->Name = $value;
      case 'IPAddr': $this->IPAddr = $value;

      case 'SurName': $this->SurName = $value;
      case 'PhoneNumber': $this->PhoneNumber = $value;
    }
  }
}