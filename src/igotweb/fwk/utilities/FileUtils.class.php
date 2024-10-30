<?php

/**
 *	Class: FileUtils
 *	Version: 0.1
 *	This class handle files.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class FileUtils {

  public static $fileMaxSize = 1000000; /* nb of octets */
	
  public static function init() {
    // We init the img max size.
    $phpIniMaxUploadSizePattern = ini_get("upload_max_filesize");
    static::$fileMaxSize = static::getSizeInOctets($phpIniMaxUploadSizePattern);
  }

  private function __construct() {
  }
  
  public static function cleanPath($path) {
    /* replace '//' or '/./' or '/foo/../' with '/' */
	$sep = preg_quote(DIRECTORY_SEPARATOR);
    $re = array('#('.$sep.'\.?'.$sep.')#', '#'.$sep.'(?!\.\.)[^'.$sep.']+'.$sep.'\.\.'.$sep.'#');
    for($n=1; $n>0; $path=preg_replace($re, $sep, $path, -1, $n)) {}
    return $path;
  }


  /*
   *  function: getFilesList
   *  This function gets the list of File names from a directory path.
   *
   *  parameters:
   *    - $directoryPath - the path of the directory.
   *  return:
   *    - array of file names.
   */
  public static function getFilesList($directoryPath) {
    $files = @scandir($directoryPath);
    if(is_array($files)) {
      $files = array_values(array_diff($files, array('.', '..')));
    }
    return $files;
  }
  
    /*
   *  function: createDirectory
   *  This function creates a directory.
   *
   *  parameters:
   *    - $directoryPath - the path of the directory.
   *  return:
   *    - "ok" if created or Error object.
   */
  public static function createDirectory($directoryPath) {
    $result = @mkdir($directoryPath, 0777, true);
    if(!$result) {
      return new Error(9472,1,$directoryPath);
    }
    
    return "ok";
  }
  
  /*
   *  function: renamePath
   *  This function renames/moves a file/directory.
   *
   *  parameters:
   *    - $oldPath - the old path.
   *    - $newPath - the new path.
   *  return:
   *    - "ok" if created or Error object.
   */
  public static function renamePath($oldPath, $newPath) {
    $result = @rename($oldPath, $newPath);
    if(!$result) {
      return new Error(9478,1,array($oldPath, $newPath));
    }
    
    return "ok";
  }
  
    /*
   *  function: copyPath
   *  This function copies a file/directory.
   *
   *  parameters:
   *    - $srcPath - the source path.
   *    - $destPath - the destination path.
   *  return:
   *    - "ok" if copied or Error object.
   */
  public static function copyPath($srcPath, $destPath) {
    $result = @copy($srcPath, $destPath);
    if(!$result) {
      return new Error(9479,1,array($srcPath, $destPath));
    }
    
    return "ok";
  }
  
  /*
   *  function: removeDirectory
   *  This function removes a directory. It removes the files inside if any.
   *
   *  parameters:
   *    - $directoryPath - the path of the directory.
   *  return:
   *    - "ok" if removed or Error object.
   */
  public static function removeDirectory($directoryPath) {
    if (!file_exists($directoryPath)) {
      return "ok";
    }
    
    // In case we target a file, we remove it
    if (!is_dir($directoryPath) || is_link($directoryPath)) {
      return static::removeFile($directoryPath);
    }
    
    // We loop into the directory
    foreach (scandir($directoryPath) as $item) {
      if ($item == '.' || $item == '..') continue;
      // We remove the file or directory
      $result = static::removeDirectory($directoryPath . DIRECTORY_SEPARATOR . $item);
      if ($result instanceof Error) {
        chmod($directoryPath . DIRECTORY_SEPARATOR . $item, 0777);
        $result = static::removeDirectory($directoryPath . DIRECTORY_SEPARATOR . $item);
        if ($result instanceof Error) {
          return $result;
        }
      }
    }
    
    // We remove the empty directory
    $result = @rmdir($directoryPath);
    if(!$result) {
      return new Error(9473,1,$directoryPath);
    }
    
    return "ok";
  }
  
  /*
   *  function: removeFile
   *  This function removes a directory. It removes the files inside if any.
   *
   *  parameters:
   *    - $directoryPath - the path of the directory.
   *  return:
   *    - "ok" if removed or Error object.
   */
  public static function removeFile($path) {
    if (!file_exists($path)) {
      return "ok";
    }
    
    // In case we target a directory, we raise an error
    if(is_dir($path)) {
      return new Error(9475,1,$path);
    }
    
    // We remove the file
    $result = @unlink($path);
    if(!$result) {
      return new Error(9475,2,$path);
    }
    
    return "ok";
  }
  
  /*
   *  function: copyDirectory
   *  This function copy a directory.
   *  It create a new directory as destination. If already exists, it add and index to the directory name.
   *
   *  parameters:
   *    - $src - the path of the source directory.
   *    - $dst - the path of the destination directory (optional).
   *  return:
   *    - "ok" if copied or Error object.
   */
  public static function copyDirectory($src, $dst = NULL) {
    if(!isset($dst)) {
      $dst = $src;
    }
    
    // We open the src directory
    $dir = @opendir($src); 
    if(!$dir) {
      return new Error(9471,2, $src);
    }
    
    // We check if the destination directory exists
    $dst = static::getAvailableDirectoryPath($dst);
    if($dst instanceof Error) {
      closedir($dir); 
      return $dst;
    }
    
    $result = static::createDirectory($dst);
    if($result instanceof Error) {
      closedir($dir); 
      return $result;
    }

    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) { 
                $result = static::copyDirectory($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file); 
                if($result instanceof Error) {
                  closedir($dir);
                  return $result;
                }
            } 
            else { 
                $result = static::copyPath($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file); 
                if($result instanceof Error) {
                  closedir($dir); 
                  return $result;
                }
            } 
        } 
    } 
    closedir($dir); 
    return "ok";
  }
  
  public static function getAvailableDirectoryPath($path) {
    // If the path does not exist we use it
    if(!file_exists($path)) {
      return $path;
    }
    
    // We loop over possible existing path with index to return the first available one.
    // We go until 99. Then we reject.
    for($i = 1 ; $i < 100 ; $i++) {
      $pathToTest = $path . "-" .$i;
      if(!file_exists($pathToTest)) {
        return $pathToTest;
      }
    }
    
    // No path found, we return an error
    return new Error(9474,1,$path);
  }

  /*
   *  function: getDirectoriesList
   *  This function return a list of directories from the path in parameter.
   *  It can include the sub directories based on parameter.
   *
   *  parameters:
   *    - $directoryPath - the root path of the directories.
   *    - $includeSubDirectories - if set to true, the list of sub directories is added.
   *  return:
   *    - directories - the list of directories names (relative path from the $directoryPath) or Error if any.
   */
  public static function getDirectoriesList($directoryPath, $includeSubDirectories = false) {

    $directoryPath = static::cleanPath($directoryPath);
    
    if(!is_dir($directoryPath)) {
      return new Error(9471,1, $directoryPath);
    }
    
    $files = scandir($directoryPath);
    $directories = array();
    foreach($files as $fileName) {
      $path = $directoryPath . DIRECTORY_SEPARATOR . $fileName;
      if(is_dir($path) && $fileName != "." && $fileName != "..") {
        $directories[] = $fileName;
        if($includeSubDirectories) {
          $subDirectories = static::getDirectoriesList($path, true);
          foreach($subDirectories as $subDirectory) {
            $directories[] = $fileName . DIRECTORY_SEPARATOR . $subDirectory;
          }
        }
      }
    }
    return $directories;
  }

  /*
   *	function: getSizeInOctets
  *	This function return integer corresponding to size in octets.
  *
  *	parameters:
  *		- $sizePattern - the size pattern (integer + letter for unit at the end. ex: 32M).
  *	return:
  *		- sizeInOctets - the size in octets (integer).
  */
  public static function getSizeInOctets($sizePattern) {
    $sizeInOctets = trim($sizePattern);
    $number = substr($sizeInOctets, 0, strlen($sizeInOctets)-1);
    
    if(!is_numeric($number)) {
      return new Error(9490,1,$sizeInOctets);
    }

    $last = strtolower($sizeInOctets[strlen($sizeInOctets)-1]);
    switch($last) {
      // Le modifieur 'G' est disponible depuis PHP 5.1.0
      case 'g':
        $number *= 1024;
      case 'm':
        $number *= 1024;
      case 'k':
        $number *= 1024;
    }
    return $number;
  }
  
  /*
   *	function: getSizeFromOctetsToPattern
  *	This function return pattern corresponding to size (G for Go, M fo Mo, K for Ko).
  *
  *	parameters:
  *  - sizeInOctets - the size in octets (integer).		
  *	return:
  *  - $sizePattern - the size pattern (integer + letter for unit at the end. ex: 32M).		
  */
  public static function getSizeFromOctetsToPattern($sizeInOctets) {
    $letters = array("","K","M","G");
    $letterIndex = 0;
    $sizePattern = $sizeInOctets;
    while($sizePattern > 1024 && $letterIndex < 3) {
      $sizePattern /= 1024;
      $letterIndex++;
    }
    $sizePattern = round($sizePattern,2);
    $sizePattern .= $letters[$letterIndex];
    return $sizePattern;
  }

  /*
   *	function: getFileExtension
  *	This function return the file extension.
  *
  *	parameters:
  *		- $filePath - complete path to file.
  *	return:
  *		- extension - the extension of the file.
  */
  public static function getFileExtension($filePath) {
    $fileName = static::getFileName($filePath);
    $system = explode('.',$fileName);
    $extension = $system[count($system)-1];

    return strtolower($extension);
  }

  /*
   *	function: getFileName
  *	This function return the file name with or without extension.
  *
  *	parameters:
  *		- $filePath - complete path to file.
  *		- $extension - boolean, true if name with extension
  *	return:
  *		- fileName - the name of the file with or without extension.
  */
  public static function getFileName($filePath,$extension = true) {
    $directories = explode(DIRECTORY_SEPARATOR,$filePath);
    $fileName = $directories[count($directories)-1];

    if(!$extension) {
      $index = strrpos($fileName,".");
      if($index !== false) {
        $fileName = substr($fileName,0,$index);
      }
    }

    return $fileName;
  }

  /*
   *	function: getDirectoryPath
  *	This function return the file directory path without file name.
  *
  *	parameters:
  *		- $filePath - complete path to file.
  *	return:
  *		- directoryPath - the path of the directory which contains the file.
  */
  public static function getDirectoryPath($filePath) {
    $directories = explode(DIRECTORY_SEPARATOR,$filePath);
    unset($directories[count($directories)-1]);
    $directoryPath = implode(DIRECTORY_SEPARATOR,$directories).DIRECTORY_SEPARATOR;

    return $directoryPath;
  }

  /*
   *	function: addSuffixToFileName
  *	This function adds suffix to fileName and return the complete new filePath.
  *
  *	parameters:
  *		- $filePath - complete path to file.
  *	return:
  *		- $filePath - the new path with suffix added to fileName.
  */
  public static function addSuffixToFileName($filePath, $suffix) {
    $directories = explode(DIRECTORY_SEPARATOR,$filePath);
    $fileName = array_pop($directories);
    $path = join(DIRECTORY_SEPARATOR,$directories);

    $system = explode('.',$fileName);
    $extension = array_pop($system);
    $name = join('.',$system);

    $newName = $name.$suffix.".".$extension;

    $newPath = $path.DIRECTORY_SEPARATOR.$newName;

    return $newPath;
  }

  /*
   *	function: getFileSizeInOctets
  *	This function get the size of a file.
  *
  *	parameters:
  *		- $path - the file path.
  *	return:
  *		- the size on the disk.
  */
  public static function getFileSizeInOctets($path) {
    if(!is_file($path)) {
      return -1;
    }
    return filesize($path);
  }

  /*
   *	function: getDirectorySizeInOctets
  *	This function get the size of a directory and all its sub directories.
  *
  *	parameters:
  *		- $path - directory.
  *	return:
  *		- the size on the disk.
  */
  public static function getDirectorySizeInOctets($path) {
    // Init
    $size = 0;

    // Trailing slash
    if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
      $path .= DIRECTORY_SEPARATOR;
    }

    // Sanity check
    if (is_file($path)) {
      return filesize($path);
    } elseif (!is_dir($path)) {
      return false;
    }

    // Iterate queue
    $queue = array($path);
    for ($i = 0, $j = count($queue); $i < $j; ++$i) {
      // Open directory
      $parent = $i;
      if (is_dir($queue[$i]) && $dir = @dir($queue[$i])) {
        $subdirs = array();
        while (false !== ($entry = $dir->read())) {
          // Skip pointers
          if ($entry == '.' || $entry == '..') {
            continue;
          }

          // Get list of directories or filesizes
          $path = $queue[$i] . $entry;
          if (is_dir($path)) {
            $path .= DIRECTORY_SEPARATOR;
            $subdirs[] = $path;
          } elseif (is_file($path)) {
            $size += static::getFileSizeInOctets($path);
          }
        }

        // Add subdirectories to start of queue
        unset($queue[0]);
        $queue = array_merge($subdirs, $queue);

        // Recalculate stack size
        $i = -1;
        $j = count($queue);

        // Clean up
        $dir->close();
        unset($dir);
      }
    }
    return $size;
  }
  
  /*
   *  function: writeFile
   *  This function write content in a file.
   *
   *  parameters:
   *    - $path - the path of the file.
   *    - $content - the content to write.
   *  return:
   *    - "ok" if written or Error object.
   */
  public static function writeFile($path, $content) {
    $result = @file_put_contents($path, $content);
    if($result === false) {
      return new Error(9476,1,$path);
    }
    return "ok";
  }
  
  /*
   *  function: readFile
  *  This function read content from a file.
  *
  *  parameters:
  *    - $path - the path of the file.
  *  return:
  *    - content if read or Error object.
  */
  public static function readFile($path) {
    $result = @file_get_contents($path);
    if($result === false) {
      return new Error(9477,1,$path);
    }
    return $result;
  }


  /*
	 *	function: uploadFile
	 *	This function tries to upload a file in the path in parameter.
	 *
	 *	parameters:
	 *		- $formFile - the file get from the form.
	 *		- $path - path to the file.
	 *		- $maxSize - the maximum size possible in octets
	 *	return:
	 *		- "ok" if uploaded else an Error object.
	 */
	public static function uploadFile($formFile, $path, $maxSize = NULL) {
		$logger = Logger::getInstance();
		
		if(!isset($maxSize)) {
		  $maxSize = static::$fileMaxSize;
		}

		// 1. We check if there was an error while uploading the file
		if($formFile["error"] == UPLOAD_ERR_INI_SIZE) {
			$logger->addLog("File size = ".$formFile["size"]);
			return new Error(9495,1,array(static::getSizeFromOctetsToPattern($maxSize)));
		}
		else if($formFile["error"] == UPLOAD_ERR_FORM_SIZE) {
			$logger->addLog("File size = ".$formFile["size"]);
			return new Error(9495,2,array(static::getSizeFromOctetsToPattern($maxSize)));
		}
		else if($formFile["error"] != 0) {
			return new Error(9496,1);
		}

		// 1. We check if the upload worked.
		if ((!@is_uploaded_file($formFile["tmp_name"])) ||
				($formFile["error"] != 0) || ($formFile["size"] == 0)) {
	        return new Error(9496,2);
		}
		
		if($formFile["size"] > $maxSize) {
			$logger->addLog("FileUtils::uploadFile - file size: ".$formFile["size"].", max size: ".FileUtils::getSizeFromOctetsToPattern($maxSize));
	        return new Error(9495,3,array(FileUtils::getSizeFromOctetsToPattern($maxSize)));
		}

		// 3. We try to move the file.
		if(!@move_uploaded_file($formFile["tmp_name"], $path)) {
      $logger->addLog("FileUtils::uploadFile - move file from: ".$formFile["tmp_name"]." => ".$path);
			return new Error(9496,3);
		}

		return "ok";
	}

  /*
   *	function: loadFile
  *	This function read a file and add it in output with corresponding headers.
  *
  *	parameters:
  *		- $location - the path to the file.
  *		- $fileName - the file name (optional to override the one from the location).
  *		- $mimeType - the mimeType to force for output.
  *   - $forceDownload - set to true to force browser to download.
  *	return:
  *		- none.
  */
  public static function loadFile($location, $filename = null, $mimeType = null, $forceDownload = false) {

    if(!file_exists($location)) {
      header ("HTTP/1.1 404 Not Found");
      return;
    }

    $size=filesize($location);
    $time=date('r',filemtime($location));

    $fm=@fopen($location,'rb');
    if(!$fm) {
      header ("HTTP/1.1 505 Internal server error");
      return;
    }
    
    if($filename == null) {
      $filename = static::getFileName($location);
    }

    if(!isset($mimeType)) {
      $extension = static::getFileExtension($location);
      switch($extension) {
        case "gif":
          $mimeType="image/gif";
          break;
        case "png":
          $mimeType="image/png";
          break;
        case "jpe":
        case "jpeg":
        case "jpg":
          $mimeType="image/jpg";
          break;
        default:
          $mimeType = "application/octet-stream";
          break;
      }
    }

    $begin=0;
    $end=$size;

    if(isset($_SERVER['HTTP_RANGE'])) {
      if(preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $begin = intval($matches[0]);
        if(!empty($matches[1])) {
          $end=intval($matches[1]);
        }
      }
    }

    if($begin>0||$end<$size) {
      header('HTTP/1.0 206 Partial Content');
    }
    else {
      header('HTTP/1.0 200 OK');
    }

    header("Content-Type: $mimeType");
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Accept-Ranges: bytes');
    header('Content-Length:'.($end-$begin));
    header("Content-Range: bytes $begin-$end/$size");
    if($forceDownload) {
      header("Content-disposition: attachment; filename=\"".$filename."\""); 
    }
    else {
      header("Content-Disposition: inline; filename=\"".$filename."\"");
    }
    header("Content-Transfer-Encoding: binary\n");
    header("Last-Modified: $time");
    header('Connection: close');

    $cur=$begin;
    fseek($fm,$begin,0);

    while(!feof($fm)&&$cur<$end&&(connection_status()==0)) {
      print fread($fm,min(1024*16,$end-$cur));
      $cur += 1024*16;
    }
  }


  /*
   *	function: loadFileContent
  *	This function read a file and add it in output with corresponding headers.
  *
  *	parameters:
  *		- $location - the path to the file.
  *		- $fileName - the file name (optional to override the one from the location).
  *		- $mimeType - the mimeType to force for output.
  *   - $forceDownload - set to true to force browser to download.
  *	return:
  *		- none.
  */
  public static function loadFileContent($content, $filename, $mimeType = null, $forceDownload = false) {

    $size=strlen($content);

    if(!isset($mimeType)) {
      $extension = static::getFileExtension($filename);
      switch($extension) {
        case "gif":
          $mimeType="image/gif";
          break;
        case "png":
          $mimeType="image/png";
          break;
        case "jpe":
        case "jpeg":
        case "jpg":
          $mimeType="image/jpg";
          break;
        case "pdf":
          $mimeType="application/pdf";
          break;
        default:
          $mimeType = "application/octet-stream";
          break;
      }
    }

    $begin=0;
    $end=$size;

    if(isset($_SERVER['HTTP_RANGE'])) {
      if(preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $begin = intval($matches[0]);
        if(!empty($matches[1])) {
          $end=intval($matches[1]);
        }
      }
    }

    if($begin>0||$end<$size) {
      header('HTTP/1.0 206 Partial Content');
    }
    else {
      header('HTTP/1.0 200 OK');
    }

    header("Content-Type: $mimeType");
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Accept-Ranges: bytes');
    header('Content-Length:'.($end-$begin));
    header("Content-Range: bytes $begin-$end/$size");
    if($forceDownload) {
      header("Content-disposition: attachment; filename=\"".$filename."\""); 
    }
    else {
      header("Content-Disposition: inline; filename=\"".$filename."\"");
    }
    header("Content-Transfer-Encoding: binary\n");
    header("Last-Modified: $time");
    header('Connection: close');

    $cur=$begin;

    while($cur<$end&&(connection_status()==0)) {
      print substr($content,$cur,min(1024*16,$end-$cur));
      $cur += 1024*16;
    }
  }
}
FileUtils::init();
?>
