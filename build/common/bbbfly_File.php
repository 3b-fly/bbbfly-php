<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

require_once(dirname(__FILE__).'/../environment/bbbfly_INI.php');

class bbbfly_File
{
  const OPTIONS_ERROR_MISSING_FILES_BATCH_NAME = 1001;

  const PROCESS_ERROR_NONE = 2000;
  const PROCESS_ERROR_NO_FILE = 2001;
  const PROCESS_ERROR_TOO_MANY_FILES = 2002;
  const PROCESS_ERROR_INVALID_METHOD = 2003;
  const PROCESS_ERROR_FILE = 2004;

  const FILE_ERROR_NONE = 3000;
  const FILE_ERROR_SIZE = 3001;
  const FILE_ERROR_UPLOAD = 3002;
  const FILE_ERROR_TEMPDIR = 3003;
  const FILE_ERROR_GENERAL = 3004;
  const FILE_ERROR_EXTENSION = 3005;

  private $_maxFilesCnt = null;
  private $_maxFileSize = null;
  private $_maxUploadSize = null;
  private $_filesBatchName = null;
  private $_allowedExtensions = array();

  private $_ErrorCode = null;
  private $_Method = null;
  private $_Result = null;

  function __construct($options=null){
    $this->_Method = $_SERVER["REQUEST_METHOD"];
    $this->setOptions($options);
  }

  protected function options(){
    return array(
      'maxFilesCnt',
      'maxFileSize',
      'maxUploadSize',
      'filesBatchName',
      'allowedExtensions'
    );
  }

  public function __get($propName){
    switch($propName){
      case 'maxFilesCnt': return $this->_maxFilesCnt;
      case 'maxFileSize': return $this->_maxFileSize;
      case 'maxUploadSize': return $this->_maxUploadSize;
      case 'filesBatchName': return $this->_filesBatchName;
      case 'allowedExtensions': return $this->_allowedExtensions;

      case 'ErrorCode': return $this->_ErrorCode;
      case 'Method': return $this->_Method;
      case 'Result': return $this->_Result;
    }
  }

  public function __set($propName,$value){
    switch($propName){
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

      case 'ErrorCode':
        if(is_int($value)){
          $this->_ErrorCode = $value;
        }
      break;
    }
  }

  public function setOptions($options){
    if(is_array($options)){
      foreach($this->options() as $name){
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
    if(!$this->filesBatchName){
      $this->ErrorCode = self::OPTIONS_ERROR_MISSING_FILES_BATCH_NAME;
      return false;
    }
    return true;
  }

  public function processFiles($options=null){
    if($this->Method !== 'POST'){
      $this->ErrorCode = self::PROCESS_ERROR_INVALID_METHOD;
      return $this->ErrorCode;
    }

    $this->ErrorCode = self::PROCESS_ERROR_NONE;

    $this->setOptions($options);
    if(!$this->validateOptions()){
      return $this->ErrorCode;
    }

    $this->applyIniOptions();

    if(!isset($_FILES[$this->filesBatchName])){
      $this->ErrorCode = self::PROCESS_ERROR_NO_FILE;
      return $this->ErrorCode;
    }

    $files =& $_FILES[$this->filesBatchName];
    $multiPart = is_array($files['name']);
    $filesCnt = $multiPart ? count($files['name']) : 1;

    if($filesCnt > $this->maxFilesCnt){
      $this->ErrorCode = self::PROCESS_ERROR_TOO_MANY_FILES;
      return $this->ErrorCode;
    }

    $this->_Result = array();
    if($multiPart){
      for($i = 0;$i < $filesCnt; $i++){
        $this->_Result[] = $this->processFile(
          $files['name'][$i],$files['tmp_name'][$i],
          $files['type'][$i],$files['size'][$i],
          $files['error'][$i]
        );
      }
    }
    else{
      $this->_Result[] = $this->processFile(
        $files['name'],$files['tmp_name'],
        $files['type'],$files['size'],
        $files['error']
      );
    }

    return $this->ErrorCode;
  }

  protected function processFile($name,$tmp_name,$type,$size,$error){
    $result = new stdClass();
    $result->name = $name;
    $result->error = self::FILE_ERROR_NONE;

    if($error !== UPLOAD_ERR_OK){
      switch($error){
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $result->error = self::FILE_ERROR_SIZE;
          $this->ErrorCode = self::PROCESS_ERROR_FILE;
        break;
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          $result->error = self::FILE_ERROR_UPLOAD;
          $this->ErrorCode = self::PROCESS_ERROR_FILE;
        break;
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
          $result->error = self::FILE_ERROR_TEMPDIR;
          $this->ErrorCode = self::PROCESS_ERROR_FILE;
        break;
        default:
        $result->error = self::FILE_ERROR_GENERAL;
          $this->ErrorCode = self::PROCESS_ERROR_FILE;
        break;
      }
      return $result;
    }

    if(!is_numeric($size)){
      $result->error = self::FILE_ERROR_UPLOAD;
      $this->_ErrorCode = self::PROCESS_ERROR_FILE;
      return $result;
    }

    if(($this->maxFileSize > 0) && ($size > $this->maxFileSize)){
      $result->error = self::FILE_ERROR_SIZE;
      $this->ErrorCode = self::PROCESS_ERROR_FILE;
      return $result;
    }

    $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(!$this->allowedExtensions[$ext]){
      $result->error = self::FILE_ERROR_EXTENSION;
      $this->ErrorCode = self::PROCESS_ERROR_FILE;
      return $result;
    }

    return $result;
  }
}