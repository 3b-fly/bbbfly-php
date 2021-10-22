<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
class bbbfly_Arguments
{
  protected static $initialized = false;
  protected static $arguments = array();

  private function __construct(){}

  public static function init(){
    if(self::$initialized){return;}

    self::processCLArgs();
    self::$initialized = true;
  }

  protected static function processCLArgs(){
    global $argv;
    global $argc;

    if(isset($argv) && ($argc > 1)){
      for($i=1;$i<$argc;$i++){
        $arg = explode('=',$argv[$i]);
        switch(count($arg)){
          case 0: break;
          case 1:
            self::$arguments[trim($arg[0])] = true;
          break;
          default:
            self::$arguments[trim($arg[0])] = trim($arg[1]);
          break;
        }
      }
    }
  }

  public static function getAll(){
    return self::$arguments;
  }

  public static function get($argName){
    return (is_string($argName) && isset(self::$arguments[$argName]))
      ? self::$arguments[$argName] : null;
  }
}

bbbfly_Arguments::init();