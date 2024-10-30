<?php
/**
 *	Class: Table
 *	Version: 0.1
 *	This class handle SQL Table object.
 *
 *	Requires:
 *		- valid DB connexion,
 *		- Error object,
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\database;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;

class Table extends GenericObject {

  public static $DEFAULT_ENGINE = "MyISAM";
  public static $DEFAULT_CHARSET = "utf8";
  public static $DEFAULT_COLLATE = "utf8_bin";
  public static $DEFAULT_AUTO_INCREMENT = "0";

	protected $name;
	protected $listColumns;

	public function __construct($name) {
    parent::__construct();
    
    $this->name = $name; //name is the one in database
    $this->engine = static::$DEFAULT_ENGINE;
    $this->defaultCharset = static::$DEFAULT_CHARSET;
    $this->collate = static::$DEFAULT_COLLATE;
    $this->autoIncrement = static::$DEFAULT_AUTO_INCREMENT;
		$this->listColumns = array();
	}
	
	/*
	 *	function: addColumn
	 *	This function add a Column object to the listColumns.
	 *	If the column already exists, it does not add it.
	 *	
	 *	parameters:
	 *		- $column : a Column object.
	 *	return:
	 *		- none.
	 */
	public function addColumn($column) {
		if($column instanceof Column && 
				!(isset($this->listColumns[$column->getName()]) && $this->listColumns[$column->getName()] instanceof Column)) {
			$this->listColumns[$column->getName()] = $column;
		}
  }
  
  /*
	 *	function: getColumn
	 *	This function get Column object from the listColumns.
	 *	
	 *	parameters:
	 *		- $name : a Column name.
	 *	return:
	 *		- Column object or null if not found.
	 */
	public function getColumn($name) {
		if(isset($this->listColumns[$name])) {
      return $this->listColumns[$name];
    }
    return null;
  }
	
	/*
	 *	function: getPrimaryKey
	 *	This function get the primary key of the table regarding its columns.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- primary key name or "noPrimaryKey" if not found.
	 */
	public function getPrimaryKey() {
		foreach($this->listColumns as $name => $column) {
			if($column->getIsPrimaryKey()) {
				return $name;		
			}	
		}
		return "noPrimaryKey";
  }

  /*
	 *	function: getUniqueKeys
	 *	This function get the unique keys of the table regarding its columns.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- unique keys name or empty array if not found.
	 */
	public function getUniqueKeys() {
    $uniqueKeys = array();
		foreach($this->listColumns as $name => $column) {
			if($column->getIsUniqueKey()) {
				$uniqueKeys[] = $name;		
			}	
		}
		return $uniqueKeys;
  }
  
  /*
	 *	function: getListColumnNames
	 *	This function returns an array of column names.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- array of column names.
	 */
	public function getListColumnNames() {
    return array_keys($this->listColumns);
  }
  
  /*
	 *	function: getCreateQuery
	 *	This function returns the SQL query associated to create the table.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- array of column names.
	 */
  public function getCreateQuery() {
    $query = "CREATE TABLE `" . $this->getName() . "` (";
    foreach($this->listColumns as $index => $column) {
      if($index > 0) {
        $query .= ",";
      }
      $query .= "`".$column->getName()."` ".$column->getType();
      if($column->getNull()) {
        $query .= " NULL";
      }
      else {
        $query .= " NOT NULL";
      }
      if($column->getNull() && $column->getDefault() == "NULL") {
        $query .= " DEFAULT NULL";
      }
      if($column->getAutoIncrement()) {
        $query .= " AUTO_INCREMENT";
      }
    }
    $primaryKey = $this->getPrimaryKey();
    if($primaryKey != "noPrimaryKey") {
      $query .= ",PRIMARY KEY (`".$primaryKey."`)";
    }
    $uniqueKeys = $this->getUniqueKeys();
    if(count($uniqueKeys)>0) {
      $query .= ",UNIQUE KEY (`".implode("`,`", $uniqueKeys)."`)";
    }
    $query .= ") ENGINE=".$this->engine."  DEFAULT CHARSET=".$this->defaultCharset." COLLATE=".$this->collate." AUTO_INCREMENT=".$this->autoIncrement.";";  
    return $query;
  }

  /*
	 *	function: getUpdateQuery
	 *	This function returns the SQL query associated to update the table.
	 *	
	 *	parameters:
   *		- $columnsToRemove - the list of Column objects to be removed.
   *		- $columnsToAdd - the list of Column objects to be added.
	 *	return:
	 *		- "ok" if updated or Error object.
	 */
  public function getUpdateQuery($columnsToRemove, $columnsToAdd) {
    $query = "";
    if(count($columnsToRemove) > 0) {
      $query .= "ALTER TABLE `" . $this->getName() . "`";
      for ($i = 0; $i < count($columnsToRemove); $i++) {
        if($i > 0) {
          $query .= ",";
        }
        $query .= " DROP COLUMN `".$columnsToRemove[$i]->getName()."`";
      }
      $query .= "; ";
    }
    if(count($columnsToAdd) > 0) {
      $query .= "ALTER TABLE `" . $this->getName() . "`";
      for ($i = 0; $i < count($columnsToAdd); $i++) {
        if($i > 0) {
          $query .= ",";
        }
        $query .= " ADD `".$columnsToAdd[$i]->getName()."` ".$columnsToAdd[$i]->getType();
        if($columnsToAdd[$i]->getNull()) {
          $query .= " NULL";
        }
        else {
          $query .= " NOT NULL";
        }
        if($columnsToAdd[$i]->getNull() && $columnsToAdd[$i]->getDefault() == "NULL") {
          $query .= " DEFAULT NULL";
        }
        if($columnsToAdd[$i]->getAutoIncrement()) {
          $query .= " AUTO_INCREMENT";
        }
      }
      $query .= "; ";
    } 
    return $query;
  }
  
  /*
   * function: __call
   * Generic getter for properties.
   */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }
	
	/*
	 *	function: equals
	 *	This function check if the structure of the 
	 *	Table object passed in parameter is the same.
	 *	
	 *	parameters:
	 *		- $table : a table object.
	 *	return:
	 *		- true if same structure else false.
	 */
	public function equals($table) {
		// 1. We check if the table is a Table object
		if(!$table instanceof Table) {
			return false;	
		}
		
		// 2. We check the table attributes
		if($this->name != $table->getName()) {
			return false;	
		}
		
		// 3. We check the number of columns
		$tableColumns = $table->getListColumns();
		if(count($this->listColumns) != count($tableColumns)) {
			return false;	
		}
		
		// 4. We check if the columns are the same
		foreach($this->listColumns as $label => $column) {
			if(!$column->equals($tableColumns[$label])) {
				return false;	
			}
		}
		return true;
	}
}
?>