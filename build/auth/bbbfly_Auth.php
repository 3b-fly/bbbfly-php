<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/


class bbbfly_Auth
{
  const METHOD_NONE = 0;
  const METHOD_BASIC = 1;

  private $_Method = self::METHOD_NONE;
  private $_Authenticated = false;
  private $_UserData = null;

  function __construct($options=null){
    $this->setOptions($options);
  }

  public function useConfig($key='Auth',$alias='default'){
    if(class_exists('bbbfly_Config',false)){
      self::setOptions(bbbfly_Config::get($key,$alias));
    }
    else{
      throw new Exception('Missing "bbbfly_Config" class.');
    }
  }

  public function __get($propName){
    switch($propName){
      case 'Method': return $this->_Method;
      case 'Authenticated': return $this->_Authenticated;
      case 'UserData': return $this->_UserData;
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'Method':
        switch($value){
          case self::METHOD_NONE:
          case 'none':
            $this->_Method = self::METHOD_NONE;
          case self::METHOD_BASIC:
          case 'basic':
            $this->_Method = self::METHOD_BASIC;
          break;
        }
      break;
    }
  }

  public function setOptions($options){
    if(isset($options['Method'])){$this->Method = $options['Method'];}
    return $this;
  }

  protected function getClientIPAddress(){
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

  protected function authenticate(){
    if($this->Authenticated){return;}

    switch($this->Method){
      case self::METHOD_BASIC:

        if(isset($_SERVER['PHP_AUTH_USER'])){
          $data = new bbbfly_Auth_UserData();
          $data->Id = $_SERVER['PHP_AUTH_USER'];
          $data->Name = $_SERVER['PHP_AUTH_USER'];
          $data->IPAddr = $this->getClientIPAddress();

          $this->_UserData = $data;
        }
      break;
    }
    $this->_Authenticated = true;
  }

  protected function getAuthData(){
    $this->authenticate();

    $data = new stdClass();
    $data->Method = $this->Method;
    $data->Authenticated = $this->Authenticated;
    $data->UserData = $this->UserData;
    return json_encode($data);
  }

  public function buildJS(){
?>
    <script type="text/javascript">
      if(bbbfly){bbbfly.AuthData = <?= $this->getAuthData(); ?>;}
    </script>
<?php
  }
}

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