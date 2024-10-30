<?php
/**
 *	Class: SiteMapUtils
 *	This class handle the SiteMap utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;

class SiteMapUtils {

  public static $SUPPORTED_SITEMAP_TYPES = array("index","pages","posts");
  
  private function __construct() {}
  
  /*
   *  function: getSiteMapURL
   *  This function returns the SiteMap URL for current context.
   *  We remove the admin mode.
   *
   *  parameters:
   *    - request - the request object.
   *    - languageCode - the language code if any.
   *    - sitemapType - the sitemap type if any.
   *  return:
   *    - The SiteMap URL or error object.
   */
  public static function getSiteMapURL(Request $request, $languageCode = null, $sitemapType = null) {
    // We check that the type is supported.
    if($sitemapType != null && !in_array($sitemapType, static::$SUPPORTED_SITEMAP_TYPES)) {
      return new Error(7703,1,$sitemapType);
    }

    // We check that the language is supported.
    if($languageCode != null && !in_array($languageCode, LanguageManager::$SUPPORTED_LANGUAGES)) {
      return new Error(7704,1,$languageCode);
    }
    
    $path = "sitemap.xml";
    if($sitemapType != null && $sitemapType !== "" && $sitemapType !== "index") {
      $path = "sitemap-".$sitemapType.".xml";
    }
    if($languageCode != null && $languageCode !== "") {
      $path = $languageCode . "/" . $path;
    }

    // We retrieve the SiteMap URL without admin mode in any cases
    $productionSiteContext = clone $request->getSiteContext();
    $productionSiteContext->setAdminMode(false);
  
    $request->switchSiteContext($productionSiteContext);
    // We retrieve the SiteMap URL for the current Site.
    $siteMapURL = UrlUtils::getSiteURL($path, $request);
    $request->switchPreviousSiteContext();

    return $siteMapURL;
  }

  /*
   *  function: pingSiteMapURL
   *  This function ping search engines with SiteMap URL in parameter.
   *
   *  parameters:
   *    - siteMapURL - the SiteMap URL to ping.
   *  return:
   *    - array of results or Error object.
   */
  public static function pingSiteMapURL($siteMapURL) {
    if (!extension_loaded('curl')) {
      // The curl extension is not loaded.
      return new Error(7701,1);
    }
  
    //echo "Starting site submission at ".date('Y-m-d H:i:s')."\n";
    if(filter_var($siteMapURL, FILTER_VALIDATE_URL) === false) {
      return new Error(7702,1,$siteMapURL);
    }

    $results = array();

    //Google
    $url = "http://www.google.com/webmasters/sitemaps/ping?sitemap=".urlencode($siteMapURL);
    $returnCode = static::myCurl($url);
    array_push($results, "Google Sitemaps has been pinged (return code: {$returnCode}).");
        
    //Bing / MSN
    $url = "http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($siteMapURL);
    $returnCode = static::myCurl($url);
    array_push($results, "Bing / MSN Sitemaps has been pinged (return code: {$returnCode}).");
        
    //ASK
    $url = "http://submissions.ask.com/ping?sitemap=".urlencode($siteMapURL);
    $returnCode = static::myCurl($url);
    array_push($results, "ASK.com Sitemaps has been pinged (return code: $returnCode).");
    
    return $results;
  }

  // cUrl handler to ping the Sitemap submission URLs for Search Engines
  private static function myCurl($url) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $httpCode;
  }
}

?>
