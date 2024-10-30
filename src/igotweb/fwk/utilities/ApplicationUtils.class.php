<?php

/**
 *	Class: ApplicationUtils
 *	Version: 0.1
 *	This class handle application utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PlatformUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SiteUtils;

class ApplicationUtils {
    
	private function __construct() {}
  
  /*
   *	function: getCurrentApplicationRootPath
   *	This function get the current application absolute root path.
   *	It is the root path where all files of the application are located.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- The current application absolute root path.
   */
  public static function getCurrentApplicationRootPath() {
    // We first check if defined within the paths.php file.
    // This file is mandatory in application file structure.
    if(isset($GLOBALS['CURRENT_APPLICATION_PATH'])) {
      return $GLOBALS['CURRENT_APPLICATION_PATH'];
    }
    return null; 
  }

  /*
   *	function: getCurrentApplicationWebRootPath
   *	This function get the current application absolute web root path.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- The current application absolute web root path.
   */
  public static function getCurrentApplicationWebRootPath() {
	  // 1. The web root path is located at the application root folder.
    return static::getCurrentApplicationRootPath() . "web" . DIRECTORY_SEPARATOR;
  }

  /*
   *	function: getApplicationRootPath
   *	This function get the application absolute root path.
   *	It is the root path where all files of the application are located.
   *
   *	parameters:
   *		- applicationShortName : the application short name.
   *    - platform : the target platform.
   *	return:
   *		- The application absolute root path.
   */
  public static function getApplicationRootPath($applicationShortName, Platform $platform = null) {
    $platformRootPath = PlatformUtils::getCurrentPlatformRootPath();
    if($platform != null) {
      $platformRootPath = $platform->getRootPath();
    }
	  // 1. Application are located at the platform root path with applicationShortName as folder.
    return $platformRootPath . $applicationShortName . DIRECTORY_SEPARATOR;
  }

  /*
   *	function: getApplicationWebRootPath
   *	This function get the application absolute web root path.
   *
   *	parameters:
   *		- applicationShortName : the application short name.
   *	return:
   *		- The application absolute web root path.
   */
  public static function getApplicationWebRootPath($applicationShortName) {
	  // 1. The web root path is located at the application root folder.
    return static::getApplicationRootPath($applicationShortName) . "web" . DIRECTORY_SEPARATOR;
  }

  /*
	 *	function: checkApplication
	 *	This function check if the root path is targetting an application.
	 *
	 *	parameters:
	 *		- rootPath - the absolute root path.
	 *	return:
	 *		- "ok" if found or Error object.
	 */
	public static function checkApplication($rootPath) {
    $logger = Logger::getInstance();
    // 1. We check that the application has a paths.php file
    $path = $rootPath.DIRECTORY_SEPARATOR.Application::$PATHS_PATH;
    if(!file_exists($path)) {
      $logger->addLog("ApplicationUtils::checkApplication : rootPath - ".$path);
      return new Error(9780,1);
    }
    return "ok";
	}

  /*
	 *	function: getApplicationsFromPlatform
	 *	This function retrieves all applications from a platform.
	 *
	 *	parameters:
	 *		- platform - the platform.
	 *	return:
	 *		- list of Application object or Error object.
	 */
	public static function getApplicationsFromPlatform(Platform $platform) {
    
    $listApplications = [];

    // We get the list of directories at the root of the platform.
    $directories = FileUtils::getDirectoriesList($platform->getRootPath(), false);
    foreach($directories as $directory) {
      if(!in_array($directory,array("igotweb-content","igotweb-fwk-vendor"))) {
        $applicationRootPath = $platform->getRootPath() . $directory;
        $application = Application::getFromPath($applicationRootPath);
        if($application instanceof Application) {
          $listApplications[] = $application;
        }
      }
    }

    return $listApplications;
	}

  /*
	 *	function: getApplicationFromWebappAndSite
	 *	This function retrieves the application corresponding to webapp and site on the platform.
	 *
	 *	parameters:
	 *		- webappShortName - the webapp short name.
	 *		- siteShortName - the site short name.
	 *		- platform - the platform.
	 *	return:
	 *		- the Application object or Error object.
	 */
  public static function getApplicationFromWebappAndSite($webappShortName, $siteShortName, $platform) {

    $listApplications = static::getApplicationsFromPlatform($platform);
    foreach($listApplications as $application) {
      if($application->getWebappShortName() == $webappShortName &&
          $application->getSiteShortName() == $siteShortName) {
        return $application;
      }
    }
    return NULL;
  }

  /*
	 *	static function: populateWebapp
	*	This function populates the Webapp within the application object.
	*
	*	parameters:
	*		- $application - the Application object.
	*	return:
	*		- none.
	*/
  public static function populateWebapp(Application $application) {
    // We create the webapp object
    $webapp = new Webapp();
    // We get the shortName from the application object
    $webapp->setShortName($application->getWebappShortName());
    // We get the server side webapp root path of the application
    $webapp->setRootPath(WebappUtils::getWebappRootPathFromApplication($application));

    // We get the description
    $description = WebappUtils::getWebappDescriptionFromRootPath($webapp->getRootPath());
    if($description instanceof Error) {
      return $description;
    }

    // We set the name from description
    $webapp->setName($description["name"]);

    // We set the webapp
    $application->setWebapp($webapp);
  }

  /*
	 *	static function: populateSite
	*	This function populates the Site within the application object.
	*
	*	parameters:
	*		- $application - the Application object.
	*	return:
	*		- none.
	*/
  public static function populateSite(Application $application) {
    $siteShortName = $application->getSiteShortName();
    $noSite = Site::getNoSite();
    
    if($siteShortName == $noSite->getShortName()) {
      // We check the nosite
      $application->setSite($noSite);
    }
    else {
      // We create the Site object
      $site = new Site();
      // We get the shortName from the application object
      $site->setShortName($siteShortName);
      // We get the server side site root path of the application
      $site->setRootPath(SiteUtils::getSiteRootPathFromApplication($application));

      // 2. We get the description
      $description = SiteUtils::getSiteDescriptionFromRootPath($site->getRootPath());
      if($description instanceof Error) {
        return $description;
      }
    
      // We set the name from description
      $site->setName($description["name"]);

      $application->setSite($site);
    }
  }

  
}
?>
