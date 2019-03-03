<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
class bbbfly_AppLibrarian
{
  const DEF_FILENAME_APP = 'controls-app.json';
  const DEF_FILENAME_LIB = 'controls.json';

  protected static $logErrors = true;

  protected static $appDir = null;
  protected static $appDef = null;

  protected static $libDefs = array();
  protected static $errors = array();

  private function __construct(){}

  protected static function clear(){
    self::$appDir = null;
    self::$appDef = null;
    self::$libDefs = array();

    self::clearErrors();
  }

  protected static function clearErrors(){
    self::$errors = array();
  }

  protected static function libOpts($id,$version){
    $obj = new stdClass();
    $obj->id = is_string($id) ? $id : '';
    $obj->version = is_string($version) ? $version : '';
    return $obj;
  }

  public static function loadApp($path){
    self::clear();
    $def = self::loadAppDef($path);
    if(is_array($def)){
      if(is_array($def['Libraries'])){
        foreach($def['Libraries'] as $libId => $libDef){

          if(is_array($libDef) && isset($libDef['Version'])){
            $lib = self::libOpts($libId,$libDef['Version']);
            self::loadLib($lib,'application');
          }
        }
      }
    }

    if(self::$logErrors){self::logErrors();}
    return !self::hasErrors();
  }

  protected static function loadLib($lib,$parent){
    $def = self::currentLib($lib,$parent);
    if(!is_null($def)){return $def;}
    $def = self::loadLibDef($lib,$parent);
    if(!is_array($def)){return $def;}
    if(is_array($def['RequiredLibraries'])){
      foreach($def['RequiredLibraries'] as $reqId => $reqVersion){
        $reqLib = self::libOpts($reqId,$reqVersion);
        self::loadLib($reqLib,$lib);
      }
    }
  }

  protected static function loadAppDef($path){
    $def = self::loadDef($path,self::DEF_FILENAME_APP);
    if(!is_array($def)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_APP_DEF,
        array('path' => $path)
      );
    }

    self::$appDir = $path;
    self::$appDef =& $def;
    return $def;
  }

  protected static function loadLibDef($lib,$parent){
    $path = self::libPath($lib->id,$lib->version);
    if(!is_string($path)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_PATH,
        array('parent' => $parent,'lib' => $lib)
      );
    }
    $libDef = self::loadDef($path,self::DEF_FILENAME_LIB);
    if(!is_array($libDef)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_DEF,
        array('parent' => $parent,'lib' => $lib,'path' => $path)
      );
    }
    $realLib = self::libOpts($libDef['Lib'],$libDef['Version']);
    if(($lib->id !== $realLib->id) || ($lib->version !== $realLib->version)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_INVALID,
        array('parent' => $parent,'required' => $lib,'real' => $realLib)
      );
    }
    self::$libDefs[$lib->id] =& $libDef;
    return $libDef;
  }

  protected static function currentLib($lib,$parent){
    if(is_array(self::$libDefs[$lib->id])){

      $current =& self::$libDefs[$lib->id];
      $currentLib = self::libOpts($current['Lib'],$current['Version']);

      if($lib->version !== $currentLib->version){
        return self::riseError(
          bbbfly_AppLibrarian_Error::ERROR_LIB_CONFLICT,
          array('parent' => $parent,'lib' => $lib,'current' => $currentLib)
        );
      }
      return $current;
    }
    return null;
  }

  protected static function loadDef($path,$file){
    $path = self::serverDirPath($path);

    if(is_string($path) && is_string($file)){
      $def = null;
      $jsonPath = $path.$file;
      $altJsonPath = $path.'_'.$file;

      if(is_file($jsonPath)){
        $def = file_get_contents($jsonPath);
      }
      elseif(is_file($altJsonPath)){
        $def = file_get_contents($altJsonPath);
      }

      if(is_string($def)){
        $def = json_decode($def,true);
        if(is_array($def)){return $def;}
      }
    }
    return null;
  }

  protected static function libPath($id,$version){
    if(!is_array(self::$appDef)){return null;}
    $appDef =& self::$appDef;

    if(
      is_array($appDef['Libraries'])
      && is_array($appDef['Libraries'][$id])
    ){
      $libDef =& $appDef['Libraries'][$id];
      $libVer = isset($libDef['Version']) ? $libDef['Version'] : '';
      if($libVer !== $version){return null;}

      $path = '';
      if(is_string(self::$appDir)){$path .= self::$appDir;}
      if(is_string($appDef['LibsPath'])){$path .= $appDef['LibsPath'];}
      if(is_string($libDef['Path'])){$path .= $libDef['Path'];}

      return self::serverDirPath($path);
    }
    return null;
  }

  protected static function serverDirPath($path){
    if(is_string($path)){
      $dirPath = realpath(self::serverPath($path));

      if(is_string($dirPath) && is_dir($dirPath)){
        return $dirPath.DIRECTORY_SEPARATOR;
      }
    }
    return null;
  }

  protected static function serverFilePath($path){
    if(is_string($path)){
      $filePath = realpath(self::serverPath($filePath));

      if(is_string($filePath) && is_file($filePath)){
        return $filePath;
      }
    }
    return null;
  }

  protected static function serverPath($path){
    if(is_string($path)){
      $path = str_replace(
        array('\\','/'),DIRECTORY_SEPARATOR,$path
      );
    }
    return $path;
  }

  protected static function clintPath($path){
    if(is_string($path)){
      $path = str_replace('\\','/',$path);
      $path = preg_replace('/\/+/', '/',$path);
    }
    return $path;
  }

  protected static function riseError($code,$options=null,$throw=false){
    $error = new bbbfly_AppLibrarian_Error($code,$options);

    self::$errors[] = $error;
    if($throw){throw $error;}
    return $error;
  }

  protected static function hasErrors(){
    return (count(self::$errors) > 0);
  }

  protected static function getErrors(){
    $errors = array();
    foreach(self::$errors as $error){
      if($error instanceof bbbfly_AppLibrarian_Error){
        $errors[] = $error->export();
      }
    }
    return $errors;
  }

  protected static function logErrors(){
    $errors = self::getErrors();
    foreach($errors as $error){
      error_log($error);
    }
  }
}

class bbbfly_AppLibrarian_Error extends Exception
{
  const ERROR_APP_DEF = 101;
  const ERROR_LIB_PATH = 201;
  const ERROR_LIB_DEF = 202;
  const ERROR_LIB_CONFLICT = 203;
  const ERROR_LIB_INVALID = 204;

  protected $options = null;

  public function __construct($code,$options){
    parent::__construct(null,$code);
    $this->options =& $options;
  }

  public function export(){
    $message = '[bbbfly_AppLibrarian_Error]:';

    switch($this->getCode()){
      case self::ERROR_APP_DEF:
        $message .= ' No valid definition in application path.';
      break;
      case self::ERROR_LIB_PATH:
        $message .= ' Invalid or missing library path.';
      break;
      case self::ERROR_LIB_DEF:
        $message .= ' No valid definition in library path.';
      break;
      case self::ERROR_LIB_CONFLICT:
        $message .= ' Different versions of the same library in use.';
      break;
      case self::ERROR_LIB_INVALID:
        $message .= ' Required library ID or version does not match.';
      break;
    }

    if(is_array($this->options) || is_object($this->options)){
      $message .= ' '.json_encode((object)$this->options);
    }

    return $message;
  }
}