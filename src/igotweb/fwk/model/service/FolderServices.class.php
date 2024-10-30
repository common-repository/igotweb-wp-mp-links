<?php
/**
 *	Class: FolderServices
 *	This class handle the services linked to generic Folder object. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use Folder;

class FolderServices {
  
  private function __construct() {}
  
  /*
   *	function: beforeAction
   *	This function is called before any action.
   *
   *	parameters:
   *		- $request - the request object.
   */
  public static function beforeAction($action, Request $request) {
    $logger = Logger::getInstance();
    // We log the fact that we are in default folder service.
    $error = Error::getGeneric("Generic Folder Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on Folder
    return $error;
  }
  
  /*
   *	action: create
   *	This action is called when the user wants to create a folder.
   *
   *	parameters:
   *		- name - the folder name.
   *		- idParentFolder - the parent folder id.
   */
  public static function create(Request $request) {
    $folderName = HttpRequestUtils::getParam("name");
    $idParentFolder = HttpRequestUtils::getParam("idParentFolder");
    
    $folder = NULL;
    if($folderName == "") {
      $request->addErrorNumber(7609);
    }
    else {
      if(!isset($idParentFolder) || $idParentFolder =="") {
        // We create a root folder
        $folder = Folder::createRootFolder($folderName);
        if($folder instanceof Error) {
          $request->addError($folder);
        }
      }
      else {
        // We create a sub folder
        $parentFolder = new Folder();
        $result = $parentFolder->getFromDB($idParentFolder);
        if($result instanceof Error) {
          $request->addError($result);
        }
        else {
          $folder = $parentFolder->createSubFolder($folderName);
          if($folder instanceof Error) {
            $request->addError($folder);
          }
        }
      }
    }
    
    if(!$request->hasError()) {
      $request->dmOutAddElement("created",true);
      $request->dmOutAddElement("folder",$folder);
    }
    else {
      $request->dmOutAddElement("created",false);
    }
  }
  
  /*
   *	action: rename
   *	This action is called when the user wants to rename a folder.
   *
   *	parameters:
   *		- name - the folder name.
   *		- idFolder - the folder id.
   */
  public static function rename(Request $request) {
    $folderName = HttpRequestUtils::getParam("name");
    $idFolder = HttpRequestUtils::getParam("idFolder");
  
    $folder = NULL;
    if($folderName == "") {
      $request->addErrorNumber(7609);
    }
    else {
      $folder = new Folder();
      $result = $folder->getFromDB($idFolder);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        $result = $folder->rename($folderName);
        if($result instanceof Error) {
          $request->addError($result);
        }
      }
    }
    
    if(!$request->hasError()) {
      $request->dmOutAddElement("updated",true);
      $request->dmOutAddElement("folder",$folder);
    }
    else {
      $request->dmOutAddElement("updated",false);
    }
  }
  
  /*
   *	action: remove
   *	This action is called when the user wants to remove a folder.
   *
   *	parameters:
   *		- idFolder - the folder SQLid.
   */
  public static function remove(Request $request) {
    $idFolder = HttpRequestUtils::getParam("idFolder");
    if($idFolder == "") {
      $request->addErrorNumber(7604);
    }
    else {
      $folder = new Folder();
      $result = $folder->getFromDB($idFolder);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        $removed = $folder->removeFromDB();
        if($removed instanceof Error) {
          $request->addError($removed);
        }
      }
    }
  
    if(!$request->hasError()) {
      $request->dmOutAddElement("removed",true);
      $request->dmOutAddElement("idFolder",$idFolder);
    }
    else {
      $request->dmOutAddElement("removed",false);
    }
  } 
}
?>