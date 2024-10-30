<?php

/**
 *	Class: BlogUtils
 *	Version: 0.1
 *	This class handle blog utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Post;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Tag;
use igotweb_wp_mp_links\igotweb\fwk\utilities\blog\PostUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\blog\TagUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;

class BlogUtils {
  
  private function __construct() {}

  /*
   *  function: hasPosts
   *  This action is called to check if there are posts available.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - boolean true if available or false.
   */
  public static function hasPosts(Request $request) {
    $hasPosts = false;
    $moduleAvailability = static::checkModuleAvailability($request);
    if($moduleAvailability instanceof Error) {
      return false;
    }
    elseif($moduleAvailability["isModuleAvailable"]) {
      $posts = (new Post())->getBeans();
      if(!$posts instanceof Error && count($posts) > 0) {
        $hasPosts = true;
      }
    }
    return $hasPosts;
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
  
    // 1. We get the list of tables for Folder service
    $tables = array_merge($tables, TagUtils::getListTablesToLoad($request));
  
    // 2. We get the list of tables for Photo service
    $tables = array_merge($tables, PostUtils::getListTablesToLoad($request));
  
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

    $result = GenericBeanUtils::$MODULE_AVAILABILITY_RESULT;

    // 1. We check that Tag module is available
    $tagModuleAvailability = TagUtils::checkModuleAvailability($request);
    if($tagModuleAvailability instanceof Error) {
      return $tagModuleAvailability;
    }
    $result = GenericBeanUtils::mergeModuleAvailabilityResults($result, $tagModuleAvailability);
    
    // 2. We check that Post module is available
    $postModuleAvailability = PostUtils::checkModuleAvailability($request);
    if($postModuleAvailability instanceof Error) {
      return $postModuleAvailability;
    }
    $result = GenericBeanUtils::mergeModuleAvailabilityResults($result, $postModuleAvailability);
    
    return $result;
  }
}
?>
