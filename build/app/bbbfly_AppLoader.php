<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>

<?php
class bbbfly_AppLoader
{
  protected static $_Options = array(
    'appName','appVersion','appCopyrights',
    'loadMessage','errorMessage','reloadText',
    'width','colors',
    'backColor','frontColor','errorColor','reloadColor',
    'img','imgUrl','imgWidth','imgHeight',
    'logo','logoUrl','logoWidth','logoHeight'
  );

  private $_appName = null;
  private $_appVersion = null;
  private $_appCopyrights = null;

  private $_loadMessage = 'Loading...';
  private $_errorMessage = 'Loading Failed';
  private $_reloadText = 'Reload';

  private $_width = 240;
  private $_backColor = '#ffffff';
  private $_frontColor = '#000000';
  private $_errorColor = '#ffffff';
  private $_reloadColor = '#ffffff';

  private $_imgUrl = null;
  private $_imgGap = 0;
  private $_imgWidth = 0;
  private $_imgHeight = 0;

  private $_logoUrl = null;
  private $_logoGap = 0;
  private $_logoWidth = 0;
  private $_logoHeight = 0;

  function __construct($options=null){
    $this->setOptions($options);
  }

  public function useConfig($key='App.Loader',$alias='default'){
    if(class_exists('bbbfly_Config',false)){
      self::setOptions(bbbfly_Config::get($key,$alias));
    }
    else{
      throw new Exception('Missing "bbbfly_Config" class.');
    }
  }

  public function __get($propName){
    switch($propName){
      case 'appName': return $this->_appName;
      case 'appVersion': return $this->_appVersion;
      case 'appCopyrights': return $this->_appCopyrights;

      case 'loadMessage': return $this->_loadMessage;
      case 'errorMessage': return $this->_errorMessage;
      case 'reloadText': return $this->_reloadText;

      case 'width': return $this->_width;
      case 'backColor': return $this->_backColor;
      case 'frontColor': return $this->_frontColor;
      case 'errorColor': return $this->_errorColor;
      case 'reloadColor': return $this->_reloadColor;

      case 'imgUrl': return $this->_imgUrl;
      case 'imgGap': return $this->_imgGap;
      case 'imgWidth': return $this->_imgWidth;
      case 'imgHeight': return $this->_imgHeight;

      case 'logoUrl': return $this->_logoUrl;
      case 'logoGap': return $this->_logoGap;
      case 'logoWidth': return $this->_logoWidth;
      case 'logoHeight': return $this->_logoHeight;

      case 'colors': return array(
        'back' => $this->backColor,
        'front' => $this->frontColor,
        'error' => $this->errorColor,
        'reload' => $this->reloadColor
      );

      case 'img': return array(
        'url' => $this->_imgUrl,
        'gap' => $this->_imgGap,
        'width' => $this->_imgWidth,
        'height' => $this->_imgHeight
      );

      case 'logo': return array(
        'url' => $this->_logoUrl,
        'gap' => $this->_logoGap,
        'width' => $this->_logoWidth,
        'height' => $this->_logoHeight
      );

      case 'Options': return self::$_Options;
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'appName': if(is_string($value)){$this->_appName = $value;} break;
      case 'appVersion': if(is_string($value)){$this->_appVersion = $value;} break;
      case 'appCopyrights': if(is_string($value)){$this->_appCopyrights = $value;} break;

      case 'loadMessage': if(is_string($value)){$this->_loadMessage = $value;} break;
      case 'errorMessage': if(is_string($value)){$this->_errorMessage = $value;} break;
      case 'reloadText': if(is_string($value)){$this->_reloadText = $value;} break;

      case 'width': if(is_int($value)){$this->_width = $value;} break;
      case 'backColor': if(self::isColor($value)){$this->_backColor = $value;} break;
      case 'frontColor': if(self::isColor($value)){$this->_frontColor = $value;} break;
      case 'errorColor': if(self::isColor($value)){$this->_errorColor = $value;} break;
      case 'reloadColor': if(self::isColor($value)){$this->_reloadColor = $value;} break;

      case 'imgUrl': if(is_string($value)){$this->_imgUrl = $value;} break;
      case 'imgGap': if(is_int($value)){$this->_imgGap = $value;} break;
      case 'imgWidth': if(is_int($value)){$this->_imgWidth = $value;} break;
      case 'imgHeight': if(is_int($value)){$this->_imgHeight = $value;} break;

      case 'logoUrl': if(is_string($value)){$this->_logoUrl = $value;} break;
      case 'logoGap': if(is_int($value)){$this->_logoGap = $value;} break;
      case 'logoWidth': if(is_int($value)){$this->_logoWidth = $value;} break;
      case 'logoHeight': if(is_int($value)){$this->_logoHeight = $value;} break;

      case 'colors': $this->setColors($value); break;
      case 'img': $this->setImg($value); break;
      case 'logo': $this->setLogo($value); break;
    }
  }

  public function setColors($colors){
    if(is_array($colors)){
      if(isset($colors['back'])){$this->backColor = $colors['back'];}
      if(isset($colors['front'])){$this->frontColor = $colors['front'];}
      if(isset($colors['error'])){$this->errorColor = $colors['error'];}
      if(isset($colors['reload'])){$this->reloadColor = $colors['reload'];}
    }
  }

  public function setImg($img){
    if(is_array($img)){
      if(isset($img['url'])){$this->imgUrl = $img['url'];}
      if(isset($img['gap'])){$this->imgGap = $img['gap'];}
      if(isset($img['width'])){$this->imgWidth = $img['width'];}
      if(isset($img['height'])){$this->imgHeight = $img['height'];}
    }
  }

  public function setLogo($logo){
    if(is_array($logo)){
      if(isset($logo['url'])){$this->logoUrl = $logo['url'];}
      if(isset($logo['gap'])){$this->logoGap = $logo['gap'];}
      if(isset($logo['width'])){$this->logoWidth = $logo['width'];}
      if(isset($logo['height'])){$this->logoHeight = $logo['height'];}
    }
  }

  public function setOptions($options){
    if(is_array($options)){
      foreach($this->Options as $name){
        if(isset($options[$name])){$this->$name = $options[$name];}
      }
    }
    return $this;
  }

  public static function isColor($color){
    if(!is_string($color)){return false;}

    $char = '[a-fA-F0-9]';
    $pattern = "~^#($char{3}|$char{4}|$char{6}|$char{8})$~";
    return (bool)preg_match($pattern,$color);
  }

  public function buildCSS(){
?>
        <style type="text/css">
          #bbbflyAppLoader {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
            background-color: <?= $this->backColor; ?>;

            font-family: Arial CE,Helvetica CE,Arial,Helvetica,sans-serif;
            text-align: center;
            color: <?= $this->frontColor; ?>;
          }

          #bbbflyAppLoaderFooter {
            position: absolute;
            left: 50%;
            bottom: 50px;
            width: <?= $this->width; ?>px;
            margin-left: -<?= ceil($this->width/2); ?>px;
          }

          #bbbflyAppLoaderVersion {
            margin-bottom: 10px;
            font-size: 10px;
          }

          #bbbflyAppLoaderCopyrights {
            font-size: 10px;
          }

          #bbbflyAppLoaderLoad {
            position: absolute;
            left: 50%;
            top: 50%;
            width: <?= $this->width; ?>px;
            margin-left: -<?= ceil($this->width/2); ?>px;
            margin-top: 20px;
          }

          #bbbflyAppLoaderLoadMessage {
            text-align: left;
            font-size: 10px;
          }

          #bbbflyAppLoaderLoadBar {
            width: 100%;
            height: 2px;
            margin-top: 10px;
          }

          #bbbflyAppLoaderLoadProgress {
            position:absolute;
            width: 0;
            height: 2px;
            background-color: <?= $this->frontColor; ?>;
          }

          #bbbflyAppLoaderError {
            position: absolute;
            display: none;
            left: 50%;
            top: 50%;
            width: <?= $this->width; ?>px;
            margin-left: -<?= ceil($this->width/2); ?>px;
            margin-top: 20px;
          }

          #bbbflyAppLoaderErrorMessage {
            font-size: 10px;
            text-align: left;
            color: <?= $this->errorColor; ?>;
          }

          #bbbflyAppLoaderErrorReload {
            margin-top: 10px;
            cursor: pointer;
          }

          #bbbflyAppLoaderErrorReload span{
            display: block;
            padding: 5px;
            font-size: 10px;
            text-align: center;
            color: <?= $this->reloadColor; ?>;
            border: 1px solid <?= $this->reloadColor; ?>;
          }

          #bbbflyAppLoaderErrorReload:hover span{
            font-weight: bold;
            color: <?= $this->backColor; ?>;
            background-color: <?= $this->reloadColor; ?>;
          }

          .bbbflyAppLoaderFinished {
            visibility: hidden;
            opacity: 0;
            filter: alpha(opacity=0);
            -webkit-transition: visibility 0s linear 1s,opacity 1s linear;
            -moz-transition: visibility 0s linear 1s,opacity 1s linear;
            -ms-transition: visibility 0s linear 1s,opacity 1s linear;
            -o-transition: visibility 0s linear 1s,opacity 1s linear;
            transition: visibility 0s linear 1s,opacity 1s linear;
          }

<?php if($this->imgUrl){ ?>
          #bbbflyAppLoaderImage {
            position: absolute;
            left: 50%;
            top: 50%;
            width: <?= $this->imgWidth; ?>px;
            height: <?= $this->imgHeight; ?>px;
            margin-left: -<?= ceil($this->imgWidth/2); ?>px;
            margin-top: -<?= $this->imgHeight+$this->imgGap; ?>px;
          }
<?php } ?>

<?php if($this->logoUrl){ ?>
          #bbbflyAppLoaderLogo {
            position: relative;
            width: <?= $this->logoWidth; ?>px;
            height: <?= $this->logoHeight; ?>px;
            margin-bottom: <?= $this->logoGap; ?>px;
          }
<?php } ?>
        </style>
<?php
  }

  public function buildHTML(){
?>
        <div id="bbbflyAppLoader">

<?php if($this->imgUrl){ ?>
          <img id="bbbflyAppLoaderImage"
            src="<?= $this->imgUrl; ?>"
            width="<?= $this->imgWidth; ?>" height="<?= $this->imgHeight; ?>"
            <?= (($this->appName) ? 'alt="'.$this->appName.'"' : ''); ?>
          />
<?php } ?>

          <div id="bbbflyAppLoaderLoad">
            <div id="bbbflyAppLoaderLoadMessage">
              <?= $this->loadMessage; ?>
            </div>
            <div id="bbbflyAppLoaderLoadBar">
              <div id="bbbflyAppLoaderLoadProgress"></div>
            </div>
          </div>

          <div id="bbbflyAppLoaderError">
            <div id="bbbflyAppLoaderErrorMessage">
              <?= $this->errorMessage; ?>
            </div>
            <div
              id="bbbflyAppLoaderErrorReload"
              onclick="window.location.reload()">
              <span><?= $this->reloadText; ?></span>
            </div>
          </div>

          <div id="bbbflyAppLoaderFooter">

<?php if($this->logoUrl){ ?>
            <img id="bbbflyAppLoaderLogo"
              src="<?= $this->logoUrl; ?>"
              width="<?= $this->logoWidth; ?>" height="<?= $this->logoHeight; ?>"
            />
<?php } ?>

<?php if($this->appVersion){ ?>
            <div id="bbbflyAppLoaderVersion">
              <?= $this->appVersion; ?>
            </div>
<?php } ?>

<?php if($this->appCopyrights){ ?>

            <div id="bbbflyAppLoaderCopyrights">
              <?= $this->appCopyrights; ?>
            </div>
          </div>
<?php } ?>
        </div>
<?php } } ?>