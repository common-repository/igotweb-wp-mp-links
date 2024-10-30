<?php

/**
 *	Class: PlatformUtils
 *	Version: 0.1
 *	This class handle platform utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ApplicationUtils;

class PlatformUtils {
  
	// The platform file name
	public static $PLATFORM_FILE_NAME = "platform.ini";
  // The platform absolute root path.
  private static $CURRENT_PLATFORM_ROOT_PATH;
  // The current platform
  private static $CURRENT;

	private function __construct() {}

	
	/*
	 *	static function: getCurrent
	*	This function get the current Platform.
	*
	*	parameters:
	*		- none.
	*	return:
	*		- Platform : the current platform or Error object.
	*/
	public static function getCurrent() {
    if(isset(static::$CURRENT)) {
      return static::$CURRENT;
    }
		
		$platform = new Platform();

    $rootPath = static::getCurrentPlatformRootPath();
		
		// We get the current platform from the framework
		$platformFilePath = $rootPath . static::$PLATFORM_FILE_NAME;
		if(file_exists($platformFilePath)) {
			$platform->setRootPath($rootPath);
      // We look to get specific framework directories
			$cfg = IniFilesUtils::getConfiguration($platformFilePath);
			if(isset($cfg["platform"]) && $cfg["content_path"]!="") {
				$platformName = $cfg["platform"];
        $platform->setName($platformName);
			}
			if(isset($cfg["content_path"]) && $cfg["content_path"]!="") {
				$contentPath = $cfg["content_path"];
        $platform->setContentPath($rootPath . $contentPath);
			}
		}  

    static::$CURRENT = $platform;
		return $platform;
	}

	/*
   *	function: getCurrentPlatformRootPath
   *	This function get the platform absolute root path.
   *	It is the root path where all webapps are located.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- The platform absolute root path.
   */
  public static function getCurrentPlatformRootPath() {
    if(!isset(static::$CURRENT_PLATFORM_ROOT_PATH)) { 
      // The current platform root path is retrieved from the current application root path
      $applicationRootPath = ApplicationUtils::getCurrentApplicationRootPath();
      if($applicationRootPath != null) {
        static::$CURRENT_PLATFORM_ROOT_PATH = FileUtils::cleanPath($applicationRootPath . ".." . DIRECTORY_SEPARATOR);
      }
    }
    return static::$CURRENT_PLATFORM_ROOT_PATH;
  }
}
?>
