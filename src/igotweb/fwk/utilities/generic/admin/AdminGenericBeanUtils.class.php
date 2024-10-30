<?php
/**
 *	Class: AdminGenericBeanUtils
 *	This class provide some utilities for generic bean administration. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\generic\admin;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\AnnotationsUtils;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;


class AdminGenericBeanUtils {
  
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
   *        - beanParameters.moduleShortName = the module short name which is used to access the generic bean.
   */
  public static function getBeanParameters(Request $request, $beanClass, $moduleData) {
    // We set default parameters for generic bean
    $beanParameters = array(
      "addRowEnabled" => false,
      "accessRights" => $moduleData["userModuleRoles"],
      "moduleShortName" => $moduleData["module"]->getShortName()
    );
    return $beanParameters;
  }
  
  /**
   *  function: getColumnsConfigurationForTableAdminDisplay
   *  This function returns the columns configuration for table admin display.
   * 
   *  params:
   *    - @param $request - the request object
   *    - @param $beanClass - The bean class with namespace
   *    - @param $beanParameters - the bean parameters
   */
  public static function getColumnsConfigurationForTableAdminDisplay(Request $request, $beanClass, $beanParameters = array()) {

    $columnsConfiguration = array(
      "excludedColumns" => array(),
      "readOnlyColumns" => array()
    );

    return $columnsConfiguration;
  }

  /**
   *  function: getColumnsConfigurationForAddAdminDisplay
   *  This function returns the columns configuration for add admin display.
   * 
   *  params:
   *    - @param $request - the request object
   *    - @param $beanClass - The bean class with namespace
   *    - @param $beanParameters - a map of parameters
   */
  public static function getColumnsConfigurationForAddAdminDisplay(Request $request, $beanClass, $beanParameters = array()) {

    $columnsConfiguration = array(
      "excludedColumns" => array("id".$beanClass::getClassName()),
      "readOnlyColumns" => array()
    );

    return $columnsConfiguration;
  }

  /*
   *  function: generateRowsFromBeanClass
   *  This function generate a row data to be used in admin.genericBean.table template.
   *
   *  parameters:
   *    - $request - the Request instance.
   *    - beanClass - the bean class name with namespace.
   *    - columnsConfiguration - the columns configuration (cf. generateRowFromBean).
   *  return:
   *    - array of rows (cf. generateRowFromBean) or Error object.
   */
  public static function generateRowsFromBeanClass(Request $request, $beanClass, $columnsConfiguration) {
    
    $rows = array();
    // We load the utils method for the bean
    $utils = $beanClass::loadAdminBeanUtilsClass($request);
    if($utils instanceof Error) {
      return $utils;
    }
      
    $beans = (new $beanClass())->getBeans();
    if($beans instanceof Error) {
      return $beans;
    }
    
    foreach($beans as $bean) {
      $row = $utils::generateRowFromBean($request, $bean, $columnsConfiguration);
      $rows[] = $row;
    }
    return $rows;
  }
  
  /*
   *  function: generateRowFromBean
   *  This function generate a row data to be used in genericBean.table template.
   *
   *  parameters:
   *    - $request - the Request instance.
   *    - instance - the bean instance.
   *    - columnsConfiguration - the columns configuration.
   *        - excludedColumns - the list of excluded columns.
   *        - readOnlyColumns - the list of read only columns (Note, the SQLid is always forced to read only).
   *  return:
   *    - row : associative array
   *        row.name : the name of the property
   *        row.value : the value of the property
   *        row.editable : true if the field is editable.
   */
  public static function generateRowFromBean(Request $request, $instance, $columnsConfiguration) {

    // We get the bean class
    $beanClass = get_class($instance);

    // We read the columns configuration
    $excludedColumns = array();
    $readOnlyColumns = array();
    if(isset($columnsConfiguration)) {
      if(isset($columnsConfiguration["excludedColumns"])) {
        $excludedColumns = $columnsConfiguration["excludedColumns"];
      }
      if(isset($columnsConfiguration["readOnlyColumns"])) {
        $readOnlyColumns = $columnsConfiguration["readOnlyColumns"];
      }
    }
    
    // We update the readOnlyColumns to add the SQL id
    if(!in_array("id".$beanClass::getClassName(), $readOnlyColumns)) {
      $readOnlyColumns[] = "id".$beanClass::getClassName();
    }
    
    // We get the properties to store
    $propertiesToStore = (new $beanClass())->getPropertiesToStore();
    array_unshift($propertiesToStore,"id".$beanClass::getClassName());

    // We check if we have localized properties.
    $localizedProperties = (new $beanClass())->getLocalizedProperties();
    $localizedInstances = array();
    if(count($localizedProperties) > 0) {
      // We retrieve the bean in every supported languages
      $idBean = $instance->getSQLid();
      $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;
      $currentLanguageCode = $request->getLanguageCode();
      // We check if we need to store the bean for each language
      foreach($supportedLanguages as $languageCode) {
        $request->switchLanguage($languageCode);
        $localizedInstance = new $beanClass();
        $result = $localizedInstance->getFromDB($idBean);
        if($result instanceof Error) {
          $request->addError($result);
        }
        else {
          $localizedInstances[$languageCode] = $localizedInstance;
        }
      }
      // We switch the language back to initial one.
      $request->switchLanguage($currentLanguageCode);
    }
  
    $propertiesMap = array();
    foreach($propertiesToStore as $property) {
    
      if(in_array($property, $excludedColumns)) {
        continue;
      }
      
      $editable = true;
      if(in_array($property, $readOnlyColumns)) {
        $editable = false;
      }

      $isJson = false;
      if($property != "id".$beanClass::getClassName()) {
        $beanPropertyAnnotation = AnnotationsUtils::getFromProperty($beanClass, $property, "beanProperty");
        if($beanPropertyAnnotation instanceof BeanProperty) {
          $isJson = $beanPropertyAnnotation->isJson;
        }
      }

      if(in_array($property, $localizedProperties)) {
        $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;
        // We retrieve the property from each localized instance
        foreach($supportedLanguages as $languageCode) {
          $localizedProperty = $property."-".$languageCode;
          // In case the property is a localized one
          $getter = "get".ucfirst($property);
          $value = $localizedInstances[$languageCode]->$getter();
          if($isJson) {
            $value = JSONUtils::buildJSONObject($value);
          }
          else if(is_array($value)) {
            $str = "ARRAY:";
            if(count($value) > 0) {
              $str .= implode(",",$value);
            }
            $value = $str;
          }
        
          $propertiesMap[$localizedProperty] = array(
              "name" => $localizedProperty,
              "value" => $value,
              "editable" => $editable
            );
        }
      }
      else {
        $getter = "get".ucfirst($property);
        if($property == "id".$beanClass::getClassName()) {
          $getter = "getSQLid";
        }
        $value = $instance->$getter();
        if($isJson) {
          $value = JSONUtils::buildJSONObject($value);
        }
        else if(is_array($value)) {
          $str = "ARRAY:";
          if(count($value) > 0) {
            $str .= implode(",",$value);
          }
          $value = $str;
        }
      
        $propertiesMap[$property] = array(
            "name" => $property,
            "value" => $value,
            "editable" => $editable
          );
      }
    }

    // We order properties based on the list columns from DB.
    $properties = array();
    $table = DBUtils::getTable($beanClass::getTableName());
    $columns = $table->getListColumns();
    foreach($columns as $column) {
      $shortName = $column->getName();
      if(in_array($shortName, $excludedColumns)) {
        continue;
      }
      $propertiesMap[$shortName]["type"] = $column->getType();
      $properties[] = $propertiesMap[$shortName];
    }
    
    $row = array(
          "id" => $instance->getSQLid(),
          "properties" => $properties
        );
    
    return $row;
  }
}
?>