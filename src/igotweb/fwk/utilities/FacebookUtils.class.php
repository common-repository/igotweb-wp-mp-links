<?php

/**
 *	Class: FacebookUtils
 *	Version: 0.1
 *	This class handle Facebook needs.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;

class FacebookUtils {

	private function __construct() {}

    public static function queryGraph($query, $accessToken) {
	  global $Facebook;
	  
	  try {
	    // Returns a `Facebook\FacebookResponse` object
	    $response = $Facebook->get($query, $accessToken);
	  } catch(Facebook\Exceptions\FacebookResponseException $e) {
	    echo 'Graph returned an error: ' . $e->getMessage();
	    exit;
	  } catch(Facebook\Exceptions\FacebookSDKException $e) {
	    echo 'Facebook SDK returned an error: ' . $e->getMessage();
	    exit;
	  }
	  
	  return $response;
    }
}
?>
