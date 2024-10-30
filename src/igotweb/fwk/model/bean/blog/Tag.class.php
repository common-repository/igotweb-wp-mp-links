<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\blog;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;

/**
 *	Class: Tag
 *	This class handle the generic Tag object related to Blog.
 *  @BeanClass(sqlTable="blogTags")
 */
class Tag extends GenericBean {
  
  /** @BeanProperty(isLocalized=true) */
  protected $key;
  /** @BeanProperty(isLocalized=true) */
  protected $title;
	
	/*
	 *	Constructor
	 *	It creates a Tag.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Post object.
	 */	
	public function __construct() {
		// We call the parent
		parent::__construct();
		
    $this->key = NULL;
    $this->title = NULL;
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
      $table->addColumn(new Column("key-".$languageCode,"varchar(100)",true,"NULL"));
      $table->addColumn(new Column("title-".$languageCode,"varchar(100)",true,"NULL"));
    }

	  return $table;
	}

  /*
	 *	static function: keyComparator
	 *	This function compare two tags by key.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int for ordering.
	 */
   public static function keyComparator($a, $b) {
    return strcasecmp($a->getKey(),$b->getKey());
  }
}

?>
