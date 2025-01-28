<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

require_once(dirname(__FILE__).'/bbbfly_RPC.php');
require_once(dirname(__FILE__).'/../common/bbbfly_MIME.php');
require_once(dirname(__FILE__).'/../common/bbbfly_Load.php');

abstract class bbbfly_RPC_Load extends bbbfly_RPC
{
  const ERROR_TYPE_MAIN = 1;
  const ERROR_TYPE_GENERAL = 2;
  const ERROR_TYPE_EXTENSION = 3;
  const ERROR_TYPE_SIZE = 4;
  const ERROR_TYPE_COUNT = 5;
  const ERROR_TYPE_BATCH = 6;

  private $_loadOptions = array();

  function __construct($loadOptions=null,$options=null,$process=true){
    if(is_array($loadOptions)){$this->loadOptions = $loadOptions;}
    parent::__construct($options,$process);
  }

  public function __get($propName){
    switch($propName){
      case 'loadOptions': return $this->_loadOptions;
      default: return parent::__get($propName);
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'loadOptions':if(is_array($value)){$this->_loadOptions = $value;}break;
      case 'outputType': parent::__set($propName,bbbfly_RPC::OUTPUT_JSON); break;
      case 'outputMime': parent::__set($propName,bbbfly_MIME::JSON); break;
      default: parent::__set($propName,$value); break;
    }
  }

  protected function doProcessRPC(){
    $loader = new bbbfly_Load($this->loadOptions);

    $resultData = array();
    switch($loader->processFiles()){
      case bbbfly_Load::PROCESS_ERROR_NONE:
      case bbbfly_Load::PROCESS_ERROR_FILE:
        $result = $loader->Result;
        if(is_array($result)){

          foreach($result as $fileResult){
            $resultItem = new stdClass();
            $resultItem->Name = $fileResult->name;

            switch($fileResult->error){
              case bbbfly_Load::FILE_ERROR_NONE:
                $resultItem->Data = $fileResult->data;
                $resultItem->DataType = $fileResult->data_type;
              break;
              case bbbfly_Load::FILE_ERROR_SIZE:
                $resultItem->Error = self::ERROR_TYPE_SIZE;
              break;
              case bbbfly_Load::FILE_ERROR_EXTENSION:
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