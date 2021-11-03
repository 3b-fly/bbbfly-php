<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>

<?php
  require_once(dirname(__FILE__).'/adodb_5.20.12/adodb-exceptions.inc.php');
  require_once(dirname(__FILE__).'/adodb_5.20.12/adodb.inc.php');

  global $ADODB_COUNTRECS;
  $ADODB_COUNTRECS = false;

  global $ADODB_FETCH_MODE;
  $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

  global $ADODB_OUTP;
  $ADODB_OUTP = 'bbbfly_ADOdb_DebugOutput';

  function bbbfly_ADOdb_DebugOutput($message,$newline){
    bbbfly_ADOdb_Connection::debugLog($message,$newline);
  }

  class bbbfly_ADOdb_Connection
  {
    const TYPE_MYSQLI = 'mysqli';
    const TYPE_MYSQLT = 'mysqlt';

    const TYPE_ORACLE = 'oracle';
    const TYPE_POSTGRES = 'postgres';

    const TYPE_MSSQL = 'mssql';
    const TYPE_MSSQL_ODBC = 'mssql_odbc';
    const TYPE_MSSQL_NATIVE = 'mssql_native';

    protected static $_debugPath = null;

    private function __construct(){}

    public static function get($opts){
      $connection = null;
      if(!is_array($opts)){return $connection;}

      $type = isset($opts['type']) ? (string)$opts['type'] : '';
      $srvr = isset($opts['server']) ? (string)$opts['server'] : '';
      $user = isset($opts['user']) ? (string)$opts['user'] : '';
      $pass = isset($opts['password']) ? (string)$opts['password'] : '';
      $db = isset($opts['database']) ? (string)$opts['database'] : '';
      $charset = isset($opts['charset']) ? (string)$opts['charset'] : '';
      $persist = isset($opts['persist']) ? (bool)$opts['persist'] : false;
      $debug = isset($opts['debug']) ? (bool)$opts['debug'] : false;

      $port = null;
      if(isset($opts['port'])){
        if($type === self::TYPE_MYSQLI){$port = (int)$opts['port'];}
        else{$srvr .= ':'.(string)$opts['port'];}
      }

      $connection = null;
      try{
        switch($type){
          case self::TYPE_MYSQLI:
          case self::TYPE_MYSQLT:
            $connection = ADONewConnection($type);
            $connection->debug = $debug;

            if(is_int($port)){$connection->port=$port;}
            if($persist){$connection->PConnect($srvr,$user,$pass,$db);}
            else{$connection->Connect($srvr,$user,$pass,$db);}

            $connection->SetCharSet($charset);
          break;

          case self::TYPE_ORACLE:
            $connection = ADONewConnection('oci8');
            $connection->debug = $debug;
            $connection->connectSID = true;
            $connection->charSet = $charset;

            if($persist){$connection->PConnect(false,$user,$pass,$srvr);}
            else{$connection->Connect(false,$user,$pass,$srvr);}
          break;
          case self::TYPE_POSTGRES:
            $connection = ADONewConnection('postgres');
            $connection->debug = $debug;
            $connection->charSet = $charset;

            if($persist){$connection->PConnect($srvr,$user,$pass,$db);}
            else{$connection->Connect($srvr,$user,$pass,$db);}
          break;

          case self::TYPE_MSSQL:
            $connection = ADONewConnection('ado_mssql');
            $connection->debug = $debug;

            $dsn = "Provider=SQLOLEDB.1;
              Password=$pass;
              Persist Security Info=True;
              User ID=$user;
              Initial Catalog=$db;
              Data Source=$srvr;";

            if($persist){$connection->PConnect($dsn);}
            else{$connection->Connect($dsn);}
          break;
          case self::TYPE_MSSQL_ODBC:
            $connection = ADONewConnection('odbc_mssql');
            $connection->debug = $debug;

            $dsn = "PROVIDER=MSDASQL;
              DRIVER={SQL Server};
              SERVER=$srvr;
              DATABASE=$db;";

            if($persist){$connection->PConnect($dsn,$user,$pass);}
            else{$connection->Connect($dsn,$user,$pass);}
          break;
          case self::TYPE_MSSQL_NATIVE:
            $connection = ADONewConnection('mssqlnative');
            $connection->debug = $debug;

            if($persist){$connection->PConnect($srvr,$user,$pass,$db);}
            else{$connection->Connect($srvr,$user,$pass,$db);}
          break;
        }
      }
      catch(Exception $e){
        error_log($e);
        $connection = null;
      }

      return $connection;
    }

    protected static function rotateDebugPath($path,$rotation=null){
      if(!is_string($path)){return null;}

      $path = str_replace(array('/','\\'),DIRECTORY_SEPARATOR,$path);
      if(substr($path,-1) === DIRECTORY_SEPARATOR){$path .= 'ADOdb_debug.log';}

      if(!is_string($rotation)){$rotation = 'Y-m-d';}

      $info = pathinfo($path);
      $path = $info['filename'];

      $errorLevel = error_reporting(0);
      $timeZone = date_default_timezone_get();
      date_default_timezone_set('UTC');
      $path .= '_'.date($rotation);
      date_default_timezone_set($timeZone);
      error_reporting($errorLevel);

      if(isset($info['dirname'])){
        $path = $info['dirname'].DIRECTORY_SEPARATOR.$path;
      }
      if(isset($info['extension'])){
        $path .= '.'.$info['extension'];
      }

      return $path;
    }

    public static function setDebugPath($path,$rotation=null){
      self::$_debugPath = self::rotateDebugPath($path,$rotation);
    }

    public static function getDebugPath($key='ADOdb',$alias='default'){
      if(is_string(self::$_debugPath)){return self::$_debugPath;}
      if(!class_exists('bbbfly_Config',false)){return null;}

      $path = bbbfly_Config::get($key.'.Debug.path',$alias);
      $rotation = bbbfly_Config::get($key.'.Debug.rotation',$alias);
      return self::rotateDebugPath($path,$rotation);
    }

    public static function debugLog($message,$newline){
      $message = (string)$message;

      $msgRows = explode("\n",$message);
      $message = '';

      foreach($msgRows as $msgRow){
        $row = rtrim(str_replace('&nbsp;',' ',strip_tags($msgRow)));
        if($row){$message .= $row.PHP_EOL;}
      }

      $message .= PHP_EOL;
      $path = self::getDebugPath();

      if(is_string($path)){
        $message = '['.date('d-M-Y H:i:s').'] '.$message;
        error_log($message,3,$path);
      }
      else{
        error_log($message,0);
      }
    }
  }