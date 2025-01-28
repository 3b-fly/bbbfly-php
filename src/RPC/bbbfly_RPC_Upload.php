<?php
require_once(dirname(__FILE__).'/bbbfly_RPC.php');
require_once(dirname(__FILE__).'/../common/bbbfly_MIME.php');
require_once(dirname(__FILE__).'/../common/bbbfly_Upload.php');

abstract class bbbfly_RPC_Upload extends bbbfly_RPC
{
  const ERROR_TYPE_MAIN = 1;
  const ERROR_TYPE_GENERAL = 2;
  const ERROR_TYPE_EXTENSION = 3;
  const ERROR_TYPE_SIZE = 4;
  const ERROR_TYPE_COUNT = 5;
  const ERROR_TYPE_BATCH = 6;

  private $_uploadOptions = array();

  function __construct($uploadOptions=null,$options=null,$process=true){
    if(is_array($uploadOptions)){$this->uploadOptions = $uploadOptions;}
    parent::__construct($options,$process);
  }

  public function __get($propName){
    switch($propName){
      case 'uploadOptions': return $this->_uploadOptions;
      default: return parent::__get($propName);
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'uploadOptions':if(is_array($value)){$this->_uploadOptions = $value;}break;
      case 'outputType': parent::__set($propName,bbbfly_RPC::OUTPUT_JSON); break;
      case 'outputMime': parent::__set($propName,bbbfly_MIME::JSON); break;
      default: parent::__set($propName,$value); break;
    }
  }

  protected function doProcessRPC(){
    $uploader = new bbbfly_Upload($this->uploadOptions);

    $resultData = array();
    switch($uploader->processFiles()){
      case bbbfly_Upload::PROCESS_ERROR_NONE:
      case bbbfly_Upload::PROCESS_ERROR_FILE:
        $result = $uploader->Result;
        if(is_array($result)){

          foreach($result as $fileResult){
            $resultItem = new stdClass();
            $resultItem->Name = $fileResult->name;

            switch($fileResult->error){
              case bbbfly_Upload::FILE_ERROR_NONE:
                $resultItem->Id = $fileResult->id;
              break;
              case bbbfly_Upload::FILE_ERROR_SIZE:
                $resultItem->Error = self::ERROR_TYPE_SIZE;
              break;
              case bbbfly_Upload::FILE_ERROR_EXTENSION:
                $resultItem->Error = self::ERROR_TYPE_EXTENSION;
              break;
              default:
                $resultItem->Error = self::ERROR_TYPE_GENERAL;
              break;
            }
            $resultData[] = $resultItem;
          }
        }
      break;
      default:
        $resultData = new stdClass();
        $resultData->Error = self::ERROR_TYPE_MAIN;
      break;
    }

    $this->setResponseData($resultData);
  }
}