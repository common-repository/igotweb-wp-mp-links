<?php

/**
 *	Class: CustomParameter
 *	Version: 0.1
 *	This class handle the custom parameters.
 *
 *	requires:
 *		- none.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;

class CustomParameter extends GenericBean {

  protected $shortName; // The short name of the parameter which is unique in DB.
  protected $name; // The name of the parameter.
  protected $value; // The value of the parameter.
  protected $description; // The description of the parameter

  function __construct() {
    parent::__construct();
    
    $this->shortName = NULL;
    $this->name = NULL;
    $this->value = NULL;
    $this->description = NULL;
  }

  /*
	 * getTable
	 * This method returns the table object associated to the bean.
	 */
	public function getTable() {
    $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;

    $table = new Table(static::getTableName());
    $table->addColumn(Column::getIdColumn(static::getSQLidColumnName()));
    // the shortName must be unique key
    $table->addColumn(new Column("shortName","varchar(30)"),false,"NULL",false,false,true);
    $table->addColumn(new Column("name","varchar(50)"));
    $table->addColumn(Column::getTextColumn("value"));
    $table->addColumn(Column::getTextColumn("description"));
	  return $table;
	}

  /*
   * function: __call
   * Generic getter for properties.
   */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }
}
?>
