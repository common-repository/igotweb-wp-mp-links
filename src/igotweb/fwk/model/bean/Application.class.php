<?php

/**
 *	Class: Application
 *	Version: 0.1
 *	This class handle the application.
 *  An application is a combination of Webapp / Site and framework as independant structure.
 *
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ApplicationUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PlatformUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\ConfigurationManager;

class Application extends GenericObject {

  public static $PATHS_PATH = "./paths.php";
  public static $FWK_SHORT_NAME = "igotweb-fwk";
  
  protected $shortName; // The short name of the application is the name used as folder name. it also identifies the application in DB.
  protected $name; // The name of the Application is an understandable label.
  protected $webappShortName; // The webapp short name of the application.
  protected $siteShortName; // The site short name of the application.

  protected $rootPath; // The server side application absolute root path.
  protected $webRootPath; // The server side application absolute web root path.
  protected $staticPath; // client side path to application specific statics from the html base href.

  protected $fwkRootPath; // The server side framework absolute root path.

  protected $webapp; // The Webapp object.
  protected $site; // The Site object.

  
  /*
	 *	Constructor
	 *	It creates an Application with no SQLid.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Webapp object.
	 */	
	public function __construct() {
		$this->shortName = NULL;
		$this->name = NULL;		
    $this->webappShortName = NULL;
    $this->siteShortName = NULL;

    $this->webapp = NULL;
    $this->site = NULL;
	}
	
	/*
	 * function: __call
	 * Generic getter for properties.
	 */
	public function __call($method, $params) {
	  return $this->handleGetterSetter($method, $params);
	}

  /*
	 *	static function: getCurrent
	*	This function get the current application.
	*
	*	parameters:
	*		- none.
	*	return:
	*		- Application : the current application or Error object.
	*/
	public static function getCurrent() {
		if(isset($GLOBALS['CURRENT_APPLICATION_LABEL'])) {
			// 1. We get the shortName from paths.php file.
      // 2. We get the application
      $application = new Application();
      $application->setShortName($GLOBALS['CURRENT_APPLICATION_LABEL']);
      $application->setWebappShortName($GLOBALS['CURRENT_WEBAPP_SHORT_NAME']);
      $application->setSiteShortName($GLOBALS['CURRENT_SITE_SHORT_NAME']);

      // We populate the paths
      $application->setRootPath(ApplicationUtils::getApplicationRootPath($application->getShortName()));
      $application->setWebRootPath(ApplicationUtils::getApplicationWebRootPath($application->getShortName()));
      $application->setStaticPath("./");

      $application->setFwkRootPath($application->getRootPath() . static::$FWK_SHORT_NAME . DIRECTORY_SEPARATOR);

      // We populate the webapp and site
      ApplicationUtils::populateWebapp($application);
      ApplicationUtils::populateSite($application);

      // We populate the application name based on the site or webapp name.
      $site = $application->getSite();
      $applicationName = $site->getName();
      if(Site::isNoSite($site)) {
        $applicationName = $application->getWebapp()->getName();
      }
      $application->setName($applicationName);

	    return $application;
		}
		return null;
  }
  
  /*
	 *	static function: getFromShortNameAndPlatform
   *	This function get the application from short name and target platform.
   *  If no platform, then the current platform is used.
	 *
	 *	parameters:
   *		- applicationShortName - the application short name.
   *		- platform - the target platform.
	 *	return:
	 *		- Application : the current application or Error object.
	 */
  public static function getFromShortNameAndPlatform($applicationShortName, Platform $platform = null) {
    if($platform == null) {
      $platform = PlatformUtils::getCurrent();
    }
    $applicationRootPath = ApplicationUtils::getApplicationRootPath($applicationShortName, $platform);
    $application = static::getFromPath($applicationRootPath);
    return $application;
  }

  /*
	 *	static function: getFromPath
	 *	This function get the application from specific root path.
	 *
	 *	parameters:
	 *		- applicationRootPath - the application root path.
	 *	return:
	 *		- Application : the current application or Error object.
	 */
  public static function getFromPath($applicationRootPath) {
    // We check that we have a valid root path
    $result = ApplicationUtils::checkApplication($applicationRootPath);
    if($result instanceof Error) {
      return $result;
    }

    // We save the current application
    $currentApplication = static::getCurrent();

    // We include the application paths file.
    include($applicationRootPath . DIRECTORY_SEPARATOR . static::$PATHS_PATH);
    // We build the application object based on new paths file included.
    $application = static::getCurrent();

    // We put back the current application paths.
    include($currentApplication->getRootPath() . DIRECTORY_SEPARATOR . static::$PATHS_PATH);

    return $application;
  }
}
?>
