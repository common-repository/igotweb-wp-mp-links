<?php
/**
 *	Class: FacebookUser
 *	Version: 0.1
 *	This class handle a connected facebook user.
 *
 *	requires:
 *		- ext.facebook-php-sdk.Facebook
 *      - CONFIG["useFacebook"] set to true
 *
 */
namespace igotweb\fwk\model\bean;

use igotweb\fwk\model\bean\generic\GenericBean;
use igotweb\fwk\model\bean\Request;
use igotweb\fwk\utilities\FacebookUtils;

class FacebookUser extends GenericBean {

  private $isLogged;

  private $accessToken;

  private $profile;

	/**
	 * This method use the Facebook object to handle the user.
	 */
	public function __construct() {
    $this->isLogged = false;
    $this->accessToken = null;
    $this->profile = null;
	}
	
	public function loginFromCallback(Request $request) {
	  global $Facebook;
	  
	  // We try to get the user
	  $helper = $Facebook->getRedirectLoginHelper();
	   
	  try {
	    $accessToken = $helper->getAccessToken();
	  } catch(Facebook\Exceptions\FacebookResponseException $e) {
	    // When Graph returns an error
	    echo 'Graph returned an error: ' . $e->getMessage();
	    exit;
	  } catch(Facebook\Exceptions\FacebookSDKException $e) {
	    // When validation fails or other local issues
	    echo 'Facebook SDK returned an error: ' . $e->getMessage();
	    exit;
	  }
	   
	  if (isset($accessToken)) {
	    // Logged in!
	    $this->accessToken = (string) $accessToken;
	    $this->updateProfile();
	    $this->isLogged = true;
	    $this->storeInSession($request);
	  }
	}
	
	/*
	 *	function: updateProfile
	 *	This method check the profile from Facebook of user logged and get it.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- boolean : true if user is logged with facebook.
	 */
	public function updateProfile() {
	  $response = FacebookUtils::queryGraph("/me", $this->accessToken);
	  $this->profile = $response->getGraphObject()->asArray();
	}

	/*
	 *	function: getIsLogged
	 *	This function returns true if the user is logged.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- boolean : true if user is logged with facebook.
	 */
	public function getIsLogged() {
	  return $this->isLogged;
	}

	/*
	 *	function: logout
	 *	This function logs the user out.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function logout() {
	  global $request;

	  // We destroy the session
	  $this->isLogged = false;
	  $this->profile = null;
	  $this->accessToken = null;
	  
	  $this->removeFromSession($request);
	}

	/*
	 *	function: getProfile
	 *	This function get the user profile.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- facebook user profile /me.
	 */
	 public function getProfile($field = null) {
	  if(!isset($this->profile)) {
	    return null;
	  }
	  if($field != null && $field != "") {
	    return $this->profile[$field];
	  }
	  return $this->profile;
	}
	
	/*
	 *	static function: getFromSession
	*	This function try to get the FacebookUser from the Session.
	*
	*	parameters:
	*		- none.
	*	return:
	*		- The FacebookUser object if found else a New User.
	*/
	public static function getFromSession(Request $request) {
	  $facebookUser = new FacebookUser();
	  if($request->sessionExistsElement("facebookUser")) {
	    $facebookUser = $request->sessionGetElement("facebookUser");
	  }
	  return $facebookUser;
	}
	
	/*
	 *	function: storeInSession
	*	This function store the FacebookUser in Session.
	*
	*	parameters:
	*		- none.
	*	return:
	*		- ok if stored else an Error object.
	*/
	public function storeInSession(Request $request) {
	  $request->sessionPutElement("facebookUser",$this);
	  return "ok";
	}
	
	/*
	 *	function: removeFromSession
	*	This function remove the FacebookUser from Session.
	*
	*	parameters:
	*		- none.
	*	return:
	*		- ok if removed else an Error object.
	*/
	public function removeFromSession(Request $request) {
	  $request->sessionRemoveElement("facebookUser");
	  return "ok";
	}

}
?>