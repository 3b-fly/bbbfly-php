<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
require_once(dirname(__FILE__).'/../environment/bbbfly_INI.php');

class bbbfly_Upload
{
  const OPTIONS_ERROR_MISSING_UPLOADDIR = 1100;
  const OPTIONS_ERROR_UPLOADDIR_NOT_WRITABLE = 1101;
  const OPTIONS_ERROR_MISSING_FILES_BATCH_NAME = 1102;
  const OPTIONS_ERROR_MISSING_FILES_EXPIRATION = 1103;

  const UPLOAD_ERROR_NONE = 0;
  const UPLOAD_ERROR_FILE = 1120;
  const UPLOAD_ERROR_NO_FILE = 1121;
  const UPLOAD_ERROR_TOO_MANY_FILES = 1122;
  const UPLOAD_ERROR_INVALID_METHOD = 1123;

  const UPLOAD_ERROR_FILE_NONE = 0;
  const UPLOAD_ERROR_FILE_SIZE = 1140;
  const UPLOAD_ERROR_FILE_UPLOAD = 1141;
  const UPLOAD_ERROR_FILE_TEMPDIR = 1142;
  const UPLOAD_ERROR_FILE_GENERAL = 1143;
  const UPLOAD_ERROR_FILE_EXTENSION = 1144;
  const UPLOAD_ERROR_FILE_MOVE = 1145;

  const CLEAR_ERROR_NONE = 0;
  const CLEAR_ERROR_REMOVE = 1160;
  const CLEAR_ERROR_REMOVE_PARTIAL = 1161;

  private static $options = array(
    'uploadDir',
    'maxFilesCnt',
    'maxFileSize',
    'maxUploadSize',
    'filesBatchName',
    'allowedExtensions',
    'filesExpiration'
  );

  private $_uploadDir = null;
  private $_maxFilesCnt = null;
  private $_maxFileSize = null;
  private $_maxUploadSize = null;
  private $_filesBatchName = null;
  private $_allowedExtensions = array();
  private $_filesExpiration = null;

  private $_ErrorCode = null;
  private $_Method = null;
  private $_Result = null;

  function __construct($options=null){
    $this->_Method = $_SERVER["REQUEST_METHOD"];
    $this->setOptions($options);
  }

  public function __get($propName){
    switch($propName){
      case 'uploadDir': return $this->_uploadDir;
      case 'maxFilesCnt': return $this->_maxFilesCnt;
      case 'maxFileSize': return $this->_maxFileSize;
      case 'maxUploadSize': return $this->_maxUploadSize;
      case 'filesBatchName': return $this->_filesBatchName;
      case 'allowedExtensions': return $this->_allowedExtensions;
      case 'filesExpiration': return $this->_filesExpiration;

      case 'ErrorCode': return $this->_ErrorCode;
      case 'Method': return $this->_Method;
      case 'Result': return $this->_Result;
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'uploadDir':
        if(is_string($value) && ($value !== '')){
          $this->_uploadDir = realpath(rtrim($value,'/\\'));
        }
      break;
      case 'maxFilesCnt':
        if(is_numeric($value) && ($value > 0)){
          $this->_maxFilesCnt = (int)$value;
        }
      break;
      case 'maxFileSize':
        if(is_numeric($value) && ($value > 0)){
          $this->_maxFileSize = (int)$value;
        }
      break;
      case 'maxUploadSize':
        if(is_numeric($value) && ($value > 0)){
          $this->_maxUploadSize = (int)$value;
        }
      break;
      case 'filesBatchName':
        if(is_string($value) && ($value !== '')){
          $this->_filesBatchName = $value;
        }
      break;
      case 'allowedExtensions':
        if(is_array($value)){
          $this->_allowedExtensions = array();
          foreach($value as $ext){
            if(is_string($ext)){
              $ext = strtolower(trim($ext));
              $this->_allowedExtensions[$ext] = true;}
          }
        }
      break;
      case 'filesExpiration':
        if(is_int($value)){
          $this->_filesExpiration = $value;
        }
      break;
    }
  }

  public function setOptions($options){
    if(is_array($options)){
      foreach(self::$options as $name){
        if(isset($options[$name])){$this->$name = $options[$name];}
      }
    }
    return $this;
  }

  protected function applyIniOptions(){
    $fileSize = round(bbbfly_INI::byteValue('upload_max_filesize'));
    $uploadSize = round(bbbfly_INI::byteValue('post_max_size'));
    if($uploadSize < $fileSize){$fileSize = $uploadSize;}

    if(is_null($this->maxFileSize) || ($fileSize < $this->maxFileSize)){
      $this->maxFileSize = $fileSize;
    }

    if(is_null($this->maxUploadSize) || ($uploadSize < $this->maxUploadSize)){
      $this->maxUploadSize = $uploadSize;
    }
  }

  protected function validateOptions(){
    if(!$this->uploadDir || !is_dir($this->uploadDir)){
      $this->_ErrorCode = self::OPTIONS_ERROR_MISSING_UPLOADDIR;
      return false;
    }
    if(!is_writable($this->uploadDir)){
      $this->_ErrorCode = self::OPTIONS_ERROR_UPLOADDIR_NOT_WRITABLE;
      return false;
    }
    return true;
  }

  protected function validateUploadOptions(){
    if(!$this->validateOptions()){return false;}

    if(!$this->filesBatchName){
      $this->_ErrorCode = self::OPTIONS_ERROR_MISSING_FILES_BATCH_NAME;
      return false;
    }
    return true;
  }

  protected function validateClearOptions(){
    if(!$this->validateOptions()){return false;}

    if(!$this->filesExpiration){
      $this->_ErrorCode = self::OPTIONS_ERROR_MISSING_FILES_EXPIRATION;
      return false;
    }
    return true;
  }

  public function upload($options=null){
    if($this->Method !== 'POST'){
      $this->_ErrorCode = self::UPLOAD_ERROR_INVALID_METHOD;
      return $this->ErrorCode;
    }

    $this->_ErrorCode = self::UPLOAD_ERROR_NONE;

    $this->setOptions($options);
    if(!$this->validateUploadOptions()){
      return $this->ErrorCode;
    }

    $this->applyIniOptions();

    if(!isset($_FILES[$this->filesBatchName])){
      $this->_ErrorCode = self::UPLOAD_ERROR_NO_FILE;
      return $this->ErrorCode;
    }

    $files =& $_FILES[$this->filesBatchName];
    $multiPart = is_array($files['name']);
    $filesCnt = $multiPart ? count($files['name']) : 1;

    if($filesCnt > $this->maxFilesCnt){
      $this->_ErrorCode = self::UPLOAD_ERROR_TOO_MANY_FILES;
      return $this->ErrorCode;
    }

    $this->_Result = array();
    if($multiPart){
      for($i = 0;$i < $filesCnt; $i++){
        $this->_Result[] = $this->uploadFile(
          $files['name'][$i],$files['tmp_name'][$i],
          $files['type'][$i],$files['size'][$i],
          $files['error'][$i]
        );
      }
    }
    else{
      $this->_Result[] = $this->uploadFile(
        $files['name'],$files['tmp_name'],
        $files['type'],$files['size'],
        $files['error']
      );
    }

    return $this->ErrorCode;
  }

  protected function uploadFile($name,$tmp_name,$type,$size,$error){

    $result = array(
      'name' => $name,
      'error' => self::UPLOAD_ERROR_FILE_NONE
    );

    if($error !== UPLOAD_ERR_OK){
      switch($error){
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $result['error'] = self::UPLOAD_ERROR_FILE_SIZE;
          $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
        break;
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          $result['error'] = self::UPLOAD_ERROR_FILE_UPLOAD;
          $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
        break;
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
          $result['error'] = self::UPLOAD_ERROR_FILE_TEMPDIR;
          $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
        break;
        default:
          $result['error'] = self::UPLOAD_ERROR_FILE_GENERAL;
          $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
        break;
      }
      return $result;
    }

    if(!is_numeric($size)){
      $result['error'] = self::UPLOAD_ERROR_FILE_UPLOAD;
      $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
      return $result;
    }

    if(($this->maxFileSize > 0) && ($size > $this->maxFileSize)){
      $result['error'] = self::UPLOAD_ERROR_FILE_SIZE;
      $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
      return $result;
    }

    $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(!$this->allowedExtensions[$ext]){
      $result['error'] = self::UPLOAD_ERROR_FILE_EXTENSION;
      $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
      return $result;
    }

    $errorLevel = error_reporting(0);
    $timeZone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $date = date('YmdHi');
    date_default_timezone_set($timeZone);
    error_reporting($errorLevel);

    $fileId = $date.'_'.getmypid().uniqid();
    $filePath = $this->uploadDir.DIRECTORY_SEPARATOR;

    $uniq = 1;
    $fileExists = true;
    while($fileExists){
      $resFileId = $fileId.'_'.$uniq.'_'.$ext;
      if(is_file($filePath.$resFileId)){$uniq++;}
      else{
        $fileExists = false;
        $result['file_id'] = $resFileId;
        $result['file_path'] = $filePath.$resFileId;
      }
    }

    if(!move_uploaded_file($tmp_name,$result['file_path'])){
      $result['error'] = self::UPLOAD_ERROR_FILE_MOVE;
      $this->_ErrorCode = self::UPLOAD_ERROR_FILE;
      return $result;
    }
    return $result;
  }

  public function clear($options=null){
    $this->_ErrorCode = self::CLEAR_ERROR_NONE;

    $this->setOptions($options);
    if(!$this->validateClearOptions()){
      return $this->ErrorCode;
    }

    $anyRemoved = false;
    $anyFailed = false;

    foreach(scandir($this->uploadDir) as $file){

      $matches = null;
      $pattern = '~^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})_.+_\d+_[a-zA-Z0-9]+$~';
      if(!preg_match($pattern,$file,$matches)){continue;}

      $errorLevel = error_reporting(0);
      $timeZone = date_default_timezone_get();
      date_default_timezone_set('UTC');

      $created = mktime(
        $matches[4],$matches[5],0,$matches[2],$matches[3],$matches[1]
      );

      if(((time()-$created)/60) > $this->filesExpiration){
        $filePath = realpath($this->uploadDir.DIRECTORY_SEPARATOR.$file);
        if(unlink($filePath)){$anyRemoved = true;}
        else{$anyFailed = true;}
      }

      date_default_timezone_set($timeZone);
      error_reporting($errorLevel);
    }

    if($anyFailed){
      $this->_ErrorCode = ($anyRemoved)
        ? self::CLEAR_ERROR_REMOVE_PARTIAL
        : self::CLEAR_ERROR_REMOVE;
    }

    return $this->ErrorCode;
  }
}