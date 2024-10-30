<?php
/**
 *	Class: Column
 *	Version: 0.1
 *	This class handle SQL Column object.
 *
 *	Requires:
 *		- valid DB connexion,
 *		- Error object,
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\database;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;

class Column extends GenericObject {

	protected $name;
	protected $type;
	protected $null;
	protected $default;
  protected $isPrimaryKey;
  protected $isUniqueKey;
	protected $autoIncrement;
  
  /*
   * Column
   * This is the object representing a MySQL column in a table.
   * 
   * @param string $name  The column name.
   * @param string $type  The type of column (int(11)).
   * @param boolean $null True if value can be null.
   */
  public function __construct($name, $type, $null = false, $default = "NULL",
      $autoIncrement = false, $isPrimaryKey = false, $isUniqueKey = false) {
    parent::__construct();
    
		$this->name = $name;
		$this->type = $type;
		$this->null = $null;
		$this->default = $default;
		$this->autoIncrement = $autoIncrement;
    $this->isPrimaryKey = $isPrimaryKey;
    $this->isUniqueKey = $isUniqueKey;
  }
  
  /*
	 *	function: getIdColumn
	 *	This function generates a column object used to be SQLid.
	 *	
	 *	parameters:
   *		- $name : the column name.
   *    - $isPrimaryKey : (optional) true if primaryKey (default = true)
	 *	return:
	 *		- Column object.
	 */
  public static function getIdColumn($name, $isPrimaryKey = true) {
    return new Column($name,"int(11)",false,"NULL",true,$isPrimaryKey);
  }

  /*
	 *	function: getTextColumn
	 *	This function generates a generic column object used to store text.
	 *	
	 *	parameters:
   *		- $name : the column name.
	 *	return:
	 *		- Column object.
	 */
  public static function getTextColumn($name) {
    return new Column($name,"text",true,"NULL");
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
	 *	Column object passed in parameter is the same.
	 *	
	 *	parameters:
	 *		- $column : a Column object.
	 *	return:
	 *		- true if same structure else false.
	 */
	public function equals($column) {
		// 1. We check if the column is a Column object
		if(!$column instanceof Column) {
			return false;
		}
		
		// 2. We check the Column attributes
		if($this->label != $column->getLabel() ||
				$this->type != $column->getType() ||
				$this->null != $column->getNull() ||
				$this->default != $column->getDefault() ||
				$this->isPrimaryKey != $column->getIsPrimaryKey() ||
				$this->autoIncrement != $column->getAutoIncrement()) {
			return false;		
		}
		
		return true;
	}
}
?>