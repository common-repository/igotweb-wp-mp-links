<?php

/**
 *	Class: Utils
 *	Version: 0.1
 *	This class provides some utils functions.
 *
 *	requires:
 *		- none.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class Utils {
  
  private function __construct() {}

	/**
	 * Check if a file exists in the include path
	 *
	 * @version     1.2.1
	 * @author      Aidan Lister <aidan@php.net>
	 * @link        http://aidanlister.com/repos/v/function.file_exists_incpath.php
	 * @param       string     $file       Name of the file to look for
	 * @return      mixed      The full path if file exists, FALSE if it does not
	 */
	public static function file_exists_incpath ($file) {
		$paths = explode(PATH_SEPARATOR, get_include_path());

	    foreach ($paths as $path) {
    	    // Formulate the absolute path
	        $fullpath = $path . DIRECTORY_SEPARATOR . $file;
			echo $fullpath;

	        // Check it
	        if (file_exists($fullpath)) {
	            return $fullpath;
	        }
	    }

	    return false;
	}

	/*
	 *	removeFromArray
	 *	This method removes a value from an array.
	 */
	public static function removeFromArray(&$array, $value){
		foreach($array as $index=>$val) {
			if($val == $value) {
				unset($array[$index]);
				$array = array_values($array);
				return true;
				break;
			}
		}
		return false;
	}

	/*
	 *	isAssociativeArray
	 *	This function return true if the array in parameter is associative.
	 *
	 *	parameters:
	 *		- array - An array.
	 *	return:
	 *		- boolean - true if associative array.
	 */
	public static function isAssociativeArray (&$array) {
		if(is_array($array) && count($array) > 0 &&
				0 !== count(array_diff_key($array, array_keys(array_keys($array))))) {
			return true;
		}
		return false;
	}
	
	/*
	 *	mapPut
	 *	This method put an element in a map.
	 *	The key can be path within objects toto.tata.key
	 */
	public static function mapPut($key,$value,&$map) {
	  $keys = explode(".", $key);
	  $array = &$map;
	
	  for($i = 0 ; $i < count($keys) ; $i++) {
	    if(count($keys) - 1 == $i) {
	      if(isset($array[$keys[$i]]) && is_array($array[$keys[$i]])) {
	        $array[$keys[$i]] = array_merge($array[$keys[$i]], $value);
	      }
	      else {
	        $array[$keys[$i]] = $value;
	      }
	    }
	    else {
	      if(!isset($array[$keys[$i]])) {
	        $array[$keys[$i]] = array();
	      }
	      $array = &$array[$keys[$i]];
	    }
	  }
	}
	
	/*
	 *	mapGet
	*	This method get a value from a map.
	*	The key can be path within objects toto.tata.key
	*/
	public static function mapGet($key,&$map) {
	  if(!isset($key) || $key == "") {
	    return NULL;
	  }
	  
	  $keys = explode(".", $key);
	  $array = &$map;
	
	  for($i = 0 ; $i < count($keys) ; $i++) {
	    if(is_array($array) && isset($array[$keys[$i]])) {
	      $array = &$array[$keys[$i]];
	    }
	    else {
	      return NULL;
	    }
	  }
	  
	  return $array;
  }
  
  /*
	 *	mapDelete
	*	This method remove a key from a map.
	*	The key can be path within objects toto.tata.key
	*/
	public static function mapDelete($key,&$map) {
	  if(!isset($key) || $key == "") {
	    return NULL;
	  }
	  
	  $keys = explode(".", $key);
	  $array = &$map;
	
	  for($i = 0 ; $i < count($keys) ; $i++) {
	    if(is_array($array) && isset($array[$keys[$i]])) {
	      $array = &$array[$keys[$i]];
	    }
	    else {
	      return NULL;
	    }
	  }
	  
	  unset($array);
  }
  

  /*
   *	arrayMergeRecursive
	 *
	 *	parameters:
	 *		- A list of array.
	 *	return:
	 *		- array - merged array.
	 */
	public static function arrayMergeRecursive() {
    return call_user_func_array('array_merge_recursive', func_get_args());
	}

	/*
	 *	arrayMergeRecursiveSimple
	 *	This function is a variation of array_merge_recursive which only merges arrays and
	 *  if for the same key there are two values which are not associative arrays then override the value.
	 *  cf. http://www.php.net/manual/en/function.array-merge-recursive.php
	 *
	 *	parameters:
	 *		- A list of array.
	 *	return:
	 *		- array - merged array.
	 */
	public static function arrayMergeRecursiveSimple() {

	  if (func_num_args() < 2) {
	    trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
	    return;
	  }
	  $arrays = func_get_args();
	  $merged = array();
	  while ($arrays) {
	    $array = array_shift($arrays);
	    if (!is_array($array)) {
	      trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
	      return;
	    }
	    if (!$array)
	      continue;
	    foreach ($array as $key => $value) {
	      if (is_string($key)) {
	        if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
	          $merged[$key] = call_user_func(array(get_called_class(),__FUNCTION__), $merged[$key], $value);
	        }
	        else {
	          $merged[$key] = $value;
	        }
	      }
	      else {
	        $merged[] = $value;
	      }
	    }
	  }
	  return $merged;
  }
  
  /*
	 *	arrayMergeRecursiveFull
	 *	This function is a variation of array_merge_recursive which only merges arrays and
	 *  if for the same key there are two values which are not associative arrays then override the value.
   *  if for the same index in list there are two values which are not arrays then override the value.
	 *  cf. http://www.php.net/manual/en/function.array-merge-recursive.php
	 *
	 *	parameters:
	 *		- A list of array.
	 *	return:
	 *		- array - merged array.
	 */
	public static function arrayMergeRecursiveFull() {

	  if (func_num_args() < 2) {
	    trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
	    return;
	  }
	  $arrays = func_get_args();
	  $merged = array();
	  while ($arrays) {
	    $array = array_shift($arrays);
	    if (!is_array($array)) {
	      trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
	      return;
	    }
	    if (!$array)
	      continue;
	    foreach ($array as $key => $value) {
        if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
          $merged[$key] = call_user_func(array(get_called_class(),__FUNCTION__), $merged[$key], $value);
        }
        else {
          $merged[$key] = $value;
        }
	    }
	  }
	  return $merged;
	}

	/*
	 *	arrayMergeUnique
   *	This function merges non associative arrays by unique values.
   *  It removes the duplicate values.
	 *
	 *	parameters:
	 *		- A list of array (list).
	 *	return:
	 *		- array - merged array (list).
	 */
	public static function arrayMergeUnique(){
	  $result = array();
	  $arrays = func_get_args();
	  foreach($arrays as $array){
	    $array = (array) $array;
	    foreach($array as $value){
	      if(array_search($value,$result)===false)$result[]=$value;
	    }
	  }
	  return $result;
  }
  
  /*
	 *	function: buildArrayFromObject
   *	This function return an array build from the object in parameter.
   *  All objects elements are converted into array based on toArray method.
	 *	
	 *	parameters:
	 *		- object - An object to convert to array.
	 *	return:
	 *		- array - an array representing the object.
	 */
	public static function buildArrayFromObject($object) {
    if(!is_string($object) && is_object($object) && 
        method_exists($object,"toArray") && is_callable(array(get_class($object), "toArray"))) {
			return $object->toArray();
    }
    else if(is_array($object) || $object instanceof \stdClass) {
      $theArray = array();
			foreach( $object as $key => $value ) {
        $convertedValue = static::buildArrayFromObject($value);
        $theArray[$key] = $convertedValue;
      }
      return $theArray;
		}
    return $object;
  }
  
  /*
	 *	function: arrayMergeFromKey
   *	This function return an array merged from current array in parameter and sub array with specific key.
   *  It applies the merge on all sub arrays available
	 *	
	 *	parameters:
   *		- array - The array to review.
   *    - key - The key matching the array to merge.
	 *	return:
	 *		- array - the merged array.
	 */
	public static function arrayMergeFromKey($array, $key) {
    $mergedArray = $array;
    if(is_array($array)) {
      foreach($array as $index => $value) {
        // We check within all sub arrays
        if($index != $key && is_array($array[$index])) {
          $mergedArray[$index] = static::arrayMergeFromKey($array[$index], $key);
        }
      }
      if(isset($array[$key]) && is_array($array[$key])) {
        // We merge both arrays.
        $mergedArray = static::arrayMergeRecursiveFull($mergedArray, $array[$key]);
        // We remove the array to merge
        unset($mergedArray[$key]);
      }
    }
    return $mergedArray;
	}

	/*
	 *	randomString
	 *	This method generate a random string based on the length in parameter.
	 */
	public static function randomString($length) {
	    $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789";
		$s = "";
    	for(;$length > 0;$length--) {
			$s .= $c{rand(0,strlen($c))};
		}
    	return str_shuffle($s);
  }
  
  /*
	 *	camel2dashed
	 *	This method converts a camelCase name into dashed name.
	 */
  public static function camel2dashed($className) {
    return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $className));
  }
	
	/*
	 *	function: loadBeanUtilsClass
	*	This function loads a bean utils and returns the full class path if loaded.
	*
	*	parameters:
	*    - $classNameWithRelativeNamespace - The class name (with relative name space from \bean\).
	*    - $request - The request object.
	*    - $siteContext - specific site context to load the utils.
	*	return:
	*		- classPath if found and loaded or Error object.
	*/
	public static function loadBeanUtilsClass($classNameWithRelativeNamespace, Request $request, SiteContext $siteContext = NULL) {
	  $logger = Logger::getInstance();
	  
	  if($siteContext == NULL) {
	    $siteContext = $request->getSiteContext();
    }
    
    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
	  
	  // We store the current include path
	  $currentIncludePath = get_include_path();
	  
	  // 1. We check at site / webapp level
	  $includePath = $webapp->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR;
	  if(!Site::isNoSite($site)) {
	    $includePath = $site->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . PATH_SEPARATOR . $includePath;
	  }
	  
    set_include_path($includePath);
	  
	  // 1. We check if we find the class
    $classPath = "utilities\\" . $classNameWithRelativeNamespace . "Utils";
	  if(!class_exists($classPath)) {
      $classPath = "igotweb\\fwk\\utilities\\" . $classNameWithRelativeNamespace . "Utils";
	    if(!class_exists($classPath)) {
	      $error = Error::getGeneric("Utils::loadBeanUtilsClass - class ".$classNameWithRelativeNamespace."Utils not found");
	      $logger->addErrorLog($error, true, false);
	      set_include_path($currentIncludePath);
	      return $error;
	    }
    }
    
	  set_include_path($currentIncludePath);
	  
	  return $classPath;
	}
	
	/*
	 *	function: loadBean
	*	This function loads a bean and returns the full class path if loaded.
	*
	*	parameters:
	*    - $className - The class name (without namespace).
	*    - $request - The request object.
	*    - $siteContext - specific site context to load the utils.
	*	return:
	*		- classPath if found and loaded or Error object.
	*/
	public static function loadBean($className, Request $request, SiteContext $siteContext = NULL) {
	  $logger = Logger::getInstance();
	   
	  if($siteContext == NULL) {
	    $siteContext = $request->getSiteContext();
    }
    
    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
	   
	  // We store the current include path
	  $currentIncludePath = get_include_path();
	   
	  // 1. We check at site / webapp level
	  $includePath = $webapp->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR;
	  if(!Site::isNoSite($site)) {
	    $includePath = $site->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . PATH_SEPARATOR . $includePath;
	  }
	   
	  set_include_path($includePath);
	   
	  // 1. We check if we find the class
	  $classPath = "\\" . $className;
	  if(!class_exists($classPath)) {
	    $classPath = "igotweb\\fwk\\model\\bean\\" . $className;
	    if(!class_exists($classPath)) {
	      $error = Error::getGeneric("Utils::loadBean - class ".$className." not found");
	      $logger->addErrorLog($error, true, false);
	      set_include_path($currentIncludePath);
	      return $error;
	    }
	  }
	   
	  set_include_path($currentIncludePath);
	   
	  return $classPath;
	}
}
?>
