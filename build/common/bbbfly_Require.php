<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
require_once(dirname(__FILE__).'/../environment/bbbfly_Arguments.php');

class bbbfly_Require
{
  protected static $options = array(
    'PHPDir',
    'LibsDir',
    'LibPathMask'
  );

  protected static $props = array(
    'PHPDir' => 'php',
    'LibsDir' => 'php/libs',
    'LibPathMask' => '%lib%/%pkg%/%path%'
  );

  protected static $usedLibs = array();
  protected static $needSync = true;

  private function __construct(){}

  public static function useConfig($key='Require',$alias='default'){
    if(class_exists('bbbfly_Config',false)){
      self::setOptions(bbbfly_Config::get($key,$alias));
    }
    else{
      throw new Exception('Missing "bbbfly_Config" class.');
    }
  }

  public static function setOptions($options){
    if(is_array($options)){
      foreach(self::$options as $name){
        $propVal = $options[$name];
        $origVal = self::$props[$name];

        if(is_string($propVal) && ($propVal !== $origVal)){
          self::$props[$name] = $propVal;
          self::$needSync = true;
        }
      }
    }
    return $this;
  }

  protected static function syncIncludePaths(){
    if(!self::$needSync){return;}

    $paths = explode(PATH_SEPARATOR,get_include_path());
    $paths = array_fill_keys(self::normalizePath($paths),true);

    $paths['.'] = true;
    $paths[self::rootPath(self::$props['PHPDir'])] = true;
    $paths[self::rootPath(self::$props['LibsDir'])] = true;

    ini_set('include_path',implode(PATH_SEPARATOR,array_keys($paths)));

    self::$needSync = false;
  }

  protected static function rootPath($path=''){
    $rootPath = '';

    if(bbbfly_Arguments::get('native')){
      $root = bbbfly_Arguments::get('root');
      if(is_string($root)){$rootPath = $root;}
    }
    elseif(is_string($_SERVER['DOCUMENT_ROOT'])){
      $rootPath = $_SERVER['DOCUMENT_ROOT'];
    }

    if($rootPath){$rootPath .= DIRECTORY_SEPARATOR;}
    if(is_string($path)){$rootPath .= $path;}

    return self::normalizePath($rootPath);
  }

  protected static function normalizePath($path){
    if(is_string($path)){
      return str_replace(array('/','\\'),DIRECTORY_SEPARATOR,$path);
    }
    elseif(is_array($path)){
      foreach($path as &$pathVal){
        $pathVal = self::normalizePath($pathVal);
      }
      unset($pathVal);
      return $path;
    }
  }

  protected static function file($source,$path,$absolute=false){
    if(!is_string($source) || !is_string($path)){return;}

    $filePath = $source.DIRECTORY_SEPARATOR.$path;
    $filePath = ($absolute)
      ? self::rootPath($filePath)
      : self::normalizePath($filePath);

    $ext = pathinfo($filePath,PATHINFO_EXTENSION);
    if($ext === ''){$filePath .= '.php';}

    require_once($filePath);
  }

  public static function php($path,$absolute=false){
    self::file(self::$props['PHPDir'],$path,$absolute);
  }

  public static function lib($lib,$version,$package,$path,$absolute=false){
    if(!is_string($lib) || !is_string($path)){return;}
    if(!is_string($version)){$version = '';}
    if(!is_string($package)){$package = '';}

    if(isset(self::$usedLibs[$lib])){
      $usedVer = self::$usedLibs[$lib];
      if($version !== $usedVer){
        throw new Exception(
          "Mixing library versions [required=$version] [used=$usedVer]."
        );
      }
    }

    $libPath = self::$props['LibPathMask'];
    $libPath = str_replace(
      array('%lib%','%ver%','%pkg%','%path%'),
      array($lib,$version,$package,$path),
      $libPath
    );

    self::file(self::$props['LibsDir'],$libPath,$absolute);
    self::$usedLibs[$lib] = $version;
  }
}