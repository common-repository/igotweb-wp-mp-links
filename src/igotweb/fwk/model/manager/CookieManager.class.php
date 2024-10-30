<?php


/**
 *	Class: CookieManager
 *	Version: 0.3
 *	This class handle cookie information.
 *  TODO - Remove the global $Cookie variable
 *
 *	requires:
 *		- none.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;

class CookieManager {

	private $siteContext;

	public function __construct(Request $request) {
		$this->siteContext = $request->getSiteContext();
	}

	/*
	 *	private function: getCookieKey
	 *	This function returns the key used in cookie
	 *	based on the application short name.
	 *
	 *	parameters:
	 *		- key - the original key.
	 *	return:
	 *		- cookieKey - the cookie key.
	 */
	private function getCookieKey($key) {
		$cookieKey = $key;

		if($this->siteContext != NULL) {
			$cookieKey = $this->siteContext->getApplication()->getShortName() . "_" .$key;
		}

		return $cookieKey;
	}

	/*
	 *	function: put
	 *	This function put the string in cookie with its key.
	 *
	 *	parameters:
	 *		- $key : the key of the object to put in cookie.
	 *		- $object : the object to put in cookie.
	 *	return:
	 *		- none.
	 */
	public function put($key,$object,$expire = 0, $path = "", $domain = "", $secure = false) {
		// We get the cookie key
		$key = $this->getCookieKey($key);

		if(is_object($object) || is_array($object)) {
			$object = serialize($object);
		}
		setcookie($key,$object,$expire,$path,$domain,$secure);
	}


	/*
	 *	function: get
	 *	This function get object in cookie linked to the key.
	 *
	 *	parameters:
	 *		- $key : the key of the object to get in cookie.
	 *	return:
	 *		- the object linked to the key.
	 */
	public function get($key) {
		// We get the cookie key
		$key = $this->getCookieKey($key);

		$object = @$_COOKIE[$key];
		// We try to unserialize the value.
		if(is_string($object)) {
			$unserializedObject = @unserialize($object);
			if(!$unserializedObject) {
				return $object;
			}
			return $unserializedObject;
		}
		return $object;
	}


	/*
	 *	function: exists
	 *	This function look if there is an object linked
	 *	to the key in parameters.
	 *
	 *	parameters:
	 *		- $key : the key of the object to get in cookie.
	 *	return:
	 *		- true if found, else false.
	 */
	public function exists($key) {
		// We get the cookie key
		$key = $this->getCookieKey($key);

		return @$_COOKIE[$key] != NULL;
	}


	/*
	 *	function: remove
	 *	This function remove the object in the cookie.
	 *
	 *	parameters:
	 *		- $key : the key of the object to remove in cookie.
	 *	return:
	 *		- none.
	 */
	public function remove($key) {
		// We get the cookie key
		$key = $this->getCookieKey($key);

		setcookie($key,"",time() - 3600);
	}

}
?>
