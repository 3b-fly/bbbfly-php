<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

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