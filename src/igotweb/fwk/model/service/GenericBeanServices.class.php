<?php
/**
 *	Class: GenericBeanServices
 *	This class handle the services linked to generic bean object.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;


class GenericBeanServices {

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
    // We log the fact that we are in default folder service.
    $error = Error::getGeneric("Generic GenericBean Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on Folder
    return $error;
  }
  
}
?>