<?php
/**
 *	Class: GenericBeanUtils
 *	This class handle the GenericBean utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\generic;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\AnnotationsUtils;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;

class GenericBeanUtils {

  public static $MODULE_AVAILABILITY_RESULT = array(
    "isModuleAvailable" => true,
    "tables" => array(),
    "unavailablePaths" => array()
  );

  private function __construct() {}
  
  /*
   *  static function: getBeanClass
   *  This function returns the bean class with namespace.
   *  It must be overriden.
   */
  protected static function getBeanClass() {
    $error = Error::getGeneric("GenericBeanUtils: getBeanClass - the method must be overriden in (" . get_called_class() . ")");
	  return $error;
  }
  
  /*
   *  function: getContentToLoad
   *  This action is called to get the web content to load.
   *  It returns a map with admin directory (key) and active directory (value).
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - content : map with admin directory (key) and active directory (value).
   */
  public static function getContentToLoad(Request $request) {
    $content = array();
    
    // There is no content associated by default. This method is to be overriden.
    
    return $content;
  }
  
  /*
   *  function: getListTablesToLoad
   *  This action is called to get the list of tables to be loaded.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - tables : array with list of table names to load.
   */
  public static function getListTablesToLoad(Request $request) {
    $tables = array();
    
    // We get the bean class
    $beanClass = static::getBeanClass();
    if($beanClass instanceof Error) {
      return $beanClass;
    }
  
    // 1. By default we load the table linked to the generic bean
    $tables[] = $beanClass::getTableName();
  
    return array_unique($tables);
  }
  
  /*
   *  function: checkModuleAvailability
   *  This function check if the module is available for current context.
   *
   *  parameters:
   *    @param Request $request The request object.
   *  return:
   *    @return result object or error object.
   * 
   *  result object:
   *    isModuleAvailable : boolean true if available.
   *    unavailablePaths : the list of unavailable paths.
   *    tables : list of GenericBean::isAvailableInDB output.
   * 
   */
  public static function checkModuleAvailability(Request $request) {

    $result = static::$MODULE_AVAILABILITY_RESULT;
    
    // We get the bean class
    $beanClass = static::getBeanClass();
    if($beanClass instanceof Error) {
      return $beanClass;
    }

    // 1. We check the associated table in DB
    return static::mergeTableAvailabilityResults($result, $beanClass);
  }

  /*
   *  function: mergeTableAvailabilityResults
   *  This function merge a table availability within results.
   *
   *  parameters:
   *    @param array $result The module availability result.
   *    @param array $beanClass The bean on which to check table availability.
   *  return:
   *    @return array updated result object or Error object.
   * 
   */
  public static function mergeTableAvailabilityResults($result, $beanClass) {
    $logger = Logger::getInstance();

    // 1. We check the DB
    $tableAvailability = $beanClass::isAvailableInDB();
    if($tableAvailability instanceof Error) {
      return $tableAvailability;
    }

    $tableName = $beanClass::getTableName();
    if(!$tableAvailability["tableExistsInDB"]) {
      $logger->addLog("GenericBeanUtils::mergeTableAvailabilityResults - " . $tableName . " table is not available in DB.");
      $result["isModuleAvailable"] = false;
    }
    if(!$tableAvailability["tableHasCorrectStructure"]) {
      $logger->addLog("GenericBeanUtils::mergeTableAvailabilityResults - " . $tableName . " table is available in DB with wrong structure.");
      $result["isModuleAvailable"] = false;
    }

    $result["tables"][] = $tableAvailability;
    
    return $result;
  }

  /*
   *  function: mergeModuleAvailabilityResults
   *  This function merge the module availability results.
   *
   *  parameters:
   *    @param array $result1 The first module availability result.
   *    @param array $result2 The second module availability result.
   *  return:
   *    @return array merged result object.
   * 
   */
  public static function mergeModuleAvailabilityResults($result1, $result2) {

    $result = static::$MODULE_AVAILABILITY_RESULT;
    
    $result["isModuleAvailable"] = $result1["isModuleAvailable"] && $result2["isModuleAvailable"];
    $result["unavailablePaths"] = array_merge($result1["unavailablePaths"], $result2["unavailablePaths"]);
    $result["tables"] = array_merge($result1["tables"], $result2["tables"]);
    
    return $result;
  }

  /*
   *  function: createTableFromTableAvailabilityResult
   *  This function create or update table in DB based on table availability result.
   *  Table availability result is generated from GenericBean::isAvailableInDB().
   *
   *  parameters:
   *    @param array $tableAvailability The table availability result.
   *  return:
   *    @return true if created or Error object.
   * 
   */
  public static function createTableFromTableAvailabilityResult($tableAvailability) {
    if(!$tableAvailability["tableExistsInDB"]) {
      // 2.2 We create the table
      $created = (new $tableAvailability["className"]())->createTableInDB();
      if($created instanceof Error) {
        return $created;
      }
    }
    else if(!$tableAvailability["tableHasCorrectStructure"]) {
      // 2.3 We update the structure of the table
      $updated = (new $tableAvailability["className"]())->updateTableInDB();
      if($updated instanceof Error) {
        return $updated;
      }
    }
    return true;
  }
}

?>
