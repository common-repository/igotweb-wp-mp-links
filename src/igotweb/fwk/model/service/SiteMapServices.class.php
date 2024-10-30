<?php
/**
 *	Class: SiteMapServices
 *	This class handle the services linked to SiteMap. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SiteMapUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;

class SiteMapServices {
  
  private function __construct() {}

  /*
   *	action: ping
   *	This action is called in order to ping search engine with current SiteMap.
   *
   *	parameters:
   *		- none.
   */
  public static function ping(Request $request) {
    
    // We get the siteMap URL
    $siteMapURL = HttpRequestUtils::getParam("siteMapURL");
    $request->dmOutAddElement("siteMapURL",$siteMapURL);
    
    // 1. We get the webapp from root path
    $results = SiteMapUtils::pingSiteMapURL($siteMapURL);
    if($results instanceof Error) {
      $request->addError($results);
    }

    if(!$request->hasError()) {
      $request->dmOutAddElement("ping",true);
      $request->dmOutAddElement("results", $results);
    }
    else {
      $request->dmOutAddElement("ping",false);
    }
  }
}
?>