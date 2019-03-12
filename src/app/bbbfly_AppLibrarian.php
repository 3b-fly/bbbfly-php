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

  protected static function pkgOpts($id,$libId){
    $obj = new stdClass();
    $obj->id = is_string($id) ? $id : '';
    $obj->lib = is_string($libId) ? $libId : '';
    return $obj;
  }

  public static function loadApp($path){
    self::clear();

    //load application definition
    $def = self::loadAppDef($path);

    //require all libraries
    if(is_array($def)){
      if(isset($def['Libraries']) && is_array($def['Libraries'])){
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

  public static function exportLibPaths($libsPath){
    self::clearErrors();
    $paths = array();

    if(is_array(self::$appDef) && is_array(self::$appDef['Libraries'])){
      $libsPath = is_string($libsPath) ? $libsPath.'/' : '';

      foreach(self::$appDef['Libraries'] as $libId => $libDef){
        if(is_array($libDef) && is_string($libDef['Path'])){
          $path = self::clintPath($libsPath.'/'.$libDef['Path']);
          $paths[$libId] = $path;
        }
      }
    }
    return $paths;
  }

  public static function exportLibFilePaths($libs=null,$debug=false){
    self::clearErrors();
    $parent = null;

    //use default libraries
    if(is_null($libs)){
      if(
        is_array(self::$appDef)
        && isset(self::$appDef['Libraries'])
        && is_array(self::$appDef['Libraries'])
      ){
        $libs =& self::$appDef['Libraries'];
        $parent = 'application';
      }
    }

    //get required packages
    $pkgFiles = array();
    self::addPackages($pkgFiles,$libs,$parent);

    //get file paths
    $paths = self::packagesToLibFilePaths($pkgFiles,$debug);

    //handle result
    if(self::$logErrors){self::logErrors();}
    return self::hasErrors() ? array() : $paths;
  }

  protected static function loadLib($lib,$parent){
    //get current version
    $def = self::currentLib($lib,$parent);
    if(!is_null($def)){return $def;}

    //load library definition
    $def = self::loadLibDef($lib,$parent);
    if(!is_array($def)){return $def;}

    //require all libraries on which it depends on
    if(
      isset($def['RequiredLibraries'])
      && is_array($def['RequiredLibraries'])
    ){
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
    //get library path
    $appLibDef = self::getAppLibDef($lib->id);

    if(is_array($appLibDef)){
      $libVer = isset($appLibDef['Version']) ? $appLibDef['Version'] : '';
      if($lib->version !== $libVer){$appLibDef = null;}
    }

    if(!is_array($appLibDef)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB,
        array('parent' => $parent,'lib' => $lib)
      );
    }

    $path = null;
    if(is_string($appLibDef['Path'])){
      $path = self::serverLibPath($appLibDef['Path']);
    }

    if(!is_string($path)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_PATH,
        array('parent' => $parent,'lib' => $lib)
      );
    }

    //get definition
    $libDef = self::loadDef($path,self::DEF_FILENAME_LIB);
    if(!is_array($libDef)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_DEF,
        array('parent' => $parent,'lib' => $lib,'path' => $path)
      );
    }

    //validate definition
    $realLib = self::libOpts($libDef['Lib'],$libDef['Version']);
    if(($lib->id !== $realLib->id) || ($lib->version !== $realLib->version)){
      return self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_INVALID,
        array('parent' => $parent,'required' => $lib,'real' => $realLib)
      );
    }

    //store definition
    self::$libDefs[$lib->id] =& $libDef;
    return $libDef;
  }

  protected static function currentLib($lib,$parent){
    if(
      isset(self::$libDefs[$lib->id])
      && is_array(self::$libDefs[$lib->id])
    ){
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

  protected static function serverLibPath($path){
    if(!is_string($path) || !is_array(self::$appDef)){return null;}
    $appDef =& self::$appDef;

    $libPath = '';
    if(is_string(self::$appDir)){$libPath .= self::$appDir;}
    if(is_string($appDef['LibsPath'])){$libPath .= $appDef['LibsPath'];}
    return self::serverDirPath($libPath.$path);
  }

  protected static function getAppLibDef($libId){
    return self::getMemberDef(self::$appDef,'Libraries',$libId);
  }

  protected static function getMemberDef(&$def,$holder,$memberId){
    if(is_array($def) && isset($def[$holder]) && is_array($def[$holder])){
      $def =& $def[$holder];

      if(isset($def[$memberId]) && is_array($def[$memberId])){
        return $def[$memberId];
      }
    }
    return null;
  }

  protected static function packagesToLibFilePaths(&$pkgFiles,$debug=false){
    $paths = array();
    if(is_array($pkgFiles)){
      foreach($pkgFiles as $libId => $pkgs){
        if(!is_array($pkgs) || (count($pkgs) < 1)){continue;}

        $libPaths = array();
        foreach($pkgs as $pkgDef){
          if(!is_array($pkgDef)){continue;}

          if(
            isset($pkgDef['Files'])
            && is_array($pkgDef['Files'])
          ){
            self::addPackagePaths($libPaths,$pkgDef['Files']);
          }

          if($debug){
            if(
              isset($pkgDef['DebugFiles'])
              && is_array($pkgDef['DebugFiles'])
            ){
              self::addPackagePaths($libPaths,$pkgDef['DebugFiles']);
            }
          }
          else{
            if(
              isset($pkgDef['ReleaseFiles'])
              && is_array($pkgDef['ReleaseFiles'])
            ){
              self::addPackagePaths($libPaths,$pkgDef['ReleaseFiles']);
            }
          }
        }

        if(count($libPaths) > 0){
          $paths[$libId] =& $libPaths;
        }
        unset($libPaths);
      }
    }
    return $paths;
  }

  protected static function addPackages(&$stack,&$libs,$parent){
    if(!is_array($libs) || !is_array($stack)){return;}

    foreach($libs as $libId => $libDef){
      if(
        is_array($libDef)
        && isset($libDef['Packages'])
        && is_array($libDef['Packages'])
      ){
        foreach($libDef['Packages'] as $pkgId){
          if(!self::getMemberDef($stack,$libId,$pkgId)){
            $pkg = self::pkgOpts($pkgId,$libId);
            self::addPackage($stack,$pkg,$parent);
          }
        }
      }
    }
  }

  protected static function addPackage(&$stack,$pkg,$parent){
    $pkgDef = self::getPackageDef($pkg);

    if(!is_array($pkgDef)){
      self::riseError(
        bbbfly_AppLibrarian_Error::ERROR_PKG_INVALID,
        array('parent' => $parent,'pkg' => $pkg)
      );
    }

    if(!isset($stack[$pkg->lib])){$stack[$pkg->lib] = array();}
    $stack[$pkg->lib][$pkg->id] =& $pkgDef;

    if(isset($pkgDef['Libraries']) && is_array($pkgDef['Libraries'])){
      self:: addPackages($stack,$pkgDef['Libraries'],$pkg);
    }
  }

  protected static function getPackageDef($pkg){
    if(
      isset(self::$libDefs[$pkg->lib])
      && is_array(self::$libDefs[$pkg->lib])
    ){
      $libDef =& self::$libDefs[$pkg->lib];
      return self::getMemberDef($libDef,'Packages',$pkg->id);
    }
    return null;
  }

  protected static function addPackagePaths(&$stack,&$files){
    if(is_array($stack) && is_array($files)){
      foreach($files as $filePath){
        $stack[] = self::clintPath($filePath);
      }
    }
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
  const ERROR_LIB = 201;
  const ERROR_LIB_PATH = 202;
  const ERROR_LIB_DEF = 203;
  const ERROR_LIB_CONFLICT = 204;
  const ERROR_LIB_INVALID = 205;
  const ERROR_PKG_INVALID = 206;

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
      case self::ERROR_LIB:
        $message .= ' Required library is not defined.';
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
      case self::ERROR_PKG_INVALID:
        $message .= ' Unknown package required.';
      break;
    }

    if(is_array($this->options) || is_object($this->options)){
      $message .= ' '.json_encode((object)$this->options);
    }

    return $message;
  }
}