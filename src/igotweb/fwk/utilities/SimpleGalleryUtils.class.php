<?php
/**
 *	Class: SimpleGalleryUtils
 *	This class handle the SimpleGallery utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SimpleGallery;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Photo;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class SimpleGalleryUtils extends GenericBeanUtils {
  
  private function __construct() {}

  /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'igotweb\fwk\model\bean\SimpleGallery';
  }
  
  /*
   *  function: getRootPath
   *  This function returns the simple galleries root path for current context.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - The simple galleries root path.
   */
  public static function getRootPath(Request $request) {
    $siteContext = $request->getSiteContext();
    $path = $siteContext->getContentPath() . $request->getConfig("simpleGalleryRootPath");
    return FileUtils::cleanPath($path);
  }
  
    /*
   *  function: getAbsoluteStaticPath
   *  This function returns the simple galleries absolute static path for current context.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - The simple galleries absolute static path.
   */
  public static function getAbsoluteStaticPath(Request $request) {
    // We populate the absolute static path
    $siteContext = $request->getSiteContext();
    $path = UrlUtils::getSiteURL(UrlUtils::cleanPath($siteContext->getStaticContentPath() . $request->getConfig("simpleGalleryRootPath")), $request);
    return $path;
  }
  
  /*
   *  function: getContentToLoad
   *  This action is called to get the web content to load.
   *  It returns a map with admin directory (key) and active directory (value).
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - content : map with admin directory (key) and active directory (value).
   */
  public static function getContentToLoad(Request $request) {
    $content = array();
    
    // 1. We get the list of tables for Folder service
    $content = Utils::arrayMergeRecursiveSimple($content,FolderUtils::getContentToLoad($request));
    
    // 2. We get the list of tables for Photo service
    $content = Utils::arrayMergeRecursiveSimple($content,PhotoUtils::getContentToLoad($request));
    
    // 3. We add the simple gallery content to the list
    $contentAdminPath = static::getRootPath($request);
    $request->getSiteContext()->setAdminMode(false);
    $contentActivePath = static::getRootPath($request);
    $request->getSiteContext()->setAdminMode(true);
    $content[$contentAdminPath] = $contentActivePath;
    
    return $content;
  }
  
  /*
   *  function: getListTablesToLoad
   *  This action is called to get the list of tables to be loaded.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - tables : array with list of table names to load.
   */
  public static function getListTablesToLoad(Request $request) {
    $tables = array();
  
    // 1. We get the list of tables for Folder service
    $tables = array_merge($tables, FolderUtils::getListTablesToLoad($request));
  
    // 2. We get the list of tables for Photo service
    $tables = array_merge($tables, PhotoUtils::getListTablesToLoad($request));
  
    // 3. We add the simple gallery table to the list
    $tables = array_merge($tables, parent::getListTablesToLoad($request));
  
    return array_unique($tables);
  }

  /*
   *  function: checkModuleAvailability
   *  This function check if the module is available for current context.
   *
   *  parameters:
   *    @param Request $request The request object.
   *  return:
   *    @return result object or error object.
   * 
   *  result object:
   *    isModuleAvailable : boolean true if available.
   *    unavailablePaths : the list of unavailable paths.
   *    tables : list of GenericBean::isAvailableInDB output.
   */
  public static function checkModuleAvailability(Request $request) {
    $logger = Logger::getInstance();

    $result = static::$MODULE_AVAILABILITY_RESULT;

    // 1. We check that Folder module is available
    $folderModuleAvailability = FolderUtils::checkModuleAvailability($request);
    if($folderModuleAvailability instanceof Error) {
      return $folderModuleAvailability;
    }
    $result = static::mergeModuleAvailabilityResults($result, $folderModuleAvailability);
    
    // 2. We check that Photo module is available
    $photoModuleAvailability = PhotoUtils::checkModuleAvailability($request);
    if($photoModuleAvailability instanceof Error) {
      return $photoModuleAvailability;
    }
    $result = static::mergeModuleAvailabilityResults($result, $photoModuleAvailability);

    // 3. We check the root path
    $rootPath = static::getRootPath($request);
    if(!is_dir($rootPath)) {
      $logger->addLog("SimpleGalleryUtils::checkModuleAvailability - the simple gallery root path does not exists (" . $rootPath . ").");
      $result["unavailablePaths"][] = $rootPath;
      $result["isModuleAvailable"] = false;
    }
    
    // 4. We check the generic part
    $genericAvailability = parent::checkModuleAvailability($request);
    if($genericAvailability instanceof Error) {
      return $genericAvailability;
    }
    $result = static::mergeModuleAvailabilityResults($result, $genericAvailability);
    
    return $result;
  }
  
  /*
   *  function: backupDirectory
   *  This function backups the gallery directory and copy it in a new folder.
   *
   *  parameters:
   *    - gallery - the SimpleGallery object.
   *  return:
   *    - "ok" if backup or Error object.
   */
  public static function backupDirectory(SimpleGallery $gallery) {
    $absolutePath = $gallery->getAbsolutePath();
    $backupPath = $gallery->getBackupPath();
    $result = FileUtils::copyDirectory($absolutePath, $backupPath);
    return $result;
  }
  
  /*
   *  function: checkDirectories
  *  This function checks the directories for simple gallery.
  *  The original directory is mandatory. The other are created if not already existing.
  *
  *  parameters:
  *    - simpleGallery - the simple gallery object.
  *  return:
  *    - "ok" if all existing or Error object.
  */
  public static function checkDirectories(SimpleGallery $simpleGallery) {
    // We get the simple gallery path
    $path = $simpleGallery->getPath();
    if(!isset($path) || $path == "") {
      return new Error(7500,1);
    }
    
    // We get the static absolute path for the simple gallery
    $staticPath = $simpleGallery->getAbsolutePath();
    
    if(!is_dir($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY)) {
      $created = FileUtils::createDirectory($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY);
      if($created instanceof Error) {
        return $created;
      }
    }
    
    if(!is_dir($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$POPUP_DIRECTORY)) {
      $created = FileUtils::createDirectory($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$POPUP_DIRECTORY);
      if($created instanceof Error) {
        return $created;
      }
    }
    
    if(!is_dir($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$THUMBNAILS_DIRECTORY)) {
      $created = FileUtils::createDirectory($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$THUMBNAILS_DIRECTORY);
      if($created instanceof Error) {
        return $created;
      }
    }
    
    return "ok";
  }
  
  /*
   *  function: generatePhotoOtherSizes
  *  This function generates the other sized (popup, thumbnail) photo from a photo linked to
  *  a simple gallery. The original photo must already exists.
  *
  *  parameters:
  *    - $simpleGallery - the Simple Gallery instance.
  *    - $photo - the Photo instance linked to the simple gallery.
  *  return:
  *    - "ok" if generated or Error object.
  */
  public static function generatePhotoOtherSizes(SimpleGallery $simpleGallery, Photo $photo) {
    // We check that the photo is associated to the simple gallery.
    if($simpleGallery->getIdFolder() !== $photo->getIdFolder()) {
      $error = Error::getGeneric("SimpleGalleryService::generatePhotoOtherSizes - the photo is not associated to the simple gallery. (photo:" . $photo->getIdFolder() . ", simplegallery:" . $simpleGallery->getIdFolder() . ")");
      return $error;  
    }
    
    // We check the directories
    static::checkDirectories($simpleGallery);
    
    $additionnalSizeInOctets = 0;
    
    // We create the other size pictures
    $staticPath = $simpleGallery->getAbsolutePath();
      // popup size
    $popupPath = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$POPUP_DIRECTORY.DIRECTORY_SEPARATOR.$photo->getName();
    $resized = ImgUtils::resize($photo->getCompletePath(), $popupPath, 800, NULL);
    if($resized instanceof Error) {
      return $resized;
    }
    $additionnalSizeInOctets += FileUtils::getFileSizeInOctets($popupPath);
      // thumbnail size
    $thumbnailPath = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$THUMBNAILS_DIRECTORY.DIRECTORY_SEPARATOR.$photo->getName();
    $resized = ImgUtils::resize($photo->getCompletePath(),$thumbnailPath, 220, 220);
    if($resized instanceof Error) {
      return $resized;
    }
    $additionnalSizeInOctets += FileUtils::getFileSizeInOctets($thumbnailPath);

    // 6. We update the additionnal size
    $photo->setAdditionnalSizeInOctets($additionnalSizeInOctets);
    
    return "ok";
  }
  
  /*
   *  function: removePhotoAllSizes
   *  This function remove all the files linked to the photo object.
   *
   *  parameters:
   *    - $simpleGallery - the Simple Gallery instance.
   *    - $photo - the Photo instance linked to the simple gallery.
   *  return:
   *    - "ok" if removed or Error object.
   */
  public static function removePhotoAllSizes(SimpleGallery $simpleGallery, Photo $photo) {
    // We check that the photo is associated to the simple gallery.
    if($simpleGallery->getIdFolder() !== $photo->getIdFolder()) {
      $error = Error::getGeneric("SimpleGalleryService::removePhotoAllSizes - the photo is not associated to the simple gallery. (photo:" . $photo->getIdFolder() . ", simplegallery:" . $simpleGallery->getIdFolder() . ")");
      return $error;  
    }
    
    // We check the directories
    static::checkDirectories($simpleGallery);
    
    // We remove all size pictures
    $staticPath = $simpleGallery->getAbsolutePath();
    
      // original size
    $originalPath = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY.DIRECTORY_SEPARATOR.$photo->getName();
    $removed = FileUtils::removeFile($originalPath);
    if($removed instanceof Error) {
      return $removed;
    }

      // popup size
    $popupPath = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$POPUP_DIRECTORY.DIRECTORY_SEPARATOR.$photo->getName();
    $removed = FileUtils::removeFile($popupPath);
    if($removed instanceof Error) {
      return $removed;
    }

      // thumbnail size
    $thumbnailPath = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$THUMBNAILS_DIRECTORY.DIRECTORY_SEPARATOR.$photo->getName();
    $removed = FileUtils::removeFile($thumbnailPath);
    if($removed instanceof Error) {
      return $removed;
    }

    // 6. We update the additionnal size
    $photo->setAdditionnalSizeInOctets(0);
    
    return "ok";
  }

  /*
   *  function: generatePhotos
   *  This function generates all the content (photos, thumbnails...) and associated Photos in DB.
   *  The path provided in parameter is the relative path from the simpleGalleryRootPath.
   *  It uses the list of photos in original directory and the Photos already in DB for this path.
   *
   *  parameters:
   *    - path - the SimpleGallery path.
   *  return:
   *    - "ok" if found or Error object.
   */
  public static function generatePhotos(SimpleGallery $simpleGallery) {
    global $suffix;
    global $request;
    
    // We check the directories
    static::checkDirectories($simpleGallery);
    
    // We create the other size pictures
    $staticPath = $simpleGallery->getAbsolutePath();
      // popup size
    $resized = ImgUtils::resize($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY,$staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$POPUP_DIRECTORY, 800, NULL);
    if($resized instanceof Error) {
      return $resized;
    }
      // thumbnail size
    $resized = ImgUtils::resize($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY,$staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$THUMBNAILS_DIRECTORY, 220, 220);
    if($resized instanceof Error) {
      return $resized;
    }

    // We remove the current photos in DB
    $removed = Photo::removeFromIdFolder($simpleGallery->getIdFolder());
	if($removed instanceof Error) {
      return $removed;
    }
	

    // We get the number of photos and generates the Photo in DB
    $nbPhotos = 0;
    $files = FileUtils::getFilesList($staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY);
    foreach($files as $file) {
      $path = $staticPath.DIRECTORY_SEPARATOR.SimpleGallery::$ORIGINALS_DIRECTORY.DIRECTORY_SEPARATOR.$file;
      if(!is_dir($path)) {
        $supported = ImgUtils::isSupportedPicture($path);
        if($supported == "ok") {
          // We generate the photo object in DB.
          $generated = Photo::generateFromPath($path,$simpleGallery->getIdFolder());
          if($generated instanceof Photo) {
            $nbPhotos += 1;
          }
        }
      }
    }
    
    // We update the number of photos
    $simpleGallery->setNbPhotos($nbPhotos);


    return "ok";
  }
  
  /*
   *  function: retrievePhotos
   *  This function retrieves the list of photos from a gallery.
   *  Photo information:
   *    - directory : the simple gallery path.
   *    - filname : the photo name.
   *    - idPhoto : the photo SQL id.
   *
   *  parameters:
   *    - $simpleGallery - the Simple Gallery instance.
   *  return:
   *    - list of map containing photo information or Error object.
   */
  public static function retrievePhotos(SimpleGallery $simpleGallery) {
    // We get the folder
    $idFolder = $simpleGallery->getIdFolder();
    // We get the associated photos from DB
    $photos = Photo::getFromIdFolder($idFolder);
    if($photos instanceof Error) {
      return $photos;
    }
    
    $items = array();
    foreach($photos as $indexPhoto => $photo) {
      $items[] = static::convertPhotoForSimpleGalleryDisplay($photo, $simpleGallery);
    }
    return $items;
  }
  
  /*
   *  function: convertPhotoForSimpleGalleryDisplay
   *  This function convert a photo object to keep needed information for display.
   *  Photo information:
   *    - directory : the simple gallery path.
   *    - filname : the photo name.
   *    - idPhoto : the photo SQL id.
   *
   *  parameters:
   *    - $simpleGallery - the Simple Gallery instance.
   *  return:
   *    - list of map containing photo information or Error object.
   */
  public static function convertPhotoForSimpleGalleryDisplay(Photo $photo, SimpleGallery $simpleGallery) {
	return array(
          "idPhoto" => $photo->getSQLid(),
          "fileName" => $photo->getName(),
          "directory" => $simpleGallery->getPath()
      );
  }
}

?>
