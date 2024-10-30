<?php
/**
 *	Class: AdminPhotoUtils
 *	This class provide some utilities for Photos administration. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\admin;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;


class AdminPhotoUtils extends \igotweb\fwk\utilities\generic\admin\AdminGenericBeanUtils {
  
  private function __construct() {}
  
  /*
   *  function: getColumnsConfigurationForTableAdminDisplay
   *  This function returns the columns configuration for table admin display.
   */
  public static function getColumnsConfigurationForTableAdminDisplay(Request $request, $beanClass, $params = array()) {

    // We check the roles to know
    $userRoles = (isset($params["userRoles"]) ? $params["userRoles"] : array());
    $excludedColumns = array(
        "id" . $beanClass::getClassName(),
        "remove"
      );
    if(in_array("full", $userRoles)) {
      $excludedColumns = array(
          "id" . $beanClass::getClassName()
      );
    }

    // We set all columns as read only
    $table = DBUtils::getTable($beanClass::getTableName());
    $readOnlyColumns = $table->getListColumnNames();

    $columnsConfiguration = array(
      "excludedColumns" => $excludedColumns,
      "readOnlyColumns" => $readOnlyColumns
    );

    return $columnsConfiguration;
  }
}
?>