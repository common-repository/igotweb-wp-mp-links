<?php


/**
 *	Class: SessionManager
 *	Version: 0.2
 *	This class handle session information.
 *
 *  TODO - Remove the $Session global variable.
 *
 *	requires:
 *		- none.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;

class SessionManager {

	private $attributes;
	private $attributesToRemove;
	private $status;

	public function __construct() {
		$this->attributes = array();
		$this->attributesToRemove = array();
		$this->status = "";
	}

	/*
	 *	private function: getSessionKey
	 *	This function returns the key used in session
	 *	based on the site.
	 *
	 *	parameters:
	 *		- key - the original key.
	 *	return:
	 *		- sessionKey - the session key.
	 */
	private function getSessionKey($key, Request $request) {
		$sessionKey = $key;

		if($this->status == "started") {
      if(isset($request)) {
        $application = $request->getApplication();
				$sessionKey = $application->getShortName() . "_" . $key;
      }
		}

		return $sessionKey;
	}

	/*
	 *	function: start
	 *	This function start the session.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function start() {
		@session_start();
		$this->status = "started";
		foreach($this->attributesToRemove as $key => $bool) {
			$this->remove($key);
		}
		foreach($this->attributes as $key => $object) {
			$this->put($key,$object);
		}
	}

	/*
	 *	function: put
	 *	This function put the object in session with its key.
	 *
	 *	parameters:
	 *		- $key : the key of the object to put in session.
	 *		- $object : the object to put in session.
	 *	return:
	 *		- none.
	 */
	public function put($key,$object, Request $request) {
		// We get the session key
		$key = $this->getSessionKey($key, $request);

		$object = serialize($object);

		if($this->status == "started") {
			$_SESSION[$key] = $object;
		}
		else {
			$this->attributes[$key] = $object;
			unset($this->attributesToRemove[$key]);
		}
	}

	/*
	 *	function: get
	 *	This function get object in session linked to the key.
	 *
	 *	parameters:
	 *		- $key : the key of the object to get in session.
	 *	return:
	 *		- the object linked to the key.
	 */
	public function get($key, Request $request) {
		// We get the session key
		$key = $this->getSessionKey($key, $request);
    
		$object = NULL;
		if($this->status == "started") {
			$object = @$_SESSION[$key];
		}
		else {
			$object = $this->attributes[$key];
		}
		// We try to unserialize the value.
		if(is_string($object)) {
			$unserializedObject = unserialize($object);
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
	 *		- $key : the key of the object to get in session.
	 *	return:
	 *		- true if found, else false.
	 */
	public function exists($key, Request $request) {
		// We get the session key
		$key = $this->getSessionKey($key, $request);

		if($this->status == "started") {
			return @$_SESSION[$key] != NULL;
		}
		else {
			return $this->attributes[$key] != NULL;
		}
	}

	/*
	 *	function: remove
	 *	This function remove the object in the session.
	 *
	 *	parameters:
	 *		- $key : the key of the object to remove in session.
	 *	return:
	 *		- none.
	 */
	public function remove($key, Request $request) {
		// We get the session key
		$key = $this->getSessionKey($key, $request);

		if($this->status == "started") {
			unset($_SESSION[$key]);
		}
		else {
			unset($this->attributes[$key]);
			$this->attributesToRemove[$key] = true;
		}
	}
}
?>
