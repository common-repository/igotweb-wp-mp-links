<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\ImgUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

/**
 *	Class: Photo
 *	This class handle the Photo object.
 *  A Photo is associated to a Folder object (extending Folder).
 *  @BeanClass(sqlTable="photos")
 */
class Photo extends GenericBean {
	
	public static $THUMB_SUFFIX = "_thumb";
	protected static $VERSION_SEPARATOR = "!";

	protected $name; // name of the original photo (includes extension).
	protected $creationDateTime; // The date time when the photo has been created.
	protected $lastUpdateDateTime; // The date time when the photo has been updated.
	protected $sizeInOctets; // The picture size in octets.
	protected $additionnalSizeInOctets; // The picture additionnal size in octets (thumbnails and other dimensions versions).
	protected $width; // The picture width.
	protected $height; // The picture height.
	protected $description; // Associated description
	
  protected $idFolder; // The folder sql id
  /** @BeanProperty(isExcludedFromDB=true) */
	protected $folder; // The Folder object
  
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $path; // absolute path to original photo (without name). It is handled by the class who creates the photo.

	
	/*
	 *	Constructor
	 *	It creates an Photo with no SQLid.
	 *	
	 *	parameters:
	 *		- name - the photo file name.
	 *		- idProfile - the associated profile id.
	 *	return:
	 *		- Photo object.
	 */	
	public function __construct() {
		$this->SQLid = NULL;
		$this->name = "";
		$this->path = NULL;
		$this->creationDateTime = NULL;
		$this->lastUpdateDateTime = NULL;
		$this->sizeInOctets = 0;
		$this->additionnalSizeInOctets = 0;
		$this->width = 0;
		$this->height = 0;
		
		$this->description = "";
		
		$this->idFolder = NULL;
		$this->folder = NULL;
	}
	
	/*
	 * getCreateTableQuery
	 * This method returns the query to create the table in DB.
	 */
	public function getCreateTableQuery() {
	  $query = "CREATE TABLE `" . static::getTableName() . "` (";
	  $query .= "`idPhoto` int(11) NOT NULL AUTO_INCREMENT,";
	  $query .= "`name` varchar(20) COLLATE utf8_bin NOT NULL,";
	  $query .= "`description` text COLLATE utf8_bin NOT NULL,";
	  $query .= "`sizeInOctets` int(8) NOT NULL DEFAULT '0',";
	  $query .= "`additionnalSizeInOctets` int(11) NOT NULL DEFAULT '0',";
	  $query .= "`width` int(5) NOT NULL,";
	  $query .= "`height` int(5) NOT NULL,";
	  $query .= "`creationDateTime` datetime NOT NULL,";
	  $query .= "`lastUpdateDateTime` datetime NOT NULL,";
	  $query .= "`idFolder` int(5) DEFAULT NULL,";
	  $query .= "PRIMARY KEY (`idPhoto`)";
	  $query .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0;";

	  return $query;
	}
	
	/*
	 *	function: storeInDB
	 *	This function store the Photo in DB.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- ok if stored else an Error object.
	 */	
	public function storeInDB() {
		
		if(!isset($this->creationDateTime) || !isset($this->lastUpdateDateTime)) {
			// The creation dateTime has to be set before we save.
			return new Error(7006,1);	
		}
		
		return parent::storeInDB();
	}

	/*
	 *	static function: getPhotos
	 *	This function get all Photo from DB.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- An array of Photo if found else an Error object.
	 */	
	public static function getPhotos() {
		$photo = new Photo();
		return $photo->getBeans();
	}
	
	/*
	 *	static function: getFromIdFolder
	 *	This function get all Photos from DB linked with idFolder.
	 *	
	 *	parameters:
	 *		- idFolder : the folder SQL id.
	 *	return:
	 *		- An array of Photo if found else an Error object.
	 */	
	public static function getFromIdFolder($idFolder) {
		
		// 1. We check the params
		$params = "`idFolder`=".$idFolder;
		
		// 2. We get the beans
		$className = ucfirst(get_called_class());
		$photo = new $className();
		return $photo->getBeans($params);
	}
	
	/*
	 *	function: removeFromIdFolder
	 *	This function try to remove the Photo from the DB with idFolder.
	 *	
	 *	parameters:
	 *		- $idFolder - the Folder SQL id.
	 *	return:
	 *		- "ok" if deleted else an Error object.
	 */
	public static function removeFromIdFolder($idFolder) {
		
		// 1. We check the params
		$params = "`idFolder`=".$idFolder;
		
		// 2. We get the beans
		$className = ucfirst(get_called_class());
		$photo = new $className();
		return $photo->removeBeansFromDB($params);
	}
	
	/*
	 *	static function: createUniquePhoto
	*	This function generates a new Photo object with unique name.
	*	The object is not already saved in DB.
	*
	*	parameters:
	*		- originalName - the original file name.
	*	return:
	*		- The Photo object or an Error object.
	*/
	public static function createUniquePhoto($originalName) {
	  $logger = Logger::getInstance();
	
	  // 1. We get the last id of photo created before.
	  $queryGetMaxId = "SELECT MAX(`idPhoto`) FROM `".static::getTableName()."` ";
	  $resultGetMaxId = DBUtils::query($queryGetMaxId);
	
	  if(!$resultGetMaxId) {
	    $logger->addLog("queryGetMaxId: ".$queryGetMaxId);
	    return new Error(7054,2);
	  }
	
	  $idPhoto = 0;
	  if(DBUtils::getNbRows($resultGetMaxId) > 0) {
	    $row = DBUtils::fetchNum($resultGetMaxId);
	    $idPhoto = intval($row[0]) + 1;
	  }
	
	  // 2. We generate the name of the photo
	  $extension = FileUtils::getFileExtension($originalName);
	  $photoName = "photo".$idPhoto.".".$extension;
	
	  // 3. We create the Photo object
	  $className = ucfirst(get_called_class());
	  $photo = new $className();
	  $photo->setName($photoName);
	  $photo->setCreationDateTime(IwDateTime::getNow());
	  $photo->setLastUpdateDateTime(IwDateTime::getNow());
	
	  return $photo;
	}
	
	/*
	 *	function: generateFromPath
	 *	This function generates a photo from path.
	 *	
	 *	parameters:
	 *		- $path - an absolute path to a picture.
	 *		- $idFolder - the folder SQL id in which we upload the photo.
	 *	return:
	 *		- photo object if valid and thumbnail created else an Error object.
	 */
	public static function generateFromPath($path, $idFolder = NULL) {
			
		// 1. We check that the file is compatible
		$supported = ImgUtils::isSupportedPicture($path);
		if($supported instanceof Error) {
			return $supported;
		}

		// 2. We create the Photo object
		$className = ucfirst(get_called_class());
		$photo = new $className();
		$photo->setName(FileUtils::getFileName($path));
		$photo->setPath(FileUtils::getDirectoryPath($path));
		$photo->setCreationDateTime(IwDateTime::getNow());
		$photo->setIdFolder($idFolder);
		$photo->setLastUpdateDateTime(IwDateTime::getNow());
		
		// We update the size in octets and dimensions
		$photo->updateDimensions();
		$photo->updateSize();
		
		// 4. We store the Photo object in DB
		$stored = $photo->storeInDB();
		if($stored instanceof Error) {
			return $stored;	
		}

		return $photo;
	}
	
	public function getEXIF() {
		$file = $this->getCompletePath();
		//$exif_data = exif_read_data($file);
		$exif_data = ImgUtils::extract_exif_from_pscs_xmp($file);
		return $exif_data;
	}
	
	/*
	 *	function: updateDimensions
	 *	This function update the photo dimensons attribute.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function updateDimensions() {

		$photoDimensions = ImgUtils::getDimensions($this->getCompletePath());
			
		if($photoDimensions instanceof Error) {
			return $photoDimensions;
		}
			
		$this->setWidth($photoDimensions["width"]);
		$this->setHeight($photoDimensions["height"]);
	}
		
	/*
	 *	function: updateSize
	 *	This function update the photo size attribute.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function updateSize() {
		global $suffix;
			
		$photoSize = FileUtils::getFileSizeInOctets($this->getCompletePath());
		$this->setSizeInOctets($photoSize);		
	}
	
	/*
	 *	function: resize
	 *	This function resize the picture and update its attributes.
	 *	
	 *	parameters:
	 *		- $maxWidth - maximum width.
	 *		- $maxHeight - maximum height.
	 *	return:
	 *		- "ok" if resized else an Error object.
	 */
	public function resize($maxWidth, $maxHeight) {
		global $suffix;
		
		if($this->width > $maxWidth || $this->height > $maxHeight) {
			$path = $this->getCompletePath();
			$resized = ImgUtils::resize($path,$path."_resized",$maxWidth,$maxHeight);
			if($resized instanceof Error) {
				return $resized;	
			}
			
			unlink($path);
			rename($path."_resized",$path);
			
			$this->updateDimensions();
			$this->updateSize();
		}
		
		return "ok";
	}
	
	/*
	 *	public function: rotate
	 *	This function rotate the photo and its additionnal pictures.
	 *	It updates the size and dimensions properties
	 *	
	 *	parameters:
	 *		- $degrees - the degrees for rotation.
	 *	return:
	 *		- "ok" if rotated else an Error object.
	 */
	public function rotate($degrees) {
		global $suffix;
		
		// We get all necessary existing path
		$originalPath = $this->getCompletePath();
		$thumbnailPath = $suffix.$this->getThumbnailPath();
		
		// We update the path to have new file names
		$this->generateNewVersionName();
		
		// We get the destination path
		$originalPathRotated = $this->getCompletePath();
		
		// We rotate the originale picture
		$rotated = ImgUtils::rotate($originalPath,$originalPathRotated,$degrees);
		if($rotated instanceof Error) {
			return $rotated;	
		}

		unlink($originalPath);
			
		$this->updateDimensions();
		$this->updateSize();

		$created = $this->createThumbnail();
		if($created instanceof Error) {
			return $created;	
		}
		if(file_exists($thumbnailPath)) {
			unlink($thumbnailPath);
		}
		
		$this->updateAdditionnalSize();
		
		return "ok";
	}
	
	/*
	 *	public function: generateNewVersionName
	 *	This function updates the photo name by generating a new version name.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- none.
	 */
	public function generateNewVersionName() {
		$logger = Logger::getInstance();
		
		$fileName = FileUtils::getFileName($this->name,false);
		$logger->addLog("filename: ".$fileName);
		// We get the current version if exists
		$pos = strrpos($fileName,static::$VERSION_SEPARATOR);
		if($pos === false) {
			// We create the first version
			$fileName .= static::$VERSION_SEPARATOR . "1";
		}
		else {
			// We udate the version index
			$index = intval(substr($fileName, $pos + 1));
			$fileName = substr($fileName, 0, $pos) . static::$VERSION_SEPARATOR . ($index + 1);
		}
		
		$this->name = $fileName . "." . FileUtils::getFileExtension($this->name);
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}
	
	public function getThumbName() {
		return FileUtils::addSuffixToFileName($this->name, static::$THUMB_SUFFIX);	
	}
	
	/*
	 *	function: getCompletePath
	 *	This function returns the complete photo path (with name).
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- originals photo path.
	 */
	 public function getCompletePath() {
		return $this->path.DIRECTORY_SEPARATOR.$this->name; 
	 }
	
	public function setPath($path) {
		$this->path = $path;
	}

	public function getPath() {
		return $this->path;
	}
	
	public function setSizeInOctets($sizeInOctets) {
		$this->sizeInOctets = $sizeInOctets;
	}
	
	public function getSizeInOctets() {
		return $this->sizeInOctets;	
	}
	
	public function setAdditionnalSizeInOctets($additionnalSizeInOctets) {
		$this->additionnalSizeInOctets = $additionnalSizeInOctets;
	}
	
	public function getAdditionnalSizeInOctets() {
		return $this->additionnalSizeInOctets;	
	}
	
	public function getTotalSizeInOctets() {
		return $this->sizeInOctets + $this->additionnalSizeInOctets;	
	}
	
	public function setWidth($width) {
		$this->width = $width;	
	}
	
	public function getWidth() {
		return $this->width;	
	}
	
	public function setHeight($height) {
		$this->height = $height;	
	}
	
	public function getHeight() {
		return $this->height;	
	}

	public function setDescription($description) {
		$this->description = $description;	
	}
	
	public function getDescription() {
		return $this->description;	
	}
	
	public function getCreationDateTime() {
		return $this->creationDateTime;	
	}
	
	public function setCreationDateTime($creationDateTime) {
		$this->creationDateTime = $creationDateTime;
	}
	
	public function getLastUpdateDateTime() {
		return $this->lastUpdateDateTime;	
	}
	
	public function setLastUpdateDateTime($lastUpdateDateTime) {
		$this->lastUpdateDateTime = $lastUpdateDateTime;
	}
	
	public function setIdFolder($idFolder) {
		if(isset($idFolder)) {
			$this->idFolder = intval($idFolder);
		}
	}

	public function getIdFolder() {
		return $this->idFolder;
	}
	
	public function getFolder() {
		if(!isset($this->folder)) {
			$this->folder = Folder::getFromDB($this->idFolder);
		}
		return $this->folder;
	}

	public function setFolder($folder) {
		if($folder instanceof Folder) {
			$this->folder = $folder;
			$this->idFolder = $folder->getSQLid();
		}
	}
		
	/*
	 *	function: toArray
	 *	This function convert the object in a map object.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- map representation of the photo.
	 */
	public function toArray() {
		$photo = array();
		
		$photo["idPhoto"] = $this->SQLid;
		$photo["name"] = $this->name;
		
		// We format the creation date time
		$creationDateTime = $this->getCreationDateTime();
		$formatted = $creationDateTime->format("d F");
		$photo["creationDateTime"] = $formatted;
		
		// We format the last updated date time
		$lastUpdateDateTime = $this->getLastUpdateDateTime();
		$formatted = $lastUpdateDateTime->format("d F");
		$photo["lastUpdateDateTime"] = $formatted;
	
		$photo["width"] = $this->getWidth();
		$photo["height"] = $this->getHeight();
		
		$photo["description"] = $this->getDescription();
		
		if(false) {
			$photo["path"] = $this->path;
			$photo["exif"] = $this->getEXIF();
		}
		
		$photo["idFolder"] = $this->idFolder;
		
		return $photo;
	}
}

?>
