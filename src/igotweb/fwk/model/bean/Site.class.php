<?php

/**
 *	Class: Site
 *	Version: 0.1
 *	This class handle the site for each webapp.
 *
 *	requires:
 *		- suffix.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PlatformUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SiteUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class Site extends GenericObject {

  public static $DESCRIPTION_PATH = "./config/site.ini";
  private static $NOSITE_SHORTNAME = "nosite";

  protected $shortName; // The short name of the site is the name used as folder name. it also identifies the site in DB.
  protected $name; // The name of the site is an understandable label.

  protected $rootPath; // server side path to the root of the site specific code.

  function __construct() {
    $this->shortName = NULL;
    $this->name = NULL;
    
    $this->rootPath = NULL;
  }

  /*
   * function: __call
  * Generic getter for properties.
  */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }
  
  /*
   *	static function: getNoSite
  *	This function retrieves the nosite site which is equivalent to the webapp.
  *
  *	parameters:
  *		- none.
  *
  *	return:
  *		- Site : the NoSite.
  */
  public static function getNoSite() {
    $nosite = new Site();
    $nosite->setName(static::$NOSITE_SHORTNAME);
    $nosite->setShortName(static::$NOSITE_SHORTNAME);
    return $nosite;
  } 

  /*
   *	static function: isNoSite
  *	This function check if site in parameter is the nosite site which is equivalent to the webapp.
  *
  *	parameters:
  *		- $site - the site object.
  *
  *	return:
  *		- boolean : true if no site.
  */
  public static function isNoSite(Site $site) {
    return $site->getName() === static::$NOSITE_SHORTNAME && $site->getShortName() === static::$NOSITE_SHORTNAME;
  } 
}
?>
