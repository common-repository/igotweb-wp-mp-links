<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

/**
 *	Class: Folder
 *	This class handle the generic Folder object.
 *  A Folder has a parent Folder and list of sub Folder Objects.
 *  @BeanClass(sqlTable="folders") 
 */
class Folder extends GenericBean {
	
	protected $idParentFolder;
	protected $parentFolder;
	
	protected $name;
	protected $creationDateTime; // The date time when the gallery has been created.
  
  /** @BeanProperty(isExcludedFromDB=true) */
	protected $subFolders; // The list of subFolders
	
	/*
	 *	Constructor
	 *	It creates a Folder.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Folder object.
	 */	
	public function __construct() {
		// We call the parent
		parent::__construct();
		
		$this->name = NULL;
		$this->creationDateTime = IwDateTime::getNow();
		
		$this->parentFolder = NULL;
		$this->idParentFolder = NULL;
		
		$this->subFolders = NULL;
	}
	
	/*
	 * getCreateTableQuery
	* This method returns the query to create the table in DB.
	*/
	public function getCreateTableQuery() {
	  $query = "CREATE TABLE `" . static::getTableName() . "` (";
	  $query .= "`idFolder` int(11) NOT NULL AUTO_INCREMENT,";
	  $query .= "`name` varchar(100) COLLATE utf8_bin NOT NULL,";
	  $query .= "`creationDateTime` datetime NOT NULL,";
	  $query .= "`idParentFolder` int(11) DEFAULT NULL,";
	  $query .= "PRIMARY KEY (`idFolder`)";
	  $query .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0;";
	  
	  return $query;
	}
	
	/*
	 *	function: getRootFolders
	 *	This function get the list of available root folders in DB.
	 *	
	 *	parameters:
	 *		- extraParams : SQL params.
	 *	return:
	 *		- array of Folder objects or Error.
	 */	
	public function getRootFolders($extraParams = NULL) {
		// We set the params
		$params = "`idParentFolder` IS NULL";
		if(isset($extraParams) && $extraParams != "") {
			$params .= " AND " . $extraParams;
		}
		// We get the folders
		return $this->getBeans($params);
	}
	
	/*
	 *	function: getListObjects
	 *	This function returns a list of objects linked with the Folder and profile.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- array of objects.
	 */
	public function getListObjects() {
		$logger = Logger::getInstance();
		
		// 1. We check that SQL id exists
		if(!isset($this->SQLid) || $this->SQLid == "") {
			return new Error(7603,1);
		}
				
		$objects = array();

		// 2. We get the list of subfolders
		$folders = $this->getSubFolders();
		if($folders instanceof Error) {
		  $logger->addErrorLog($folders);
		}
		else {
		  $objects = array_merge($objects,$folders);		  
		}
		
		// 3. We get the list of photos.
		$photos = Photo::getFromIdFolder($this->SQLid);
		if($photos instanceof Error) {
		  $logger->addErrorLog($photos);
		}
		else {
		  $objects = array_merge($objects,$photos);
		}
				
		return $objects;
	}
		
	/*
	 *	function: removeFromDB
	 *	This function try to remove the Folder from the DB.
	 *	The folder has to be empty to be able to remove it.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- true if deleted else an Error object.
	 */
	public function removeFromDB() {
		$logger = Logger::getInstance();
		
		// 1. We check that SQL id exists
		if(!isset($this->SQLid) || $this->SQLid == "") {
			return new Error(7604,1);
		}
		
		// 2. We check that the folder is empty
		$objects = $this->getListObjects();
		if($objects instanceof Error) {
		  return $objects;
		}
		else if(count($objects) > 0) {
			return new Error(7610);	
		}
		
		return parent::removeFromDB();
	}
	
	/*
	 *	function: getSubFoldersFromDB
	 *	This function retrieves a list of subfolders from DB.
	 *	
	 *	parameters:
	 *		- extraParams : SQL params.
	 *	return:
	 *		- array of Folder objects.
	 */
	public function getSubFoldersFromDB($extraParams = NULL) {
		// We set the params
		$params = "`idParentFolder`=".$this->SQLid;
		if(isset($extraParams) && $extraParams != "") {
			$params .= " AND " . $extraParams;
		}
		// We get the folders
		return $this->getBeans($params);
	}
	
	/*
	 *	function: buildSubFoldersFromList
	 *	This function build the list of subFolders based on the
	 *	array of Folder objects in parameter.
	 *	
	 *	parameters:
	 *		- $folders : an array of Folder objects.
	 *	return:
	 *		- array of remaining Folders not part of subFolders.
	 */
	public function buildSubFoldersFromList($folders) {
		$subFolders = array();
		foreach($folders as $index => $folder) {
			if($folder->getIdParentFolder() == $this->getSQLid()) {
				$subFolders[] = $folder;
				unset($folders[$index]);
			}
		}
		// We set the subFolders
		$this->subFolders = $subFolders;
		$this->sortSubFolders();
		// We reindex the remaining folders
		$folders = array_values($folders);
		return $folders;
	}
	
	/*
	 *	function: buildSubFoldersTreeFromList
	 *	This function build the complete tree of subFolders based on the
	 *	array of Folder objects in parameter. This array contains all subfolders within the hierarchy.
	 *	
	 *	parameters:
	 *		- $folders : an array of Folder objects.
	 *	return:
	 *		- none.
	 */
	public function buildSubFoldersTreeFromList($folders) {
		// We build the subFolders from list
		$folders = $this->buildSubFoldersFromList($folders);
		// We get the generated subFolders
		$subFolders = $this->getSubFolders();
		foreach($subFolders as $index => $subFolder) {
			// We build the subFolders Tree with remaining folders
			$subFolder->buildSubFoldersTreeFromList($folders);
		}
	}
	
	/*
	 *	function: getSubFolders
	 *	This function returns the subfolders.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- array of Folder objects or Error object.
	 */
	public function getSubFolders() {
		if(!isset($this->subFolders)) {
			$subFolders = $this->getSubFoldersFromDB();
			if($subFolders instanceof Error) {
				return $subFolders;	
			}
			$this->subFolders = $subFolders;
			$this->sortSubFolders();
		}
		return $this->subFolders;
	}
	
	/*
	 *	function: buildSubFoldersHierarchyFromDB
	 *	This function retrieve the complete hierarchy of subfolders from DB.
	 *
	 *	parameters:
	 *		- none.
	 *
	 *	return:
	 *		- array of Folder objects.
	 */
	public function buildSubFoldersHierarchyFromDB() {
		$subFolders = $this->getSubFolders();
		if($subFolders instanceof Error) {
			return $subFolders;	
		}	
		foreach($subFolders as $index => $subFolder) {
			// We build the subFolders hierarchy for subFolders
			$subFolder->buildSubFoldersHierarchyFromDB();
			if($subFolders instanceof Error) {
				return $subFolders;	
			}
		}
		
		return "ok";
	}
	
	/*
	 *	function: getFolderFromHierarchy
	 *	This function return a folder within hierarchy from idFolder in param.
	 */
	public function getFolderFromHierarchy($idFolder) {
		// We check if current folder is the one
		if($this->getSQLid() == $idFolder) {
			return $this;
		}
		
		// We check within the subFolders
		$subFolders = $this->getSubFolders();
		foreach($subFolders as $index => $subFolder) {
			// We build the subFolders hierarchy for subFolders
			$theFolder = $subFolder->getFolderFromHierarchy($idFolder);
			$className = ucfirst(get_class($this));
			if($theFolder instanceof $className) {
				return $theFolder;
			}
		}
		
		return NULL;
	}
	
	/*
	 *	function: sortSubFolders
	 *	This function sort the subFolders.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- array of Folder objects.
	 */
	public function sortSubFolders() {
		if(isset($this->subFolders) && is_array($this->subFolders)) {
			usort($this->subFolders, array("Folder", "compare"));
		}
	}
	
	/*
	 *	static function: compare
	 *	This function compare two folders.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int for ordering.
	 */
	public static function compare($folderA, $folderB) {
		if ($folderA->getName() < $folderB->getName()) {
			 return -1;
		}
		if ($folderA->getName() > $folderB->getName()) {
			return 1;
		}
		return 0;
	}
  
  /*
	 *	function: createRootFolder
	 *	This function create a root folder.
	 *	
	 *	parameters:
	 *		- $folderName - the folder name.
	 *	return:
	 *		- Folder object if created else an Error object.
	 */
  public static function createRootFolder($folderName) {
    // 1. We create the instance.
    $className = ucfirst(get_called_class());
		$folder = new $className();
    
    // 1. We check that the subfolder do not already exists
		$params = "`name`='".$folderName."'";
		$rootFolders = $folder->getRootFolders($params);
		if($rootFolders instanceof Error) {
			return $rootFolders;	
		}
		
		if(count($rootFolders) > 0) {
			// The root folder already exists
			return new Error(7611,2);	
		}
    
    // We update the root folder
    $folder->setName($folderName);
    
    // We store it in DB
    $result = $folder->storeInDB();
    if($result instanceof Error) {
      return $result;
    }
    
    return $folder;
  }
	
	/*
	 *	function: createSubFolder
	 *	This function create a sub folder for the current folder.
	 *	
	 *	parameters:
	 *		- $folderName - the folder name.
	 *	return:
	 *		- Folder object if created else an Error object.
	 */
	public function createSubFolder($folderName, $store = true) {
		// 1. We check that the subfolder do not already exists
		$params = "`name`='".$folderName."'";
		$subFolders = $this->getSubFoldersFromDB($params);
		if($subFolders instanceof Error) {
			return $subFolders;	
		}
		
		if(count($subFolders) > 0) {
			// The folder already exists
			return new Error(7611,1);	
		}
		
		// We create the subfolder
		$className = ucfirst(get_class($this));
		$folder = new $className();
		$folder->setName($folderName);
		$folder->setParentFolder($this);
		if($store) {
			$stored = $folder->storeInDB();
			if($stored instanceof Error) {
				return $stored;	
			}
		}
		
		return $folder;
	}
	
	/*
	 *	function: rename
	 *	This function rename the folder.
	 *	
	 *	parameters:
	 *		- $folderName - the folder name.
	 *	return:
	 *		- "ok" if updated else an Error object.
	 */
	public function rename($folderName) {
		// 1. We check that the new name is different
		if($this->getName() == $folderName) {
			return "ok";	
		}
		
		// 1. We check that there is not folder existing with same name at the same level in hierarchy
		$parentFolder = $this->getParentFolder();
		
		if($parentFolder instanceof Folder) {
    		$params = "`name`='".$folderName."'";
    		$subFolders = $parentFolder->getSubFoldersFromDB($params);
    		if($subFolders instanceof Error) {
    			return $subFolders;	
    		}
    		
    		if(count($subFolders) > 0) {
    			// The folder already exists
    			return new Error(7611,2);	
    		}
		}
		
		// We update the folder
		$this->setName($folderName);
		
		// We store the updated folder
		$stored = $this->storeInDB();
		if($stored instanceof Error) {
			return $stored;	
		}
		
		return "ok";
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getCreationDateTime() {
		return $this->creationDateTime;	
	}
		
	public function setCreationDateTime($creationDateTime) {
		$this->creationDateTime = $creationDateTime;
	}

	public function getIdParentFolder() {
		return $this->idParentFolder;
	}
	
	public function setIdParentFolder($idParentFolder) {
		if(isset($idParentFolder)) {
			$this->idParentFolder = intval($idParentFolder);
		}
	}
	
	public function getParentFolder() {
		if(!isset($this->parentFolder) && isset($this->idParentFolder)) {
		  $className = ucfirst(get_class($this));
			$parentFolder = new $className();
			$parentFolder->getFromDB($this->idParentFolder);
			$this->parentFolder = $parentFolder;
		}
		return $this->parentFolder;
	}
	
	public function setParentFolder($parentFolder) {
		if($parentFolder == NULL) {
			$this->parentFolder = NULL;
			$this->idParentFolder = NULL;
		}
		$className = ucfirst(get_class($this));
		if($parentFolder instanceof $className) {
			$this->parentFolder = $parentFolder;
			$this->idParentFolder = $parentFolder->getSQLid();
		}
	}
	
	public function toArray() {
		// We get the parrent array
		$array = parent::toArray();
		
		// We override the creation date time
		$creationDateTime = $this->getCreationDateTime();
		$formatted = $creationDateTime->format("d/m/Y");
		$array["creationDateTime"] = $formatted;
		
		// We add subfolders if available
		unset($array["subFolders"]);
		if(isset($this->subFolders)) {
			$array["subFolders"] = $this->subFolders;
		}
		
		return $array;	
	} 
}

?>
