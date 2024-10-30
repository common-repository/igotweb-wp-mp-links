<?php
/**
 *	Class: GenericBean
 *	This class handle the GenericBean object to be stored in DB.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\generic;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use ReflectionClass;
use igotweb_wp_mp_links\igotweb\fwk\utilities\AnnotationsUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Table;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Column;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class GenericBean extends GenericObject {

  public static $SQL_ARRAY_SEPARATOR = ",";

  protected $SQLid;

  /**
   * We store the localized properties values when we retrieve the bean. 
   * @BeanProperty(isExcludedFromDB=true) 
   */
  protected $localizedPropertiesValues;
  /**
   * A bean is populated in a specific language. 
   * @BeanProperty(isExcludedFromDB=true) 
   */
  protected $beanLanguageCode;

  /*
   *	protected Constructor
  *	GenericBean should only be extended.
  */
  protected function __construct() {
    parent::__construct();
  }

  /*
   *	handleGetterSetter
   *	This method should be used in __call method to handle generic getter and setter.
   *  It overrides the GenericObject method.
   */
  public function handleGetterSetter($method, $params) {
    $logger = Logger::getInstance();
    global $request;
  
    //did you call get or set
    if ( preg_match("/^[gs]et([A-Z][\w]+)$/", $method, $matches ) ) {
      // We get the property
      $property = lcfirst($matches[1]);
      $idProperty = "id".ucfirst($property);
      // We look for Generic bean if the SQL id is a property
      if(property_exists($this, $property) && property_exists($this, $idProperty) && 'g' == $method[0]) {
        if(!isset($this->$property) && isset($this->$idProperty)) {
          $class = Utils::loadBean(ucfirst($property), $request);
          $instance = new $class();
          $result = $instance->getFromDB($this->$idProperty);
          if($result instanceof Error) {
            return $result;
          }
          $this->$property = $instance;
        }
        return $this->$property;
      }
      else {
        return parent::handleGetterSetter($method, $params);
      }
    }
    else {
      $error = Error::getGeneric(get_class($this) . ": Call to undefined method : " . $method . ".");
      $logger->addErrorLog($error,true,false);
      return $error;
    }
  }
  
  /*
   *	function: loadBeanUtilsClass
   *	This function loads a bean utils and returns the full class path if loaded.
   *
   *	parameters:
   *    - $request - the Request instance.
   *    - $siteContext - specific site context to load the utils.
   *	return:
   *		- classPath if found and loaded or Error object.
   */
  public static function loadBeanUtilsClass(Request $request, SiteContext $siteContext = NULL) {
    $logger = Logger::getInstance();
    
    $className = static::getClassName();
    $relativeNamespace = static::getBeanRelativeNamespace();
    $result = Utils::loadBeanUtilsClass($relativeNamespace.$className, $request, $siteContext);

    // If specific class utils is not found we backup to GenericBeanUtils.
    if($result instanceof Error) {
      $className = GenericBean::getClassName();
      $relativeNamespace = GenericBean::getBeanRelativeNamespace();
      $result = Utils::loadBeanUtilsClass($relativeNamespace.$className, $request, $siteContext);
    }
    
    return $result;
  }

  /*
   *	function: loadAdminBeanUtilsClass
   *	This function loads the admin bean utils and returns the full class path if loaded.
   *
   *	parameters:
   *    - $request - the Request instance.
   *    - $siteContext - specific site context to load the utils.
   *	return:
   *		- classPath if found and loaded or Error object.
   */
  public static function loadAdminBeanUtilsClass(Request $request, SiteContext $siteContext = NULL) {
    $logger = Logger::getInstance();
    
    $className = static::getClassName();
    $relativeNamespace = static::getBeanRelativeNamespace();
    $result = Utils::loadBeanUtilsClass($relativeNamespace."admin\\Admin".$className, $request, $siteContext);

    // If specific class utils is not found we backup to AdminGenericBeanUtils.
    if($result instanceof Error) {
      $className = GenericBean::getClassName();
      $relativeNamespace = GenericBean::getBeanRelativeNamespace();
      $result = Utils::loadBeanUtilsClass($relativeNamespace."admin\\Admin".$className, $request, $siteContext);
    }
    
    return $result;
  }
  
  /*
   *   static function: getServiceName
   *   This function returns the service name (in lowercase) for the bean.
   */
  public static function getServiceName() {
    return lcfirst(static::getClassName());
  }

  /*
   *   static function: getSQLidColumnName
   *   This function returns the SQLid column name in DB.
   */
  public static function getSQLidColumnName() {
    return "id".static::getClassName();
  }

  /*
   *   static function: getTableName
   *   This function returns the table name associated to bean based on current configuration.
   */
  public static function getTableName() {
    
    // We check from the annotations
    $annotationBeanClass = AnnotationsUtils::getFromClass(get_called_class(), "beanClass");
    if($annotationBeanClass instanceof Error) {
      return $annotationBeanClass;
    }
    if(isset($annotationBeanClass->sqlTable)) {
      $tableName = $annotationBeanClass->sqlTable;
    }

    // We backup to the generic name from className
    if(!isset($tableName)) {
      $className = static::getClassName();
      $tableName = Utils::camel2dashed($className);
      if(substr($tableName, -1) !== "s") {
        $tableName .= "s";
      }
    }

    return DBUtils::getCurrentConfigTableName($tableName);
  }
  
  /*
   * static::isAvailableInDB
   * This function check if the corresponding table is available in DB with correct structure.
   * @return: It returns an object with several informations or Error object.
   * {
   *    "className" : the associated class name,
   *    "tableName" : the table name,
   *    "tableExistsInDB" : boolean,
   *    "tableHasCorrectStructure" : boolean,
   *    "missingColumns" : array of column names that are not in current table.
   *    "toBeRemovedColumns" : array of column names that are not expected from the Bean.
   * }
   */
  public static function isAvailableInDB() {
    $tableName = static::getTableName();
	  if($tableName instanceof Error) {
	    return $tableName;
    }

    $className = get_called_class();
    
    $result = array(
      "className" => $className,
      "tableName" => $tableName,
      "tableExistsInDB" => false,
      "tableHasCorrectStructure" => false,
      "missingColumns" => array(),
      "toBeRemovedColumns" => array()
    );
    
    // 1. We check that the table exists in DB.
    $exists = DBUtils::tableExists($tableName);
    $result["tableExistsInDB"] = $exists;

    // 2. We check that we have the expected columns.
    if($exists) {
      $bean = new $className();
      $beanColumns = $bean->getTableColumnsToStore();
      $beanColumns[] = "id".static::getClassName();
  
      $table = DBUtils::getTable($tableName);
      $currentTableColumns = $table->getListColumnNames();
  
      $missingColumns = array_values(array_diff($beanColumns, $currentTableColumns));
      $toBeRemovedColumns = array_values(array_diff($currentTableColumns, $beanColumns));
      $result["missingColumns"] = $missingColumns;
      $result["toBeRemovedColumns"] = $toBeRemovedColumns;
  
      if(count($missingColumns) == 0 && count($toBeRemovedColumns) == 0) {
        $result["tableHasCorrectStructure"] = true;
      }
    }
    
    return $result;
  }
  
  /*
   * createTableInDB
   * This function create the table in DB.
   */
  public function createTableInDB() {
    $logger = Logger::getInstance(); 
    
    // We get the specific query
    $query = $this->getCreateTableQuery();
    if($query instanceof Error) {
      return $query;
    }
    
    // We create the table
    $resultCreate = DBUtils::query($query);
    if(!$resultCreate) {
      $logger->addLog("GenericBean::createTableInDB: ".$query);
      return new Error(9106,3,array(get_class($this)));
    }
    
    return "ok";
  }

  /*
   * updateTableInDB
   * This function update the table in DB. If the table does not exist in DB, it creates it.
   */
  public function updateTableInDB() {
    $logger = Logger::getInstance(); 
    
    $tableAvailability = static::isAvailableInDB();

    // If the table does not exist, we create it in DB.
    if(!$tableAvailability["tableExistsInDB"]) {
      return $this->createTableInDB();
    }

    // We get the table name
    $tableName = static::getTableName();
    if($tableName instanceof Error) {
      return $tableName;
    }

    // We get the list of columns to remove
    $columnsToRemove = array();
    if(count($tableAvailability["toBeRemovedColumns"]) > 0) {
      // We get the existing table
      $existingTable = DBUtils::getTable($tableName);

      // We get the column objects to remove.
      foreach($tableAvailability["toBeRemovedColumns"] as $columnName) {
        $column = $existingTable->getColumn($columnName);
        if($column instanceof Column) {
          $columnsToRemove[] = $column;
        }
      }
    }

    // We get the list of columns to add
    $columnsToAdd = array();
    if(count($tableAvailability["missingColumns"]) > 0) {
      // We get the expected table
      $beanTable = $this->getTable();
      if($beanTable instanceof Error) {
        return $beanTable;
      }

      // We get the column objects to add.
      foreach($tableAvailability["missingColumns"] as $columnName) {
        $column = $beanTable->getColumn($columnName);
        if($column instanceof Column) {
          $columnsToAdd[] = $column;
        }
      }
    }

    // We get the specific query
    $query = $this->getUpdateTableQuery($columnsToRemove, $columnsToAdd);
    if($query instanceof Error) {
      return $query;
    }
    
    // We update the table
    $resultUpdate = DBUtils::query($query);
    if(!$resultUpdate) {
      $logger->addLog("GenericBean::updateTableInDB: ".$query);
      return new Error(9109,1,array($tableName));
    }
    
    return "ok";
  }

  

  /*
   * getTable
   * This method returns the table object associated to the bean.
  * There is no generic method, it has to be redefined within the object.
  */
  public function getTable() {
    return new Error(9106,1,array(get_class($this)));
  }

  /*
   * getTableColumns
   * This method returns the list of columns for the table (except the SQLid).
   * It is based on the table structure associated to the bean.
   */
  public function getTableColumns() {
    $table = $this->getTable();
    if($table instanceof Error) {
      return $table;
    }

    // We get the list of columns
    $listColumns = $table->getListColumns();

    // We filter the SQLid
    foreach($listColumns as $index => $column) {
      if($column->getName() == static::getSQLidColumnName()) {
        unset($listColumns[$index]);
        break;
      }
    }

    $listColumns = array_values($listColumns);
    return $listColumns;
  }
  
  /*
   * getCreateTableQuery
   * This method returns the query to create the table in DB.
   */
  public function getCreateTableQuery() {
    $table = $this->getTable();
    if($table instanceof Error) {
      return $table;
    }

    return $table->getCreateQuery();
  }

  /*
   * getCreateTableQuery
   * This method returns the query to create the table in DB.
   */
  public function getUpdateTableQuery($columnsToRemove, $columnsToAdd) {
    $table = $this->getTable();
    if($table instanceof Error) {
      return $table;
    }

    return $table->getUpdateQuery($columnsToRemove, $columnsToAdd);
  }

  /*
   * getExcludedPropertiesToStore
   * This method returns the list of bean properties we do not store in DB.
   */
  public function getExcludedPropertiesToStore() {

    // We check from the annotations
    $excludedProperties = AnnotationsUtils::getListProperties(get_class($this), "beanProperty", "isExcludedFromDB", true);

    return $excludedProperties;
  }

  /*
   * getLocalizedProperties
   * This method returns the list of bean properties that are localized in DB.
   */
  public function getLocalizedProperties() {

    // We check from the annotations
    $localizedProperties = AnnotationsUtils::getListProperties(get_class($this), "beanProperty", "isLocalized", true);

    return $localizedProperties;
  }

  /*
   * getJsonProperties
   * This method returns the list of bean properties that are localized in DB.
   */
  public function getJsonProperties() {
    
    // We check from the annotations
    $localizedProperties = AnnotationsUtils::getListProperties(get_class($this), "beanProperty", "isJson", true);

    return $localizedProperties;
  }


  /*
   * getPropertiesToStore
   * This method returns the list of bean properties that are stored in DB.
   */
  public function getPropertiesToStore() {
    // 1. We get the generic object properties
    $propertiesToStore = $this->getProperties();

    // 2. We get the excluded properties
    $excludedProperties = $this->getExcludedPropertiesToStore();

    // 3. We look for excluded properties. It can be the property name or the idProperty.
    foreach($propertiesToStore as $index => $property) {
      if(in_array("id" . ucfirst($property), $excludedProperties) ||
          in_array($property, $excludedProperties)) {
        // We remove the property
        unset($propertiesToStore[$index]);
      }
    }

    // We reindex the array
    $propertiesToStore = array_values($propertiesToStore);

    return $propertiesToStore;
  }

  /*
	 *	function: getProperties
   *	This function return the list of bean properties. 
   *  Here is the list of excluded properties: 
   *    - static properties.
   *    - properties where idProperty is also a property.
   *    - SQLid property.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- list of properties name.
	 */
  public function getProperties() {
    // We get the properties from Generic Object.
    $properties = parent::getProperties();

    // We look in properties if for property we have also idProperty.
		// In that case, only idProperty is kept.
		foreach($properties as $index => $property) {
			if(in_array("id" . ucfirst($property), $properties)) {
				// We remove the property
				unset($properties[$index]);
      }
      else if($property == "SQLid") {
        // We remove the property
				unset($properties[$index]);
      }
    }
    
    // We reindex the array
		$properties = array_values($properties);

    return $properties;
  }

  /*
   *  public function: getTableColumnsToStore
   *  Based on the properties to store, we calculate the corresponding columns in the table.
   *  It does not contains the SQLid.
   */
  public function getTableColumnsToStore() {
    $tableColumnsToStore = array();

    $propertiesToStore = $this->getPropertiesToStore();
    $localizedProperties = $this->getLocalizedProperties();

    for($i = 0 ; $i < count($propertiesToStore) ; $i++) {
      $propertyName = $propertiesToStore[$i];
      if(in_array($propertyName, $localizedProperties)) {
        // We have one column per supported language
        $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;
        foreach($supportedLanguages as $languageCode) {
          array_push($tableColumnsToStore, $propertyName."-".$languageCode);
        }
      }
      else {
        // The column has the same name as the property
        array_push($tableColumnsToStore, $propertyName);
      }
    }

    return $tableColumnsToStore;
  }

  /*
   *	function: storeInDB
  *	This function store the bean in DB.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if stored else an Error object.
  */
  public function storeInDB() {
    $logger = Logger::getInstance();
    	
    if(!isset($this->SQLid)) {
      // 1. If there is no SQL id, the bean has to be created in DB.
      $queryCreate = $this->getCreateQuery();
      if($queryCreate instanceof Error) {
        return $queryCreate;
      }
      $resultCreate = DBUtils::query($queryCreate);

      if(!$resultCreate) {
        $logger->addLog("queryCreate: ".$queryCreate);
        return new Error(9101,1,array(get_class($this)));
      }
      	
      $this->SQLid = DBUtils::lastInsertId();
    }
    else {
      // 2. If there is an SQL id, we try to update the bean in DB.
      $queryUpdate = $this->getUpdateQuery();
      if($queryUpdate instanceof Error) {
        return $queryUpdate;
      }
      $resultUpdate = DBUtils::query($queryUpdate);
      	
      if(!$resultUpdate) {
        $logger->addLog("queryUpdate: ".$queryUpdate);
        return new Error(9101,2,array(get_class($this)));
      }
    }

    return "ok";
  }

  /*
   *	function: getCreateQuery
  *	This function returns the insert DB query for the bean.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- insert query or an Error object.
  */
  public function getCreateQuery() {
    global $request;

    $tableName = static::getTableName();
    if($tableName instanceof Error) {
      return $tableName;
    }

    $propertiesToStore = $this->getPropertiesToStore();
    $localizedProperties = $this->getLocalizedProperties();

    $queryCreate = "INSERT INTO `".$tableName."` ";
    $queryCreate .= "(";
    for($i = 0 ; $i < count($propertiesToStore) ; $i++) {
      if($i > 0) {
        $queryCreate .= ",";
      }
      $propertyName = $propertiesToStore[$i];
      // In case of localized property, we store the value in current language.
      if(in_array($propertyName, $localizedProperties)) {
        $currentLanguageCode = $request->getLanguageCode();
        $propertyName .= "-" . $currentLanguageCode;
      }
      $queryCreate .= "`".$propertyName."`";
    }
    $queryCreate .= ") VALUES (";
    for($i = 0 ; $i < count($propertiesToStore) ; $i++) {
      if($i > 0) {
        $queryCreate .= ",";
      }
      $sqlValue = $this->getSQLValue($propertiesToStore[$i]);
      if($sqlValue instanceof Error) {
        return $sqlValue;
      }
      $queryCreate .= $sqlValue;
    }
    $queryCreate .= ")";
    return $queryCreate;
  }

  /*
   *	function: getUpdateQuery
  *	This function returns the update DB query for the bean.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- update query or an Error object.
  */
  public function getUpdateQuery() {
    global $request;

    $tableName = static::getTableName();
    if($tableName instanceof Error) {
      return $tableName;
    }

    $propertiesToStore = $this->getPropertiesToStore();
    $localizedProperties = $this->getLocalizedProperties();

    $queryUpdate = "UPDATE `".$tableName."` SET";
    for($i = 0 ; $i < count($propertiesToStore) ; $i++) {
      if($i > 0) {
        $queryUpdate .= ",";
      }
      $propertyName = $propertiesToStore[$i];
      // In case of localized property, we store the value in current language.
      if(in_array($propertyName, $localizedProperties)) {
        $currentLanguageCode = $request->getLanguageCode();
        $propertyName .= "-" . $currentLanguageCode;
      }
      $queryUpdate .= " `".$propertyName."` = ".$this->getSQLValue($propertiesToStore[$i]);
    }
    $queryUpdate .= " WHERE `id".static::getClassName()."` = ".$this->SQLid;
    return $queryUpdate;
  }

  /*
   *	protected function: getSQLValue
  *	This function get the SQL value to put in DB from object property.
  *
  *	parameters:
  *		- $propertyName - the object property name.
  *	return:
  *		- the SQL value to insert in DB.
  */
  protected function getSQLValue($propertyName) {
    $getter = "get".ucfirst($propertyName);
    $value = $this->$getter();
    $isJson = false;
    $beanPropertyAnnotation = AnnotationsUtils::getFromProperty(get_class($this), $propertyName, "beanProperty");
    if($beanPropertyAnnotation instanceof BeanProperty) {
      $isJson = $beanPropertyAnnotation->isJson;
    }

    if(!isset($value)) {
      return "NULL";
    }
    else if($isJson) {
      $value = JSONUtils::buildJSONObject($value);
      return "'".DBUtils::quote($value)."'";
    }
    else if($value instanceof IwDateTime) {
      return "'".$value->getSQLDateTime()."'";
    }
    else if(is_bool($value)) {
      return DBUtils::booleanToDB($value);
    }
    else if(is_int($value)) {
      return $value;
    }
    else if(is_string($value)) {
      return "'".DBUtils::quote($value)."'";
    }
    else if(is_array($value)) {
      $str = "'ARRAY:";
      if(count($value) > 0) {
        $str .= implode(GenericBean::$SQL_ARRAY_SEPARATOR,$value);
      }
      $str .= "'";
      return $str;
    }
    return $value;
  }

  /*
   *	protected function: getFromSQLValue
  *	This function get object value to be set from SQL value.
  *
  *	parameters:
  *		- $SQLValue - the DB SQL value.
  *	return:
  *		- the value to set in object.
  */
  protected function getFromSQLValue($propertyName,$SQLValue) {
    // We check if property is stored as JSON
    $isJson = false;
    $beanPropertyAnnotation = AnnotationsUtils::getFromProperty(get_class($this), $propertyName, "beanProperty");
    if($beanPropertyAnnotation instanceof BeanProperty) {
      $isJson = $beanPropertyAnnotation->isJson;
    }

    $value = $SQLValue;
    if($isJson) {
      $value = JSONUtils::getObjectFromJSON($value);
    }
    else if(preg_match("/^ARRAY:(.*)/i",$SQLValue,$matches)) {
      $value = explode(GenericBean::$SQL_ARRAY_SEPARATOR,$matches[1]);
    }
    else if(preg_match("/DateTime$/i",$propertyName) || preg_match("/Date$/i",$propertyName)) {
      $value = IwDateTime::getFromSQLDateTime($SQLValue);
    }
    else if(preg_match("/^is[A-Z]/",$propertyName) &&
        ($SQLValue == DBUtils::booleanToDB(true) || $SQLValue == DBUtils::booleanToDB(false))) {
      $value = DBUtils::getBoolean($SQLValue);
    }
    return $value;
  }

  /*
   *	function: getFromDB
  *	This function try to get the bean from the DB, it updates the current bean.
  *
  *	parameters:
  *		- $SQLid - the id of the bean.
  *	return:
  *		- "ok" if found and updated else an Error object.
  */
  public function getFromDB($SQLid,$params = NULL) {
    $logger = Logger::getInstance();

    $className = static::getClassName();

    // 1. We check that SQL id exists
    if(!isset($SQLid) || $SQLid == "") {
      return new Error(9102,1,array($className));
    }

    // We build the query parameters
    $parameters = "`".static::getSQLidColumnName()."`=".$SQLid;
    if(isset($params) && $params != "") {
      $parameters .= " AND ".$params;
    }

    $result = $this->getBean($parameters);
    return $result;
  }

  /*
   *	function: getBean
  *	This function try to get the bean from the DB.
  *
  *	parameters:
  *		- $params - string of parameters to put after WHERE instruction..
  *	return:
  *		- "ok" if found and updated else an Error object.
  */
  public function getBean($params) {

    // We get the real class name with namespace
    $className = get_class($this);

    // 1. We check that params exists
    if(!isset($params) || $params == "") {
      return new Error(9102,2,array($className));
    }

    $bean = new $className();
    $beans = $bean->getBeans($params);
    if($beans instanceof Error) {
      return $beans;
    }

    if(count($beans) < 1) {
	    $error = new Error(9102,3,array($className));
      return $error;
    }

    // We update current object from the bean returned
    $this->updateFromBean($beans[0]);

    return "ok";
  }

  /*
   *	function: getBeans
  *	This function get all Beans from DB.
  *
  *	parameters:
  *		- params : string of parameters to put after WHERE instruction.
  *	return:
  *		- An array of GenericBean if found else an Error object.
  */
  public function getBeans($params = NULL) {
    // 1.We get the beans in raw format
    $resultGetBeans = $this->getRawBeans($params);
    if($resultGetBeans instanceof Error) {
      return $resultGetBeans;
    }
    
    $className = get_class($this);

    // 2. We create the beans from DB datas and put it in the list
    $beans = array();
    while($datas = DBUtils::fetchAssoc($resultGetBeans)) {
      $bean = new $className();
      $bean->getFromSQLResult($datas);
      if(get_class($bean) == $className) {
        array_push($beans,$bean);
      }
    }
    return $beans;
  }
  
  /*
   *	function: getBeans
  *	This function get all Beans from DB.
  *
  *	parameters:
  *		- params : string of parameters to put after WHERE instruction.
  *	return:
  *		- An array of GenericBean if found else an Error object.
  */
  public function getRawBeans($params = NULL) {
    $logger = Logger::getInstance();

    $tableName = static::getTableName();
    if($tableName instanceof Error) {
      return $tableName;
    }

    // 1. We get the Profiles from DB.
    $queryGetBeans = "SELECT * FROM `".$tableName."` ";
    if(isset($params) && $params != "") {
      $queryGetBeans .= "WHERE " . $params;
    }
    $resultGetBeans = DBUtils::query($queryGetBeans);
    if(!$resultGetBeans) {
      $className = get_class($this);
      $logger->addLog("queryGetBeans: ".$queryGetBeans);
      return new Error(9105,1,array($className));
    }
    
    return $resultGetBeans;
  }

  /*
   *	function: removeFromDB
  *	This function try to remove the Bean from the DB.
  *
  *	parameters:
  *		- $SQLid - the id of the Bean.
  *	return:
  *		- "ok" if deleted else an Error object.
  */
  public function removeFromDB() {
    $className = static::getClassName();
    
    // 1. We check that SQL id exists
    if(!isset($this->SQLid) || $this->SQLid == "") {
      return new Error(9104,3,array($className));
    }

    // 2. We remove the bean
    $params = "`".static::getSQLidColumnName()."`=".$this->SQLid;
    $result = $this->removeBeansFromDB($params);
    
    return $result;
  }

  /*
   *	function: removeBeansFromDB
  *	This function try to remove the Beans from the DB.
  *
  *	parameters:
  *		- $SQLid - the id of the Bean.
  *	return:
  *		- "ok" if deleted else an Error object.
  */
  public function removeBeansFromDB($params) {
    $logger = Logger::getInstance();

    $className = get_class($this);
    
    // 1. We check that SQL id exists
    if(!isset($params) || $params == "") {
      return new Error(9104,1,array($className));
    }

    $tableName = static::getTableName();
    if($tableName instanceof Error) {
      return $tableName;
    }

    // 2. We get the Profile from DB.
    $queryDelete = "DELETE FROM `".$tableName."` ";
    $queryDelete .= "WHERE " . $params;
    $resultDelete = DBUtils::query($queryDelete);
    
    if(!$resultDelete) {
      $logger->addLog("queryDelete: ".$queryDelete);
      return new Error(9104,2,array($className));
    }

    return "ok";
  }

  /*
   *	function: getFromSQLResult
  *	This function populate the object based on a SQL result associative array.
  *
  *	parameters:
  *		- $datas - a map generated with sql query.
  *	return:
  *		- none.
  */
  public function getFromSQLResult($datas) {
    $this->updateFromSQLResult($datas);
  }

  /*
   *	function: updateFromSQLResult
  *	This function update the object based on a SQL result associative array.
  *
  *	parameters:
  *		- $datas - a map generated with sql query.
  *	return:
  *		- none.
  */
  public function updateFromSQLResult($datas) {
    $logger = Logger::getInstance();
    global $request;

    $className = static::getClassName();
    $SQLid = @$datas["id".$className];

    if(!isset($SQLid) || $SQLid == "") {
      $error = new Error(9103,1,array($className));
      $logger->addErrorLog($error, true);
      return $error;
    }

    // We set the SQL id
    $this->setSQLid($SQLid);

    // We get the properties stored in DB
    $properties = $this->getPropertiesToStore();
    // We get the localized properties
    $localizedProperties = $this->getLocalizedProperties();
    $localizedPropertiesValues = array();

    $currentLanguageCode = null;
    foreach($properties as $property) {
      $setter = "set".ucfirst($property);
      
      $SQLPropertyName = $property;
      // In case of localized property, we store the value in current language.
      // We also store the localized property in other languages.
      if(in_array($SQLPropertyName, $localizedProperties)) {
        // We set the current language property
        $currentLanguageCode = $request->getLanguageCode();
        $SQLPropertyName .= "-" . $currentLanguageCode;

        // We store the property in all supported languages
        $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;
        if(count($supportedLanguages) > 0) {
          foreach($supportedLanguages as $languageCode) {
            if(!isset($localizedPropertiesValues[$languageCode])) {
              $localizedPropertiesValues[$languageCode] = array();
            }
            $localizedPropertiesValues[$languageCode][$property] = $this->getFromSQLValue($property,$datas[$property . "-" . $languageCode]);
          }
        }
      }
      if(isset($datas[$SQLPropertyName])) {
        $value = $this->getFromSQLValue($property,$datas[$SQLPropertyName]);
        $this->$setter($value);
      }
    }

    if(count($localizedProperties) > 0) {
      $this->setLocalizedPropertiesValues($localizedPropertiesValues);
      $this->setBeanLanguageCode($currentLanguageCode);
    }

  }

  /*
   *	function: updateFromBean
  *	This function update the object based on same class instance.
  *
  *	parameters:
  *		- $bean - an instance of similar bean.
  *	return:
  *		- none.
  */
  public function updateFromBean($bean) {
    $logger = Logger::getInstance();

    $className = get_class($this);

    // We check that the bean is of the same class
    if(!$bean instanceof $className) {
      return;
    }

    $SQLid = $bean->getSQLid();
    if(!isset($SQLid) || $SQLid == "") {
      $logger->addLog("No SQL id - ".$className);
      return new Error(9103,2,array($className));
    }

    // We set the SQL id
    $this->setSQLid($SQLid);    

    // We get the properties
    $properties = $this->getProperties();

    foreach($properties as $property) {
      $setter = "set".ucfirst($property);
      $getter = "get".ucfirst($property);
      $value = $bean->$getter();
      $this->$setter($value);
    }
  }

  /*
   *	function: getLocalizedBean
   *	This function get the corresponding bean with localized value in language code in parameter.
   *
   *	parameters:
   *		- languageCode : the language code.
   *	return:
   *		- A GenericBean with localized value or an Error object.
   */
  public function getLocalizedBean($languageCode) {
    // 1. We check that the language code is supported language.
    $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;
    if(!in_array($languageCode, $supportedLanguages)) {
      return new Error(9107,1,$languageCode);
    }

    // 2. We check that we have values for the requested language
    if(!isset($this->localizedPropertiesValues) || !isset($this->localizedPropertiesValues[$languageCode])) {
      return new Error(9108,1,$languageCode);
    }

    // 3. We clone the current bean.
    $bean = clone $this;
    
    // 4. We populate the localized properties.
    foreach($this->localizedPropertiesValues[$languageCode] as $property => $value) {
      $setter = "set".ucfirst($property);
      $bean->$setter($value);
    }

    // 5. We populate the bean language
    $bean->setBeanLanguageCode($languageCode);
    
    return $bean;
  }
  	
  public function getSQLid() {
    return $this->SQLid;
  }

  public function setSQLid($SQLid) {
    if(!isset($SQLid)) {
      $this->SQLid = NULL;
    }
    else {
      $this->SQLid = intval($SQLid);
    }
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
    
    // We add the SQLid as idClassName
    $className = static::getClassName();
    $bean["id".$className] = $this->SQLid;

    return $bean;
  }
}

/*
 *	get_called_class implementation while not in php 5.3
*/
if(!function_exists('get_called_class')) {
  class class_tools {
    static $i = 0;
    static $fl = null;

    static function get_called_class() {
      $bt = debug_backtrace();

      if(self::$fl == $bt[2]['file'].$bt[2]['line']) {
        self::$i++;
      } else {
        self::$i = 0;
        self::$fl = $bt[2]['file'].$bt[2]['line'];
      }

      $lines = file($bt[2]['file']);

      preg_match_all('
          /([a-zA-Z0-9\_]+)::'.$bt[2]['function'].'/',
          $lines[$bt[2]['line']-1],
          $matches
      );

      return $matches[1][self::$i];
    }
  }

  function get_called_class() {
    return class_tools::get_called_class();
  }
}

?>
