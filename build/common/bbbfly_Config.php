<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
class bbbfly_Config
{
  protected static $configs = array();

  private function __construct(){}

  public static function load($path,$alias='default'){
    if(!is_string($path) || !is_string($alias)){return false;}

    if(is_file($path)){
      if(
        isset(self::$configs[$alias])
        && (self::$configs[$alias]['path'] === $path)
      ){return true;}

      $data = null;
      $ext = strtolower(pathinfo($path,PATHINFO_EXTENSION));

      switch($ext){
        case 'ini':
          $data = parse_ini_file($path,true);
        break;
        case 'json':
          $data = json_decode(file_get_contents($path,true),true);
        break;
        case 'xml':
          $xml = simplexml_load_file(
            $path,'SimpleXMLElement',
            LIBXML_COMPACT|LIBXML_NOCDATA
          );

          if($xml instanceof SimpleXMLElement){
            $data = json_decode(json_encode($xml),true);
          }
        break;
      }

      if(is_array($data)){
        self::$configs[$alias] = array(
          'path' => $path,
          'type' => $ext,
          'config' => $data
        );
        return true;
      }
    }
    return false;
  }

  public static function get($key,$alias='default'){
    if(!is_string($key) || !is_string($alias)){return null;}
    if(!isset(self::$configs[$alias])){return null;}

    $config = self::$configs[$alias]['config'];
    $keys = explode('.',$key);

    foreach($keys as $keyName){
      if(!is_array($config)){return null;}
      if(isset($config[$keyName])){$config = $config[$keyName];}
      else{return null;}
    }
    return $config;
  }
}