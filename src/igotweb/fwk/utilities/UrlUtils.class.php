<?php

/**
 *	Class: UrlUtils
 *	Version: 0.1
 *	This class handle URL needs.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;

class UrlUtils {

	private function __construct() {}

    public static function cleanPath($path) {
	  $dir_sep = preg_quote(DIRECTORY_SEPARATOR);
      $sep = preg_quote("/");
	  
	  // We replace the DIRECTORY SEPARATOR with "/"
	  $path = preg_replace('#('.$dir_sep.')#', $sep, $path);
	  
      /* replace '//' or '/./' or '/foo/../' with '/' */
      $re = array('#('.$sep.'\.?'.$sep.')#', '#'.$sep.'(?!\.\.)[^'.$sep.']+'.$sep.'\.\.'.$sep.'#');
      for($n=1; $n>0; $path=preg_replace($re, $sep, $path, -1, $n)) {}
      return $path;
    }
	
	/*
	 *	function: getSiteRoot
	 *	This function retrieve the site root based on current context.
	 *
	 *	parameters:
	 *		- request : the request object.
	 *	return:
	 *		- the site root absolute URL.
	 */
	public static function getSiteRoot(Request $request) {
	  // We get the site domain
	  $domain = static::getSiteDomain($request);
	  // We check if the domain is set in the configuration
	  if(!isset($domain)) {
	    return NULL;
	  }
	  $configSiteRoot = $request->getConfig("siteRoot");
	  $siteRoot = LanguageManager::templateReplace($configSiteRoot,$domain);
	  return static::relativeToAbsoluteURL("./", $siteRoot);
	}
	
	/*
	 *	function: getSiteDomain
	*	This function retrieve the site domain based on current context.
	*
	*	parameters:
	*		- request : the request object.
	*	return:
	*		- the site domain (www.igot-web.com).
	*/
	public static function getSiteDomain(Request $request) {
	  $context = $request->getSiteContext();
	  $adminMode = $context->getAdminMode();
	  $shadowMode = $context->getShadowMode();
	  
	  $domain = $request->getConfig("siteDomain");
	  if($adminMode) {
	    $domain = $request->getConfig("adminSiteDomain");
	  }
	  else if($shadowMode) {
	    $domain = $request->getConfig("shadowSiteDomain");
	  }
	  return $domain;
	}
	
	/*
	 *	function: getSiteURL
	*	This function return a page absolute URL for current site.
	*
	*	parameters:
	*      - path : the relative path
	*		- request : the request object.
	*	return:
	*		- the page absolute URL.
	*/
	public static function getSiteURL($path, Request $request) {
	  $siteRoot = static::getSiteRoot($request);
	  $url = static::relativeToAbsoluteURL($path, $siteRoot);
	  return $url;
	}
	
	/*
	 *	static function: getCurrentPageRoot
	*	This function get the current page root URL (protocol + domain).
	*/
	public static function getCurrentPageRoot() {
	  $pageDomain = static::getHttpProtocol();
	  $pageDomain .= "://";
	  $pageDomain .= static::getCurrentPageDomain() . "/";
	  return $pageDomain;
	}
	
	/*
	 *	static function: getCurrentPageURL
	*	This function get the current page URL.
	*/
	public static function getCurrentPageURL() {
	  $pageURL = static::getCurrentPageRoot();
	  $pageURL .= static::getRequestURI();
	  return $pageURL;
	}

	/*
	 *	function: relativeToAbsoluteURL
	 *	This function generates an absolute URL from a relative path.
	 *
	 *	parameters:
	 *		- rel : the relative path to base URL
	 *		- base : the base URL to use (optional).
	 *	return:
	 *		- base absolute path.
	 */
	public static function relativeToAbsoluteURL($rel, $base = NULL) {
	  $currentBase = UrlUtils::getCurrentBaseUrl();
	  if($base == NULL) {
	    $base = $currentBase;
	  }

	  /* queries and anchors */
	  if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;
	  
	  /* parse currentBase URL and convert to local variables:
	  $scheme, $host, $path */
	  extract(parse_url($currentBase));
	  if(!isset($port)) {
	    $port = "";
	  }
	  
	  /* parse base URL and convert to local variables:
	  $scheme, $host, $path */
	  extract(parse_url($base));
	  
	  /* If an absolute URL is used as rel, we use it instead of the current base URL except for the port */
	  if (parse_url($rel, PHP_URL_SCHEME) != '') {
	    $relURL = parse_url($rel);
	    $base = $relURL["host"];
	    $host = $base;
	    $path = "./";
	    $rel = $relURL["path"];
          }

	  /* remove non-directory element from path */
	  $path = preg_replace('#/[^/]*$#', '', $path);

	  /* destroy path if relative url points to root */
	  if ($rel[0] == '/') $path = '';

	  /* We chech the protocol */
	  $protocol = $scheme.'://';

	  /* We check the port number */
	  $port = UrlUtils::generateUrlPort($port, $protocol);

	  /* dirty absolute URL */
	  $abs = "$host$port$path/$rel";

	  $abs = UrlUtils::cleanPath($abs);

	  /* absolute URL is ready! */
	  return $protocol.$abs;
	}

	/*
	 *	function: getCurrentBaseUrl
	 *	This function gets the current base Url.
	 *
	 *	parameters:
	 *		- none
	 *	return:
	 *		- base url.
	 */
	public static function getCurrentBaseUrl() {
	  $protocol = UrlUtils::getHttpProtocol() . '://';
	  $host = UrlUtils::getCurrentPageDomain();
	  $requestUri = UrlUtils::getRequestURI();
	  $currentUrl = $protocol.$host.$requestUri;
	  $parts = parse_url($currentUrl);
	  $port = isset($parts['port']) ? $parts['port'] : NULL;

	  // use port if non default
	  $port = UrlUtils::generateUrlPort($port, $protocol);

	  // rebuild
	  return $protocol . $parts['host'] . $port . "/";
	}

	/*
	 *	function: generateUrlPort
	 *	This function generate the port part of the URL based on port number and protocol.
	 *
	 *	parameters:
	 *		- $portNumber : the port number
	 *		- $protocol : the protocol (http:// or https://)
	 *	return:
	 *		- port number for URL.
	 */
	public static function generateUrlPort($portNumber, $protocol) {
		$port =
			isset($portNumber) && $portNumber !== "" &&
			(($protocol === 'http://' && $portNumber !== 80) ||
			($protocol === 'https://' && $portNumber !== 443))
			? ':' . $portNumber : '';

		return $port;
	}

	/*
	 *	function: getCurrentPageDomain
	 *	This function gets the current page domain.
	 *
	 *	parameters:
	 *		- disablePorts - boolean true to remove the ports from the domain.
	 *	return:
	 *		- http host with port number based on parameter.
	 */
	public static function getCurrentPageDomain($disablePorts = FALSE) {
	  $domain = $_SERVER['HTTP_HOST'];
	  if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	    $domain = $_SERVER['HTTP_X_FORWARDED_HOST'];
	  }
	  
	  if($disablePorts) {
	    $pos = strrpos($domain, ":");
		if($pos > 0) {
	      $domain = substr($domain,0,$pos);
		}
	  }
	  return $domain;
	}

	/*
	 *	function: getRequestURI
	 *	This function gets the request URI.
	 *
	 *	parameters:
	 *		- none
	 *	return:
	 *		- request URI.
	 */
	public static function getRequestURI() {
		return $_SERVER['REQUEST_URI'];
	}

	/*
	 *	function: getHttpProtocol
	 *	This function gets the http protocol.
	 *
	 *	parameters:
	 *		- none
	 *	return:
	 *		- http protocol.
	 */
	public static function getHttpProtocol() {
	  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
	    if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
	      return 'https';
	    }
	    return 'http';
	  }
	  if (isset($_SERVER['HTTPS']) &&
	      ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
	    return 'https';
	  }
	  return 'http';
  }
  
  /*
	 *	function: generateJSONURLParam
	 *	This function generates a JSON object which can be added in request URL as parameter.
	 *
	 *	parameters:
	 *		- object - the object to convert into JSON URL parameter.
	 *	return:
	 *		- JSON URL parameter.
	 */
  public static function generateJSONURLParam($object) {
    $jsonParams = htmlentities(
      JSONUtils::buildJSONObject($object),
      ENT_QUOTES,
      'UTF-8'
    );
    return $jsonParams;
  }
}
?>
