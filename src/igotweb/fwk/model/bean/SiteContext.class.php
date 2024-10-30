<?php

/**
 *	Class: SiteContext
 *	Version: 0.1
 *	This class store a context (application, platform, adminMode).
 *
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;

class SiteContext extends GenericObject {

  protected $application; // The application object.
  protected $platform; // The platform (local, validation or production).
  protected $adminMode; // boolean (true if we target the admin mode for DB and content)
  protected $shadowMode; // boolean (true if we target the shadow url for the platform)

  function __construct() {
    $this->application = NULL;
    $this->platform = NULL;
    $this->adminMode = false; // By default we target the active mode
    $this->shadowMode = false; // By default we target the default URL
  }
  
  public function getShortName() {
    $shortName = $this->platform->getName() . "/" . $this->application->getWebappShortName();
    if($this->application->getSiteShortName() != (Site::getNoSite())->getShortName()) {
      $shortName .= "/" . $this->application->getSiteShortName();
    }
    if($this->adminMode) {
      $shortName .= "/admin";
    }
    else if($this->shadowMode) {
      $shortName .= "/shadow";
    }
	
    return $shortName;
  }

  /*
   * function: __call
  * Generic getter for properties.
  */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }

  /*
   *	function: getContentRootPath
  *	This function get the relative path for customized content.
  * The root path is without the active or admin subfolder.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- the content root path.
  */
  public function getContentRootPath() {
    $platform = $this->getPlatform();

    // The content path is defined from the root of the platform / igotweb-content / application short name.
    $contentRootPath = $platform->getRootPath() . "igotweb-content" . DIRECTORY_SEPARATOR . $this->getApplication()->getShortName() . DIRECTORY_SEPARATOR;
    	
    return $contentRootPath;
  }
  
  /*
   *	function: getContentPath
  *	This function get the relative path for customized content.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- the content path.
  */
  public function getContentPath() {
    $contentType = "active";
    if($this->getAdminMode()) {
      $contentType = "admin";
    }
    
    $contentRootPath = $this->getContentRootPath();
    $contentPath = $contentRootPath . $contentType . DIRECTORY_SEPARATOR;
	
    return $contentPath;
  }
  
  /*
   *	function: getStaticContentPath
  *	This function get the client side path to site specific statics content from the html base href.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- the content path.
  */
  public function getStaticContentPath() {
    $contentType = "active";
    if($this->getAdminMode()) {
      $contentType = "admin";
    }

    $staticPath = $this->getApplication()->getStaticPath();
    $staticContentPath = $staticPath . "content" . DIRECTORY_SEPARATOR . $contentType . DIRECTORY_SEPARATOR;
	
    return $staticContentPath;
  }
}
?>
