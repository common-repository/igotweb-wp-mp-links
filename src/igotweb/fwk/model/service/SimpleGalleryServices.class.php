<?php
/**
 *	Class: SimpleGalleryServices
 *	This class handle the services linked to generic SimpleGallery object.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SimpleGalleryUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SimpleGallery;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class SimpleGalleryServices {

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
    $error = Error::getGeneric("Generic Simple Gallery Service - user not allowed by default");
    $logger->addErrorLog($error);
    // By default we prevent any action on Folder
    return $error;
  }
  
  /*
   *  action: getSimpleGallery
   *  This action is called when the user wants to retrieve a SimpleGallery.
   *
   *  parameters:
   *    - idSimpleGallery - the simpleGallery SQL id.
   *    - includePhotos - boolean, true to retrieve details about the photos (SimpleGalleryUtils::retrievePhotos).
   */
  public static function getSimpleGallery(Request $request) {
    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery");
    $includePhotos = HttpRequestUtils::getBoolParam("includePhotos");
    
    $gallery = new SimpleGallery();
    $result = $gallery->getFromDB($idSimpleGallery);
    if($result instanceof Error) {
      $request->addError($result);
    }
    else if($includePhotos) {
      $photos = SimpleGalleryUtils::retrievePhotos($gallery);
      if($photos instanceof Error) {
        $request->addError($result);
      }
      else {
        $gallery->setPhotos($photos);
      }
    }
    
    if(!$request->hasError()) {
      $request->dmOutAddElement("simpleGallery",$gallery);
    }
  }

  /*
   *  action: create
   *  This action is called when the user wants to create an empty SimpleGallery.
   *
   *  parameters:
   *    - name - the gallery name.
   *    - description - the gallery description.
   *    - idFolder - the folder SQL id.
   */
  public static function create(Request $request) {

    $galleryName = HttpRequestUtils::getParam("name");
    $galleryDescription = HttpRequestUtils::getParam("description");
    $idFolder = HttpRequestUtils::getParam("idFolder");
    
    $gallery = SimpleGallery::createEmptyGallery($galleryName, $idFolder, $galleryDescription);
    if($gallery instanceof Error) {
      $request->addError($gallery);
    }
    
    if($request->hasError()) {
      $request->dmOutAddElement("created",false);
    }
    else {
      $request->dmOutAddElement("created",true);
      $request->dmOutAddElement("gallery",$gallery);
    }
  }
  
  /*
   *  action: createFromPath
   *  This action is called when the user wants to create a SimpleGallery from an existing path.
   *
   *  parameters:
   *    - name - the gallery name.
   *    - description - the gallery description.
   *    - path - the path to photos.
   *    - idFolder - the folder SQL id.
   */
  public static function createFromPath(Request $request) {
  
    $galleryName = HttpRequestUtils::getParam("name");
    $galleryDescription = HttpRequestUtils::getParam("description");
    $galleryPath = HttpRequestUtils::getParam("path");
    $idFolder = HttpRequestUtils::getParam("idFolder");
  
    if($idFolder == "" || $galleryName == "" || $galleryPath == "") {
      $request->addErrorNumber(7609);
    }
    else {
      // We need to check if there is no gallery already existing for this path
      $existing = SimpleGallery::getFromPath($galleryPath);
      if($existing instanceof Error) {
        $request->addError($existing);
      }
      else if($existing!= NULL && $existing instanceof SimpleGallery) {
        $request->addErrorNumber(7641);
      }
      else {
        $gallery = new SimpleGallery();
        $gallery->setName($galleryName);
        $gallery->setDescription($galleryDescription);
        $gallery->setPath($galleryPath);
        $gallery->setIdFolder($idFolder);
        $generated = SimpleGalleryUtils::generatePhotos($gallery);
        if($generated instanceof Error) {
          $request->addError($generated);
        }
        else {
          $result = $gallery->storeInDB();
          if($result instanceof Error) {
            $request->addError($result);
          }
        }
      }
    }
    
    if($request->hasError()) {
      $request->dmOutAddElement("created",false);
    }
    else {
      $request->dmOutAddElement("created",true);
      $request->dmOutAddElement("gallery",$gallery);
    }
  }
  
  /*
   *	action: update
   *	This action is called when the user wants to update a simple gallery.
   *
   *	parameters:
   *	  - idSimpleGallery - the gallery SQL id.
   *      - name - the gallery name
   *      - description - the gallery description.
   *
   */
  public static function update(Request $request) {
    
    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery");
    $name = HttpRequestUtils::getParam("name");
    $description = HttpRequestUtils::getParam("description");
    
    if($idSimpleGallery == "") {
      $request->addErrorNumber(7609);
      $request->dmOutAddElement("updated",false);
    }
    else {
      $gallery = new SimpleGallery();
      $result = $gallery->getFromDB($idSimpleGallery);
      if($result instanceof Error) {
        $request->addError($result);
        $request->dmOutAddElement("updated",false);
      }
      else {
        if($name != "") {
          $gallery->setName($name);
        }
        if($description != "") {
          $gallery->setDescription($description);
        }
        $result = $gallery->storeInDB();
        if($result instanceof Error) {
          $request->addError($result);
          $request->dmOutAddElement("updated",false);
        }
        else {
          $request->dmOutAddElement("updated",true);
          $request->dmOutAddElement("simpleGallery",$gallery);
        }
      }
    }
  }

  /*
   *	action: removeSimpleGallery
   *	This action is called when the user wants to remove a gallery.
   *
   *	parameters:
   *		- idSimpleGallery - the gallery SQL id.
   */
  public static function remove(Request $request) {

    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery");

    if($idSimpleGallery == "") {
      $request->addErrorNumber(7609);
      $request->dmOutAddElement("removed",false);
    }
    else {
      $gallery = new SimpleGallery();
      $result = $gallery->getFromDB($idSimpleGallery);
      if($result instanceof Error) {
        $request->addError($result);
        $request->dmOutAddElement("removed",false);
      }
      else {
        $result = $gallery->remove();
        if($result instanceof Error) {
          $request->addError($result);
          $request->dmOutAddElement("removed",false);
        }
        else {
          $request->dmOutAddElement("removed",true);
          $request->dmOutAddElement("simpleGallery",$gallery);
        }
      }
    }
  }
  
  /*
   *	action: uploadPhoto
   *	This action is called when the user upload a photo.
   *
   *	parameters:
   *		- $idSimpleGallery : the simple gallery SQL id.
   *		- $photo : the photo uploaded file.
   */
  public static function uploadPhoto(Request $request) {

    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery",NULL);
    $photo = HttpRequestUtils::getFileParam("photo");
    if($photo instanceof Error) {
      $request->addError($photo);
    }
    else {
      $simpleGallery = new SimpleGallery();
      $result = $simpleGallery->getFromDB($idSimpleGallery);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        $photo = $simpleGallery->uploadPhoto($photo);
        if($photo instanceof Error) {
          $request->addError($photo);
        }
      }
    }
    
    if(!$request->hasError()) {
      $request->dmOutAddElement("photoUploaded",true);
      $request->dmOutAddElement("photo",SimpleGalleryUtils::convertPhotoForSimpleGalleryDisplay($photo, $simpleGallery));
    }
    else {
      $request->dmOutAddElement("photoUploaded",false);
    }
  }
  
  /*
   *	action: removePhotos
  *	This action is called when the user wants to remove photos.
  *
  *	parameters:
  *		- $idSimpleGallery : the simple gallery SQL id.
  *		- $idPhotos : the list of Photo SQL id.
  */
  public static function removePhotos(Request $request) {
  
    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery",NULL);
    $idPhotos = HttpRequestUtils::getArrayParam("idPhotos");
    
    $simpleGallery = new SimpleGallery();
    $result = $simpleGallery->getFromDB($idSimpleGallery);
    if($result instanceof Error) {
      $request->addError($result);
    }
    else {
      $result = $simpleGallery->removePhotos($idPhotos);
      if($result instanceof Error) {
        $request->addError($result);
      }
    }
  
    if(!$request->hasError()) {
      $request->dmOutAddElement("photosRemoved",true);
      $request->dmOutAddElement("simpleGallery",$simpleGallery);
    }
    else {
      $request->dmOutAddElement("photosRemoved",false);
    }
  }
  
  /*
   *	action: generatePhotosOtherSizes
  *	This action is called when the user wants to generate photos other sizes.
  *
  *	parameters:
  *		- $idSimpleGallery : the simple gallery SQL id.
  *		- $idPhotos : the list of Photo SQL id.
  */
  public static function generatePhotosOtherSizes(Request $request) {
  
    $idSimpleGallery = HttpRequestUtils::getParam("idSimpleGallery",NULL);
    $idPhotos = HttpRequestUtils::getArrayParam("idPhotos");
  
    $simpleGallery = new SimpleGallery();
    $result = $simpleGallery->getFromDB($idSimpleGallery);
    if($result instanceof Error) {
      $request->addError($result);
    }
    else {
      $result = $simpleGallery->generatePhotosOtherSizes($idPhotos);
      if($result instanceof Error) {
        $request->addError($result);
      }
    }
  
    if(!$request->hasError()) {
      $request->dmOutAddElement("photosGenerated",true);
      $request->dmOutAddElement("simpleGallery",$simpleGallery);
    }
    else {
      $request->dmOutAddElement("photosGenerated",false);
    }
  }
}
?>