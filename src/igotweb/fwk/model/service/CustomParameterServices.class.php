<?php
/**
 *	Class: CustomParameterServices
 *	This class handle the services linked to generic CustomParameter object.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\CustomParameterUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\CustomParameter;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class CustomParameterServices {

  private function __construct() {}

  /*
   *  function: beforeAction
   *  This function is called before any action.
   *
   *  parameters:
   *    - $request - the request object.
   */
  public static function beforeAction($action, Request $request) {
    $logger = Logger::getInstance();
    // We log the fact that we are in default CustomParameterServices service.
    $error = Error::getGeneric("Generic CustomParameterServices - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on Folder
    return $error;
  }
  
  
}
?>