<?php

/**
 *	Class: JSONUtils
 *	Version: 0.1
 *	This class handle JSON needs.
 *
 *	requires:
 *		- none.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;
 
class JSONUtils {
	
	public static $DateTimeFormat = "Y-m-d H:i:s";
	
	private function __construct() {}	
  
  /*
	 *	function: getObjectFromJSON
	 *	This function return an object from JSON string.
	 *	
	 *	parameters:
	 *		- json - a json string.
	 *	return:
	 *		- Object.
	 */
  public static function getObjectFromJSON($json) {
    return json_decode($json);
  }
  
  /*
	 *	function: buildJSONObject
	 *	This function return a JSON string build from the object in parameter.
	 *	
	 *	parameters:
	 *		- object - An object to convert to JSON.
	 *	return:
	 *		- JSON string - a string representing the object.
	 */
	public static function buildJSONObject($object) {
    if(is_array($object)) {
			if(Utils::isAssociativeArray($object)) {
				return static::buildJSONMap($object);
			}
			else {
				return static::buildJSONArray($object);
			}
		}
		else if(is_numeric($object)) {
			return json_encode($object);
		}
		else if(is_bool($object)) {
			if($object) {
				return "true";
			}
			else {
				return "false";
			}
		}
		else if(is_string($object)) {
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $object) . '"';
		}
		else if($object instanceof \DateTime) {
			return '"' . $object->format(JSONUtils::$DateTimeFormat) . '"';
		}
		else if(method_exists($object,"toJSON")) {
			return $object->toJSON();
		}
    else if($object instanceof \stdClass) {
      return static::buildJSONMap($object);
    }
	}

	/*
	 *	function: buildJSONMap
	 *	This function return a JSON string build from the associative array (map) in parameter.
	 *	
	 *	parameters:
	 *		- map - An associative array.
	 *	return:
	 *		- JSON string - a string representing the map.
	 */
	private static function buildJSONMap($map) {
		$str = "{";
		$first = true;
		foreach( $map as $key => $value ) {
			$convertedValue = static::buildJSONObject($value);
			if(isset($convertedValue) && $convertedValue !== "") {
				if(!$first) { $str .= ","; }
				$str .= "\"".$key."\":".$convertedValue;
				$first = false;
			}
		}
		$str .= "}";
		return $str;
	}
	
	/*
	 *	function: buildJSONArray
	 *	This function return a JSON string build from the array (list) in parameter.
	 *	
	 *	parameters:
	 *		- array - An array (list).
	 *	return:
	 *		- JSON string - a string representing the array.
	 */
	private static function buildJSONArray($array) {
		$str = "[";
		$first = true;
		for ($i = 0 ; $i < count($array) ; $i++) {
			if(!$first) { $str .= ","; }
			$str .= static::buildJSONObject($array[$i]);
			$first = false;
		}
		$str .= "]";
		return $str;
	}
}

?>
