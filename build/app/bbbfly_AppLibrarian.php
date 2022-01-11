<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

require_once(dirname(__FILE__).'/../common/bbbfly_Require.php');

class bbbfly_AppLibrarian
{
  const DEF_FILENAME_APP = 'controls-app.json';
  const DEF_FILENAME_LIB = 'controls.json';

  const PKG_APP_ID = '~APP~';
  const PKG_APP_LIB = '~APP~';

  const PKG_USER_ID = '~USER~';
  const PKG_USER_LIB = '~USER~';

  protected $errors = array();
  protected $logErrors = true;

  protected $appDir = null;
  protected $libDirs = array();

  protected $appDef = null;
  protected $libDefs = array();

  protected $packages = array();
  protected $packageStack = null;
  protected $pathStack = null;

  public function __construct(){
    $this->packageStack = new bbbfly_AppLibrarian_PackageStack();
    $this->pathStack = new bbbfly_AppLibrarian_PathStack();
  }

  protected function clear(){
    $this->appDir = null;
    $this->libDirs = array();

    $this->appDef = null;
    $this->libDefs = array();

    $this->packages = array();

    $this->clearPackages();
    $this->clearErrors();
  }

  protected function clearPackages(){
    $this->packageStack->clear();
    $this->pathStack->clear();
  }

  protected function clearErrors(){
    $this->errors = array();
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

  public function loadApp($serverPath,$clientPath=null){
    $this->clear();
    $def = $this->loadAppDef($serverPath);
    if(is_array($def)){
      if(isset($def['Libraries']) && is_array($def['Libraries'])){
        $libsPath = '';

        if(is_string($clientPath)){
          $libsPath = $clientPath.'/';
        }

        foreach($def['Libraries'] as $libId => $libDef){
          if(is_array($libDef)){

            if(isset($libDef['Version'])){
              $lib = self::libOpts($libId,$libDef['Version']);
              $this->loadLib($lib,'application');
            }
            if(is_string($libDef['Path'])){
              $path = self::clientPath($libsPath.'/'.$libDef['Path']);
              $this->libDirs[$libId] = $path;
            }
          }
        }
      }
    }

    if($this->logErrors){$this->logErrors();}
    return !$this->hasErrors();
  }

  protected function getLibPath($libId){
    if(is_string($libId) && isset($this->libDirs[$libId])){

      $path = $this->libDirs[$libId];
      if(is_string($path)){return $path;}
    }
    return null;
  }

  public function exportLibPaths(){
    $this->clearErrors();
    return $this->libDirs;
  }

  public function exportLibFilePaths($libs=null,$debug=false,$restrict=false){
    $this->clearPackages();
    $this->clearErrors();

    $prnt = self::pkgOpts(
      self::PKG_USER_ID,
      self::PKG_USER_LIB
    );
    if(is_null($libs)){
      if(
        is_array($this->appDef)
        && isset($this->appDef['Libraries'])
        && is_array($this->appDef['Libraries'])
      ){
        $libs =& $this->appDef['Libraries'];
        $prnt = self::pkgOpts(
          self::PKG_APP_ID,
          self::PKG_APP_LIB
        );
      }
    }
    $this->mapPackages($libs,$prnt);
    $paths = $this->getFilePaths($libs,$prnt,$debug);
    if($restrict){
      $this->pathStack->restrictPaths();
    }
    if($this->logErrors){$this->logErrors();}
    return $this->hasErrors() ? array() : $paths;
  }

  protected function loadLib($lib,$prnt){
    $def = $this->currentLib($lib,$prnt);
    if(!is_null($def)){return $def;}
    $def = $this->loadLibDef($lib,$prnt);
    if(!is_array($def)){return $def;}
    if(
      isset($def['RequiredLibraries'])
      && is_array($def['RequiredLibraries'])
    ){
      foreach($def['RequiredLibraries'] as $reqId => $reqVersion){
        $reqLib = self::libOpts($reqId,$reqVersion);
        $this->loadLib($reqLib,$lib);
      }
    }
  }

  protected function loadAppDef($path){
    $def = self::loadDef($path,self::DEF_FILENAME_APP);
    if(!is_array($def)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_APP_DEF,
        array('path' => $path)
      );
    }

    $this->appDir = $path;
    $this->appDef =& $def;
    return $def;
  }

  protected function loadLibDef($lib,$prnt){
    $appLibDef = $this->getAppLibDef($lib->id);

    if(is_array($appLibDef)){
      $libVer = isset($appLibDef['Version']) ? $appLibDef['Version'] : '';
      if($lib->version !== $libVer){$appLibDef = null;}
    }

    if(!is_array($appLibDef)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB,
        array('parent' => $prnt,'lib' => $lib)
      );
    }

    $path = null;
    if(is_string($appLibDef['Path'])){
      $path = $this->serverLibPath($appLibDef['Path']);
    }

    if(!is_string($path)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_PATH,
        array('parent' => $prnt,'lib' => $lib)
      );
    }
    $libDef = self::loadDef($path,self::DEF_FILENAME_LIB);
    if(!is_array($libDef)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_DEF,
        array('parent' => $prnt,'lib' => $lib,'path' => $path)
      );
    }
    $realLib = self::libOpts($libDef['Lib'],$libDef['Version']);
    if(($lib->id !== $realLib->id) || ($lib->version !== $realLib->version)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_LIB_INVALID,
        array('parent' => $prnt,'required' => $lib,'real' => $realLib)
      );
    }
    $this->libDefs[$lib->id] =& $libDef;
    return $libDef;
  }

  protected function getLib($libId){
    if(
      isset($this->libDefs[$libId])
      && is_array($this->libDefs[$libId])
    ){
      $libDef =& $this->libDefs[$libId];
      return self::libOpts($libDef['Lib'],$libDef['Version']);
    }
    return null;
  }

  protected function currentLib($lib,$prnt){
    if(
      isset($this->libDefs[$lib->id])
      && is_array($this->libDefs[$lib->id])
    ){
      $current =& $this->libDefs[$lib->id];
      $currentLib = self::libOpts($current['Lib'],$current['Version']);

      if($lib->version !== $currentLib->version){
        return $this->riseError(
          bbbfly_AppLibrarian_Error::ERROR_LIB_CONFLICT,
          array('parent' => $prnt,'lib' => $lib,'current' => $currentLib)
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

  protected function serverLibPath($path){
    if(!is_string($path) || !is_array($this->appDef)){return null;}
    $appDef =& $this->appDef;

    $libPath = '';
    if(is_string($this->appDir)){$libPath .= $this->appDir;}
    if(is_string($appDef['LibsPath'])){$libPath .= $appDef['LibsPath'];}
    return self::serverDirPath($libPath.$path);
  }

  protected function getAppLibDef($libId){
    return self::getMember($this->appDef,'Libraries',$libId);
  }

  protected static function getMember(&$stack,$holderId,$memberId){
    if(is_array($stack) && isset($stack[$holderId])){
      if(!is_array($stack[$holderId])){return null;}

      $stack =& $stack[$holderId];
      if(isset($stack[$memberId])){
        return $stack[$memberId];
      }
    }
    return null;
  }

  protected static function addMember(&$stack,$holderId,$memberId,&$member){
    if(
      is_array($stack) && is_string($holderId)
      && is_string($memberId) && $member
    ){
      if(!isset($stack[$holderId])){$stack[$holderId] = array();}

      if(is_array($stack[$holderId])){
        $stack[$holderId][$memberId] = $member;
        return true;
      }
    }
    return false;
  }

  protected static function getMemberDef(&$stack,$holderId,$memberId){
    $def = self::getMember($stack,$holderId,$memberId);
    return is_array($def) ? $def : null;
  }

  protected function getPackage($pkg){
    $def = self::getMember($this->packages,$pkg->lib,$pkg->id);
    return is_object($def) ? $def : null;
  }

  protected function addPackage($pkg,&$package){
    if(is_object($package)){
      return self::addMember(
        $this->packages,$pkg->lib,$pkg->id,$package
      );
    }
    return false;
  }

  protected function mapPackages(&$libs,$prnt){
    if(!is_array($libs)){return null;}

    $packages = array();
    foreach($libs as $libId => $libDef){
      if(
        is_array($libDef)
        && isset($libDef['Packages'])
        && is_array($libDef['Packages'])
      ){
        foreach($libDef['Packages'] as $pkgId){
          $pkg = self::pkgOpts($pkgId,$libId);
          $package = $this->getPackage($pkg);

          if(!$package){$package = $this->mapPackage($pkg,$prnt);}
          if($package instanceof bbbfly_AppLibrarian_Package){
            $packages[] =& $package;
          }
          unset($package);
        }
      }
    }
    return $packages;
  }

  protected function mapPackage($pkg,$prnt){
    $pkgDef = $this->getPackageDef($pkg);

    if(!is_array($pkgDef)){
      return $this->riseError(
        bbbfly_AppLibrarian_Error::ERROR_PKG_INVALID,
        array('parent' => $prnt,'pkg' => $pkg)
      );
    }

    $path = $this->getLibPath($pkg->lib);
    $package = new bbbfly_AppLibrarian_Package($pkg,$pkgDef,$path);
    $this->addPackage($pkg,$package);

    if(isset($pkgDef['Libraries'])){
      $packages = $this->mapPackages($pkgDef['Libraries'],$pkg);
      $package->requirePackages($packages);
    }
    if(isset($pkgDef['Extends'])){
      $packages = $this->mapPackages($pkgDef['Extends'],$pkg);
      $package->extendPackages($packages);
    }
    return $package;
  }

  protected function stackPackages(&$libs,&$prnt){
    if(is_array($libs)){
      foreach($libs as $libId => $libDef){
        if(
          is_array($libDef)
          && isset($libDef['Packages'])
          && is_array($libDef['Packages'])
        ){
          foreach($libDef['Packages'] as $pkgId){
            $pkg = self::pkgOpts($pkgId,$libId);
            $package = $this->getPackage($pkg);

            if(!($package instanceof bbbfly_AppLibrarian_Package)){
              $this->riseError(
                bbbfly_AppLibrarian_Error::ERROR_PKG_INVALID,
                array('parent' => $prnt,'pkg' => $pkg)
              );
              continue;
            }

            $this->stackPackage($package,false);
          }
        }
      }
    }

    $this->resolveExtensions();
    return $this->packageStack->getPackages();
  }

  protected function stackPackage(&$package,$extend=false,$parents=array()){
    if(!($package instanceof bbbfly_AppLibrarian_Package)){return;}
    if(!is_array($parents)){return;}

    $canAdd = (count($package->extends) < 1);

    if(!$canAdd){
      if(!$extend){
        $this->packageStack->addExtension($package);
      }
      else{
        foreach($package->extends as $extended){
          if($this->packageStack->containsPackage($extended)){
            $canAdd = true;
            break;
          }
        }
      }
    }

    if($canAdd){
      $parents[$package->id] =& $package;

      foreach($package->requires as $reqPackage){
        if(isset($parents[$reqPackage->id])){

          $this->riseError(
            bbbfly_AppLibrarian_Error::ERROR_PKG_CIRCREQ,
            array('parent' => $package->pkg,'pkg' => $reqPackage->pkg)
          );
          continue;
        }

        $this->stackPackage($reqPackage,$extend,$parents);
      }
      $this->packageStack->addPackage($package);
    }
  }

  protected function resolveExtensions(){
    $extCnt = $this->packageStack->extensionsCnt();
    if($extCnt < 1){return;}

    foreach($this->packageStack->getExtensions() as $package){
      $this->stackPackage($package,true);
    }

    if($this->packageStack->extensionsCnt() !== $extCnt){
      $this->resolveExtensions();
    }
  }

  protected function getFilePaths(&$libs,&$prnt,$debug=false){
    $packages = $this->stackPackages($libs,$prnt);

    foreach($packages as $package){
      if(
        !($package instanceof bbbfly_AppLibrarian_Package)
        || !is_array($package->def)
      ){continue;}

      $def = $package->def;
      $lib = $this->getLib($package->pkg->lib);

      if(isset($def['Files'])&& is_array($def['Files'])){
        $this->stackLibFilePaths(
          $def['Files'],$package->path,$lib->version
        );
      }

      if($debug){
        if(isset($def['DebugFiles'])&& is_array($def['DebugFiles'])){
          $this->stackLibFilePaths(
            $def['DebugFiles'],$package->path,$lib->version
          );
        }
      }
      else{
        if(isset($def['ReleaseFiles']) && is_array($def['ReleaseFiles'])){
          $this->stackLibFilePaths(
            $def['ReleaseFiles'],$package->path,$lib->version
          );
        }
      }
    }

    return $this->pathStack->getPaths();
  }

  protected function stackLibFilePaths(&$files,$libPath,$libVersion){
    if(!is_array($files) || !is_string($libPath)){return;}

    foreach($files as $filePath){
      if($libVersion){
        $filePath .= (strpos($filePath,'?') === false) ? '?' : '&';
        $filePath .= 'lib_v='.$libVersion;
      }

      $this->pathStack->addPath(
        self::clientPath($libPath.$filePath)
      );
    }
  }

  protected function getPackageDef($pkg){
    if(
      isset($this->libDefs[$pkg->lib])
      && is_array($this->libDefs[$pkg->lib])
    ){
      $libDef =& $this->libDefs[$pkg->lib];
      return self::getMemberDef($libDef,'Packages',$pkg->id);
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
      $filePath = realpath(self::serverPath($path));

      if(is_string($filePath) && is_file($filePath)){
        return $filePath;
      }
    }
    return null;
  }

  protected static function serverPath($path){
    if(!is_string($path)){return $path;}

    $pattern = '~^([a-zA-Z]:)?[\\\|\\/].+~';
    $isAbs = preg_match($pattern,$path);

    return $isAbs
      ? bbbfly_Require::normalizePath($path)
      : bbbfly_Require::rootPath($path);
  }

  protected static function clientPath($path){
    if(is_string($path)){
      $path = str_replace('\\','/',$path);
      $path = preg_replace('/\/+/', '/',$path);
    }
    return $path;
  }

  protected function addError($error,$throw=false){
    if($error instanceof bbbfly_AppLibrarian_Error){
      $this->errors[] = $error;
      if($throw){throw $error;}
    }
  }

  protected function riseError($code,$options=null,$throw=false){
    $error = new bbbfly_AppLibrarian_Error($code,$options);

    $this->addError($error);
    return $error;
  }

  public function hasErrors(){
    return (count($this->errors) > 0);
  }

  protected function getErrors(){
    $errors = array();
    foreach($this->errors as $error){
      if($error instanceof bbbfly_AppLibrarian_Error){
        $errors[] = $error->export();
      }
    }
    return $errors;
  }

  protected function logErrors(){
    $errors = $this->getErrors();
    foreach($errors as $error){
      error_log($error);
    }
  }
}

class bbbfly_AppLibrarian_Package
{
  protected static $lastId = 0;
  protected static $packages = array();

  protected $_id = null;
  protected $_pkg = null;
  protected $_def = null;
  protected $_path = '';

  protected $_requires = array();
  protected $_requiredBy = array();
  protected $_extends = array();
  protected $_extendedBy = array();

  public function __construct(&$pkg,&$def,$path){
    $this->_id = ++self::$lastId;
    self::$packages[$this->id] =& $this;

    if(is_object($pkg)){$this->_pkg =& $pkg;}
    if(is_array($def)){$this->_def =& $def;}
    if(is_string($path)){$this->_path = $path;}
  }

  public function __get($propName){
    switch($propName){
      case 'id': return $this->_id;
      case 'pkg': return $this->_pkg;
      case 'def': return $this->_def;
      case 'path': return $this->_path;
      case 'requires': return $this->_requires;
      case 'requiredBy': return $this->_requiredBy;
      case 'extends': return $this->_extends;
      case 'rextendedBy': return $this->_extendedBy;
    }
  }

  public function requirePackages(&$packages){
    if(is_array($packages)){
      foreach($packages as $package){
        if($package instanceof bbbfly_AppLibrarian_Package){
          $package->requireByPackage($this);
          $this->_requires[$package->id] =& $package;
        }
        unset($package);
      }
    }
  }

  public function requireByPackage(&$package){
    if($package instanceof bbbfly_AppLibrarian_Package){
      $this->_requiredBy[$package->id] =& $package;
    }
  }

  public function extendPackages(&$packages){
    if(is_array($packages)){
      foreach($packages as $package){
        if($package instanceof bbbfly_AppLibrarian_Package){
          $package->extendByPackage($this);
          $this->_extends[$package->id] =& $package;
        }
        unset($package);
      }
    }
  }

  public function extendByPackage(&$package){
    if($package instanceof bbbfly_AppLibrarian_Package){
      $this->_extendedBy[$package->id] =& $package;
    }
  }

  public static function getById($id){
    return isset(self::$packages[$id])
      ? self::$packages[$id] : null;
  }
}

class bbbfly_AppLibrarian_PackageStack
{
  protected $_stack = array();
  protected $_packages = array();
  protected $_extensions = array();

  public function clear(){
    $this->_stack = array();
    $this->_packages = array();
    $this->_extensions = array();
  }

  public function addPackage(&$package){
    if(
      ($package instanceof bbbfly_AppLibrarian_Package)
      && !isset($this->_packages[$package->id])
    ){
      unset($this->_extensions[$package->id]);

      $this->_packages[$package->id] = true;
      $this->_stack[] = $package;
    }
  }

  public function addExtension(&$package){
    if(
      ($package instanceof bbbfly_AppLibrarian_Package)
      && !isset($this->_extensions[$package->id])
      && !isset($this->_packages[$package->id])
    ){
      $this->_extensions[$package->id] = $package;
    }
  }

  public function containsPackage(&$package){
    return (
      ($package instanceof bbbfly_AppLibrarian_Package)
      && isset($this->_packages[$package->id])
    );
  }

  public function extensionsCnt(){
    return count($this->_extensions);
  }

  public function getExtensions(){
    return $this->_extensions;
  }

  public function getPackages(){
    return $this->_stack;
  }


}

class bbbfly_AppLibrarian_PathStack
{
  protected $_stack = array();
  protected $_paths = array();
  protected $_restrict = array();

  public function clear($restrictions=false){
    $this->_stack = array();
    $this->_paths = array();

    if($restrictions){
      $this->_restrict = array();
    }
  }

  public function addPath($path){
    if(
      is_string($path)
      && !isset($this->_paths[$path])
      && !isset($this->_restrict[$path])
    ){
      $this->_paths[$path] = true;
      $this->_stack[] = $path;
    }
  }

  public function restrictPaths(){
    foreach($this->_paths as $path => $value){
      $this->_restrict[$path] = $value;
    }
  }

  public function getPaths(){
    return $this->_stack;
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
  const ERROR_PKG_INVALID = 301;
  const ERROR_PKG_CIRCREQ = 302;

  protected $options = null;

  public function __construct($code,$options){
    parent::__construct('',$code);
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
      case self::ERROR_PKG_CIRCREQ:
        $message .= ' Packages circular requirement.';
      break;
    }

    if(is_array($this->options) || is_object($this->options)){
      $message .= ' '.json_encode((object)$this->options);
    }

    return $message;
  }
}