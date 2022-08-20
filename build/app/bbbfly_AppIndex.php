<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

require_once(dirname(__FILE__).'/bbbfly_AppLoader.php');
require_once(dirname(__FILE__).'/../environment/bbbfly_Arguments.php');
require_once(dirname(__FILE__).'/../auth/bbbfly_Auth.php');

class bbbfly_AppIndex
{
  protected static $debug = false;
  protected static $nativeBuild = false;

  protected static $appLang = 'en';
  protected static $appName = null;
  protected static $appAuthor = null;
  protected static $appVersion = null;
  protected static $appCopyrights = null;

  protected static $settingsURL = null;
  protected static $serverURL = null;

  protected static $startParams = null;
  protected static $supportedLangs = null;
  protected static $resources = null;

  protected static $devices = null;
  protected static $fileSources = null;
  protected static $initFiles = null;
  protected static $loadFiles = null;
  protected static $deviceFiles = null;

  protected static $themeOptions = null;
  protected static $loaderOptions = null;
  protected static $authOptions = null;

  private function __construct(){}

  public static function useConfig($key='App.Index',$alias='default'){
    if(class_exists('bbbfly_Config',false)){
      self::setOptions(bbbfly_Config::get($key,$alias));
    }
    else{
      throw new Exception('Missing "bbbfly_Config" class.');
    }
  }

  protected static function mergeArrays($original,$new){
    if(!is_array($original) || !is_array($new)){return;}
    foreach($new as $key => $value){
      if(
        is_array($value)
        && isset($original[$key]) && is_array($original[$key])
      ){
        $original[$key] = self::mergeArrays($original[$key],$value);
      }
      else{
        $original[$key] = $value;
      }
    }
    return $original;
  }

  protected static function replaceStrings($subject,&$search,&$replace){
    if(is_string($subject) && is_array($search) && is_array($replace)){
      foreach($search as $key => $searchVal){
        $replaceVal = is_string($replace[$key]) ? $replace[$key] : '';
        $subject = str_replace($searchVal,$replaceVal,$subject);
      }
    }
    return $subject;
  }

  public static function setDebug($debug=true){
    if(is_bool($debug)){self::$debug = $debug;}
  }

  public static function setNativeBuild($native=true){
    if(is_bool($native)){self::$nativeBuild = $native;}
  }

  public static function setSettingsURL($url){
    if(is_string($url)){self::$settingsURL = $url;}
  }

  public static function setServerURL($url){
    if(is_string($url)){self::$serverURL = $url;}
  }

  public static function setStartParams($params,$merge=true){
    if(is_array($params)){
      self::$startParams = (is_array(self::$startParams) && $merge)
        ? self::mergeArrays(self::$startParams,$params) : $params;
    }
  }

  public static function setSupportedLangs($langs,$merge=true){
     if(is_array($langs)){
       $langs = array_fill_keys($langs,true);
       self::$supportedLangs = (is_array(self::$supportedLangs) && $merge)
         ? self::mergeArrays(self::$supportedLangs,$langs) : $langs;
     }
  }

  public static function setLangResources($resources,$merge=true){
    if(is_array($resources)){
      self::$resources = (is_array(self::$resources) && $merge)
         ? self::mergeArrays(self::$resources,$resources) : $resources;
    }
  }

  public static function setResourceLangs($resources,$merge=true){
    if(is_array($resources)){
      if(!$merge || !is_array(self::$resources)){
        self::$resources = array();
      }

      foreach($resources as $resName => $res){
        if(is_array($res)){
          foreach($res as $lang => $langRes){
            if(
              !isset(self::$resources[$lang])
              || !is_array(self::$resources[$lang])
            ){
              self::$resources[$lang] = array();
            }
            self::$resources[$lang][$resName] = $langRes;
          }
        }
      }
    }
  }

  public static function processLangs(){
    if(!is_array(self::$supportedLangs)){self::$supportedLangs = array();}

    if(is_string(self::$appLang)){
      if(
        !isset(self::$supportedLangs[self::$appLang])
        || !self::$supportedLangs[self::$appLang]
      ){
        self::$supportedLangs[self::$appLang] = true;
      }

      if(
        is_array(self::$resources)
        && isset(self::$resources[self::$appLang])
        && is_array(self::$resources[self::$appLang])
      ){
        $res =& self::$resources[self::$appLang];

        if(!is_string(self::$appName)){
          if(isset($res['appName']) && is_string($res['appName'])){
            self::$appName = $res['appName'];
          }
        }
        if(!is_string(self::$appCopyrights)){
          if(isset($res['appCopyrights']) && is_string($res['appCopyrights'])){
            self::$appCopyrights = $res['appCopyrights'];
          }
        }

        unset($res);
      }
    }
  }

  public static function setDevices($options,$merge=true){
    if(is_array($options)){
      self::$devices = (is_array(self::$devices) && $merge)
        ? self::mergeArrays(self::$devices,$options) : $options;
    }
  }

  public static function setFileSources($options,$merge=true){
    if(is_array($options)){
      self::$fileSources = (is_array(self::$fileSources) && $merge)
        ? self::mergeArrays(self::$fileSources,$options) : $options;
    }
  }

  public static function setInitFiles($options,$merge=true){
    if(is_array($options)){
      self::$initFiles = (is_array(self::$initFiles) && $merge)
        ? self::mergeArrays(self::$initFiles,$options) : $options;
    }
  }

  public static function setLoadFiles($options,$merge=true){
    if(is_array($options)){
      self::$loadFiles = (is_array(self::$loadFiles) && $merge)
        ? self::mergeArrays(self::$loadFiles,$options) : $options;
    }
  }

  public static function setDeviceFiles($options,$merge=true){
    if(is_array($options)){
      self::$deviceFiles = (is_array(self::$deviceFiles) && $merge)
        ? self::mergeArrays(self::$deviceFiles,$options) : $options;
    }
  }

  public static function setTheme($options,$merge=true){
    if(is_array($options)){
      self::$themeOptions = (is_array(self::$themeOptions) && $merge)
        ? self::mergeArrays(self::$themeOptions,$options) : $options;
    }
  }

  public static function setLoader($options,$merge=true){
    if(is_array($options)){
      self::$loaderOptions = (is_array(self::$loaderOptions) && $merge)
        ? self::mergeArrays(self::$loaderOptions,$options) : $options;
    }
  }

  public static function setAuth($options,$merge=true){
    if(is_array($options)){
      self::$authOptions = (is_array(self::$authOptions) && $merge)
        ? self::mergeArrays(self::$authOptions,$options) : $options;
    }
  }

  public static function processArgs(){
    $args = bbbfly_Arguments::getAll();

    if(isset($args['debug'])){self::$debug = $args['debug'];}
    if(isset($args['native'])){self::$nativeBuild = $args['native'];}

    if(isset($args['lang']) && is_string($args['lang'])){self::$appLang = $args['lang'];}
    if(isset($args['name']) && is_string($args['name'])){self::$appName = $args['name'];}
    if(isset($args['version']) && is_string($args['version'])){self::$appVersion = $args['version'];}

    unset($args);
  }

  public static function setOptions($options){
    if(!is_array($options)){return;}

    if(isset($options['appLang']) && is_string($options['appLang'])){self::$appLang = $options['appLang'];}
    if(isset($options['appName']) && is_string($options['appName'])){self::$appName = $options['appName'];}
    if(isset($options['appAuthor']) && is_string($options['appAuthor'])){self::$appAuthor = $options['appAuthor'];}
    if(isset($options['appVersion']) && is_string($options['appVersion'])){self::$appVersion = $options['appVersion'];}
    if(isset($options['appCopyrights']) && is_string($options['appCopyrights'])){self::$appCopyrights = $options['appCopyrights'];}

    if(isset($options['theme'])){self::setTheme($options['theme']);}
    if(isset($options['loader'])){self::setLoader($options['loader']);}
    if(isset($options['auth'])){self::setAuth($options['auth']);}

    if(isset($options['debug'])){self::setDebug($options['debug']);}
    if(isset($options['nativeBuild'])){self::setNativeBuild($options['nativeBuild']);}
    if(isset($options['settingsURL'])){self::setSettingsUrl($options['settingsURL']);}
    if(isset($options['serverURL'])){self::setServerURL($options['serverURL']);}

    if(isset($options['startParams'])){self::setStartParams($options['startParams']);}
    if(isset($options['supportedLangs'])){self::setSupportedLangs($options['supportedLangs']);}
    if(isset($options['langResources'])){self::setLangResources($options['langResources']);}
    if(isset($options['resourceLangs'])){self::setResourceLangs($options['resourceLangs']);}

    if(isset($options['devices'])){self::setDevices($options['devices']);}
    if(isset($options['fileSources'])){self::setFileSources($options['fileSources']);}
    if(isset($options['initFiles'])){self::setInitFiles($options['initFiles']);}
    if(isset($options['loadFiles'])){self::setLoadFiles($options['loadFiles']);}
    if(isset($options['deviceFiles'])){self::setDeviceFiles($options['deviceFiles']);}
  }

  public static function build($options){
    self::setOptions($options);
    self::processLangs();
    self::processArgs();
    self::buildHtml();
  }

  protected static function buildHtml(){
    $appName = is_string(self::$appName) ? self::$appName : '';
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

    <html xmlns="http://www.w3.org/1999/xhtml" lang="<?= self::$appLang; ?>">
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="imagetoolbar" content="no" />
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
        <meta name="viewport" content="width=device-width, user-scalable=no,
          initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="misapplication-tap-highlight" content="no" />

<?php self::buildAppMeta(); ?>

<?php self::buildTheme(self::$themeOptions); ?>

        <title><?= $appName; ?></title>

        <style type="text/css">
          body {
            margin:0;
            overflow:hidden;
            height:100%;
          }
          .ngApplication {
            position:absolute;
            left:0%;
            top:0%;
            width:100%;
            height:100%;
            overflow:hidden;
          }
        </style>
<?php self::buildLoader(self::$loaderOptions,false,true); ?>

<?php self::buildInitContent(self::$initFiles,self::$fileSources); ?>

<?php self::buildAuth(self::$authOptions); ?>

<?php self::buildJS(); ?>

      </head>
      <body scroll="no" onload="ngLoadApplication('ngApp')">
 <?php self::buildLoader(self::$loaderOptions,true,false); ?>
        <div id="ngApp" class="ngApplication"></div>

      </body>
    </html>
<?php
  }

  protected static function buildAppMeta(){
?>
        <meta http-equiv="Content-Language" content="<?= self::$appLang; ?>" />
<?php if(is_string(self::$appAuthor)){ ?>
        <meta name="author" content="<?= self::$appAuthor; ?>" />
<?php } ?>
<?php if(is_string(self::$appCopyrights)){ ?>
        <meta name="copyright" content="<?= self::$appCopyrights; ?>" />
<?php }
  }

  protected static function buildTheme($options){
    if(!is_array($options)){return;}
    $color = isset($options['color']) ? $options['color'] : null;
    $icons = isset($options['icons']) ? $options['icons'] : null;

    if(is_string($color)){ ?>
        <meta name="theme-color" content="<?= $color; ?>" />
<?php }

    self::buildIcons($icons);
  }

  protected static function buildIcons($options){
    if(!is_array($options)){return;}
    $favicon = isset($options['favicon']) ? $options['favicon'] : null;
    $apple = isset($options['apple-touch']) ? $options['apple-touch'] : null;
    $msapp = isset($options['msapplication']) ? $options['msapplication'] : null;

    if(is_array($favicon) && is_string($favicon['url'])){ ?>
        <link rel="icon" type="image/x-icon" href="<?= $favicon['url']; ?>" />
        <link rel="shortcut icon" href="<?= $favicon['url']; ?>" />
<?php }
    if(is_array($apple) && is_string($apple['url'])){ ?>
        <link rel="apple-touch-icon" href="<?= $apple['url']; ?>" />
        <link rel="apple-touch-icon-precomposed" href="<?= $apple['url']; ?>" />
<?php }
    if(is_array($msapp) && is_string($msapp['url'])){ ?>
        <meta name="msapplication-TileImage" content="<?= $msapp['url']; ?>" />
<?php
      if(is_string($msapp['color'])){ ?>
        <meta name="msapplication-TileColor" content="<?= $msapp['color']; ?>" />
<?php
      }
    }
  }

  protected static function buildDevices($devices){
    if(!is_array($devices)){return;}
?>
          var ngDevices = <?= json_encode($devices); ?>;
<?php
  }

  protected static function buildLoader($opts,$buildHtml=true,$buildCss=true){
    $options = array(
      'appName' => '%appName%',
      'appVersion' => 'v%appVersion%',
      'appCopyrights' => '%appCopyrights%<br />All rights reserved.'
    );

    if(is_array($opts)){$options = array_merge($options,$opts);}

    $search = array(
      '%appName%','%appVersion%',
      '%appAuthor%','%appCopyrights%'
    );

    $replace = array(
      self::$appName,self::$appVersion,
      self::$appAuthor,self::$appCopyrights
    );

    $options['appName'] = self::replaceStrings(
      $options['appName'],$search,$replace
    );
    $options['appVersion'] = self::replaceStrings(
      $options['appVersion'],$search,$replace
    );
    $options['appCopyrights'] = self::replaceStrings(
      $options['appCopyrights'],$search,$replace
    );

    $loader = new bbbfly_AppLoader($options);
    if($buildCss){$loader->buildCSS();}
    if($buildHtml){$loader->buildHTML();}
  }

  protected static function buildAuth($opts){
    $auth = new bbbfly_Auth($opts);
    $auth->useConfig();
    $auth->buildJS();
  }

  protected static function buildJS(){
?>
        <script type="text/javascript">
          var ngDEBUG = <?= json_encode(self::$debug); ?>;
          var ngVERSION = <?= json_encode(self::$appVersion); ?>;

<?php self::buildDevices(self::$devices); ?>

<?php self::buildLoadContent(self::$loadFiles,self::$fileSources); ?>

<?php self::buildDeviceContent(self::$deviceFiles,self::$fileSources,self::$devices); ?>

<?php self::buildFileSources(self::$fileSources); ?>

<?php self::buildStartParams(self::$startParams); ?>

<?php self::buildResources(self::$supportedLangs,self::$resources); ?>

          if(bbbfly.AppIndex){bbbfly.AppIndex.InitIndex();}

        </script>
<?php
  }

  protected static function buildStartParams($params){
?>
          function ngStartParams(){
            this.Name = '<?= self::$appName; ?>';
            this.Version = '<?= self::$appVersion; ?>';
            this.Author = '<?= self::$appAuthor; ?>';
            this.Copyrights = '<?= self::$appCopyrights; ?>';

            this.ServerURL = '<?= self::$serverURL; ?>';
            this.Native = <?= json_encode(self::$nativeBuild); ?>;

            this.Lang = '<?= self::$appLang; ?>';
            this.SupportedLangs = <?= json_encode(self::$supportedLangs); ?>;
            this.SupportedLangsLocked = false;

<?php
    if(is_array($params)){
      foreach($params as $key => $value){
?>
            this.<?= $key; ?> = <?= json_encode($value); ?>;
<?php
      }
    }

    if(is_string(self::$settingsURL)){
      ?>
            this.AppSettingsStorageURL = '<?= self::$settingsURL; ?>';
<?php } ?>
          }
<?php
  }

  protected static function buildResources($supportedLangs,$resources){
    if(!is_array($supportedLangs) || !is_array($resources)){return;}
?>
          if(typeof(ngc_Lang) !== 'object'){ngc_Lang = new Array();}
<?php
    foreach($resources as $lang => $langRes){
      if(
        is_string($lang) && is_array($langRes)
        && isset($supportedLangs[$lang]) && $supportedLangs[$lang]
      ){
?>
          if(typeof(ngc_Lang['<?= $lang; ?>']) !== 'object'){
            ngc_Lang['<?= $lang; ?>'] = new Array();
          }
<?php
        foreach($langRes as $resName => $res){
          if(is_string($resName)){
?>
          ngc_Lang['<?= $lang; ?>']['<?= $resName; ?>'] = <?= json_encode($res); ?>;
<?php
          }
        }
      }
    }
  }

  private static function joinURL(){
    $url = '';
    foreach(func_get_args() as $path){
      if(is_string($path)){$url .= rtrim($path,'/').'/';}
    }
    return rtrim($url,'/');
  }

  private static function filesToURLs($files,$sources=null){
    $urls = array();
    if(!is_array($files)){return $urls;}
    foreach($files as $sourceName => $paths){
      if(is_string($paths)){$urls[] = $paths;}
      elseif(is_array($paths) && is_array($sources) && is_string($sourceName)){
        if(isset($sources[$sourceName]) && is_string($sources[$sourceName])){
          foreach($paths as $path){
            if(is_string($path)){
              $urls[] = self::joinURL($sources[$sourceName],$path);
            }
          }
        }
      }
    }
    return $urls;
  }

  protected static function buildFileSources($sources){
    if(!is_array($sources)){return;}
?>
          var ngLib = {
<?php
    foreach($sources as $name => $path){
      if(is_string($name) && is_string($path)){
?>
            '<?= $name; ?>':{URL:'<?= rtrim($path,'/').'/'; ?>'},
<?php
      }
    }
?>
          };
<?php
  }

  protected static function buildInitContent($files,$sources){
    $fileURLs = self::filesToURLs($files,$sources);

    foreach($fileURLs as $fileURL){
      if(is_string($fileURL)){
        $url = parse_url($fileURL);

        if(is_array($url) && isset($url['path']) && is_string($url['path'])){
          switch(pathinfo($url['path'],PATHINFO_EXTENSION)){
            case 'js':
?>
        <script type="text/javascript" src="<?= $fileURL; ?>"></script>
<?php
            break;
            case 'css':
?>
        <link rel="stylesheet" type="text/css" href="<?= $fileURL; ?>">
<?php
            break;
          }
        }
      }
    }
  }

  protected static function buildLoadContent($files,$sources){
    $fileURLs = self::filesToURLs($files,$sources);
    if(is_string(self::$settingsURL)){
      array_push($fileURLs,self::$settingsURL.'?load=1');
    }
?>
          var ngAppFiles = [
<?php foreach($fileURLs as $fileURL){ ?>
            '<?= $fileURL; ?>',
<?php } ?>
          ];
<?php
  }

  protected static function buildDeviceContent($deviceFiles,$sources,$devices){
    if(
      !is_array($deviceFiles) || (count($deviceFiles) < 1)
      || !is_array($devices) || (count($devices) < 1)
    ){return;}
?>
          var ngAppDeviceFiles = {
<?php
    foreach($deviceFiles as $deviceName => $files){
      if(is_string($deviceName)){
        $device = strstr($deviceName,'_',true);
        if(is_string($device)){
          if(
            !isset($devices[$device])
            || !is_array($devices[$device])
          ){continue;}
          else{
            $profile = substr($deviceName,strlen($device)+1);
            if(
              !isset($devices[$device][$profile])
              || !is_array($devices[$device][$profile])
            ){continue;}
          }
        }
        else{
          if(
            !isset($devices[$deviceName])
            || !is_array($devices[$deviceName])
          ){continue;}
        }

        $fileURLs = self::filesToURLs($files,$sources);
?>
            '<?= $deviceName; ?>': [
<?php   foreach($fileURLs as $fileURL){ ?>
              '<?= $fileURL; ?>',
<?php   } ?>
            ],
<?php
      }
    }
?>
          };
<?php
  }
}
?>
