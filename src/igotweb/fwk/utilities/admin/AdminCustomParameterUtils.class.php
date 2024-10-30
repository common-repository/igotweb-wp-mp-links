<?php
/**
 *	Class: AdminCustomParameterUtils
 *	This class provide some utilities for Custom parameters administration. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\admin;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;


class AdminCustomParameterUtils extends \igotweb\fwk\utilities\generic\admin\AdminGenericBeanUtils {
  
  private function __construct() {}

  /**
   *  function: getBeanParameters
   *  This function returns the bean parameters for generic bean module.
   *  This bean parameters is used as input of the genericBean module.
   * 
   *  params:
   *    - @param $request - the request object
   *    - @param $beanClass - The bean class with namespace
   *    - @param $moduleData - the module data in which the generic bean is used (cf. AdminModuleUtils::getModuleData)
   * 
   *  return:
   *    - beanParameters - the map of parameters
   *        - beanParameters.addRowEnabled = set to true when user can add row.
   *        - beanParameters.accessRights = list of access rights for the generic bean for current user.
   */
  public static function getBeanParameters(Request $request, $beanClass, $moduleData) {
    // We get the default values
    $beanParameters = parent::getBeanParameters($request, $beanClass, $moduleData);
    
    // We set specific values
    $addRowEnabled = false;
    if(in_array("full", $moduleData["userModuleRoles"])) {
      $addRowEnabled = true;
    }
    $beanParameters["addRowEnabled"] = $addRowEnabled;

    return $beanParameters;
  }
  
  /*
   *  function: getColumnsConfigurationForTableAdminDisplay
   *  This function returns the columns configuration for table admin display.
   */
  public static function getColumnsConfigurationForTableAdminDisplay(Request $request, $beanClass, $beanParameters = array()) {

    // We get the access rights
    $accessRights = $beanParameters["accessRights"];
    

    $excludedColumns = array(
      "id" . $beanClass::getClassName(),
      "shortName",
      "remove"
    );
    $readOnlyColumns = array("name", "description");
    if(in_array("full", $accessRights)) {
      $excludedColumns = array(
          "id" . $beanClass::getClassName()
      );
      $readOnlyColumns = array();
    }

    $columnsConfiguration = array(
      "excludedColumns" => $excludedColumns,
      "readOnlyColumns" => $readOnlyColumns
    );

    return $columnsConfiguration; 
  }
}
?>