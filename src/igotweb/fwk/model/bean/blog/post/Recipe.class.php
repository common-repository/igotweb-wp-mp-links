<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\post;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\post\Content;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Table;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Column;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;

/**
 *	Class: Recipe
 *	This class handle the Recipe object extending Content.
 *  @BeanClass(sqlTable="blogPostRecipe")
 */
class Recipe extends Content {
	
  protected $timePreparation; // The preparation time in minutes
  protected $timeCooking; // The cooking time in muntes
  /** @BeanProperty(isJson=true, isLocalized=true) */
  protected $ingredients; // The list of ingredients with quantity (keys: name, quantity).
  protected $quantity; // The quantity.
  protected $quantityType; // The quantity type.
  protected $skillLevel; // Level of difficulties (1: Very Easy, 2: Easy, 3: Medium, 4: Hard).
	
	/*
	 *	Constructor
	 *	It creates a Post.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Post object.
	 */	
	public function __construct() {
		// We call the parent
		parent::__construct();
		
    $this->timePreparation = null;
    $this->timeCooking = null;
    $this->ingredients = null;
    $this->quantity = null;
    $this->quantityType = null;
    $this->skillLevel = null;
  }

  /*
	 * getTable
	 * This method returns the table object associated to the bean.
	 */
	public function getTable() {
    $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;

    $parentClassName = get_parent_class($this);
    $parentTableColumns = (new $parentClassName())->getTableColumns();
    if($parentTableColumns instanceof Error) {
      return $parentTableColumns;
    }

    $table = new Table(static::getTableName());
    $table->addColumn(Column::getIdColumn(static::getSQLidColumnName()));
    $table->addColumn(new Column("timePreparation","int(2)"));
    $table->addColumn(new Column("timeCooking","int(2)"));
    $table->addColumn(new Column("quantity","int(2)"));
    $table->addColumn(new Column("quantityType","varchar(15)"));
    $table->addColumn(new Column("skillLevel","int(1)"));
    foreach($supportedLanguages as $languageCode) {
      $table->addColumn(Column::getTextColumn("ingredients-".$languageCode));
    }

    // We add the columns from the Content class.
    foreach($parentTableColumns as $column) {
      $table->addColumn($column);
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
    $bean["totalDuration"] = $this->getTimePreparation() + $this->getTimeCooking();

    return $bean;
  }
}

?>
