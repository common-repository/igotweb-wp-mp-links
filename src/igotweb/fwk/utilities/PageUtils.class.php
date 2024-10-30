<?php

/**
 *	Class: PageUtils
 *	Version: 0.1
 *	This class handle page utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;

class PageUtils {

	private static $PAGE_PATH;
	private static $PANEL_PATH;
	public static $TEMPLATE_PATH;
	
	private static $DATA_FILE_EXTENSION = ".data.php";

	private $pageName;

	public static function init() {
		self::$PAGE_PATH = "pages".DIRECTORY_SEPARATOR;
		self::$PANEL_PATH = "panels".DIRECTORY_SEPARATOR;
		self::$TEMPLATE_PATH = "templates".DIRECTORY_SEPARATOR;
  }
  
  /*
   *	static function: getParametersMapFromPath
   *	This function returns a map of parameters built from URL path.
   *  id/toto/name/tata => array("id" => "toto", "name" => "tata").
   *
   *	parameters:
   *		- parametersPath - the parameters in path formatted.
   */
  public static function getParametersMapFromPath($parametersPath) {
    $parametersMap = array();
    if($parametersPath != null && $parametersPath != "") {
      $parametersSplit = explode("/", $parametersPath);
      for ($i = 0; $i < count($parametersSplit); $i+=2) {
        $key = $parametersSplit[$i];
        $value = $parametersSplit[$i + 1];
        $parametersMap[$key] = $value;
      }
    }
    return $parametersMap;
  }

	function __construct(SiteContext $siteContext, $pageName) {
    $this->siteContext = $siteContext;
		$this->pageName = $pageName;
	}

	/*
	 *	function: getItemData
	 *	This function retrieve the datas for a specific item within a root path.
	 *	It checks if there is specific data for current language or site.
	 *
	 *	parameters:
	 *		- rootPath - the root path containing the item data files.
	 *      - itemName - the item name.
	 *		- params - the parameter to be used to generate the data ($PARAMS variable accessible).
	 *      - data - the data array to populate (optional, empty array by default).
	 *	return:
	 *		- Array - an array of data.
	 */
	public function getItemData($rootPath, $itemName, $params = NULL, $data = array()) {
	  global $request;
	  global $PageUtils;
	  global $FacebookUser;

	  $PARAMS = array();
	  if(isset($params) && is_array($params)) {
	      $PARAMS = $params;
    }
    
    $application = $this->siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();

	  $language = $request->getLanguageCode();
	  $itemPath = $this->getItemPath($itemName);
    $fwkPath = $this->getFwkPath($itemName);

	  $itemDataFiles = array();

	  // 1. We check if there is non localized data for the item.
    // 1.1. We look in the framework first.
    $filePath = $application->getFwkRootPath().$rootPath.$fwkPath.static::$DATA_FILE_EXTENSION ;
	  if(file_exists($filePath)) {
	    array_push($itemDataFiles, $filePath);
	  }
	  // 1.2. We look at webapp file.
	  $filePath = $webapp->getRootPath().$rootPath.$itemPath.static::$DATA_FILE_EXTENSION;
    if(file_exists($filePath)) {
	    array_push($itemDataFiles, $filePath);
	  }
	  if(!Site::isNoSite($site)) {
	    // 1.3. We look for site specific file
	    $filePath = $site->getRootPath().$rootPath.$itemPath.static::$DATA_FILE_EXTENSION;
	    if(file_exists($filePath)) {
	      array_push($itemDataFiles, $filePath);
      }
	  }

	  // 2. We check if there is localized data for the item.
    // 2.1. We look in the framework first
    $filePath = $application->getFwkRootPath().$rootPath.$fwkPath."-".$language.static::$DATA_FILE_EXTENSION;
    if(file_exists($filePath)) {
	    array_push($itemDataFiles, $filePath);
	  }
	  // 2.2. We look at webapp file
	  $filePath = $webapp->getRootPath().$rootPath.$itemPath."-".$language.static::$DATA_FILE_EXTENSION;
    if(file_exists($filePath)) {
	    array_push($itemDataFiles, $filePath);
	  }
	  if(!Site::isNoSite($site)) {
	    // 2.3. We look for site and language specific file
	    $filePath = $site->getRootPath().$rootPath.$itemPath."-".$language.static::$DATA_FILE_EXTENSION;
	    if(file_exists($filePath)) {
	      array_push($itemDataFiles, $filePath);
	    }
	  }

	  // We build the data
	  foreach($itemDataFiles as $file) {
	    include($file);
	  }

	  return $data;
	}

	/*
	 *	getItemPath
	 *	This method get the item path based on item name.
	 *	It replaces the . by directory separators.
	 */
	public function getItemPath($itemName) {
	  $itemPath = preg_replace("/\./", DIRECTORY_SEPARATOR, $itemName);
	  return $itemPath;
	}
  
  /*
	 *	getFwkPath
	 *	This method get the item path based on item name for framework.
	 */
	public function getFwkPath($itemName) {
    // We get the generic item path
	  $itemPath = $this->getItemPath($itemName);
    // We remove the fwk prefix which allow to target framework specific items only.
    $itemPath = preg_replace("/fwk\\" . DIRECTORY_SEPARATOR . "/", "", $itemPath);
	  return $itemPath;
	}

	/*
	 *	function: getPanelData
	 *	This function retrieve the datas needed for any panel.
	 *
	 *	parameters:
	 *		- panelName : the name of the panel.
	 *       - params : map of parameters to be used to generate the panel data.
	 *	return:
	 *		- Array - an array of data.
	 */
	public function getPanelData($panelName, $params = NULL) {
	  // We get the data for the panel.
	  $data = $this->getItemData(static::$PANEL_PATH, $panelName, $params);

	  return $data;
	}
	
	/*
	 *	function: getPanelPath
	 *	This function retrieve the path for panel name in parameter.
	 *
	 *	parameters:
	 *		- $panelName : the panel name.
	 *	return:
	 *		- the path of the panel to be included.
	 */
	public function getPanelPath($panelName) {

	  $panelPath = $this->getItemPath($panelName);
    $fwkPath = $this->getFwkPath($panelName);

    $application = $this->siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();

	  // By default we look in the webapp directory
	  $path = $webapp->getRootPath().static::$PANEL_PATH.$panelPath.".php";
	  if(!Site::isNoSite($site)) {
	    // We check if there is the panel specific at site level.
	    $sitePath = $site->getRootPath().static::$PANEL_PATH.$panelPath.".php";
	    if(file_exists($sitePath)) {
	      $path = $sitePath;
	    }
	  }
	  
	  if(!file_exists($path)) {
	    // We look in the framework if it exists
	    $frameworkPath = $application->getFwkRootPath().static::$PANEL_PATH.$fwkPath.".php" ;
	    if(file_exists($frameworkPath)) {
	      $path = $frameworkPath;
	    }
	    else {
	      // We return an error if the path does not exists
	      return new Error(9451, 1, $panelName);
	    }
	  }

	  return $path;
	}

	/*
	 *	function: getPageData
	 *	This function retrieve the datas needed to display the page.
	 *
	 *	parameters:
	 *		- pageName - Can be used to override the current page.
	 *	return:
	 *		- Array - an array of data.
	 */
	public function getPageData($pageName = NULL, $params = NULL) {
	  if(!isset($pageName)) {
	    $pageName = $this->pageName;
	  }
      
	  // We get the common pages data
	  $commonPageData = $this->getItemData(static::$PAGE_PATH, "common", $params);
      
	  // We get the page specific data
	  $specificPageData = $this->getItemData(static::$PAGE_PATH, $pageName, $params);
      
	  $pageData = Utils::arrayMergeRecursiveSimple($commonPageData, $specificPageData);
      
	  return $pageData;
	}

	/*
	 *	function: getPagePath
	 *	This function retrieve the path for the current page.
	 *
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- the path of the page to be included.
	 */
	public function getPagePath() {
    
    $pageName = $this->getItemPath($this->pageName);

    $webapp = $this->siteContext->getApplication()->getWebapp();
    $site = $this->siteContext->getApplication()->getSite();

	  // By default the path is in the current webapp
	  $path = $webapp->getRootPath().static::$PAGE_PATH.$pageName.".php";
	  if(!Site::isNoSite($site)) {
	    // We check if there is the page specific at site level.
	    $sitePath = $site->getRootPath().static::$PAGE_PATH.$pageName.".php";
	    if(file_exists($sitePath)) {
	      $path = $sitePath;
	    }
	  }
	  
	  if(!file_exists($path)) {
	    // We return an error if the path does not exists
	    return new Error(9452, 1, $this->pageName);
	  }
	  return $path;
	}

}
PageUtils::init();
?>
