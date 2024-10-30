<?php

/**
 *	Class: DropboxUtils
 *	Version: 0.1
 *	This class handle Dropbox utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;


class DropboxUtils {

  public static $NOT_FOUND_ERROR = 7501;
  
  private static $instance;
  private $dropbox;
  private $tmpDirectory;
  
    
	private function __construct(Request $request = null) {
    if(isset($request)) {
      // We compute the tmp directory used to store files before moving them to Dropbox
      $contentDirectory = $request->getSiteContext()->getContentRootPath();
      $tmpDirectory = $contentDirectory . "tmpDropbox" . DIRECTORY_SEPARATOR;

      $config = array(
        "clientID" => $request->getConfig("fwk-dropboxClientId"),
        "clientSecret" => $request->getConfig("fwk-dropboxClientSecret"),
        "clientAccessToken" =>$request->getConfig("fwk-dropboxClientAccessToken"),
        "tmpDirectory" => $tmpDirectory
      );

      $this->updateConfig($config);
    }
  }

  /**
    * This method get the instance of DropboxUtils.
    * @param void
    * @return DropboxUtils instance
    */
  public static function getInstance(Request $request = null) {
    if(is_null(static::$instance)) {
      static::$instance = new DropboxUtils($request);  
    }
    return static::$instance;
  }

  /**
   * updateConfig
   * This method update the dropbox configuration.
   * @param config the updated configuration.
   */
  public function updateConfig($config) {
    if(isset($config["clientID"]) && 
        isset($config["clientSecret"]) && 
        isset($config["clientAccessToken"])) {
      $app = new DropboxApp(
        $config["clientID"], 
        $config["clientSecret"],
        $config["clientAccessToken"]);
      $this->dropbox = new Dropbox($app);
    }

    if(isset($config["tmpDirectory"])) {
      $this->tmpDirectory = $config["tmpDirectory"];
    }
  }

  /**
    * listFolder
    * This method list content of a folder
    * @param path the target path
    */
  public function listFolder($path) {
    $result = "ok";
    try {
      $result = $this->dropbox->listFolder($path);
    }
    catch(DropboxClientException $e) {
      $message = $e->getMessage();
      $result = Error::getGeneric("Exception: ".$message);
      $apiError = $this->handleDropBoxAPIErrors($message);
      if($apiError instanceof Error) {
        $result = $apiError;
      }
    }
    return $result;
  }

  public function handleDropBoxAPIErrors($json) {
    $error = null;
    $json = json_decode($json, true);
    if($json != null) {
      if(isset($json["error_summary"])) {
        if(strpos($json["error_summary"], "not_found") !== false) {
          $error = new Error(static::$NOT_FOUND_ERROR);
        }
      }
    }
    return $error;
  }

  public function createFolder($path) {
    $result = "ok";
    try {
      $result = $this->dropbox->createFolder($path);
    }
    catch(DropboxClientException $e) {
      $message = $e->getMessage();
      $result = Error::getGeneric("Exception: ".$message);
    }
    return $result;
  }

  /**
   * uploadFile
   * This method upload a formFile in corresponding path in Dropbox
   * @param formFile the form file
   * @param dropboxPath the target folder in Dropbox (without filename inside)
   */
  public function uploadFile($formFile, $dropboxPath, $maxSizeInOctets) {
    // 1. We check that $path is valid folder
    $result = $this->listFolder($dropboxPath);
    if($result instanceof Error) {
      if($result->getNumber() == 7501) {
        // In case of folder not found, we create the path
        $created = $this->createFolder($dropboxPath);
        if($created instanceof Error) {
          return $created;
        }
      }
      else {
        return $result;
      }
    }

    $originalFileName = $formFile["name"];

    // 2. We upload the file in temporary directory
    $completeTmpPath = $this->tmpDirectory . $originalFileName;
    $result = FileUtils::uploadFile($formFile, $completeTmpPath, $maxSizeInOctets);
    if($result instanceof Error) {
      return $result;
    }

    // We generate the complete path
    $dropboxCompletePath = $this->getCompletePath($dropboxPath, $originalFileName);

    try {
      $dropboxFile = new DropboxFile($completeTmpPath);
      $result = $this->dropbox->upload($dropboxFile, $dropboxCompletePath);
      $result = $result->getData();
    }
    catch(DropboxClientException $e) {
      $result = Error::getGeneric($e->getMessage());
    }

    // We remove the temporary file
    FileUtils::removeFile($completeTmpPath);

    return $result;
  }

  /**
   * getCompletePath
   * This method generate the complete path of file to be uploaded in dropbox
   */
  public function getCompletePath($path, $originalFileName) {
    $extension = FileUtils::getFileExtension($originalFileName);
    $fileName = FileUtils::getFileName($originalFileName, false);
    return $dropboxPath . DIRECTORY_SEPARATOR . $fileName . "-" . date("Y-m-d-H-i-s") . "." . $extension;
  }

  /**
   * getTemporaryLinkFromPath
   * This method generate a temporary link available 4 hours to a file
   * @param path the dropbox path to file
   */
  public function getTemporaryLinkFromPath($path) {
    $result = null;
    try {
      $temporaryLink = $this->dropbox->getTemporaryLink($path);
      $result = $temporaryLink->getLink();
    }
    catch(DropboxClientException $e) {
      $message = $e->getMessage();
      $result = Error::getGeneric("Exception: ".$message);
    }
    return $result;
  }

  /**
   * downloadFile
   * This method downloads a file from dropbox and returns its content.
   * @param path the dropbox path to file
   */
  public function downloadFile($path) {
    $result = null;
    try {
      $file = $this->dropbox->download($path);
      $result = $file->getContents();
    }
    catch(DropboxClientException $e) {
      $message = $e->getMessage();
      $result = Error::getGeneric("Exception: ".$message);
    }
    return $result;
  }

   /**
   * deleteFile
   * This method delete file or folder in dropbox.
   * @param path the dropbox path to file
   */
  public function deleteFile($path) {
    $result = "ok";
    try {
      $result = $this->dropbox->delete($path);
    }
    catch(DropboxClientException $e) {
      $message = $e->getMessage();
      $result = Error::getGeneric("Exception: ".$message);
      $apiError = $this->handleDropBoxAPIErrors($message);
      if($apiError instanceof Error) {
        $result = $apiError;
      }
    }
    return $result;
  }
}
?>
