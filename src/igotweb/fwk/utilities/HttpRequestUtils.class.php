<?php

/**
 *	Class: HttpRequestUtils
 *	Version: 0.1
 *	This class handle http requests needs.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;

class HttpRequestUtils {

	private static $JSON_PARAMETERS = null;
  
	private function __construct() {}
		
	public static function retrieveParametersFromJSON() {
		if(isset($_SERVER["CONTENT_TYPE"]) && strpos(strtolower($_SERVER["CONTENT_TYPE"]), "application/json") !== false) {
			# Get JSON as a string
			$json_str = file_get_contents('php://input');
			# Get as an object
			static::$JSON_PARAMETERS = json_decode($json_str, true);
		}
	}

	public static function getParam($paramName,$defaultValue = "") {
		$paramValue = @$_POST[$paramName];
		if($paramValue == NULL || $paramValue == "") {
			$paramValue = @$_GET[$paramName];
		}
		if(isset(static::$JSON_PARAMETERS) && ($paramValue == NULL || $paramValue == "")) {
			$paramValue = @static::$JSON_PARAMETERS[$paramName];
		}
		if(!isset($paramValue) || $paramValue == "") {
			$paramValue = $defaultValue;
		}
		return $paramValue;
	}

	public static function getBoolParam($paramName) {
		$paramValue = self::getParam($paramName);
		if($paramValue == "true" || $paramValue == "y") {
			return true;
		}
		return false;
	}

  /*
   *  This is used for parameters coming from lists in html input as checkboxes for example.
   *  If only one value, we create array with one value.
   *  If value is string using pattern: ARRAY:XX,YY, we create array from this value.
   */
	public static function getArrayParam($paramName) {
		$paramValue = self::getParam($paramName);
		if(!is_array($paramValue)) {
      if(preg_match("/^ARRAY:(.*)/i",$paramValue,$matches)) {
        $paramValue = explode(",",$matches[1]);
      }
      else {
        $paramValue = array($paramValue);
      }
		}
		return $paramValue;
  }
  
  public static function getTypedParam($paramName) {
    // We check if parameter name is boolean is[A-Z].
    if(preg_match("/^is[A-Z]/",$paramName)) {
      return self::getBoolParam($paramName);
    }
    if(preg_match("/DateTime$/",$paramName) || preg_match("/Date$/",$paramName)) {
      return self::getDateParamFromPattern($paramName);
    }
    $paramValue = self::getParam($paramName);
    if(preg_match("/^ARRAY:(.*)/i",$paramValue,$matches)) {
      $paramValue = explode(",",$matches[1]);
    }
    return $paramValue;
  }

	public static function getFileParam($paramName) {
		if(isset($_FILES[$paramName])) {
			return $_FILES[$paramName];
		}
		return new Error(9301,1,$paramName);
	}

	public static function getArrayFileParam($paramName) {
		if(isset($_FILES[$paramName])) {
			$files = $_FILES[$paramName];
			if(!is_array($files)) {
				return array($files);
			}
			return $files;
		}
		return new Error(9301,2,$paramName);
	}

	/*
	 *	static function: getDateParam
	 *	This function get the IwDateTime object corresponding to date inputs.
	 *	Every fields are mandatory.
	 *
	 *	parameters:
	 *		- $paramName - string, the prefix used for date fields.
	 *	return:
	 *		- IwDateTime - the IwDateTime object corresponding to date or Error.
	 */
	public static function getDateParam($paramName) {
		$year = self::getParam($paramName."Year");
		$month = self::getParam($paramName."Month");
		$day = self::getParam($paramName."Day");

		$isNotYear = false;
		$isNotMonth = false;
		$isNotDay = false;

		if($year == "") {
			$isNotYear = true;
		}
		if($month == "") {
			$isNotMonth = true;
		}
		if($day == "") {
			$isNotDay = true;
		}

		if($isNotYear && $isNotMonth && $isNotDay) {
			// The complete date is not defined
			return new Error(9504);
		}
		// Now we can have max 2 errors
		if($isNotYear && $isNotMonth) {
			return new Error(9508);
		}
		if($isNotYear && $isNotDay) {
			return new Error(9509);
		}
		if($isNotMonth && $isNotDay) {
			return new Error(9510);
		}
		// Now we can have max 1 error
		if($isNotYear) {
			return new Error(9505);
		}
		if($isNotMonth) {
			return new Error(9506);
		}
		if($isNotDay) {
			return new Error(9507);
		}

		return IwDateTime::getFromDate($year,$month,$day);
  }
  
  /*
	 *	static function: getDateParamFromPattern
	 *	This function get the IwDateTime object from string param using pattern.
	 *
	 *	parameters:
   *		- $paramName - string.
   *    - $pattern - the pattern used to get the date object. (https://secure.php.net/manual/fr/datetime.createfromformat.php)
	 *	return:
	 *		- IwDateTime - the IwDateTime object corresponding to date or Error.
	 */
	public static function getDateParamFromPattern($paramName, $pattern = null) {
    if(!isset($pattern)) {
      $pattern = IwDateTime::$JSONFormatDateTime;
      if(preg_match("/Date$/i",$paramName)) {
        $pattern = IwDateTime::$JSONFormatDate;
      }
    }
    $paramValue = self::getParam($paramName);
		return IwDateTime::createFromFormat($pattern, $paramValue);
	}

	/*
	 *	static function: getParams
	 *	This function get an array of request parameters which key is matching
	 *	pattern in parameter.
	 *
	 *	parameters:
	 *		- $pattern - string, the regexp pattern to be used to look for parameters.
	 *	return:
	 *		- array - associative array with all request parameters and values matching pattern.
	 */
	public static function getParamsFromPattern($pattern) {
		$parameters = array();
		// We first look in POST parameters
		foreach($_POST as $key => $value) {
			if(preg_match($pattern,$key)) {
				$parameters[$key] = $value;
			}
		}
		// We then look in GET parameters
		foreach($_GET as $key => $value) {
			if(preg_match($pattern,$key)) {
				$parameters[$key] = $value;
			}
		}
		return $parameters;
	}
}
?>
