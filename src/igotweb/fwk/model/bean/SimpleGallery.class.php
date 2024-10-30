<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ImgUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\SimpleGalleryUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;

/**
 *	Class: SimpleGallery
 *	This class handle the SimpleGallery object.
 *  A SimpleGallery is associated to a Folder object and linked to a path in the statics which
 *  contains all associated photos. These Photo objects are associated to Folder.
 *  @BeanClass(sqlTable="simpleGalleries")
 */
class SimpleGallery extends GenericBean {

  public static $ORIGINALS_DIRECTORY = "originals";
  public static $POPUP_DIRECTORY = "popup";
  public static $THUMBNAILS_DIRECTORY = "thumbnails";
  
  public static $IMG_MAX_SIZE = 3000000; /* nb of octets */

  protected $name;
  protected $description;
  protected $path; // The path to from the webRootPath/simpleGalleryRootPath to the gallery's folders.
  protected $idFolder; // The associated folder
  protected $nbPhotos; // The number of photos.
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $photos; // Array of associated photos (map containing photos information cf. SimpleGalleryUtils::retrievePhotos).

  /*
   *  Constructor
   *  It creates an SimpleGallery with no SQLid.
   *
   *  parameters:
   *    - name : the gallery name.
   *  return:
   *    - SimpleGallery object.
   */
  public function __construct() {
    parent::__construct();

    $this->name = "";
    $this->description = "";
    $this->path = "";
    $this->idFolder = NULL;
    $this->nbPhotos = 0;
    $this->photos = array();
  }

  /*
	 * getTable
	 * This method returns the table object associated to the bean.
	 */
	public function getTable() {
    $table = new Table(static::getTableName());
    $table->addColumn(Column::idSimpleGallery("idSimpleGallery"));
    $table->addColumn(new Column("idFolder","int(5)"));
    $table->addColumn(new Column("name","varchar(20)"));
    $table->addColumn(Column::getTextColumn("description"));
    $table->addColumn(new Column("path","varchar(200)"));
    $table->addColumn(new Column("nbPhotos","int(5)"));
    return $table;
  }

  /*
   *  function: getAbsolutePath
   *  This function returns the absolute path to the gallery.
   *
   *  parameters:
   *    - none.
   *  return:
   *    - The absolute path.
   */
  public function getAbsolutePath() {
    global $request;
    return SimpleGalleryUtils::getRootPath($request) . DIRECTORY_SEPARATOR . $this->path;
  }

  /*
   *  function: getBackupPath
   *  This function returns the absolute path to backup the gallery.
   *
   *  parameters:
   *    - none.
   *  return:
   *    - The absolute backup path.
   */
  public function getBackupPath() {
    global $request;
    return SimpleGalleryUtils::getRootPath($request) . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $this->path;
  }

  /*
   *  static function: getGalleries
   *  This function get all SimpleGallery from DB.
   *
   *  parameters:
   *    - none.
   *  return:
   *    - An array of SimpleGallery if found else an Error object.
   */
  public static function getGalleries() {
    $gallery = new SimpleGallery();
    $galleries = $gallery->getBeans();
    return $galleries;
  }

  /*
   *  static function: getFromPath
   *  This function get a SimpleGallery from DB with path.
   *
   *  parameters:
   *    - path : the SimpleGallery path.
   *  return:
   *    - A SimpleGallery objects if found  or NULL if not found or an Error object in case of issue.
   */
  public static function getFromPath($path) {
    $className = ucfirst(get_called_class());
    $gallery = new $className();
    $galleries = $gallery->getBeans("`path`='".$path."'");
	
	if($galleries instanceof Error) {
        return $galleries;
    }
	
	if(count($galleries) < 1) {
		return NULL;
	}
    
    return $galleries[0];
  }
  
   /*
   *  static function: getFromName
   *  This function get a SimpleGallery from DB with name.
   *
   *  parameters:
   *    - name : the SimpleGallery name.
   *  return:
   *    - A SimpleGallery objects if found  or NULL if not found or an Error object in case of issue.
   */
  public static function getFromName($name) {
    $className = ucfirst(get_called_class());
    $gallery = new $className();
    $galleries = $gallery->getBeans("`name`='".$name."'");
	
	if($galleries instanceof Error) {
        return $galleries;
    }
	
	if(count($galleries) < 1) {
		return NULL;
	}
    
    return $galleries[0];
  }

  /*
   *	function: createEmptyGallery
   *	This function create a root folder.
   *
   *	parameters:
   *		- $name - the gallery name.
   *		- $idFolder - the folder SQLid.
   *		- $description - the gallery description (optional).
   *	return:
   *		- SimpleGallery object if created else an Error object.
   */
  public static function createEmptyGallery($name, $idFolder, $description = NULL) {
    global $request;

    if($idFolder == "" || $name == "") {
      return new Error(7609,1);
    }

    // for empty galleries, we use the name with lower case and - instead of spaces
    $galleryPath = strtolower(preg_replace("/\s+/", "-", $name));

    // We check that the directory does not already exist
    $absolutePath = SimpleGalleryUtils::getRootPath($request) . DIRECTORY_SEPARATOR . $galleryPath;
    if(file_exists($absolutePath)) {
      return new Error(7641,1);
    }

    // We need to check if there is no gallery already existing for this path
    $existings = static::getFromPath($galleryPath);
    if($existings instanceof Error) {
      return $existings;
    }

    if(count($existings) > 0) {
      return new Error(7641,2);
    }

    // 1. We create the instance.
    $className = ucfirst(get_called_class());
    $gallery = new $className();
    $gallery->setName($name);
    $gallery->setDescription($description);
    $gallery->setPath($galleryPath);
    $gallery->setIdFolder($idFolder);
    $result = $gallery->storeInDB();
    if($result instanceof Error) {
      return $result;
    }

    // 2. We create the directory
    $result = FileUtils::createDirectory($absolutePath);
    if($result instanceof Error) {
      return $result;
    }
	
	// We check the directories
    $result = SimpleGalleryUtils::checkDirectories($gallery);
    if($result instanceof Error) {
      return $result;
    }

    return $gallery;
  }

  /*
   *	function: remove
   *	This function removes the gallery.
   *
   *	parameters:
   *		- none.
   *	return:
   *		- "ok" if removed else an Error object.
   */
  public function remove() {
    // 1. We rename the directory for backup
    $absolutePath = $this->getAbsolutePath();
    $result = SimpleGalleryUtils::backupDirectory($this);
    if($result instanceof Error) {
      return $result;
    }
	
    // 2. We remove the directory
    $result = FileUtils::removeDirectory($absolutePath);
    if($result instanceof Error) {
      return $result;
    }
	
	// 3. We remove the photos associated to the gallery from DB.
    $removed = Photo::removeFromIdFolder($this->getIdFolder());
    if($removed instanceof Error) {
      return $removed;
    }

    // 4. We remove the gallery from DB
    $result = $this->removeFromDB();
    if($result instanceof Error) {
      return $result;
    }

    return "ok";
  }

  /*
   *	function: uploadPhoto
   *	This function upload a photo for the user.
   *
   *	parameters:
   *		- $formFile - a user form file picture.
   *	return:
   *		- photo object if uploaded and thumbnail created else an Error object.
   */
  public function uploadPhoto($formFile) {
     
    // 1. We create a new Photo object with unique name
    $photo = Photo::createUniquePhoto($formFile["name"]);
    if($photo instanceof Error) {
      return $photo;
    }
    
    // We check the directories
    $result = SimpleGalleryUtils::checkDirectories($this);
    if($result instanceof Error) {
      return $result;
    }

    // 2. We upload the photo.
    $staticPath = $this->getAbsolutePath();
    $photo->setPath($staticPath.DIRECTORY_SEPARATOR.static::$ORIGINALS_DIRECTORY);
    $uploaded = ImgUtils::uploadPicture($formFile, $photo->getCompletePath(), static::$IMG_MAX_SIZE);
    if($uploaded instanceof Error) {
      return $uploaded;
    }
    
    // We associate the photo to the folder
    $photo->setIdFolder($this->getIdFolder());

    // We update the size in octets and dimensions
    $photo->updateDimensions();
    $photo->updateSize();
    
    // We create the other size photos
    $result = SimpleGalleryUtils::generatePhotoOtherSizes($this, $photo);
    if($result instanceof Error) {
      return $result;
    }

    // 7. We update the number of photos
    $this->setNbPhotos($this->getNbPhotos() + 1);
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    // 8. We store the photo in DB
    $stored = $photo->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    return $photo;
  }
  
  /*
   *	function: removePhotos
   *	This function remove a list of photos.
   *
   *	parameters:
   *		- $idPhotos - an array of photos SQL id.
   *	return:
   *		- "ok" if removed else an Error object.
   */
  public function removePhotos($idPhotos) {
    // We remove the files
    foreach($idPhotos as $idPhoto) {
      // We get the photo object
      $photo = new Photo();
      $result = $photo->getFromDB($idPhoto);
      if($result instanceof Error) {
        return $result;
      }
      
      // We remove all the photo files
      $result = SimpleGalleryUtils::removePhotoAllSizes($this, $photo);
      if($result instanceof Error) {
        return $result;
      }
      
      // We remove the photo from DB
      $result = $photo->removeFromDB();
      if($result instanceof Error) {
        return $result;
      }
    }
    
    // We update the number of photos
    $this->setNbPhotos($this->getNbPhotos() - count($idPhotos));
    
    // We store the updated gallery
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }
    
    // We update the current object
    $this->photos = SimpleGalleryUtils::retrievePhotos($this);
    
    return "ok";
  }
  
  /*
   *	function: generatePhotosOtherSizes
  *	This function generate the photos other sizes for a list of photos.
  *
  *	parameters:
  *		- $idPhotos - an array of photos SQL id.
  *	return:
  *		- "ok" if generated else an Error object.
  */
  public function generatePhotosOtherSizes($idPhotos) {
    // We get the static path
    $staticPath = $this->getAbsolutePath();
    
    // We remove the files
    foreach($idPhotos as $idPhoto) {
      // We get the photo object
      $photo = new Photo();
      $result = $photo->getFromDB($idPhoto);
      if($result instanceof Error) {
        return $result;
      }
      
      // We update the path to original photo
      $photo->setPath($staticPath.DIRECTORY_SEPARATOR.static::$ORIGINALS_DIRECTORY);
  
      // We generate the photo other sizes
      $result = SimpleGalleryUtils::generatePhotoOtherSizes($this, $photo);
      if($result instanceof Error) {
        return $result;
      }
      
      // We store the photo in DB
      $stored = $photo->storeInDB();
      if($stored instanceof Error) {
        return $stored;
      }
    }
  
    return "ok";
  }

  /*
   *   function: __call
   *   Generic getter and setter for properties.
   */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }

  public function setIdFolder($idFolder) {
    if(!isset($idFolder)) {
      $this->idFolder = NULL;
    }
    else {
      $this->idFolder = intval($idFolder);
    }
  }
  
  public function setNbPhotos($nbPhotos) {
    if(!isset($nbPhotos)) {
      $this->nbPhotos = NULL;
    }
    else {
      $this->nbPhotos = intval($nbPhotos);
    }
  }


  public function toArray() {
    $gallery = parent::toArray();
    $gallery["photos"] = $this->photos;
    return $gallery;
  }
}

?>
