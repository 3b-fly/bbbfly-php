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

  class bbbfly_ADOdb_Connection
  {
    const TYPE_MYSQLI = 'mysqli';
    const TYPE_MYSQLT = 'mysqlt';

    const TYPE_ORACLE = 'oracle';
    const TYPE_POSTGRES = 'postgres';

    const TYPE_MSSQL = 'mssql';
    const TYPE_MSSQL_ODBC = 'mssql_odbc';
    const TYPE_MSSQL_NATIVE = 'mssql_native';

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
  }