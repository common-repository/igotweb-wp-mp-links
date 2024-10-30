<?php
/**
 *	Class: ServiceUtils
 *	This class provide some utilities for services. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class ServiceUtils {
  
  private function __construct() {}
  
  /*
   *  function: handleAjaxService
   *  This function handle an ajax service.
   *  An ajax service is an ajax request with two post parameters:
   *    - service : The service class to be used.
   *    - action : The action of the service class to call.
   *  It generate the JSON response corresponding to the service.
   */
  public static function handleAjaxService(Request $request) {
    // We get the mandatory parameters
    $service = HttpRequestUtils::getParam("service");
    $action = HttpRequestUtils::getParam("action");
    
    if($service != "" && $action != "") {
      // We call the action from service
      $result = static::call($service, $action, $request);
      if($result instanceof Error) {
        $request->addError($result);
      }
    
      // We set the charset because of accent
      // We need to put content type text/html instead of application/json. The reason is that for form submitted in chrome, it adds a <pre> html tag.
      header('Content-Type: text/html; charset='.$request->getConfig("charset"));
      // We return the request in JSON
      echo $request->toJSON();
    }
  }
  
  /*
   *	function: call
   *	This function calls an action from a service.
   *
   *	parameters:
   *		- $service - the service name.
   *    - $action - the action name.
   *	return:
   *		- none.
   */
  protected static function call($service, $action, Request $request) {
    
    if($service != "" && $action != "") {
      // 1. We load the class
      $classPath = static::loadServiceClass($service);
      if($classPath instanceof Error) {
        return $classPath;
      }
      
      // 2. We call the beforeAction if method exists
      $beforeAction = array($classPath, "beforeAction");
      if(is_callable($beforeAction)) {
        $result = call_user_func($beforeAction, $action, $request);
        if($result instanceof Error) {
          $request->addError($result);
        }
      }
      
      // 3. We call the action
      if(!$request->hasError()) {
        $function = array($classPath, $action);
        if(is_callable($function)) {
          call_user_func($function, $request);
        }
        else {
          $error = Error::getGeneric("ServiceUtils::call - cannot call action (".$service.", ".$action.")");
          $request->addError($error);
        }
      }
    }
  }
  
  /*
   *	function: loadServiceClass
   *	This function loads a module service and return the full class path if loaded.
   *
   *	parameters:
   *		- $service - the service name.
   *    - $siteContext - specific site context if needed.
   *	return:
   *		- classPath if found and loaded else Error object.
   */
  public static function loadServiceClass($service, SiteContext $siteContext = NULL) {
    global $request;
    $logger = Logger::getInstance();
    
    if($siteContext == NULL) {
      $siteContext = $request->getSiteContext();
    }

    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
    
    if($service == "") {
      $error = Error::getGeneric("ServiceUtils::loadServiceClass - service name empty");
      $logger->addErrorLog($error, true, false);
      return $error;
    }
    
    // We store the current include path
    $currentIncludePath = get_include_path();
    
    // 1. We check at site / webapp level
    $includePath = $webapp->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR;
    if(!Site::isNoSite($site)) {
      $includePath = $site->getRootPath() . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . PATH_SEPARATOR . $includePath;
    }
    
    set_include_path($includePath);
    
    // 1. We check if we find the class
    $classPath = "service\\" . ucfirst($service) . "Services";
    if(!class_exists($classPath)) {
      $classPath = "igotweb\\fwk\\model\\service\\" . ucfirst($service) . "Services";
      if(!class_exists($classPath)) {
        $error = Error::getGeneric("ServiceUtils::loadServiceClass - service ".$service." not found");
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