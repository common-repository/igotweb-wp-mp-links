<?php

/**
 *	Class: WebappUtils
 *	Version: 0.1
 *	This class handle webapp utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PlatformUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ApplicationUtils;

class WebappUtils {
  
	private function __construct() {}

  /*
   *	function: getWebappRootPathFromApplication
   *	This function get the webapp absolute root path of application in parameter.
   *	This is the path to webapp backend folder
   *
   *	parameters:
   *    - application : the application object
   *	return:
   *		- The webapp absolute root path.
   */
  public static function getWebappRootPathFromApplication(Application $application) {
    return ApplicationUtils::getApplicationRootPath($application->getShortName()) . $application->getWebappShortName() . DIRECTORY_SEPARATOR;    
  }

  /*
   *	function: updatePathsFromApplication
   *	This function update the webapp paths based on the application.
   *
   *	parameters:
   *		- webapp : the webapp object.
   *    - application : the application object
   *	return:
   *		- none.
   */
  public static function updatePathsFromApplication(Webapp $webapp, Application $application) {
    $webapp->setRootPath(static::getWebappRootPathFromApplication($application));
    $webapp->setWebRootPath($application->getWebRootPath());
  }

	/*
	 *	function: checkWebapp
	 *	This function check if the root path is targetting a webapp.
	 *
	 *	parameters:
	 *		- webappRootPath - the webapp root path.
	 *	return:
	 *		- "ok" if found or Error object.
	 */
	public static function checkWebapp($webappRootPath) {
	  $logger = Logger::getInstance();
    // 1. We check that the webapp has a description file
    $path = $webappRootPath.DIRECTORY_SEPARATOR.Webapp::$DESCRIPTION_PATH;
    if(!file_exists($path)) {
      $logger->addLog("WebappUtils::checkWebapp : rootPath - ".$path);
      return new Error(9770,1);
    }
    return "ok";
	}
  
  /*
	 *	function: getWebappDescriptionFromRootPath
	 *	This function get the webapp description from the root path.
	 *
	 *	parameters:
	 *		- webappRootPath - the webapp root path
	 *	return:
	 *		- Map of description if found or Error object.
	 */
  public static function getWebappDescriptionFromRootPath($webappRootPath) {
    // 1. We check that the webapp exists
    $exists = static::checkWebapp($webappRootPath);
    if($exists instanceof Error) {
      return $exists;
    }
    
    // 2. We get the configuration
    $config = IniFilesUtils::getConfiguration($webappRootPath.DIRECTORY_SEPARATOR.Webapp::$DESCRIPTION_PATH);
    
    return $config;
  }
}
?>
