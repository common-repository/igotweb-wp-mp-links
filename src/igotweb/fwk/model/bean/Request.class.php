<?php

/**
 *	Class: Request
 *	Version: 0.2
 *	This class handle request data.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\manager\ConfigurationManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\StaticsManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\TemplateManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\DBManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\SessionManager;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\CookieManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\User;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PageUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ServiceUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SiteUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PlatformUtils;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class Request {

  private $errors;
  private $dmIn;
  private $dmOut;
  private $jsResources; // map of resources used by javascript (key, value)
  private $templates; // list of templates to be prefilled with json datas.
  private $jsonData; // JSONData included in HTML page via javascript.

  private $currentPageName;
  private $currentScriptName;
  private $customHeaders; // This array contains the list of custom headers sent to the response.

  private $siteContext; // The current site context object.  
  private $initialSiteContext; // The initial context object created by the request.
  private $previousSiteContext; // The previous context object populated on switch.

  private $configurationManager;
  private $languageManager;
  private $staticsManager;
  private $templateManager;
  private $dbManager;
  private $sessionManager;
  private $cookieManager;

  /*
   *  getRequest
   *  Static method which create the request if not exist and return it.
   */
  public static function getRequest() {
    global $request;
    if(!isset($request)) {
      $request = new Request();
      // We populate the current script name
      $request->currentScriptName = FileUtils::getFileName($_SERVER['SCRIPT_FILENAME'],false);
      // we populate the managers
      $request->populateManager();

      // We start the session
      $request->sessionManager->start();

      // We connect to the webapp/site DB
      $request->dbConnect();

      // We populate the user information
      $request->populateUser();
    }
    return $request;
  }

  private function __construct() {
    $logger = Logger::getInstance();

    // We initialize default values
    $this->errors = array();
    $this->logs = array();
    $this->jsResources = array();
    $this->dmIn = array();
    $this->dmOut = array();
    $this->templates = array();
    $this->jsonData = array();
    $this->customHeaders = array();

    // We retrieve JSON parameters if any
    HttpRequestUtils::retrieveParametersFromJSON();

    // We get the current platform object
    $platform = PlatformUtils::getCurrent();

    // We get the current application
    $application = Application::getCurrent();

    // We init the site context with platform
    $this->siteContext = new SiteContext();
    $this->siteContext->setPlatform($platform);
    $this->siteContext->setApplication($application);

    // We need to have access to framework DB
    $this->dbManager = new DBManager($this);
    $result = $this->dbConnectFwk();
    if($result instanceof Error) {
      $logger->addErrorLog($result, true, true);
      header('HTTP/1.1 404 Not Found');
      exit();
    }

    // We set the context
    $this->switchSiteContext($this->siteContext);

    // We check if we are in admin mode
    $adminSiteDomain = $this->getConfig("adminSiteDomain");
    if(isset($adminSiteDomain)){
      $currentPageDomain = UrlUtils::getCurrentPageDomain(true);
      $adminMode = $adminSiteDomain == $currentPageDomain;
      $this->siteContext->setAdminMode($adminMode);
      
      // We force to switch context again to be in admin mode
      $this->initialSiteContext = NULL;
      $this->switchSiteContext($this->siteContext);
    }

    // We check if we are in shadow mode
    $shadowSiteDomain = $this->getConfig("shadowSiteDomain");
    if(isset($shadowSiteDomain)){
      $currentPageDomain = UrlUtils::getCurrentPageDomain(true);
      $shadowMode = $shadowSiteDomain == $currentPageDomain;
      $this->siteContext->setShadowMode($shadowMode);
    }
  }

  private function populateManager() {
    // We build the Manager classes
    $this->languageManager = new LanguageManager($this);
    $this->staticsManager = new StaticsManager($this);
    $this->templateManager = new TemplateManager($this);
    $this->sessionManager = new SessionManager();
    $this->cookieManager = new CookieManager($this);
  }

  private function populateUser() {
    // We get the user
    if($this->getConfigAsBoolean("useUsers")) {
      // We check if the user already exists
      $this->user = User::getFromSession($this);
    }
    else {
      $this->user = NULL;
    }
  }

  public function redirectToURL($url) {
    header ('Location: '.$url.'');
    exit(0);
  }

  public function sendCustomHeader($type, $value) {
    $this->customHeaders[$type] = $value;
    header($type.': '.$value);
  }

  public function getCustomHeader($type) {
    return $this->customHeaders[$type];
  }

  public function getCurrentPageName() {
    return $this->currentPageName;
  }
  
  /*
   *	function: switchSiteContext
   *	This function switch the context of the request.
   *  It is also called to initialize the first site context.
   *
   *	parameters:
   *		- $siteContext: the site context object.
   *	return:
   *		- none.
   */
  public function switchSiteContext(SiteContext $siteContext) {
    // We store the initial site context
    if(!isset($this->initialSiteContext)) {
      $this->initialSiteContext = clone $siteContext;
    }
    else {
      // We store the previous site context
      $this->previousSiteContext = clone $this->siteContext;

      // We check if current context is already the good one.
      if(isset($this->siteContext) && $this->siteContext == $siteContext) {
        return;
      }
    }

    // We set the new site context
    $this->siteContext = $siteContext;
    
    // We add the webapp lib path to include path
    set_include_path(get_include_path() . PATH_SEPARATOR . $siteContext->getApplication()->getWebapp()->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR);
    // We add the site lib path to include path
    if(!Site::isNoSite($siteContext->getApplication()->getSite())) {
      set_include_path(get_include_path() . PATH_SEPARATOR . $siteContext->getApplication()->getSite()->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR);
    }
    
    // We try to connect to the corresponding DB is possible
    // dbManager is not set when switch context is called to initialize the request.
    if(isset($this->dbManager)) {
      $result = $this->dbConnect();
      if($result instanceof Error) {
        $this->addError($result);
      }
    }
    
    // We load the context configuration
    if(!isset($this->configurationManager)) {
      $this->configurationManager = new ConfigurationManager($this);
    }
    else {
      $this->configurationManager->populateCurrentConfiguration($this);
    }
  }
  
  /*
   *	function: switchInitialSiteContext
   *	This function switch back to the initial context of the request.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- none.
   */
  public function switchInitialSiteContext() {
    $this->switchSiteContext($this->initialSiteContext);
  }

  /*
   *	function: switchPreviousSiteContext
   *	This function switch back to the previous context before previous switch.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- none.
   */
  public function switchPreviousSiteContext() {
    $logger = Logger::getInstance();
    // We check if there is a previous site context
    if(!isset($this->previousSiteContext)) {
      $logger->addLog("Request->switchPreviousSiteContext : There is no previous site context.", true, false);
    }
    // We switch to previous site context
    $this->switchSiteContext($this->previousSiteContext);
    // We remove the previous site context
    unset($this->previousSiteContext);
  }

  /*
   *	function: dispatchToPage
   *	This function dispatch the request to a page.
   *
   *	parameters:
   *		- pageName - this is the page to be displayed
   *	return:
   *		- none.
   */
  public function dispatchToPage($pageName) {
    $logger = Logger::getInstance();
    global $Facebook;
    global $FacebookUser;
    global $Session;
    global $request;

    // We replace the / by . to have correct page name.
    $pageName = preg_replace("/\//", ".", $pageName);

    // We create the PageUtils object
    $PageUtils = new PageUtils($this->getSiteContext(), $pageName);
    $pagePath = $PageUtils->getPagePath();

    if($pagePath instanceof Error || !file_exists($pagePath)) {
      $siteRoot = UrlUtils::getSiteRoot($this);
      if(!empty($siteRoot)) {
        // We redirect to site root.
        $logger->addLog("Request->dispatchToPage : page (".$pageName.") not found - redirect to siteRoot", true, false);
        header ('Location: '.$siteRoot.'');
        exit();
      }
      return;
    }

    $this->currentPageName = $pageName;

    // We check the required config keys for statics
    $this->staticsManager->checkPageStaticsRequiredConfiguration($pageName);

    // We load the page strings
    $this->loadPageResources($pageName);

    // We include the page resources needed for js
    $this->requireJsResources($this->languageManager->getJsPageResources($pageName));

    // In case user is enabled, we add the user in JSONData
    if($this->getConfigAsBoolean("useUsers")) {
      $this->jsonDataAddElement("user",$this->getUser());
    }
    
    if(!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
      // This seems to happen from Apache logs
      ob_start();
      $logger->addLog("Request->dispatchToPage : HTTP_ACCEPT_ENCODING not defined. (request URI:" . $_SERVER['REQUEST_URI'] . ")", true, false);
    }
    else if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
        ob_start("ob_gzhandler");
    }
    else {
      ob_start();
    }
		// We include the page
    include($pagePath);
    ob_flush();
  }

  /*
   *	function: validateAccess
  *	This function checks if the site has restricted access and validate the access.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- "ok" if access is valid or Error object if access forbidden.
  */
  public function validateAccess() {

    if($this->getConfig("site-restrictAccess")) {
      /*

      // We keep the cookie 30 days
      $expire = time()+60*60*24*30;

      // We encrypt the login and password
      $login = CryptographUtils::encode($this->login, $this);
      $password = CryptographUtils::encode($this->password, $this);

      $this->cookiePutElement($this->getConfig("fwk-cookiePrefix")."uid",$login,$expire,"/");
      $this->cookiePutElement($this->getConfig("fwk-cookiePrefix")."upd",$password,$expire,"/");
      */
      	
      return new Error(403);
    }

    return "ok";
  }

  /*
   *	function: includePanel
  *	This method includes the panel in parameter within the current content.
  *	The panel can be at framework, webapp or site level.
  *	Use "fwk." prefix to target framework panels directly.
  *
  *	parameters:
  *		- panelName : the panel name.
  *	return:
  *		- none.
  */
  public function includePanel($panelName) {
    $logger = Logger::getInstance();
     
    $pageUtils = new PageUtils($this->getSiteContext(), $this->currentPageName);
    $panelPath = $pageUtils->getPanelPath($panelName);
    if($panelPath instanceof Error) {
      $logger->addErrorLog($panelPath, true);
    }
    else {
      include($panelPath);
    }
  }

  /*
   * isRequestForStatics
   * This method return true if the request is called for statics generation.
   */
  public function isRequestForStatics() {
    return $this->currentScriptName == "statics";
  }

  public function updateCurrentScriptName($newScriptName) {
    $this->currentScriptName = $newScriptName;
  }



  public function getLogs() {
    return $this->logs;
  }

  public function addErrorNumber($errorNumber,$secondNumber = NULL, $parameters = NULL) {
    $error = new Error($errorNumber,$secondNumber,$parameters);
    $this->addError($error);
  }
  
  public function addErrors($errors) {
    $this->errors = array_merge($this->errors, $errors);
  }

  public function addError($error) {
    array_push($this->errors,$error);
  }

  public function hasError() {
    if(count($this->errors) > 0) {
      return true;
    }
    return false;
  }

  /*
   *	function: getErrors
  *	This function get the array of errors associated to the current request.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- Array - The array of Error objects.
  */
  public function getErrors() {
    return $this->errors;
  }

  // JS RESOURCES MANAGEMENT

  public function requireJsResources($listKeys) {
    foreach($listKeys as $key) {
      $this->requireJsResource($key);
    }
  }

  public function requireJsResource($key) {
    if(!array_key_exists($key, $this->jsResources)) {
      $this->jsResources[$key] = $this->getString($key);
    }
  }

  // TEMPLATE MANAGER METHODS

  public function getTemplateManager() {
    return $this->templateManager;
  }

  public function requireTemplates($templateNames, $options = array()) {
    $this->templateManager->requireTemplates($templateNames, $options);
  }

  public function requireTemplate($templateName, $options = array()) {
    $this->templateManager->requireTemplate($templateName, $options);
  }

  public function loadRequiredTemplates() {
    $this->templateManager->loadRequiredTemplates();
  }

  public function initIncludedTemplates() {
    $this->templateManager->initIncludedTemplates();
  }

  public function getTemplateData($templateName, $params = NULL) {
    return $this->templateManager->getTemplateData($templateName, $params);
  }

  public function includeTemplate($templateName, $datas = array(), $callback = NULL) {
    $this->templateManager->includeTemplate($templateName, $datas, $callback);
  }

  // LANGUAGE MANAGER METHODS

  public function getLanguageManager() {
    return $this->languageManager;
  }

  public function getLanguageCode() {
    return $this->languageManager->getLanguageCode();
  }

  public function switchLanguage($languageCode) {
    return $this->languageManager->switchLanguage($languageCode);
  }

  public function getLanguage($languageCode = NULL) {
    return $this->languageManager->getLanguage($languageCode);
  }

  public function getAvailableLanguages() {
    return $this->languageManager->getAvailableLanguages();
  }

  public function getString($key,$params = "") {
    return $this->languageManager->getString($key, $params);
  }

  public function loadPageResources($pageName) {
    $this->languageManager->loadPageResources($pageName);
  }

  // STATICS MANAGER METHODS

  public function getStaticsManager() {
    return $this->staticsManager;
  }

  public function loadStatic($name, $scope, $type) {
    $this->staticsManager->loadStatic($name, $scope, $type);
  }

  public function includeStatic($path, $scope, $type) {
    $this->staticsManager->includeStatic($path, $scope, $type);
  }

  public function addStaticInPageConfig($path, $type) {
    $this->staticsManager->addStaticInPageConfig($this->currentPageName, $path, $type);
  }

  // SESSION MANAGER METHODS

  public function getSessionManager() {
    return $this->sessionManager;
  }

  public function sessionPutElement($key,$object) {
    $this->sessionManager->put($key, $object, $this);
  }

  public function sessionGetElement($key) {
    return $this->sessionManager->get($key, $this);
  }

  public function sessionExistsElement($key) {
    return $this->sessionManager->exists($key, $this);
  }

  public function sessionRemoveElement($key) {
    $this->sessionManager->remove($key, $this);
  }

  // COOKIE MANAGER METHODS

  public function getCookieManager() {
    return $this->cookieManager;
  }

  public function cookiePutElement($key,$object,$expire = 0, $path = "", $domain = "", $secure = false) {
    $this->cookieManager->put($key,$object,$expire,$path,$domain,$secure);
  }

  public function cookieGetElement($key) {
    return $this->cookieManager->get($key);
  }

  public function cookieExistsElement($key) {
    return $this->cookieManager->exists($key);
  }

  public function cookieRemoveElement($key) {
    $this->cookieManager->remove($key);
  }

  // DB MANAGER METHODS

  public function getDBManager() {
    return $this->dbManager;
  }

  /*
   * function: dbConnect
  * This function connects to the webapp/site DB.
  */
  public function dbConnect(SiteContext $siteContext = NULL) {
    // 1. We connect to the corresponding DB.
    return $this->dbManager->connect($siteContext, $this);
  }

  /*
   * function: dbConnectFwk
  * This function connects to the framework DB.
  */
  public function dbConnectFwk() {
    // 1. We connect to the framework DB.
    return $this->dbManager->connectFwk($this);
  }

  // CONFIGURATION MANAGER METHODS

  public function getConfigurationManager() {
    return $this->configurationManager;
  }

  public function getConfig($key = NULL) {
    return $this->getConfigurationManager()->getConfig($key);
  }

  public function getConfigAsBoolean($key) {
    return $this->getConfigurationManager()->getConfigAsBoolean($key);
  }
  
  public function getCustomParameter($key = null, $default = null) {
    return $this->getConfigurationManager()->getCustomParameter($key, $default);
  }

  public function getCustomParameterAsBoolean($key = null, $default = null) {
    return $this->getConfigurationManager()->getCustomParameterAsBoolean($key, $default);
  }

  public function getCustomParameterFromJSON($key = null, $default = null) {
    return $this->getConfigurationManager()->getCustomParameterFromJSON($key, $default);
  }

  // USER METHODS

  public function getUser() {
    return $this->user;
  }
  
  public function getSiteContext() {
    return $this->siteContext;
  }

  public function getInitialSiteContext() {
    return $this->initialSiteContext;
  }

  public function getApplication() {
    return $this->siteContext->getApplication();
  }

  public function getPlatformRootPath() {
    return $this->getPlatform()->getRootPath();
  }
  
  public function getFwkRootPath() {
    return $this->getApplication()->getFwkRootPath();
  }

  public function getFwkWebRootPath() {
    return $this->getApplication()->getFwkWebRootPath();
  }

  public function getFwkStaticPath() {
    return $this->getApplication()->getFwkStaticPath();
  }

  public function getPlatform() {
    return $this->siteContext->getPlatform();
  }

  public function getDmOut() {
    return $this->dmOut;
  }

  public function dmOutAddElement($key,$value) {
    Utils::mapPut($key,$value,$this->dmOut);
  }

  /*
   *	function: dmOutGetElement
  *	This function get an element from the dataMap - Out.
  *
  *	parameters:
  *	  - param: $key.
  *	return:
  *		- the corresponding object or NULL.
  */
  public function dmOutGetElement($key) {
    return Utils::mapGet($key,$this->dmOut);
  }

  /*
   *	function: dmOutRemoveElement
  *	This function removes a key from the dataMap - Out.
  *
  *	parameters:
  *		- $key.
  *	return:
  *		- none.
  */
  public function dmOutRemoveElement($key) {
    return Utils::mapDelete($key,$this->dmOut);
  }

  /*
   *	jsonDataAddElement
  *	This method adds element to json data.
  *	The key can be path within objects toto.tata.key
  */
  public function jsonDataAddElement($key,$value) {
    Utils::mapPut($key,$value,$this->jsonData);
  }

  /*
   *	jsonDataAddConfigKeys
  *	This method adds config elements to json data.
  *	They are stored under the config namespace (JSONData.config.key).
  */
  public function jsonDataAddConfigKeys($keys) {
    foreach ($keys as $key) {
      $value = $this->getConfig($key);
      if($key != "" && isset($value)) {
        $this->jsonDataAddElement("config.".$key, $value);
      }
    }
  }

  /*
   *	jsonDataAddTemplateData
  *	This method adds template data to json data.
  *	They are stored under the templates namespace (JSONData.templates).
  */
  public function jsonDataAddTemplateData($templateName, $templateData, $templateParameters = null) {
    // We store the template data
    $templateNamePath = preg_replace("/\./","/",$templateName);
    if($templateParameters != null && $templateParameters != "") {
      $templateNamePath .= "-" . $templateParameters;
    }
    // We convert the data into array and merge the post process data.
    $templateData = TemplateManager::mergePostProcessData($templateData);

    $this->jsonDataAddElement("templates.".$templateNamePath,$templateData);
  }

  /**
   *  public function getJSONData
   *  This methods returns the JSONData as JSON string.
   */
  public function getJSONData() {
    // We add the jsResources
    $this->jsonDataAddElement("resources",$this->jsResources);
    // We return the jsonData as JSON.
    $jsonString = JSONUtils::buildJSONObject($this->jsonData);
    return $jsonString;
  }

  /*
   *	function: generateHTMLLogs
  *	This function generate the HTML Logs if trace is in session.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- echo the HTML logs.
  */
  public function generateHTMLLogs() {
    global $Session;
    $logger = Logger::getInstance();

    if($this->getConfigAsBoolean("useUsers")) {
      $logger->addLog($this->getUser()->toJSON());
    }

    if($this->sessionExistsElement("trace")) {
      $trace = $this->sessionGetElement("trace");
      $listLogs = $logger->getListLogs();
      if($trace == "all" && count($listLogs)>0) {
        $logger->displayLogs();
      }
    }
  }

  /**
   * toJSON
   * This method transform a request output object in JSON.
   * Here are the fields
   *  - response : the dmOut object
   *  - templates : the required templates
   *  - resources : the localized resources to be used on the frontend.
   *  - errors : the list of errors if any.
   *  - logs : the list of logs if traces are activated.
   */
  public function toJSON() {
    global $Session;
    $logger = Logger::getInstance();

    $request = array();
    
    // We build the response including any post proessing.
    $request["response"] = TemplateManager::mergePostProcessData($this->getDmOut());

    $jsonRequiredTemplates = $this->getTemplateManager()->getJSONRequiredTemplates();
    if($jsonRequiredTemplates != NULL) {
      $request["templates"] = $jsonRequiredTemplates["templates"];
      $this->requireJsResources($jsonRequiredTemplates["resources"]);
    }
    
    if(count($this->jsResources)>0) {
      $request["resources"] = $this->jsResources;
    }

    $request["errors"] = $this->getErrors();

    if($this->sessionExistsElement("trace")) {
      $trace = $this->sessionGetElement("trace");
      $listLogs = $logger->getListLogs();
      if($trace == "all" && count($listLogs)>0) {
        $request["logs"] = $listLogs;
      }
    }

    $JSONRequest = JSONUtils::buildJSONObject($request);

    return $JSONRequest;
  }

}

?>
