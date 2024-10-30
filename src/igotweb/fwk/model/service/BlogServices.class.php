<?php
/**
 *	Class: BlogServices
 *	This class handle the services linked to generic Blog object.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class BlogServices {

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
    // We log the fact that we are in default blog service.
    $error = Error::getGeneric("Generic Blog Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on blog
    return $error;
  }
  
}
?>