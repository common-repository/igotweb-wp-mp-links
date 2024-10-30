<?php

/**
 *	Class: PropertiesFilesUtils
 *	Version: 0.1
 *	This class handle properties files.
 *	Keys in properties file must be unique even if inside section or not.
 *
 *	requires:
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;

class PropertiesFilesUtils extends IniFilesUtils {

  /*
   *	public static function: getProperties
   *	This function returns an associative array with key values corresponding to properties file.
   */
  public static function getProperties($path) {
    return static::getConfiguration($path, NULL);
  }

  /*
   *	protected static function: evaluateValue
   *	This function evaluates a value from properties file.
   *
   *	parameters:
   *		- $value : the value to evaluate.
   */
  protected static function evaluateValue($value, $config, $sections) {
    // We remove any space arround the value.
    $value = trim($value);

    // We check if the value is an array
    if(preg_match("/".static::$PATTERN_ARRAY."/i",$value,$array)) {
      preg_match_all("/".static::$PATTERN_ARRAY_VALUE."/i",$array[1],$items);
      $value = $items[2];
    }

    return $value;
  }
}
?>
