<?php
require_once(dirname(__FILE__).'/bbbfly_RPC.php');
require_once(dirname(__FILE__).'/../adodb/bbbfly_ADOdb_Connection.php');

abstract class bbbfly_RPC_DB extends bbbfly_RPC
{
  const RPC_ERROR_DB_CONNECTION = 1100;

  protected static $_Options = array(
    'dbConfigAlias','dbConfigName','completeTrans'
  );

  private $_dbConfigAlias = 'default';
  private $_dbConfigName = 'DB';
  private $_completeTrans = true;

  private $_DBConnection = null;

  public function __get($propName){
    switch($propName){
      case 'dbConfigAlias': return $this->_dbConfigAlias;
      case 'dbConfigName': return $this->_dbConfigName;
      case 'completeTrans': return $this->_completeTrans;

      case 'DBConnection' : return $this->_DBConnection;
      case 'Options': return array_merge(parent::$_Options,self::$_Options);
      default: return parent::__get($propName);
    }
  }

  public function __set($propName,$value){
    switch($propName){
      case 'dbConfigAlias': if(is_string($value)){$this->_dbConfigAlias = $value;} break;
      case 'dbConfigName': if(is_string($value)){$this->_dbConfigName = $value;} break;
      case 'completeTrans': if(is_bool($value)){$this->_completeTrans = $value;} break;
      default: parent::__set($propName,$value); break;
    }
  }

  protected function getConnectionOptions(){
    if(class_exists('bbbfly_Config',false)){
      return bbbfly_Config::get($this->dbConfigName,$this->dbConfigAlias);
    }
    return null;
  }

  protected function completeTransactions(){
    $db = $this->DBConnection;
    if(!$db || ($db->transOff < 1)){return;}
    if($this->ErrorCode !== self::RPC_ERROR_NONE){$db->FailTrans();}

    $failed = $db->HasFailedTrans();
    for($i=$db->transOff;$i>0;$i--){
      $db->CompleteTrans();
    }
    return !$failed;
  }

  protected function beforeProcessRPC(){
    parent::beforeProcessRPC();
    if($this->ErrorCode !== self::RPC_ERROR_NONE){return;}

    if(!is_null($this->_DBConnection)){return;}

    try{
      $db = bbbfly_ADOdb_Connection::get(
        $this->getConnectionOptions()
      );

      if($db && $db->IsConnected()){
        $db->SetTransactionMode('REPEATABLE READ');
        $db->SetFetchMode(ADODB_FETCH_ASSOC);
      }
      else{
        $this->riseError(self::RPC_ERROR_DB_CONNECTION);
      }

      $this->_DBConnection = $db;

    }catch(Exception $e){
      $this->riseError(self::RPC_ERROR_DB_CONNECTION,null,$e);
    }
  }

  protected function afterProcessRPC(){
    if($this->DBConnection){
      if($this->completeTrans){
        $this->completeTransactions();
      }
      $this->DBConnection->Close();
    }
    parent::afterProcessRPC();
  }
}