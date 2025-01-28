<?php
require_once(dirname(__FILE__).'/bbbfly_File.php');
require_once(dirname(__FILE__).'/../environment/bbbfly_INI.php');

class bbbfly_Upload extends bbbfly_File
{
  const OPTIONS_ERROR_MISSING_UPLOADDIR = 1101;
  const OPTIONS_ERROR_UPLOADDIR_NOT_WRITABLE = 1102;
  const OPTIONS_ERROR_MISSING_FILES_EXPIRATION = 1103;

  const FILE_ERROR_MOVE = 3101;

  const CLEAR_ERROR_NONE = 4100;
  const CLEAR_ERROR_NOFILE = 4101;
  const CLEAR_ERROR_REMOVE = 4102;
  const CLEAR_ERROR_REMOVE_PARTIAL = 4103;

  private $_uploadDir = null;
  private $_filesExpiration = null;

  protected function options(){
    return array_merge(
      parent::options(),
      array('uploadDir','filesExpiration')
    );
  }

  public function __get($propName){
    switch($propName){
      case 'uploadDir': return $this->_uploadDir;
      case 'filesExpiration': return $this->_filesExpiration;
      default: return parent::__get($propName);
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'uploadDir':
        if(is_string($value) && ($value !== '')){
          $this->_uploadDir = realpath(rtrim($value,'/\\'));
        }
      break;
      case 'filesExpiration':
        if(is_int($value)){
          $this->_filesExpiration = $value;
        }
      break;
      default:
        parent::__set($propName,$value);
      break;
    }
  }

  protected function validateUploadDirOptions(){
    if(!$this->uploadDir || !is_dir($this->uploadDir)){
      $this->ErrorCode = self::OPTIONS_ERROR_MISSING_UPLOADDIR;
      return false;
    }
    if(!is_writable($this->uploadDir)){
      $this->ErrorCode = self::OPTIONS_ERROR_UPLOADDIR_NOT_WRITABLE;
      return false;
    }
    return true;
  }

  protected function validateOptions(){
    if(!parent::validateOptions()){return false;}
    if(!$this->validateUploadDirOptions()){return false;}
    return true;
  }

  protected function validateClearOptions(){
    if(!$this->validateUploadDirOptions()){return false;}

    if(!$this->filesExpiration){
      $this->ErrorCode = self::OPTIONS_ERROR_MISSING_FILES_EXPIRATION;
      return false;
    }
    return true;
  }

  protected function processFile($name,$tmp_name,$type,$size,$error){
    $result = parent::processFile($name,$tmp_name,$type,$size,$error);
    if($this->ErrorCode !== self::PROCESS_ERROR_NONE){return $result;}

    $errorLevel = error_reporting(0);
    $timeZone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $date = date('YmdHi');
    date_default_timezone_set($timeZone);
    error_reporting($errorLevel);

    $fileId = $date.'_'.getmypid().uniqid();
    $uploadPath = $this->uploadDir.DIRECTORY_SEPARATOR;
    $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));

    $uniq = 1;
    $fileExists = true;
    $filePath = null;

    while($fileExists){
      $resFileId = $fileId.'_'.$uniq.'_'.$ext;

      if(is_file($uploadPath.$resFileId)){
        $uniq++;
      }else{
        $result->id = $resFileId;

        $filePath = $uploadPath.$resFileId;
        $fileExists = false;
      }
    }

    if(!move_uploaded_file($tmp_name,$filePath)){
      $result->error = self::FILE_ERROR_MOVE;
      $this->ErrorCode = self::PROCESS_ERROR_FILE;
      return $result;
    }
    return $result;
  }

  public function clear($options=null){
    $this->ErrorCode = self::CLEAR_ERROR_NONE;

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
      $this->ErrorCode = ($anyRemoved)
        ? self::CLEAR_ERROR_REMOVE_PARTIAL
        : self::CLEAR_ERROR_REMOVE;
    }
    elseif(!$anyRemoved){
      $this->ErrorCode = self::CLEAR_ERROR_NOFILE;
    }

    return $this->ErrorCode;
  }
}