<?php

/**
 *	Class: SiteContextUtils
 *	Version: 0.1
 *	This class handle site context utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ApplicationUtils;

class SiteContextUtils {

	private function __construct() {}

	
	/**
	 *	static function: getFromApplicationShortName
	*	This function get the Site context from application shortName on current platform.
	*
	*	parameters:
  *		- @param $applicationShortName = the application short name.
  *		- @param $platform = the associated Platform object.
	*	return:
	*		- SiteContext if found or Error object.
	*/
	public static function getFromApplicationShortName($applicationShortName, Platform $platform) {
    // We check for the application
    $application = Application::getFromShortNameAndPlatform($applicationShortName, $platform);
    if($application instanceof Error) {
      return $application;
    }
    
    $context = new SiteContext();
    $context->setApplication($application);
    $context->setPlatform($platform);

    return $context;
	}
}
?>
