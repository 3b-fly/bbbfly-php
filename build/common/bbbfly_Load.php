<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

require_once(dirname(__FILE__).'/bbbfly_File.php');
require_once(dirname(__FILE__).'/../environment/bbbfly_INI.php');

class bbbfly_Load extends bbbfly_File
{
  const FILE_ERROR_OPEN = 3201;

  const DATATYPE_BASE64 = 2;

  protected function processFile($name,$tmp_name,$type,$size,$error){
    $result = parent::processFile($name,$tmp_name,$type,$size,$error);
    if($this->ErrorCode !== self::PROCESS_ERROR_NONE){return $result;}

    $file = fopen($tmp_name,'rb');
    if(!$file){
      $result->error = self::FILE_ERROR_OPEN;
      $this->ErrorCode = self::PROCESS_ERROR_FILE;
      return $result;
    }

    $result->data = '';
    $result->data_type = self::DATATYPE_BASE64;

    while(!feof($file)){
      $chunk = fread($file,1024);
      $result->data .= base64_encode($chunk);
    }

    fclose($file);
    return $result;
  }
}