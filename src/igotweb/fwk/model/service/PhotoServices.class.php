<?php
/* --------------------------------------------------------------------------------*/
/*                             PHOTOS ACTIONS	                                   */
/* --------------------------------------------------------------------------------*/
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use \Photo;

class PhotoServices {
  
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
    $error = Error::getGeneric("Generic Photo Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on Folder
    return $error;
  }

  /*
   *	action: movePhotosInFolder
   *	This action is called when the user wants to move several photos
   *	in a Folder.
   *
   *	parameters:
   *		- $idPhotos : the photo ids.
   *		- $idFolder : the destination folder.
   */
  public static function movePhotosInFolder(Request $request) {

    $idPhotos = HttpRequestUtils::getArrayParam("idPhotos");
    $idFolder = HttpRequestUtils::getParam("idFolder");

    $idPhotosMoved = array();
    $idPhotosErrors = array();

    $moved = false;

    if($idFolder == "") {
      $request->addErrorNumber(7614,1);
    }
    else if(count($idPhotos) == 0) {
      $request->addErrorNumber(7615,1);
    }
    else {
      // We get the Folder
      $folder = new Folder();
      $result = $folder->getFromDB($idFolder);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        for($i = 0 ; $i < count($idPhotos) ; $i++) {
          // We get the photo
          $photo = new Photo();
          $result = $photo->getFromDB($idPhotos[$i]);
          if($result instanceof Error) {
            $request->addError($result);
            $idPhotosErrors[] = $idPhotos[$i];
          }
          else {
            // We move the photo in folder
            $photo->setIdFolder($idFolder);
            $stored = $photo->storeInDB();
            if($stored instanceof Error) {
              $request->addError($stored);
              $idPhotosErrors[] = $idPhotos[$i];
            }
            else {
              $idPhotosMoved[] = $idPhotos[$i];
              $moved = true;
            }
          }
        }
      }
    }

    $request->dmOutAddElement("idPhotosMoved",$idPhotosMoved);
    $request->dmOutAddElement("idPhotosErrors",$idPhotosErrors);
    $request->dmOutAddElement("idFolder",$idFolder);
    $request->dmOutAddElement("moved",$moved);
  }

  /*
   *	action: removePhotos
   *	This action is called when the user wants to remove photos
   *
   *	parameters:
   *		- $idPhotos : the list of photo ids.
   */
  public static function removePhotos(Request $request) {

    $idPhotos = HttpRequestUtils::getArrayParam("idPhotos");

    $idPhotosRemoved = array();
    $idPhotosErrors = array();
    $removed = false;

    if(count($idPhotos) == 0) {
      $request->addErrorNumber(7004,4);
    }
    else {
      for($i = 0 ; $i < count($idPhotos) ; $i++) {
        // We get the photo
        $photo = new Photo();
        $result = $photo->getFromDB($idPhoto);
        if($result instanceof Error) {
          $idPhotosErrors[] = $idPhotos[$i];
          $request->addError($result);
        }
        else {
          $removed = $photo->removeFromDB();
          if($removed instanceof Error) {
            $idPhotosErrors[] = $idPhotos[$i];
            $request->addError($removed);
          }
          else {
            $idPhotosRemoved[] = $idPhotos[$i];
            $removed = true;
          }
        }
      }
    }

    if($removed) {
      /* Almost one photo has been removed */
      $request->dmOutAddElement("removed",$removed);
      $request->dmOutAddElement("newMaxPhotoSize",ini_get("upload_max_filesize"));
      $request->dmOutAddElement("idPhotosRemoved",$idPhotosRemoved);
      $request->dmOutAddElement("idPhotosErrors",$idPhotosErrors);
    }
    else {
      $request->dmOutAddElement("idPhotosErrors",$idPhotosErrors);
      $request->dmOutAddElement("removed",false);
    }
  }

  /*
   *	action: rotatePhoto
   *	This action is called when the user wants to rotate a photo.
   *
   *	parameters:
   *		- $idPhoto : the photo id.
   *		- $degrees : the degrees used to rotate the photo.
   */
  public static function rotatePhoto(Request $request) {

    $idPhoto = HttpRequestUtils::getParam("idPhoto");
    $degrees = HttpRequestUtils::getParam("degrees");

    if($idPhoto == "" || $degrees == "") {
      $request->addErrorNumber(9606,5);
    }
    else {
      // We get the photo
      $photo = new Photo();
      $result = $photo->getFromDB($idPhoto);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        // 3. We rotate the photo
        $rotated = $photo->rotate($degrees);
        if($rotated instanceof Error) {
          $request->addError($rotated);
        }
        else {
          // 5. We save the photo and profile
          $stored = $photo->storeInDB();
          if($stored instanceof Error) {
            $request->addError($stored);
          }
        }
      }
    }

    if(!$request->hasError()) {
      $request->dmOutAddElement("rotated",true);
      $request->dmOutAddElement("photo",$photo);
    }
    else {
      $request->dmOutAddElement("rotated",false);
      $request->dmOutAddElement("photo",$photo);
    }
  }

  /*
   *	action: updatePhotoDescription
   *	This action is called when the user wants to update a photo description.
   *
   *	parameters:
   *		- $idPhoto : the photo id.
   *		- $description : the photo description.
   */
  public static function updatePhotoDescription(Request $request) {
    $idPhoto = HttpRequestUtils::getParam("idPhoto");
    $description = HttpRequestUtils::getParam("description","");

    if($idPhoto == "") {
      $request->addErrorNumber(7008,1);
    }
    else {
      // We get the photo
      $photo = new Photo();
      $result = $photo->getFromDB($idPhoto);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        // We update the photo description
        $photo->setDescription($description);
        $stored = $photo->storeInDB();
        if($stored instanceof Error) {
          $request->addError($stored);
        }
        else {
          $request->dmOutAddElement("photo",$photo);
        }
      }
    }
  }
}
?>