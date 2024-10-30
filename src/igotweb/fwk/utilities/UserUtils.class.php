<?php
/**
 *	Class: UserUtils
 *	This class handle the User utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\User;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;

class UserUtils extends GenericBeanUtils {
  
  private function __construct() {}
  
    /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'igotweb\fwk\model\bean\User';
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
  
    // 1. We check if we need to include profile or not.
    if(class_exists("\Profile")) {
      $tables[] = \Profile::getTableName();
    }
  
    // 3. We add the user table to the list
    $tables = array_merge($tables, parent::getListTablesToLoad($request));
  
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
   */
  public static function checkModuleAvailability(Request $request) {

    $result = static::$MODULE_AVAILABILITY_RESULT;

    // 1. We check if Profile table is available in DB if needed.
    if(class_exists("\Profile")) {
      $result = static::mergeTableAvailabilityResults($results, "\Profile");
      if($result instanceof Error) {
        return $result;
      }
    }
    
    // 2. We check the DB
    $result = static::mergeTableAvailabilityResults($results, "User");
    if($result instanceof Error) {
      return $result;
    }
    
    return $result;
  }
}

?>
