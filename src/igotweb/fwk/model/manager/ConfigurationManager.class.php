<?php

/**
 *	Class: ConfigurationManager
 *	Version: 0.1
 *	This class handle framework, webapp, site configurations on platforms.
 *  It also handles the custom parameters.
 *
 *	requires:
 *		IniFilesUtils
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\CustomParameterUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\CustomParameter;

class ConfigurationManager {

  // CONFIG keys for framework paths
  public static $FWK_ROOT_PATH_KEY = "igotweb_fwk_lib_dir";

  // Generic path to configuration file
  private static $CONFIG_FILE_PATH = "config/config.ini";

  private $currentConfiguration;
  private $currentCustomParameters;

  function __construct(Request $request) {
    // We populate the current configuration
    $this->populateCurrentConfiguration($request);
  }

  /*
   *  function: populateCurrentConfiguration
   *  This function populates the current platform configuration based on the webapp and site in parameter.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - none.
   */
  public function populateCurrentConfiguration(Request $request) {
    $frameworkRootPath = $request->getFwkRootPath();
    $platform = $request->getPlatform();
    
    $application = $request->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
     
    $frameworkDirectories = array(
        "all" => array(
            static::$FWK_ROOT_PATH_KEY => $frameworkRootPath
        )
    );
     
    // We get the framework default configuration
    $frameworkConfiguration = IniFilesUtils::getConfiguration($frameworkRootPath.static::$CONFIG_FILE_PATH, $frameworkDirectories);
    $frameworkConfiguration = static::getPlatformConfiguration($frameworkConfiguration, $platform);
     
    // We get the webapp default configuration
    $webappConfiguration = IniFilesUtils::getConfiguration($webapp->getRootPath().static::$CONFIG_FILE_PATH);
     
    // We get the site specific configuration
    if(!Site::isNoSite($site)) {
      $webappConfiguration = IniFilesUtils::getConfiguration($site->getRootPath().static::$CONFIG_FILE_PATH,$webappConfiguration);
    }
     
    $webappConfiguration = static::getPlatformConfiguration($webappConfiguration, $platform);

    // We build the configration
    $this->currentConfiguration = array_merge($frameworkConfiguration,$webappConfiguration);
    
    // 2. We get the current Custom Parameter
    $customParameterModuleAvailability = CustomParameterUtils::checkModuleAvailability($request);
    if($customParameterModuleAvailability["isModuleAvailable"]) {
      $customParameters = array();
      $beans = (new CustomParameter())->getBeans();
      foreach($beans as $customParameter) {
        $customParameters[$customParameter->getShortName()] = $customParameter->getValue();
      }
      $this->currentCustomParameters = $customParameters;
    }
  }

  /*
   *	function: getPlatformConfiguration
  *	This function merge the platform configuration with the common configuration and return a single configuration map.
  *
  *	parameters:
  *		- configuration - configuration with key for each platform and common one.
  *		- platform - the platform to target.
  *	return:
  *		- configuration - merged configuration map.
  */
  public static function getPlatformConfiguration($configuration, Platform $platform) {
    if(is_array($configuration)) {
      if(isset($configuration["all"])) {
        // There is possibility in config.ini to set common values to be used on every platforms.
        if(isset($platform) && isset($configuration[$platform->getName()])) {
          $configuration = array_merge($configuration["all"],$configuration[$platform->getName()]);
        }
        else {
          // We only have common configuration for all platform.
          $configuration = $configuration["all"];
        }
      }
      else if(isset($platform) && isset($configuration[$platform->getName()])) {
        // We take the platform configuration
        $configuration = $configuration[$platform->getName()];
      }
    }
    return $configuration;
  }

  /*
   *	function: getConfig
  *	This function returns the configuration value from the key in parameter. If no key provided, the current configuration map is returned.
  *
  *	parameters:
  *		- key.
  *	return:
  *		- config : the value in case the key is provided or the current configuration map.
  */
  public function getConfig($key = NULL) {
    $configuration = $this->currentConfiguration;
    if(!isset($key) || $key == "") {
      return $configuration;
    }

    if(isset($configuration) && isset($configuration[$key])) {
      return $configuration[$key];
    }
    return NULL;
  }

  /*
   *	function: getConfigAsBoolean
  *	This function is a wrapper of the getConfig function but returns true if the config is a boolean set to true.
  *
  *	parameters:
  *		- key.
  *	return:
  *		- boolean.
  */
  public function getConfigAsBoolean($key) {
    $value = $this->getConfig($key);
    if(isset($value) && is_bool($value)) {
      return $value;
    }
    return false;
  }
  
  /*
   *	function: getCustomParameter
   *  This function returns the Custom Parameter value from the key in parameter.
   *  If no key provided, the current Custom Parameter map is returned.
   *
   *	parameters:
   *		- key.
   *	return:
   *		- config : the value in case the key is provided or the current custom parameter map.
   *    - default : the default value to return if no custom parameter is defined.
   */
  public function getCustomParameter($key, $default = null) {
    $configuration = $this->currentCustomParameters;
    if(!isset($key) || $key == "") {
      return $configuration;
    }
    
    if(isset($configuration) && isset($configuration[$key])) {
      return $configuration[$key];
    }
    return $default;
  }

  /*
   *	function: getCustomParameterAsBoolean
   *  This function is a wrapper of the getCustomParameter function but returns true if the value is a boolean set to true..
   *
   *	parameters:
   *		- key.
   *	return:
   *		- value : the value in case the key is provided or the current custom parameter map.
   *    - default : the default value to return if no custom parameter is defined.
   */
  public function getCustomParameterAsBoolean($key, boolean $default = null) {
    $value = $this->getCustomParameter($key);
    if(!isset($value)) {
      $value = $default;
    }
    else if(!is_bool($value)) {
      $value = false;
    }
    return $value;
  }

  /*
   *	function: getCustomParameterFromJSON
   *  This function is a wrapper of the getCustomParameter function but returns an object built from JSON value.
   *
   *	parameters:
   *		- key.
   *	return:
   *		- value : the value in case the key is provided or the current custom parameter map.
   *    - default : the default value to return if no custom parameter is defined.
   */
  public function getCustomParameterFromJSON($key, $default = null) {
    $value = $this->getCustomParameter($key);
    if(isset($value)) {
      $value = JSONUtils::getObjectFromJSON($value);
    }
    else {
      $value = $default;
    }
    return $value;
  }
}
?>
