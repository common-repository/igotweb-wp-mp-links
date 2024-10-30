<?php

/**
 *	Class: LanguageManager
 *	Version: 0.2
 *	This class handle the different languages and localization.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PropertiesFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class LanguageManager {

	public static $SUPPORTED_LANGUAGES;
	public static $LOCALES = array("fr" => "fr_FR","en" => "en_GB");

	private $siteContext;

	/*
	 *	Common resources are retrieved from:
	 *		- framework : language file.
	 *		- webapp : language file.
	 *		- site : language file.
	 */
	private $commonResources;

	/*
	 *	Page resources are retrieved from:
	 *		- webapp : page specific language file.
	 *		- site : page specific language file.
	 */
	private $pageResources;
  
  /*
	 *	Template resources are retrieved from:
	 *		- framework : template specific language file.
	 *		- webapp : template specific language file.
	 *		- site : template specific language file.
   *  This attribute is a map with key = the template name and value = map of resources.
	 */
	private $templateResources;
  
  private $templateContext; // If set, the value is an array with all current template name from the first generic one to the last included sub template

	/*
	 *	The current language used for localization.
	 */
	private $languageCode;

	/*
	 * This attribute store the available languages (supported and available)
	 */
	private $availableLanguages;

	/*
	 *  The resource file type (php or properties)
	 */
	private $resourceFileType;

	function __construct(Request $request) {
	  $this->siteContext = $request->getSiteContext();
	  $this->resourceFileType = $request->getConfig("fwk-resourceFileType");
    $this->templateContext = array();
	  
	  if(!isset(self::$SUPPORTED_LANGUAGES)) {
	    self::$SUPPORTED_LANGUAGES = $request->getConfig("supportedLanguages");
	  }
	}

	public function getLanguageCode() {
	  return $this->languageCode;
  }
  
  /*
	 * This method switch language and locale based on new language Code.
	 */
  public function switchLanguage($languageCode) {
    global $request;
    $logger = Logger::getInstance();
    
    // No need to update language code if already the correct one.
    if($this->languageCode === $languageCode) {
      return;
    }

    $this->languageCode = $languageCode;
    $request->sessionPutElement("language",$languageCode);
    $internalLocale = $this->getLocale();
    $locale = setlocale(LC_ALL,$internalLocale);
    $logger->addLog("LanguageManager->checkLanguageToDisplay: local to set: ".$internalLocale.", php locale: ".setlocale(LC_ALL, 0));
  }

	/*
	 * This method return a structured array with all information about current language.
	 */
	public function getLanguage($languageCode = NULL) {
	  if($languageCode == NULL) {
	    $languageCode = $this->languageCode;
	  }
	  return array(
	      "code" => $languageCode,
	      "label" => $this->getLanguageLabel($languageCode)
	  );
	}

	/*
	 * This method returns a list of structured map will all information about all supported
	 * and available languages.
	 */
	public function getAvailableLanguages() {
	  if(isset($this->availableLanguages)) {
	    return $this->availableLanguages;
	  }

	  $availableLanguages = array();
	  foreach(self::$SUPPORTED_LANGUAGES as $languageCode) {
	    if($this->isSupportedLanguage($languageCode)) {
	      $availableLanguages[] = $this->getLanguage($languageCode);
	    }
	  }
	  $this->availableLanguages = $availableLanguages;
	  return $this->availableLanguages;
	}

	public function getResourceFileType() {
	  return $this->resourceFileType;
	}

	/*
	 *  function: getLanguageLabel
	 *  This function get the language in parameter label.
	 */
	public function getLanguageLabel($language = NULL) {
	  if($language == NULL) {
	    $language = $this->languageCode;
	  }
	  if($this->resourceFileType == "php") {
	    return $this->getStringFromList("languages",$language);
	  }
	  else {
	    return $this->getString("languages.".$language);
	  }
	}

	/*
	 *	function: checkLanguageToDisplay
	 *	This function checks which language to use based on:
	 *		- language request parameter.
	 *		- session stored language.
	 *		- default browser language.
	 *	It stores the language within session and update the locale when found.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- the language if found.
	 */
	public function checkLanguageToDisplay() {
		$logger = Logger::getInstance();
		global $request;

		// default language
		$language = NULL;

		// First we look in the request
		$requestLanguage = HttpRequestUtils::getParam("language");
		if($requestLanguage != "") {
      if($this->isSupportedLanguage($requestLanguage)) {
        $language = $requestLanguage;
        $logger->addLog("LanguageManager->checkLanguageToDisplay: language found in request (".$requestLanguage.")");
      }
      else {
        $logger->addLog("LanguageManager->checkLanguageToDisplay: language found in request but not supported(".$requestLanguage.")");
      }
		}

		// We look in session if not in the request
		if(!isset($language) && $request->sessionExistsElement("language")) {
			$sessionLanguage = $request->sessionGetElement("language");
			if($sessionLanguage != "" && $this->isSupportedLanguage($sessionLanguage)) {
				$language = $sessionLanguage;
				$logger->addLog("LanguageManager->checkLanguageToDisplay: language found in session (".$language.")");
			}
		}

		// Finally we look for default browser language if still not found
		if(!isset($language) && isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
			$acceptedLanguages = explode(",",$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			for($i = 0 ; $i < count($acceptedLanguages) ; $i++) {
				$acceptedLanguage = $acceptedLanguages[$i];
				if($acceptedLanguage != "" && $this->isSupportedLanguage($acceptedLanguage)) {
					$language = $acceptedLanguage;
					$logger->addLog("LanguageManager->checkLanguageToDisplay: language found in browser accepted languages (".$language.")");
					break;
				}
			}
		}

		// If still not found, we search for the first supported language which has a file.
		if(!isset($language)) {
			for($i = 0 ; $i < count(self::$SUPPORTED_LANGUAGES) ; $i++) {
				$supportedLanguage = self::$SUPPORTED_LANGUAGES[$i];
				if($this->checkLanguageFile($supportedLanguage)) {
					$language = $supportedLanguage;
					$logger->addLog("LanguageManager->checkLanguageToDisplay: language found in supported languages (".$language.")");
					break;
				}
			}
		}

		if(isset($language) && $language != "") {
			$this->switchLanguage($language);
		}
		else {
			// There is no language and corresponding language file.
			$logger->addLog("LanguageManager->checkLanguageToDisplay: NO LANGUAGE");
		}
		return $language;
	}

	/*
	 *	function: isSupportedLanguage
	 *	This function checks if the language code is supported and available.
	 *
	 *	parameters:
	 *		- $language - the language.
	 *	return:
	 *		- boolean : true if supported.
	 */
	public function isSupportedLanguage($language) {
		$supported = false;
		if(in_array($language,self::$SUPPORTED_LANGUAGES) && $this->checkLanguageFile($language)) {
			$supported = true;
		}
		return $supported;
	}

	/*
	 *	function: checkLanguageFile
	 *	This function check if corresponding language file exists.
	 *
	 *	parameters:
	 *		- $language - the language.
	 *	return:
	 *		- boolean : true if file exists.
	 */
	public function checkLanguageFile($language) {
		global $request;
		$logger = Logger::getInstance();

		// 1. We get the path to framework language file and webapp language file.
		$frwLanguagePath = $request->getConfig("fwk-languagesDirectory").$language.".".$this->resourceFileType;
		$webappLanguagePath = $request->getApplication()->getWebapp()->getRootPath()."languages".DIRECTORY_SEPARATOR.$language.".".$this->resourceFileType;

		// 2. We check that both exist
		$isFrwLanguage = file_exists($frwLanguagePath);
		$isWebappLanguage = file_exists($webappLanguagePath);
		$isLanguage = $isFrwLanguage && $isWebappLanguage;
		if(!$isLanguage) {
			$logger->addLog("LanguageManager->checkLanguageFile: webapp language exists (".$webappLanguagePath."): ".$isWebappLanguage);
			$logger->addLog("LanguageManager->checkLanguageFile: framework language exists (".$frwLanguagePath."): ".$isFrwLanguage);
		}
		return $isLanguage;
	}

	/*
	 *	private function: getResourcesFromFile
	 *	This method get the resources from a file.
	 *
	 *	parameters:
	 *		- path - the path to resource file.
	 *	return:
	 *		- an associative array of resources.
	 */
	private function getResourcesFromFile($path) {
	  $resources = array();
	  if($this->resourceFileType == "php") {
	    global $request;

	    // We store the already defined vars
	    $previousVars = get_defined_vars();

	    // We include the file
	    require_once($path);

	    // We put all strings in global context
	    $newVars = array_diff_key(get_defined_vars(),$previousVars,array("previousVars" => "ok"));
	    $resources = $newVars;
	  }
	  else {
	    $resources = PropertiesFilesUtils::getProperties($path);
	  }
	  return $resources;
	}

	/*
	 *	function: loadCommonResources
	 *	This method loads the common resources
	 *		- framework : language file.
	 *		- webapp : language file.
	 *		- site : language file.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function loadCommonResources() {
	  global $request;
    
	  if(!isset($this->languageCode) || $this->languageCode == "") {
	    return;
    }
    
    $application = $this->siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();

	  $fwkPath = $request->getConfig("fwk-languagesDirectory").$this->languageCode.".".$this->resourceFileType;
	  $webappPath = $webapp->getRootPath()."languages".DIRECTORY_SEPARATOR.$this->languageCode.".".$this->resourceFileType;

	  $fwkProperties = $this->getResourcesFromFile($fwkPath);
	  $webappProperties = $this->getResourcesFromFile($webappPath);
	  $properties = Utils::arrayMergeRecursiveSimple($fwkProperties,$webappProperties);

	  // We include the site specific language files if exists
	  if(!Site::isNoSite($site)) {
	    $sitePath = $site->getRootPath()."languages".DIRECTORY_SEPARATOR.$this->languageCode.".".$this->resourceFileType;
	    $metaPath = $site->getRootPath()."languages".DIRECTORY_SEPARATOR."meta.".$this->resourceFileType;

	    // We get the site specific language file
	    if(file_exists($sitePath)) {
	      $metaProperties = $this->getResourcesFromFile($metaPath);
	      $siteProperties = $this->getResourcesFromFile($sitePath);
	      $properties = Utils::arrayMergeRecursiveSimple($properties, $siteProperties, $metaProperties);
	    }
	  }
	  $this->commonResources = $properties;
	}

	/*
	 *	function: loadPageResources
	 *	This function load the page resources.
	 *		- webapp : page specific language file.
	 *		- site : page specific language file.
	 *
	 *	parameters:
	 *		- pageName - the page name.
	 *	return:
	 *		- none.
	 */
	public function loadPageResources($pageName) {
	  global $request;

	  if(!isset($this->languageCode) || $this->languageCode == "") {
	    return;
	  }

	  $properties = array();

	  if(isset($pageName) && $pageName != "") {
      $application = $this->siteContext->getApplication();
      $webapp = $application->getWebapp();
      $site = $application->getSite();

	    $webappPath = $webapp->getRootPath()."languages".DIRECTORY_SEPARATOR.$this->languageCode."-".$pageName.".".$this->resourceFileType;

	    // We include the page specific language file if exists
	    if(file_exists($webappPath)) {
	      $webappProperties = $this->getResourcesFromFile($webappPath);
	      $properties = array_merge($properties, $webappProperties);
	    }
	    // We include the site specific language files if exists
	    if(!Site::isNoSite($site)) {
	      $sitePath = $site->getRootPath()."languages".DIRECTORY_SEPARATOR.$this->languageCode."-".$pageName.".".$this->resourceFileType;
	      if(file_exists($sitePath)) {
	        $siteProperties = $this->getResourcesFromFile($sitePath);
	        $properties = array_merge($properties, $siteProperties);
	      }
	    }
	  }

	  $this->pageResources = $properties;
	}
  
  /*
	 *	function: loadTemplateResources
	 *	This function load the template resources.
	 *
	 *	parameters:
	 *		- templateName - the template name.
   *    - options : options to be used for template requirement (cf. TemplateManager->requireTemplate).
	 *	return:
	 *		- none.
	 */
	public function loadTemplateResources($templateName, $options = array()) {
	  global $request;
    
	  if(!isset($templateName) || $templateName == "" || 
        !isset($this->languageCode) || $this->languageCode == "") {
	    return;
	  }
    
    // We check if the resources already exist
    if(isset($this->templateResources[$templateName])) {
      return;
    }
    
	  $properties = array();

    $templateManager = $request->getTemplateManager();
    $files = $templateManager->getTemplateResourcesFilesPath($templateName, $options);
      
    foreach ($files as $path) {
      $templateProperties = $this->getResourcesFromFile($path);
      $properties = array_merge($properties, $templateProperties);
    }

	  $this->templateResources[$templateName] = $properties;
	}
  
  /*
	 *	function: addTemplateContext
	 *	This function add a template context to the list
	 *
	 *	parameters:
	 *		- templateName - the current templateName
   *    - options : options to be used for template requirement (cf. TemplateManager->requireTemplate).
	 *	return:
	 *		- none.
	 */
  public function addTemplateContext($templateName, $options = array()) {
    // We make sure that we have the resources available for the template.
    $this->loadTemplateResources($templateName, $options);
    // We update the current context.
    $this->templateContext[] = $templateName;
  }
  
    /*
	 *	function: removeTemplateContext
	 *	This function remove the template context and all following contexts from the list.
	 *
	 *	parameters:
	 *		- templateName - the current templateName
	 *	return:
	 *		- none.
	 */
  public function removeTemplateContext($templateName) {
    $index =  array_search($templateName, $this->templateContext);
    if($index) {
      array_splice($this->templateContext, $index);
    }
  }
	
	/*
	 *	function: getJsPageResources
	 *	This function return the javascript page resources.
	 *		- webapp : js page specific language file.
	 *		- site : js page specific language file.
	 *
	 *	parameters:
	 *		- pageName - the page name.
	 *	return:
	 *		- list of resource keys.
	 */
	public function getJsPageResources($pageName) {
	  global $request;
	  
	  // This array is populated within the included files
    $listStrings = array();
    
    $application = $request->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
	  
	  $path = $webapp->getRootPath()."lib".DIRECTORY_SEPARATOR."jsStrings.php";
	  if(file_exists($path)) {
	    include($path);
	  }
	  
	  if(!Site::isNoSite($site)) {
	    $sitePath = $site->getRootPath()."lib".DIRECTORY_SEPARATOR."jsStrings.php";
	    if(file_exists($sitePath)) {
	      include($sitePath);
	    }
	  }
	  
	  return $listStrings;
	}

	/*
	 *	function: getLocale
	 *	This function get the locale associated to current language.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- the current locale.
	 */
	public function getLocale() {
		return static::$LOCALES[$this->languageCode];
	}

	/*
	 *	function: getResource
	 *	This function get a resource loaded from the key.
	 *
	 *	parameters:
	 *		- $key - the resource key.
	 *	return:
	 *		- the resource value.
	 */
	public function getResource($key) {
	  $value = null;
    
    // We check if we have a template specific resource.
    if(isset($this->templateContext)) {
      $templateContext = array_reverse($this->templateContext);
      foreach($templateContext as $templateName) {
        if(isset($this->templateResources[$templateName]) &&
            isset($this->templateResources[$templateName][$key]) &&
            $this->templateResources[$templateName][$key] != "") {
          $value = $this->templateResources[$templateName][$key];
          break;
        }
      }
    }

	  if(isset($this->pageResources[$key]) && $this->pageResources[$key] != "") {
	    $value = $this->pageResources[$key];
	  }
	  else if(isset($this->commonResources[$key]) && $this->commonResources[$key] != "") {
	    $value = $this->commonResources[$key];
	  }

    // We decode utf-8 chars for display in html
    if($value != null && $value != "") {
      $value = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', array($this, 'replace_unicode_escape_sequence'), $value);
    }

	  return $value;
	}
	
	/*
	 * This function replace the unicode \uXXXX with the corresponding char
	 */
	private function replace_unicode_escape_sequence($match) {
	  return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
	}

	/*
	 *	function: getString
	 *	This function get a message value corresponding to a key in language file.
	 *	Parameters can be used to be replaced in the message.
	 *
	 *	parameters:
	 *		- $key - the message key.
	 *		- $params - array of params or string.
	 *	return:
	 *		- the message.
	 */
	public function getString($key,$params = "") {
	  $logger = Logger::getInstance();
	  
	  $value = $this->getResource($key);

	  if($value != NULL && $params != "") {
	    $value = LanguageManager::templateReplace($value,$params);
	  }
	  else if(!isset($value)) {
	    //1. The default value will be the key.
	    $value = static::generateNotFoundKeyValue($key);
	    $logger->addLog("LanguageManager : the resource is not found (" . $key . ")", true, false);
	  }

	  return $value;
  }
  
  /*
	 *	static function: generateNotFoundKeyValue
	 *	This function generate the value in case of not found key.
	 *
	 *	parameters:
	 *		- $key - the message key.
	 *	return:
	 *		- the not found key value.
	 */
  private static function generateNotFoundKeyValue($key) {
    return "#".$key."#";
  }

  /*
	 *	static function: isNotFoundKeyValue
	 *	This function check if the value corresponds to a not found key value.
	 *
	 *	parameters:
	 *		- $value - the value to check.
	 *	return:
	 *		- true if not found key value else false.
	 */
  public static function isNotFoundKeyValue($value) {    
    return preg_match("/^#([^ #]+)#$/i",$value);
  }

	/*
	 *	function: getStringFromList
	 *	This function get a message value corresponding to a key in language file.
	 *	The value corresponding to the key is a list.
	 *	Parameters can be used to be replaced in the message.
	 *
	 *	parameters:
	 *		- $key - the message key.
	 *		- $index - the index of the label within the list.
	 *		- $params - array of params or string.
	 *	return:
	 *		- the message.
	 */
	public function getStringFromList($key, $index, $params = "") {
	  $value = $this->getResource($key);

	  if(is_array($value) && isset($value[$index])) {
	    $value = $value[$index];
	  }
	  else {
	    $value = NULL;
	  }

	  if($value != NULL && $params != "") {
	    $value = static::templateReplace($value,$params);
	  }
	  else if(!isset($value)) {
	    //1. The default value will be the key.
	    $value = "#".$key."[".$index."]#";
	  }

	  return $value;
	}

	/*
	 *	static function: templateReplace
	 *	This function returns the message generated from template with
	 *	parameters.
	 *
	 *	parameters:
	 *		- $template- the template.
	 *		- $params - array of params or string.
	 *	return:
	 *		- the message.
	 */
	public static function templateReplace($template, $params) {
		// In case the resource is an array and params is the index or the key
		if(is_array($template) && is_int($params) && count($template) > $params) {
			return $template[$params];
		}
		else if(is_array($template) && is_string($params) && isset($template[$params])) {
			return $template[$params];
		}

		// We generate the template
		if(is_int($params) || is_float($params)) {
			$params = strval($params);
		}
		if(!is_array($params) && is_string($params) && $params != "") {
			$params = array($params);
		}
		if(is_array($params)) {
			for($i = 0 ; $i < count($params) ; $i++) {
				$param = $params[$i];
				$template = str_replace("{".$i."}",$param,$template);
			}
		}

		return $template;
	}
}
?>
