<?php
/**
 *	Class: DBUtils
 *	Version: 0.2
 *	This class handle DB needs.
 *  It implements My SQL.
 *
 *	Requires:
 *		- suffix variable,
 *		- Error object,
 *		- Logger,
 *		- request,
 *		- Table and Column object,
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class DBUtils {

  public static $prefixTables = "{TABLE_PREFIX}";

  private static $currentConnection;
  private static $currentConfiguration;

  private function __construct() {
  }

  /*
   *	function: connect
   *	This function connect to SQL DB based on the configuration.
   */
  public static function connect($dbConfig) {
    // 1. We connect to the DB
    $connexion = mysql_connect($dbConfig->getServer(), $dbConfig->getUser(), $dbConfig->getPassword());
    if(!$connexion) {
      return new Error(9761);  
    }
    
    // 2. We select the DB.
    $selected = mysql_select_db($dbConfig->getDataBase(), $connexion);
    if(!$selected) {
      return new Error(9762);
    }
    
    mysql_query("SET NAMES ".$dbConfig->getCharset());
    static::$currentConnection = $connexion;
    static::$currentConfiguration = $dbConfig;
      
    return "ok";
  }

  /*
   *	function: disconnect
   *	This function connect to SQL DB based on the configuration.
   */
  public static function disconnect() {
    if(isset(static::$currentConnection)) {
      mysql_close(static::$currentConnection);
      static::$currentConnection = NULL;
    }
    static::$currentConfiguration = NULL;
  }
  
  public static function getCurrentConfig() {
    return static::$currentConfiguration;
  }

  /*
   *  function: query
   *  This function handle the query to SQL DB.
   */
  public static function query($query) {
    $logger = Logger::getInstance();
    if(!isset(static::$currentConnection)) {
      $logger->addLog("DBUtils::query : There is no current connexion.");
      return false;
    }
    
    // We handle multiple queries
    $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);
    if(is_array($queries) && count($queries) > 1) {
      // For multiple queries, we loop over each queries
      foreach ($queries as $singleQuery){
        if (strlen(trim($singleQuery)) > 0) {
          $result = mysql_query($singleQuery);
          if(!$result) {
            $logger->addLog("DBUtils::query issue : ".$singleQuery);
            return false;
          }
        }
      }
      return true;
    }
    else {
      return mysql_query($query);
    } 
  }
  
  /*
   *  function: errorInfo
   *  This function returns the last SQL error.
   */
  public static function errorInfo() {
    return mysql_error();
  }
  
  /*
   *  function: getCurrentConfigTableName
   *  This function get the table name from current configuration.
   */
  public static function getCurrentConfigTableName($tableName) {
	
	if(!isset(static::$currentConfiguration)) {
	  $error = Error::getGeneric("DBUtils::getCurrentConfigTableName (" . $tableName . ") - There is no current configuration.");
      return $error;
	}
	
    $name = static::$currentConfiguration->getPrefixTables();
    $name .= $tableName;
    $name .= static::$currentConfiguration->getSuffixTables();
    return $name;
  }
  
  /*
   *  function: removeSuffixFromTableName
   *  This function remove the suffix from a table name.
   */
  public static function removeSuffixFromTableName($tableName) {
    return preg_replace("/" . preg_quote(static::$currentConfiguration->getSuffixTables()) . "$/", "", $tableName);
  }

  /*
   *  function: fetchAssoc
   *  This function returns an associative array for the next row of the result.
   */
  public static function fetchAssoc($result) {
    return mysql_fetch_assoc($result);
  }

  /*
   *  function: fetchNum
   *  This function returns an array for the next row of the result with column indexed by number.
   */
  public static function fetchNum($result) {
    return mysql_fetch_row($result);
  }

  /*
   *  function: getNbRows
   *  This function returns the number of rows in the result.
   */
  public static function getNbRows($result) {
    return mysql_num_rows($result);
  }

  /*
   *  function: lastInsertId
   *  This function returns the id for the last element inserted.
   */
  public static function lastInsertId() {
    return mysql_insert_id();
  }

  /*
   *	function: tableExists
   *	This function check if the table exists in the dataBase.
   *
   *	parameters:
   *		- $tableName : the name of the table.
   *	return:
   *		- true if exists, else false.
   */
  public static function tableExists($tableName) {
    $queryTable = "SHOW COLUMNS FROM `".$tableName."`;";
    $resultTable = static::query($queryTable);

    if(!$resultTable) {
      return false;
    }

    return true;
  }

  /*
   *	static function: getTable
   *	This function get the table object from the dataBase regarding the tableName.
   *
   *	parameters:
   *		- $tableName : the name of the table in DB.
   *	return:
   *		- Table object if found, else Error object.
   */
  public static function getTable($tableName) {
    // 1. We check that the table exists
    if(!static::tableExists($tableName)) {
      return new Error(9763);
    }

    // 2. We create the table object
    $table = new Table($tableName);

    // 3. We add the columns to the table
    $listColumns = DBUtils::getTableColumns($tableName);
    if($listColumns instanceof Error) {
      return $listColumns;
    }
    $table->setListColumns($listColumns);

    return $table;
  }

  /*
   *	function: getTableColumns
   *	This function get the columns from a table in dataBase.
   *
   *	parameters:
   *		- $tableName : the name of the table.
   *	return:
   *		- an associative array of Columns or Error object.
   */
  public static function getTableColumns($tableName) {
    // 1. Request information on the table
    $queryTable = "DESCRIBE `".$tableName."`;";
    $resultTable = static::query($queryTable);

    // 2. We check that the table exists
    if(!$resultTable) {
      return new Error(9764);
    }

    // 3. We create an array with the result
    $listColumns = array();
    while($columnDB = mysql_fetch_assoc($resultTable)) {
      $column = new Column($columnDB["Field"],$columnDB["Type"]);
      $column.setNull($columnDB["Null"]);
      $column.setKey($columnDB["Key"]);
      $column.setDefault($columnDB["Default"]);
      $column.setExtra($columnDB["Extra"]);
      $listColumns[$column->getLabel()] = $column;
    }

    return $listColumns;
  }
  
  public static function getDBDump($tableNames) {
    
    // We check if we have a single table in parameter
    if(!is_array($tableNames)) {
      $tableNames = array($tableNames);
    }
    
    $config = static::$currentConfiguration;
    $user = $config->getUser();
    $server = $config->getServer();
    $password = $config->getPassword();
    $dataBase = $config->getDataBase();
    
    $command = 'D:\Userfiles\nigot\Documents\Docs\perso\xampp\mysql\bin\mysqldump';
    $command .= ' --host=' . $server;
    $command .= ' --user=' . $user;
    $command .= ' --password=' . $password;
    $command .= ' --no-create-db --default-character-set=utf8 --lock-tables=FALSE';
    $command .= ' ' . $dataBase;
    foreach($tableNames as $tableName) {
      $command .= ' ' . $tableName;
    }
    
    $logCommand = preg_replace("/" . preg_quote(" --password=" . $password) . "/","",$command);

    // In case of error 127, please check that the path is correctly set
    // In OS X Local: '/Applications/MAMP/Library/bin/'
	// In Windows Local: 'D:\Userfiles\nigot\Documents\Docs\perso\xampp\mysql\bin\' must be put in front of mysqldump
    ob_start();
    echo "-- Dump command \n";
    echo "-- " . $logCommand . "\n";
    echo "-- \n";
    passthru($command, $return);
    $sql = ob_get_contents();
    ob_end_clean(); 
    return $sql;
  }
  
  /*
   *	function: quote
   *	This function convert a string into mysql string. It escape DB special characters.
   *
   *	parameters:
   *		- $value : a string value.
   *	return:
   *		- a SQL escaped string.
   */
  public static function quote($value) {
    return mysql_real_escape_string($value);
  }

  /*
   *	function: booleanToDB
   *	This function convert a boolean into mysql data.
   *
   *	parameters:
   *		- $bool : a boolean.
   *	return:
   *		- a SQL BOOL.
   */
  public static function booleanToDB($bool) {
    if($bool) {
      return 1;
    }
    return 0;
  }

  /*
   *	function: getBoolean
   *	This function convert a BOOL data into boolean.
   *
   *	parameters:
   *		- $mySQLData : a BOOL from mySQL result.
   *	return:
   *		- a boolean.
   */
  public static function getBoolean($mySQLData) {
    if($mySQLData == 0) {
      return false;
    }
    return true;
  }

  /*
   *	function: getPhpTimeFromDateTime
   *	This function return a php time from a DateTime SQL object.
   *
   *	parameters:
   *		- $inputDateTime : a mysql DateTime (AAAA-MM-JJ HH:MM:SS).
   *	return:
   *		- a php time.
   */
  public static function getPhpTimeFromDateTime($inputDate) {
    $jour = substr($inputDate,8,2);
    $mois = substr($inputDate,5,2);
    $annee = substr($inputDate,0,4);
    $heure = substr($inputDate,11,2);
    $minute = substr($inputDate,14,2);
    $seconde = substr($inputDate,17,2);
    return mktime($heure,$minute,$seconde,$mois,$jour,$annee);
  }

  /*
   *	function: getSQLDateTime
   *	This function return a SQL Date Time from a php time.
   *
   *	parameters:
   *		- $time : a php time.
   *	return:
   *		- a SQL Date time (AAAA-MM-JJ HH:MM:SS).
   */
  public static function getSQLDateTime($time = NULL) {
    if(!isset($time)) {
      return date("Y-m-d H:i:s");
    }
    else {
      return date("Y-m-d H:i:s",$time);
    }
  }
}

?>
