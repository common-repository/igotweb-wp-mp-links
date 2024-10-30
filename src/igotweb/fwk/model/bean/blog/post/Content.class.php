<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\post;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Table;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Column;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;

/**
 *	Class: Content
 *	This class handle the Content object used in posts.
 *  @BeanClass(sqlTable="blogPostContent")
 */
class Content extends GenericBean {
  
  /** @BeanProperty(isLocalized=true,isJson=true) */
  protected $value; // The value of the content.
	
	/*
	 *	Constructor
	 *	It creates a Post Content.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Content object.
	 */	
	public function __construct() {
		// We call the parent
		parent::__construct();
		
    $this->value = NULL;
  }

  /*
   *   static function: getType
   *   This function returns the type of content. it is based on the class name.
   */
  public function getType() {
    return static::getServiceName();
  }
  
  /*
   *   function: __call
   *   Generic getter and setter for properties.
   */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }
	
	/*
	 * getTable
	 * This method returns the table object associated to the bean.
	 */
	public function getTable() {
    $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;

    $table = new Table(static::getTableName());
    $table->addColumn(Column::getIdColumn(static::getSQLidColumnName()));
    
    // We generate the localized properties
    foreach($supportedLanguages as $languageCode) {
      $table->addColumn(Column::getTextColumn("value-".$languageCode));
    }
    
	  return $table;
  }
  
  /*
  *	function: toArray
  *	This function convert the object in associative array format.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- associative array representation of the bean.
  */
  public function toArray() {
    // We get the object view as array
    $bean = parent::toArray();
    
    // We add the type
    $bean["type"] = $this->getType();

    return $bean;
  }
}

?>
