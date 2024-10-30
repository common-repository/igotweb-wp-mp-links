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
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Table;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Column;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use \mysqli;

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
    $connexion = @new mysqli($dbConfig->getServer(), $dbConfig->getUser(), $dbConfig->getPassword());
    if($connexion->connect_error) {
      return new Error(9761);  
    }
    
    // 2. We select the DB.
    $selected = $connexion->select_db($dbConfig->getDataBase());
    if(!$selected) {
      return new Error(9762);
    }
    
    $connexion->query("SET NAMES ".$dbConfig->getCharset());
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
      static::$currentConnection->close();
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
   *  It returns mysqli_result if success or false.
   */
  public static function query($query) {
    $logger = Logger::getInstance();
    if(!isset(static::$currentConnection)) {
      $logger->addLog("DBUtils::query : There is no current connexion.");
      return false;
    }
    
    $result = true;
    if (static::$currentConnection->multi_query($query)) {
      do {
        // grab the result of the next query
        $result = static::$currentConnection->store_result();
        if ($result === false) {
          if(static::$currentConnection->error != '') {
            $logger->addLog("DBUtils::query issue : ".static::$currentConnection->error);
            return false;
          }
          else {
            $result = true;
          }
        }
      } while (static::$currentConnection->more_results() && static::$currentConnection->next_result()); // while there are more results
    } else {
      // First query failed
      $logger->addLog("DBUtils::query issue : ".static::$currentConnection->error);
      return false;
    }
    
    if(static::$currentConnection->errno) {
      $logger->addLog("DBUtils::query issue : ".static::$currentConnection->error);
      return false;
    }
    
    return $result;
  }
  
  /*
   *  function: errorInfo
   *  This function returns the last SQL error.
   */
  public static function errorInfo() {
    return static::$currentConnection->error;
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
    return $result->fetch_assoc();
  }

  /*
   *  function: fetchNum
   *  This function returns an array for the next row of the result with column indexed by number.
   */
  public static function fetchNum($result) {
    return $result->fetch_row();
  }
  
  /*
   *  function: fetchFields
   *  This function returns an array of infos about the fields from a DB query.
   *    - name
   *    - table
   *    - max_length
   *    - flags
   *    - type
   *
   *  Types
   *    http://www.php.net/manual/en/mysqli.constants.php
   *
   *  Flags
   *    NOT_NULL_FLAG = 1                                                                             
   *    PRI_KEY_FLAG = 2                                                                              
   *    UNIQUE_KEY_FLAG = 4                                                                           
   *    BLOB_FLAG = 16                                                                                
   *    UNSIGNED_FLAG = 32                                                                            
   *    ZEROFILL_FLAG = 64                                                                            
   *    BINARY_FLAG = 128                                                                             
   *    ENUM_FLAG = 256                                                                               
   *    AUTO_INCREMENT_FLAG = 512                                                                     
   *    TIMESTAMP_FLAG = 1024                                                                         
   *    SET_FLAG = 2048                                                                               
   *    NUM_FLAG = 32768                                                                              
   *    PART_KEY_FLAG = 16384                                                                         
   *    GROUP_FLAG = 32768                                                                            
   *    UNIQUE_FLAG = 65536
   */
  public static function fetchFields($result) {
    return $result->fetch_fields();
  }

  /*
   *  function: getNbRows
   *  This function returns the number of rows in the result.
   */
  public static function getNbRows($result) {
    return $result->num_rows;
  }

  /*
   *  function: lastInsertId
   *  This function returns the id for the last element inserted.
   */
  public static function lastInsertId() {
    return static::$currentConnection->insert_id;
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
    $listColumns = static::getTableColumns($tableName);
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
    while($columnDB = static::fetchAssoc($resultTable)) {
      $column = new Column($columnDB["Field"],$columnDB["Type"]);
      $column->setNull($columnDB["Null"] === "YES");
      $column->setIsPrimaryKey($columnDB["Key"] === "PRI");
      $column->setDefault($columnDB["Default"]);
      $column->setAutoIncrement($columnDB["Extra"] != NULL && strpos($columnDB["Extra"],"auto_increment") !== false);
      $listColumns[$columnDB["Field"]] = $column;
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
    
    $command = 'mysqldump';
    $command .= ' --host=' . $server;
    $command .= ' --user=' . $user;
    $command .= ' --password=' . $password;
    $command .= ' --no-create-db --default-character-set=utf8 --lock-tables=FALSE';
    $command .= ' ' . $dataBase;
    foreach($tableNames as $tableName) {
      $command .= ' ' . $tableName;
    }
    
    $logCommand = preg_replace("/" . preg_quote(" --password=" . $password) . "/","",$command);

    /*
     * In case of error 127, please check that the path is correctly set
     * In OS X Local: '/Applications/MAMP/Library/bin/'
	   * In Windows Local: 'D:\Userfiles\nigot\Documents\Docs\perso\xampp\mysql\bin\' must be put in front of mysqldump
     *
     * On OS X Local: 
     *    - which php should return path to MAMP php. If not (https://gist.github.com/irazasyed/5987693).
     *    - To setup PATH we need to create /Applications/MAMP/Library/bin/envvars file. 
     *        Get the current PATH in console with $PATH command
     *        Copy it and paste it in envvars file with export PATH="THE $PATH" (be careful as no space between name = and value)
     *        (https://stackoverflow.com/questions/40767857/change-path-environment-variable-in-mamp)
     *  
     */
    ob_start();
    echo "-- Dump command \n";
    echo "-- " . $logCommand . "\n";
    echo "-- \n";
    passthru($command, $return);
    echo "-- return: " . $return ." \n";
    $sql = ob_get_contents();
    ob_end_clean();

    if($return == 127) {
      $logger = Logger::getInstance();
      $logger->addLog("DBUtils::getDBDump - issue with mysqldump - ".$logCommand." - ENV['PATH']: ".$_ENV['PATH']);
      return new Error(9765);
    } 
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
    return static::$currentConnection->real_escape_string($value);
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
