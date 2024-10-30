<?php
/**
 *	Class: DropboxServices
 *	This class handle the services linked to Dropbox. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DropboxUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class DropboxServices {
  
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
    /*
    $error = Error::getGeneric("Generic Dropbox Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on blog
    return $error;
    */
  }

  /*
   *	action: listFolder
   *	This action is called to get the list of content in a Folder.
   *
   *	parameters:
   *		- $path : the target path.
   */
  public static function listFolder(Request $request) {

    $path = HttpRequestUtils::getParam("path");

    $dropboxUtils = DropboxUtils::getInstance($request);

    $result = $dropboxUtils->listFolder($path);
    if($result instanceof Error) {
      $request->addError($result);
    }
    else {
      $request->dmOutAddElement("listFolder",$result);
    }
  }

  /*
   *	action: uploadFile
   *	This action is called to upload a file.
   *
   *	parameters:
   *		- $path : the target path (folder).
   *    - $file : the file
   */
  public static function uploadFile(Request $request) {

    $path = HttpRequestUtils::getParam("path");
    $file = HttpRequestUtils::getFileParam("file");
    if($file instanceof Error) {
      $request->addError($file);
    }

    $dropboxUtils = DropboxUtils::getInstance($request);

    $result = $dropboxUtils->uploadFile($file, $path);
    if($result instanceof Error) {
      $request->addError($result);
    }
    else {
      $request->dmOutAddElement("file", $result);
    }
  }
  

  
}
?>