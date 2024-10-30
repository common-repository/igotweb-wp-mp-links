<?php

/**
 *	Class: SiteUtils
 *	Version: 0.1
 *	This class handle site utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class SiteUtils {
  
  private function __construct() {}
    
  /*
   *	function: getSiteRootPathFromApplication
   *	This function get the site absolute root path of application in parameter.
   *	This is the path to site backend folder
   *
   *	parameters:
   *    - application : the application object
   *	return:
   *		- The site absolute root path.
   */
  public static function getSiteRootPathFromApplication(Application $application) {
    // The site root path is within the webapp
    $webappRootPath = WebappUtils::getWebappRootPathFromApplication($application);
    return $webappRootPath . "sites" . DIRECTORY_SEPARATOR . $application->getSiteShortName() . DIRECTORY_SEPARATOR;
  }
  
	/*
	 *	function: checkSite
	 *	This function check if the root path is targetting a site.
	 *
	 *	parameters:
	 *		- rootPath - the absolute root path.
	 *	return:
	 *		- "ok" if found or Error object.
	 */
	public static function checkSite($rootPath) {
	  $logger = Logger::getInstance();
      // 1. We check that the site has a description file
	  $path = $rootPath.Site::$DESCRIPTION_PATH;
      if(!file_exists($path)) {
        $logger->addLog("SiteUtils::checkSite : rootPath - ".$path);
				debug_print_backtrace();
        return new Error(9790,1);
      }
      return "ok";
	}
  
  /*
	 *	function: getSiteDescriptionFromRootPath
	 *	This function get the site description from the root path.
	 *
	 *	parameters:
	 *		- rootPath - the absolute root path.
	 *	return:
	 *		- Map of description if found or Error object.
	 */
  public static function getSiteDescriptionFromRootPath($rootPath) {
    // 1. We check that the webapp exists
    $exists = static::checkSite($rootPath);
    if($exists instanceof Error) {
      return $exists;
    }
    
    // 2. We get the configuration
    $config = IniFilesUtils::getConfiguration($rootPath.Site::$DESCRIPTION_PATH);
    
    return $config;
  }
  
  /*
	 *	function: getSiteShortNameFromPath
	 *	This function get the site short name from the root path.
	 *
	 *	parameters:
	 *		- rootPath - the absolute root path.
	 *	return:
	 *		- the short name if found or Error object.
	 */
  public static function getSiteShortNameFromPath($rootPath) {
    // 1. We check that the site exists
    $exists = static::checkSite($rootPath);
    if($exists instanceof Error) {
      return $exists;
    }
    
    // 2. We get the shortName
    $shortName = substr($rootPath,strrpos($rootPath, DIRECTORY_SEPARATOR) + 1);
    
    return $shortName;
  }
}
?>
