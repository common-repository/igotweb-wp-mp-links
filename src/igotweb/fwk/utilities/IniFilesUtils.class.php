<?php

/**
 *	Class: IniFilesUtils
 *	Version: 0.1
 *	This class handle ini configuration files.
 *	Keys in configuration file must be unique even if inside section or not.
 *
 *	requires:
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\configuration\RawConfiguration;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\configuration\RawItem;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;

class IniFilesUtils {

	protected static $PATTERN_PROPERTY = "\s*([^=\s]*)\s*=\s*(.*)\s*"; // key = value
  protected static $PATTERN_CONTINUE_PROPERTY = "\s*([^=\s]*)\s*\.=\s*(.*)\s*"; // key .= value
	protected static $PATTERN_COMMENT = "\/\*(.*)\*\/"; // /* COMMENTS */
	protected static $PATTERN_SECTION = "^\[([^\]]+)\]\s*$"; // [SECTION]
	protected static $PATTERN_SUB_SECTION = "^\s+\[([^\]]+)\]\s*$"; // SPACE [SUBSECTION]
	protected static $PATTERN_ARRAY = "^\[(\".*\")\]$"; // ["..."]
	protected static $PATTERN_ARRAY_VALUE = "(\"([^\"]*)\")"; // "array value"
	protected static $PROPERTY_SEPARATOR = "\.";

	private function __construct() {}
	
	/*
	 *	public static function: getRawConfiguration
	 *	This function returns an array with translated configuration file.
	 *	The values are not evaluated.
	 */
	public static function getRawConfiguration($path) {
		$config = new RawConfiguration();

		$lines = array();
		if(file_exists($path)) {
			$lines = file($path);
		}

		$sections = NULL;

		// Loop through our array, show HTML source as HTML source; and line numbers too.
		foreach ($lines as $line) {
			// We check if we have a new section
			preg_match("/".static::$PATTERN_SECTION."/i", $line, $matches);
			if(count($matches) == 2) {
				$sections = array($matches[1]);
			}
			// We check if we have a new sub section
			preg_match("/".static::$PATTERN_SUB_SECTION."/i", $line, $matches);
			if(count($matches) == 2) {
				if(count($sections) == 1) {
					$sections[] = $matches[1];
				}
				else if(count($sections) == 2) {
					$sections[1] = $matches[1];
				}
			}

			$raw = new RawItem();

			// We check if we have comment
			preg_match("/".static::$PATTERN_COMMENT."/i", $line, $matches);
			if(count($matches) == 2) {
				$comment = $matches[1];

				// We build the raw array that we store
				$raw->setComment($comment);
			}

			// We check if we have a property
			preg_match("/^".static::$PATTERN_PROPERTY."/i", $line, $matches);
			if(count($matches) == 3) {
				$key = $matches[1];
				$value = $matches[2];

				// We remove the comment from the value
				$value = preg_replace("/".static::$PATTERN_COMMENT."/i","",$value);

				$raw->setKey($key);
				$raw->setValue($value);
				$raw->setSections($sections);
			}
      
      // We check if we have a continue property
      preg_match("/^".static::$PATTERN_CONTINUE_PROPERTY."/i", $line, $matches);
      if(count($matches) == 3) {
        $key = $matches[1];
				$value = $matches[2];

				// We remove the comment from the value
				$value = preg_replace("/".static::$PATTERN_COMMENT."/i","",$value);
        
        // We get the last raw item
        $last = $config->getLastRawItem();
        if($last->getKey() == $key) {
          // We update the value
          $value = $last->getValue() . $value;
          $last->setValue($value);
        }
      
        // We do not add new raw item
				$raw = NULL;
      }

			// We store the raw line in configuration array
			$config->addRawItem($raw);
		}
		return $config;
	}

	/*
	 *	public static function: getConfiguration
	 *	This function returns an associative array with key values corresponding to configuration file.
	 *	The values are evaluated with existing ones and with default configuration if any.
	 */
	public static function getConfiguration($path, $defaultConfig = NULL) {

		$config = array();
		if(isset($defaultConfig)) {
			$config = $defaultConfig;
		}

		// We get the raw configuration
		$rawConfig = static::getRawConfiguration($path);

		// We loop through raw config to convert it into configuration array.
		$list = $rawConfig->getList();
		foreach ($list as $raw) {
			if($raw->getKey() !== NULL && $raw->getValue() !== NULL) {
				$key = $raw->getKey();
				$sections = $raw->getSections();
				$value = static::evaluateValue($raw->getValue(), $config, $sections);

				if(isset($sections) && count($sections) > 0) {
					// In case of sections, we generate the corresponding arrays
					if(!isset($config[$sections[0]])) {
						$config[$sections[0]] = array();
					}
					if(count($sections) > 1) {
						if(!isset($config[$sections[0]][$sections[1]])) {
							$config[$sections[0]][$sections[1]] = array();
						}
						$config[$sections[0]][$sections[1]][$key] = $value;
					}
					else {
						$config[$sections[0]][$key] = $value;
					}
				}
				else {
					$config[$key] = $value;
				}
			}
		}

		return $config;
	}

	/*
	 *	public static function: evaluateValue
	 *	This function evaluates a value from configuration file.
	 *
	 *	parameters:
	 *		- $value : the value to evaluate.
	 *		- $config : existing config to be used if reference to keys are included within the value.
	 */
	protected static function evaluateValue($value, $config, $sections) {
		// We remove any space arround the value.
		$value = trim($value);

		switch($value) {
			case "true":
				$value = true;
			break;
			case "false":
				$value = false;
			break;
			case "NULL":
				$value = NULL;
			break;
			default:
				// We check if the value is an array
				if(preg_match("/".static::$PATTERN_ARRAY."/i",$value,$array)) {
					preg_match_all("/".static::$PATTERN_ARRAY_VALUE."/i",$array[1],$items);
					$value = $items[2];
				}
				else {
					preg_match_all("/(\"[^\"]*\"|[^\"\s]+)/i",$value,$items);
					$value = "";
					foreach($items[0] as $item) {
						if(preg_match("/\"([^\"]*)\"/i",$item,$itemContent)) {
							// We get the content of the string
							$value .= $itemContent[1];
						}
						else {
							// We get the configuration keys value
							$vars = preg_split("/".static::$PROPERTY_SEPARATOR."/",$item);
							foreach($vars as $var) {
								if($var != "" && count($sections) > 1 && isset($config[$sections[0]]) &&
										isset($config[$sections[0]][$sections[1]]) && isset($config[$sections[0]][$sections[1]][$var])) {
									// We check if the key is already defined in configuration in the current section
									$value .= $config[$sections[0]][$sections[1]][$var];
								}
								else if($var != "" && count($sections) > 0 && isset($config[$sections[0]]) &&
										isset($config[$sections[0]][$var])) {
									// We check if the key is already defined in configuration in the parent section
									$value .= $config[$sections[0]][$var];
								}
								else if($var != "" && isset($config[$var])) {
									// We check if the key is already defined in configuration in the root section
									$value .= $config[$var];
								}
								else if(count($vars) == 1) {
									// In case we have a number
									$value .= floatval($var);
								}
							}
						}
					}
				}
			break;
		}
		return $value;
	}

	/*
	 *	public static function: storeRawConfiguration
	 *	This function stores the raw configuration into the path in parameter.
	 *	If file already exists, it updates the existing keys and add new ones if
	 *	not already present.
	 */
	public static function storeRawConfiguration($path, $rawConfig, $merge = true) {

		// 1. We get the config to store
		$configToStore = array();
		if(file_exists($path) && $merge) {
			// The file already exists, so we update the configuration.
			$configToStore = static::getRawConfiguration($path);
			// We merge the rawConfig into configToStore
			$configToStore = static::mergeRawConfiguration($rawConfig, $configToStore);
		}
		else {
			// We create a new file
			$configToStore = $rawConfig;
		}

		// 2. We generate the file
		$file = fopen($path,"w") or new Error(9701,1);
		if($file instanceof Error) {
			return $file;
		}

		$lines = array();
		foreach($configToStore->getList() as $raw) {
			$line = "";
			if($raw->getKey() !== NULL && $raw->getValue() !== NULL) {
				$line .= $raw->getKey()." = ".$raw->getValue();
			}
			if($raw->getComment() !== NULL) {
				$line .= "/* ".trim($raw->getComment())." */";
			}
			if($line != "" || $raw->getIsEmptyLine()) {
				$lines[] = $line;
			}
		}

		$content = implode("\n",$lines);

		$written = fwrite($file,$content) or new Error(9701,2);
		if($written instanceof Error) {
			return $written;
		}
		fclose($file);

		return "ok";
	}

	/*
	 *	public static function: mergeRawConfiguration
	 *	This function merges a source configuration into destination configuration.
	 *	It returns an array of merged raw configurations.
	 */
	public static function mergeRawConfiguration($srcRawConfig, $destRawConfig) {
		// We loop over the srcRawConfig to update existing keys or to push it at the end of destRawConfig.
		$indexSrc = 0;
		foreach($srcRawConfig->getList() as $raw) {
			// We check if the raw already exists
			$found = false;
			$indexDest = 0;
			foreach($destRawConfig->getList() as $existingRaw) {
				// We do not care about sections. Keys must be unique
				if($existingRaw->getKey() !== NULL && $raw->getKey() !== NULL && $existingRaw->getKey() == $raw->getKey()) {
					if($raw->getValue() !== NULL) { $existingRaw->setValue($raw->getValue()); }
					if($raw->getSections() !== NULL && count($raw->getSections()) > 0) { $existingRaw->setSections($raw->getSections()); }
					if($raw->getComment() !== NULL) { $existingRaw->setComment($raw->getComment()); }
					$found = true;
					break;
				}
			}
			// If not found, we add the raw only if it is a parameter
			if(!$found && $raw->getKey() !== NULL) {
				$destRawConfig->addRawItem($raw);
			}
		}
		return $destRawConfig;
	}

	/*
	 *	public static function: storeConfiguration
	 *	This function stores the configuration into the path in parameter.
	 */
	public static function storeConfiguration($path, $config) {
		// 1. We need to convert configuration into understandable raw configuration
		$rawConfig = new RawConfiguration();
		foreach ($config as $key => $value) {
			$raw = new RawItem($key,$value);
			// We push the raw configuration item
			$rawConfig->addRawItem($raw);
		}

		// 2. We store the raw configuration
		static::storeRawConfiguration($path,$rawConfig);
	}
}
?>
