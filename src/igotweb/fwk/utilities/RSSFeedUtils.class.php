<?php
/**
 *	Class: RSSFeedUtils
 *	This class handle the RSS Feed utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;

class RSSFeedUtils {
  
  private function __construct() {}
  
  /*
   *  function: getRSSFeedURL
   *  This function returns the RSS Feed URL for current context.
   *  We remove the admin mode.
   *
   *  parameters:
   *    - request - the request object.
   *    - languageCode - the language code if any.
   *  return:
   *    - The SiteMap URL or error object.
   */
  public static function getRSSFeedURL(Request $request, $languageCode = null) {
    // We check that the language is supported.
    if($languageCode != null && !in_array($languageCode, LanguageManager::$SUPPORTED_LANGUAGES)) {
      return new Error(7804,1,$languageCode);
    }

    //  We default to current language code if not provided
    if($languageCode == null) {
      $languageCode = $request->getLanguageCode();
    }
    
    // We create the path
    $path = $languageCode . "/feed";

    // We retrieve the RSS Feed URL without admin mode in any cases
    $productionSiteContext = clone $request->getSiteContext();
    $productionSiteContext->setAdminMode(false);
  
    $request->switchSiteContext($productionSiteContext);
    // We retrieve the RSS Feed URL for the current Site.
    $rssFeedURL = UrlUtils::getSiteURL($path, $request);
    $request->switchPreviousSiteContext();

    return $rssFeedURL;
  }
}

?>
