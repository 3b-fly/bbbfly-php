<?php
require_once(dirname(__FILE__).'/../common/bbbfly_MIME.php');
require_once(dirname(__FILE__).'/../environment/bbbfly_Arguments.php');

abstract class bbbfly_RPC
{
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_POST = 'POST';
  const METHOD_OPTIONS = 'OPTIONS';

  const CHARSET_UTF8 = 'UTF-8';
  const CHARSET_W1250 = 'windows-1250';

  const OUTPUT_TEXT = 0;
  const OUTPUT_JSON = 1;
  const OUTPUT_HTML = 2;
  const OUTPUT_XML = 3;
  const OUTPUT_PDF = 4;
  const OUTPUT_FILE = 5;
  const OUTPUT_JAVASCRIPT = 6;
  const OUTPUT_JAVASCRIPT_IFRAME = 7;

  const EXPIRES_MAX_TS = 2147483647; //32-bit integer

  const RPC_ERROR_NONE = 0;
  const RPC_ERROR_MISSING_EXTENSION = 1000;
  const RPC_ERROR_INVALID_PARAMS = 1001;
  const RPC_ERROR_PROCESS = 1002;

  protected static $_Options = array(
    'cache','buffer','charset','origins',
    'outputType','outputMime','outputCharset','lateOutput',
    'outputFile','fileName','fileChunkSize','xSendFile',
    'paramsCharset','paramRules'
  );

  private $_cache = false;
  private $_buffer = true;
  private $_charset = null;
  private $_origins = array();

  private $_outputType = null;
  private $_outputMime = null;
  private $_outputCharset = null;
  private $_lateOutput = false;

  private $_outputFile = false;
  private $_fileName = null;
  private $_fileChunkSize = 1024;
  private $_xSendFile = false;

  private $_paramsCharset = null;
  private $_paramRules = array();

  private $_Method = null;
  private $_Upload = false;

  private $_ErrorCode = null;
  private $_ErrorMessage = null;

  private $_Params = array();
  private $_Headers = array();
  private $_ResponseData = null;

  function __construct($options=null,$process=true){
    $this->detectRequestProperties();

    $this->riseError(self::RPC_ERROR_NONE);

    $this->charset = self::CHARSET_UTF8;
    $this->paramsCharset = self::CHARSET_UTF8;
    $this->outputCharset = self::CHARSET_UTF8;
    $this->outputType = self::OUTPUT_TEXT;

    $this->setOptions($options);
    $this->getRequestParams();
    if($process){$this->process();}
  }

  public function __get($propName){
    switch($propName){
      case 'cache': return $this->_cache;
      case 'buffer': return $this->_buffer;
      case 'charset': return $this->_charset;
      case 'origins': return $this->_origins;

      case 'outputType': return $this->_outputType;
      case 'outputMime': return $this->_outputMime;
      case 'outputCharset': return $this->_outputCharset;
      case 'lateOutput': return $this->_lateOutput;

      case 'outputFile': return $this->_outputFile;
      case 'fileName': return $this->_fileName;
      case 'fileChunkSize': return $this->_fileChunkSize;
      case 'xSendFile': return $this->_xSendFile;

      case 'paramsCharset': return $this->_paramsCharset;
      case 'paramRules': return $this->_paramRules;

      case 'Method': return $this->_Method;
      case 'Upload': return $this->_Upload;
      case 'Options': return self::$_Options;

      case 'ErrorCode': return $this->_ErrorCode;
      case 'ErrorMessage': return $this->_ErrorMessage;
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'cache': if(is_bool($value) || is_int($value)){$this->_cache = $value;} break;
      case 'buffer': if(is_bool($value)){$this->_buffer = $value;} break;
      case 'charset': if(is_string($value)){$this->_charset = $value;} break;
      case 'origins': if(is_array($value)){$this->_origins = $value;} break;

      case 'outputType': if(is_int($value)){$this->setOutputType($value);} break;
      case 'outputMime': if(is_string($value)){$this->_outputMime = $value;} break;
      case 'outputCharset': if(is_string($value)){$this->_outputCharset = $value;} break;
      case 'lateOutput': if(is_bool($value)){$this->_lateOutput = $value;} break;

      case 'outputFile': if(is_bool($value)){$this->_outputFile = $value;} break;
      case 'fileName': if(is_string($value)){$this->_fileName = $value;} break;
      case 'fileChunkSize': if(is_int($value)){$this->_fileChunkSize = $value;} break;
      case 'xSendFile': if(is_bool($value)){$this->_xSendFile = $value;} break;

      case 'paramsCharset': if(is_string($value)){$this->_paramsCharset = $value;} break;
      case 'paramRules': if(is_array($value)){$this->_paramRules = $value;} break;
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

  protected function riseError($code,$message=null,$exception=null){
    if(is_int($code)){$this->_ErrorCode = $code;}
    if(is_string($message) || is_null($message)){
      $this->_ErrorMessage = $message;
    }

    if($exception){
      if($exception instanceof Exception){
        error_log($exception->getMessage());
      }
      elseif(is_string($exception)){
        error_log($exception);
      }
    }
  }

  public function isParam($paramName){
    return (is_string($paramName) && isset($this->_Params[$paramName]));
  }

  public function getParam($paramName,$defVal=null){
    return ($this->isParam($paramName)) ? $this->_Params[$paramName] : $defVal;
  }

  public function allParams(){
    if(func_num_args() < 1){return false;}
    foreach(func_get_args() as $paramName){
      if(!$this->isParam($paramName)){return false;}
    }
    return true;
  }

  public function anyParam(){
    if(func_num_args() < 1){return false;}
    foreach(func_get_args() as $paramName){
      if($this->isParam($paramName)){return true;}
    }
    return false;
  }

  protected function detectRequestProperties(){
    $this->_Method = $_SERVER['REQUEST_METHOD'];

    switch($this->Method){
      case self::METHOD_PUT:
        $this->_Upload = true;
      break;
      case self::METHOD_POST:
        if(count($_FILES) > 0){$this->_Upload = true;}
      break;
    }
  }

  public function setOutputType($outputType){
    $outputMime = null;
    $outputFile = false;

    switch($outputType){
      case self::OUTPUT_TEXT: $outputMime = bbbfly_MIME::TEXT; break;
      case self::OUTPUT_JSON: $outputMime = bbbfly_MIME::JSON; break;
      case self::OUTPUT_HTML: $outputMime = bbbfly_MIME::HTML; break;
      case self::OUTPUT_XML: $outputMime = bbbfly_MIME::XML; break;
      case self::OUTPUT_PDF: $outputMime = bbbfly_MIME::PDF; break;

      case self::OUTPUT_JAVASCRIPT: $mime = bbbfly_MIME::JAVASCRIPT; break;
      case self::OUTPUT_JAVASCRIPT_IFRAME: $mime = bbbfly_MIME::HTML; break;

      case self::OUTPUT_FILE:
        $outputMime = bbbfly_MIME::BIN;
        $outputFile = true;
      break;
      default: return;
    }

    $this->_outputType = $outputType;
    $this->outputMime = $outputMime;
    $this->outputFile = $outputFile;
  }

  protected function addHeaders($headers){
    if(is_array($headers)){
      $this->_Headers = array_merge($this->_Headers,$headers);
    }
  }

  protected function setResponseData($data){
    $this->_ResponseData = $data;
  }

  protected function getRequestParams(){
    $params = array();

    switch(PHP_SAPI){
      case 'cli':
        $params = bbbfly_Arguments::getAll();
      break;
      default:
        if($this->Upload){$params =& $_GET;}
        elseif($this->Method === self::METHOD_POST){$params =& $_POST;}
        else{$params =& $_GET;}

        if(is_array($params) && (count($params) > 0)){
          foreach($params as &$param){
            $param = $this->decodePHPParam($param);
          }
		  unset($param);
        }

      break;
    }
    $this->_Params = $params;
  }

  protected function decodePHPParam($param){
    if(is_string($param) && ($param !== '')){
      $quotes = strtolower(ini_get('magic_quotes_gpc'));
      if($quotes === 'on'){$param = stripslashes($param);}

      if($this->paramsCharset !== $this->charset){
        if(extension_loaded('iconv')){
          $param = iconv($this->paramsCharset,$this->charset,$param);
        }
        else{
          $this->riseError(self::RPC_ERROR_MISSING_EXTENSION,'iconv');
        }
      }
    }
    return $param;
  }

  protected function setParamRule($paramName,$rules){
    if(is_string($paramName) && is_array($rules)){
      $this->_paramRules[$paramName] = $rules;
    }
  }

  protected function validateParams(){
    $valid = true;

    foreach($this->paramRules as $paramName => $rules){
      if(!is_array($rules)){continue;}

      $param = null;
      if(isset($this->_Params[$paramName])){
        $param =& $this->_Params[$paramName];
      }

      if(!is_string($param)){
        if(in_array('require',$rules)){$valid = false;}
      }
      elseif($param === ''){
        if(in_array('not_empty',$rules)){$valid = false;}
      }
      else{
        $valid = $this->validateParamType($param,$rules);
      }

      if(!$valid){
        $this->invalidateParam($paramName);
        break;
      }

      unset($param);
    }

    return $valid;
  }

  protected function validateParamType($param,$rules){
    if(is_string($rules)){$rules = array($rules => true);}

    $pattern = null;
    $delimiter = null;

    foreach($rules as $rule){
      if(!is_string($rule)){continue;}

      if(substr($rule,0,5) === 'array'){
        $delimiter = substr($rule,6,-1);
      }
      elseif(substr($rule,0,4) === 'enum'){
        $pattern = str_replace(
          array(
            '+','*','?','\\',
            '(',')','{','}','[',']',
            '$','^','~'
          ),
          array(
            '\+','\*','\?','\\\\',
            '\(','\)','\{','\}','\[','\]',
            '\$','\^','\~'
          ),
          substr($rule,5,-1)
        );
      }
      else{
        switch($rule){
          case 'boolean': $pattern = '[0,1]'; break;
          case 'integer': $pattern = '[-+]?[0-9]+'; break;
          case '+integer': $pattern = '[+]?[0-9]+'; break;
          case '-integer': $pattern = '[-][0-9]+'; break;
          case 'float': $pattern = '[-+]?[0-9]*\.?[0-9]+'; break;
          case '+float': $pattern = '[+]?[0-9]*\.?[0-9]+'; break;
          case '-float': $pattern = '[-][0-9]*\.?[0-9]+'; break;
        }
      }
    }

    if(is_string($delimiter)){
      if(!is_string($pattern)){$pattern = '.*';}
      $pattern = "($pattern)($delimiter($pattern))*";
    }

    if(is_string($pattern)){
      return (bool)preg_match('~^'.$pattern.'$~',$param);
    }

    return true;
  }

  protected function invalidateParam($paramName,$exception=null){
    if(!is_string($paramName) && !is_array($paramName)){return;}

    $this->riseError(
      self::RPC_ERROR_INVALID_PARAMS,
      is_string($paramName) ? $paramName : json_encode($paramName),
      $exception
    );
  }

  protected function setCacheHeaders(){
    $expiresTS = time();
    if($this->cache){
      if(is_int($this->cache)){$expiresTS += $this->cache;}
      else{$expiresTS = self::EXPIRES_MAX_TS;}

      $this->addHeaders(array(
        'Cache-Control' => 'public',
        'Pragma' => 'cache'
      ));
    }
    else{
      $expiresTS = 0;
      $this->addHeaders(array(
        'Cache-Control' => array('no-cache','must-revalidate'),
        'Pragma' => 'no-cache'
      ));
    }


    if($expiresTS > self::EXPIRES_MAX_TS){
      $expiresTS = self::EXPIRES_MAX_TS;
    }

    $this->addHeaders(array(
      'Last-Modified' => gmdate('D, d M Y H:i:s').' GMT',
      'Expires' => gmdate('D, d M Y H:i:s',$expiresTS).' GMT'
    ));
  }

  protected function setOptionsHeaders(){
    if($this->Method !== self::METHOD_OPTIONS){return;}

    if(isset($_SERVER['HTTP_ORIGIN'])){
      $allow = ($_SERVER['HTTP_ORIGIN'] === $_SERVER['SERVER_NAME']);

      if(!$allow){
        foreach($this->origins as $origin){
          if($_SERVER['HTTP_ORIGIN'] === $origin){
            $allow = true;
            break;
          }
        }
      }

      if($allow){
        $this->addHeaders(array(
          'Access-Control-Allow-Origin' => $_SERVER['HTTP_ORIGIN'],
          'Access-Control-Allow-Headers' => array('Origin','Accept','Content-Type'),
          'Access-Control-Allow-Methods' => array(
            self::METHOD_GET,
            self::METHOD_PUT,
            self::METHOD_POST,
            self::METHOD_OPTIONS
          )
        ));
      }
    }
  }

  protected function setResultHeaders(){
    if($this->Method === self::METHOD_OPTIONS){return;}

    if($this->outputFile){
      $this->addHeaders(array(
        'Content-Transfer-Encoding' => 'Binary',
        'Connection' => 'Keep-Alive'
      ));
    }

    $this->addHeaders(array(
      'Content-Type' => $this->outputMime.'; charset='.$this->outputCharset
    ));
  }

  protected function outputHeaders(){
    if(headers_sent()){return;}

    foreach($this->_Headers as $name => $value){
      $header = '';
      if(is_string($value)){$header .= $value;}
      elseif(is_array($value)){$header .= join(',',$value);}
      else{continue;}
      header($name.': '.$header);
    }
  }

  protected function validateOutput(){
    return true;
  }

  public function process($options=null){
    $this->setOptions($options);
    if(!$this->validateOutput()){return;}

    $this->setCacheHeaders();
    $this->setOptionsHeaders();

    if($this->Method === self::METHOD_OPTIONS){
      ob_start();
      $this->outputHeaders();
      ob_flush();
      return;
    }

    if($this->lateOutput){
      $this->beforeProcessRPC();
      $this->processRPC();
      $this->setResultHeaders();
      $this->startOutput();
      $this->afterProcessRPC();
      $this->endOutput();
    }
    else{
      $this->setResultHeaders();
      $this->startOutput();
      $this->beforeProcessRPC();
      $this->processRPC();
      $this->afterProcessRPC();
      $this->endOutput();
    }
  }

  protected function startOutput(){
    $this->outputHeaders();

    if($this->outputFile){
      ob_clean();
    }

    if($this->canBuffer()){
      ob_start();
      ob_implicit_flush(false);
    }
    else{
      ini_set('zlib.output_compression','Off');
      ini_set('output_buffering ','0');
      ini_set('implicit_flush','1');

      ob_flush();
      ob_implicit_flush(true);
    }

    switch($this->outputType){
      case self::OUTPUT_JAVASCRIPT_IFRAME:
        print(
          '<!doctype html>'.PHP_EOL.
          '<html>'.PHP_EOL.
          '<body>'.PHP_EOL.
          '<script type="text/javascript">'.PHP_EOL
        );
      case self::OUTPUT_JAVASCRIPT:
        print('(function() {'.PHP_EOL);
      break;
    }
  }

  protected function endOutput(){
    switch($this->outputType){
      case self::OUTPUT_JAVASCRIPT:
      case self::OUTPUT_JAVASCRIPT_IFRAME:
        print(PHP_EOL.'})();');

        if($this->outputType === self::OUTPUT_JAVASCRIPT_IFRAME){
          print(
            PHP_EOL.'</script>'.
            PHP_EOL.'</body>'.
            PHP_EOL.'</html>'
          );
        }
      break;
    }

    if($this->canBuffer()){
      ob_flush();
      ob_end_clean();
    }
  }

  protected function canBuffer(){
    return ($this->outputFile) ? true : $this->buffer;
  }

  protected function beforeProcessRPC(){
    $this->validateParams();
  }

  protected function processRPC(){
    if($this->ErrorCode === self::RPC_ERROR_NONE){
      try{
        $this->doProcessRPC();
      }
      catch(Exception $e){
        $this->riseError(self::RPC_ERROR_PROCESS,null,$e);
      }
    }
  }

  protected function afterProcessRPC(){
    $this->output($this->_ResponseData);
  }

  protected function output(&$outputData){
    $output = $this->outputToString($outputData);

    if($this->outputFile){
      if(headers_sent()){return;}

      if($this->ErrorCode !== self::RPC_ERROR_NONE){
        header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
      }
      elseif(!is_string($output)){
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
      }
      elseif($this->outputType === self::OUTPUT_FILE){
        $this->outputBinFile($output);
      }
      else{
        $this->outputAsFile($output);
      }
    }
    elseif(is_string($output)){
      print $output;
    }
  }

  protected function outputToString(&$outputData){
    switch($this->outputType){
      case self::OUTPUT_TEXT:
      case self::OUTPUT_JAVASCRIPT:
      case self::OUTPUT_JAVASCRIPT_IFRAME:
      case self::OUTPUT_JSON:
        if(is_array($outputData) || is_object($outputData)){
          return json_encode($outputData);
        }
        else{
          return strval($outputData);
        }
      break;
      case self::OUTPUT_HTML:
        if(is_string($outputData)){
          return $outputData;
        }
        elseif(is_a($outputData,'DOMDocument')){
          return $outputData->saveHTML();
        }
      break;
      case self::OUTPUT_XML:
      case self::OUTPUT_PDF:
        if(is_string($outputData)){
          return $outputData;
        }
        elseif(is_a($outputData,'SimpleXMLElement')){
          return $outputData->asXML();
        }
      break;
      case self::OUTPUT_FILE:
        if(is_string($outputData) && is_file($outputData)){
          return $outputData;
        }
      break;
    }
    return null;
  }

  protected function outputBinFile($filePath){
    if(headers_sent()){return;}

    $fileName = $this->fileName;
    if(!is_string($fileName)){$fileName = basename($filePath);}

    header('Content-Disposition: attachment; filename="'.$fileName.'"');
    header('Content-Length:'.filesize($filePath));

    if($this->xSendFile){
      header('X-Sendfile: '.$filePath);
      return;
    }

    set_time_limit(0);
    $file = fopen($filePath,'rb');

    if($file){
      while(!feof($file)){
        print(fread($file,$this->fileChunkSize));
        ob_flush();
        flush();
      }
      fclose($file);
    }
  }

  protected function outputAsFile($fileData){
    if(headers_sent()){return;}

    $fileName = $this->fileName;
    if(!is_string($fileName)){$fileName = 'file';}
    if(!is_string($fileData)){$fileData = '';}

    header('Content-Disposition: attachment; filename='.$fileName);
    header('Content-Length:'.strlen($fileData));

    set_time_limit(0);
    foreach(str_split($fileData,$this->fileChunkSize) as $chunk){
      print($chunk);
      ob_flush();
      flush();
    }
  }

  abstract protected function doProcessRPC();
}

