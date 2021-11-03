<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>

<?php
class bbbfly_CookieSettings
{
  public static function process(){
    $clear = (isset($_REQUEST['clear']) && ($_REQUEST['clear'] === '1'));
    $load = (isset($_REQUEST['load']) && ($_REQUEST['load'] === '1'));
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

    if($clear || $load){
      self::setHeaders();

      print('(function(){'.PHP_EOL);

      if($clear){
        self::clear();
        print('var s = new Array();'.PHP_EOL);
      }
      elseif($load){
        print('var s = '.json_encode(self::load()).';'.PHP_EOL);
      }

      if($id){
        print(
          'if(typeof ngset_do_load === "function"){'
            . 'ngset_do_load("'.$id.'",s);'
          .'}'.PHP_EOL
        );
      }
      else{
        print('ngLoadedSettings = s;'.PHP_EOL);
      }

      print('})();'.PHP_EOL);
    }
  }

  public static function setHeaders(){
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header("Content-Type: application/x-javascript; charset=UTF-8");
  }

  protected static function clear(){
    $expire = (time() - 3600);
    for($i=1;$i<=50;$i++){
      setcookie('_ngs'.$i,'',$expire);
    }
  }

  protected static function load(){
    $params = '';
    $paramsLength = 0;

    for($i=1;$i<50;$i++)
    {
      $index = '_ngs'.$i;
      if(!isset($_COOKIE[$index]) || !$_COOKIE[$index]){break;}
      $value = $_COOKIE[$index];
      $params.= $value;
      $length = strlen($value);
      if(($paramsLength)&&($length != $paramsLength)) break;
      $paramsLength = $length;
    }

    $out_stack = null;
    $out_name_stack = null;

    $settings = new stdClass();
    if($params){
      $namevals = explode('@',$params);
      $cnt = count($namevals);
      for($i=0;$i<$cnt;$i++){
        $name = null;
        $val = null;
        list($name,$val) = explode('-',$namevals[$i],2);
        if(!$name){continue;}

        if(($name === '}') && (!$val) && (is_array($out_stack))){
          $nout = $settings;
          $settings = array_pop($out_stack);
          $name = array_pop($out_name_stack);
          $settings->{$name} = $nout;
        }
        elseif((!$val) && ($i+1<$cnt) && ($namevals[$i+1] === '{')){
          if(!is_array($out_stack)){
            $out_stack = array();
            $out_name_stack = array();
          }
          array_push($out_stack,$settings);
          array_push($out_name_stack,$name);
          $i++;
          $settings = new stdClass();
        }
        else{
          $settings->{$name} = $val;
        }
      }
    }

    foreach($settings as &$value){
      $value = urldecode(stripslashes(str_replace('%u0040','@',$value)));
    }

    return $settings;
  }
}

bbbfly_CookieSettings::process();