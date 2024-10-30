<?php

/**
 *	Class: ImgUtils
 *	Version: 0.1
 *	This class handle Img needs.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class ImgUtils {

	public static $imgMaxSize = 1000000; /* nb of octets */
	public static $thumbMaxWidth = 150;
	public static $thumbMaxHeight = 150;
  
  public static function init() {
    // We init the img max size.
    $phpIniMaxUploadSizePattern = ini_get("upload_max_filesize");
    static::$imgMaxSize = FileUtils::getSizeInOctets($phpIniMaxUploadSizePattern);
  }

	private function __construct() {}

	/*
	 *	function: uploadPicture
	 *	This function tries to upload a picture in the path in parameter.
	 *
	 *	parameters:
	 *		- $formFile - the file get from the form.
	 *		- $picture - path to the picture.
	 *		- $maxSize - the maximum size possible in octets
	 *	return:
	 *		- "ok" if uploaded else an Error object.
	 */
	public static function uploadPicture($formFile, $picture, $maxSize = NULL) {
		$logger = Logger::getInstance();
		
		if(!isset($maxSize)) {
		  $maxSize = static::$imgMaxSize;
		}

		$result = FileUtils::uploadFile($formFile, $picture, $maxSize);
		if($result == "ok") {
			// 2. We check the image type.
			$extension = FileUtils::getFileExtension($picture);
			if(!preg_match('/jpg|png|gif|jpeg/i',$extension)) {
				$removed = FileUtils::removeFile($picture);
				if($removed instanceof Error) {
					$logger->addLog("ImgUtils::uploadPicture - wrong extension - cannot remove file: ".$picture);
				}
				return new Error(9603,1);
			}
		}

		return $result;
	}

	/*
	 *	function: createThumb
	 *	This function creates an img thumbnails.
	 *
	 *	parameters:
	 *		- $picture - path to the originale picture.
	 *		- $thumb - path to the thumbnails.
	 *	return:
	 *		- "ok" if created else an Error object.
	 */
	public static function createThumb($picture,$thumb) {
		return static::resize($picture, $thumb,static::$thumbMaxWidth, static::$thumbMaxHeight);
	}

	/*
	 *	function: resize
	 *	This function creates an img resized.
	 *	The image ratio is kept. The EXIF orientation is fixed as well.
	 *
	 *	parameters:
	 *		- $source - path to the originale picture.
	 *		- $destination - path to the destination picture.
	 *		- $maxWidth - maximum width.
	 *		- $maxHeight - maximum height.
	 *	return:
	 *		- "ok" if created else an Error object.
	 */
	public static function resize($source, $destination, $maxWidth, $maxHeight) {
		global $request;
		$logger = Logger::getInstance();

		// In case we  have directories, we do it for all files
		if(is_dir($source) && is_dir($destination)) {
			$files = FileUtils::getFilesList($source);
			foreach($files as $file) {
				if(!is_dir($file)) {
					$supported = static::isSupportedPicture($source.DIRECTORY_SEPARATOR.$file);
					if($supported == "ok") {
						set_time_limit(30);
						$resized = static::resize($source.DIRECTORY_SEPARATOR.$file, $destination.DIRECTORY_SEPARATOR.$file, $maxWidth, $maxHeight);
						if($resized instanceof Error) {
							return $resized;
						}
					}
				}
			}
			return "ok";
		}

		// 1. We check that the file exists and is supported
		$supported = static::isSupportedPicture($source);
		if($supported instanceof Error) {
			return $supported;
		}

		$dimensions = static::getDimensions($source);
		if(!isset($maxWidth)) {
			$maxWidth = $dimensions["width"];
		}
		if(!isset($maxHeight)) {
			$maxHeight = $dimensions["height"];
		}
    
    // In case the image is at the correct size, we just copy it
    if($maxWidth == $dimensions["width"] && $maxHeight == $dimensions["height"]) {
      $result = FileUtils::copyPath($source, $destination);
      if($result instanceof Error) {
        $logger->addErrorLog($result);
        $logger->addLog("ImgUtils::resize - error while copying the picture in destination (same size as original)");
        return new Error(9602,2);
      }
      return "ok";
    }
    
		// First we try with image magick
		require_once($request->getConfig("fwk-extLibDirectory")."phmagick/phmagick.php");

		$phMagick = new \phMagick($source);
		$phMagick->setDestination($destination)->resize($maxWidth,$maxHeight);
		$logs = $phMagick->getLog();
		if($logs[0]["return"] == 0) {
			return "ok";
		}

		// Then we try with GD library
		require_once($request->getConfig("fwk-extLibDirectory")."phpThumb/ThumbLib.inc.php");

		try {
			$phpThumb = \PhpThumbFactory::create($source);
			// We reduce the quality to have little thumbnails
			$phpThumb->setOptions(array('jpegQuality' => 75));
			$phpThumb->resize($maxWidth, $maxHeight);
			$phpThumb->save($destination);
		}
		catch(Exception $e) {
			$logger->addLog($e->getMessage());
			return new Error(9602,1);
		}
    
    // We check if we have to fix the orientation
    $orientation = static::getDimensions($source);
    $imageOrientation = false;
    $rotateImage = 0;
    //We convert the exif rotation to degrees for further use
    if (6 == $orientation) {
        $rotateImage = 90;
        $imageOrientation = true;
    } elseif (3 == $orientation) {
        $rotateImage = 180;
        $imageOrientation = true;
    } elseif (8 == $orientation) {
        $rotateImage = 270;
        $imageOrientation = true;
    }
    // We fix the orientation if needed.
    if($imageOrientation) {
      $result = static::rotate($destination, $destination, $rotateImage);
      if($result instanceof Error) {
        return $result;
      }
    }

		return "ok";
	}

	/*
	 *	function: rotate
	 *	This function creates an img rotated.
	 *
	 *	parameters:
	 *		- $source - path to the originale picture.
	 *		- $destination - path to the destination picture.
	 *		- $degrees - the degrees to rotate.
	 *	return:
	 *		- "ok" if rotated else an Error object.
	 */
	public static function rotate($source, $destination, $degrees) {
		global $request;
		$logger = Logger::getInstance();

		// 1. We check that the file exists and is supported
		$supported = static::isSupportedPicture($source);
		if($supported instanceof Error) {
			return $supported;
		}
    
    // We check if we have to fix the orientation
    $orientation = static::getDimensions($source);
    //We convert the exif rotation to degrees for further use
    if (6 == $orientation) {
        $degrees += 90;
    } elseif (3 == $orientation) {
        $degrees += 180;
    } elseif (8 == $orientation) {
        $degrees += 270;
    }

		// First we try with image magick
		require_once($request->getConfig("fwk-extLibDirectory")."phmagick/phmagick.php");

		$start = mktime();

		$phMagick = new phMagick($source);
		$phMagick->setDestination($destination)->rotate($degrees);
		$logs = $phMagick->getLog();

		$end = mktime();
		$logger->addLog("rotationTime = ".($end-$start));

		if($logs[0]["return"] == 0) {
			return "ok";
		}

		// Then we try with GD library
		require_once($request->getConfig("fwk-extLibDirectory")."phpThumb/ThumbLib.inc.php");

		try {
			$phpThumb = PhpThumbFactory::create($source);
			// We reduce the quality to have little thumbnails
			$phpThumb->rotateImageNDegrees(-$degrees);
			$phpThumb->save($destination);
		}
		catch(Exception $e) {
			$logger->addLog($e->getMessage());
			return new Error(9606,1);
		}

		return "ok";
	}

	/*
	 *	function: getDimensions
	 *	This function check the picture dimensions.
	 *
	 *	parameters:
	 *		- $picture - path to the picture.
	 *	return:
	 *		- ["width","height"] if found else an Error object.
	 */
	public static function getDimensions($picture) {
		global $request;

		// 1. We check that the picture is supported
		$supported = static::isSupportedPicture($picture);
		if($supported instanceof Error) {
			return $supported;
		}

		$infos = getimagesize($picture);

		$dimensions = array();
		$dimensions["width"] = $infos[0];
		$dimensions["height"] = $infos[1];

		return $dimensions;
	}
  
	/*
	 *	function: getOrientation
	 *	This function check the picture orientation.
   *  http://www.impulseadventure.com/photo/exif-orientation.html
	 *
	 *	parameters:
	 *		- $picture - path to the picture.
	 *	return:
	 *		- orientation : integer which represent the exif orientation or Error object.
	 */
	public static function getOrientation($picture) {
		// 1. We check that the picture is supported
		$supported = static::isSupportedPicture($picture);
		if($supported instanceof Error) {
			return $supported;
		}

		$exif = exif_read_data($picture);
    $orientation = $exif['IFD0']['Orientation'];

		return $orientation;
	}

	/*
	 *	function: isSupportedPicture
	 *	This function checks if the picture exists and is correct format.
	 *
	 *	parameters:
	 *		- $picture - the file to test.
	 *	return:
	 *		- "ok" if supported else an Error object.
	 */
	public static function isSupportedPicture($picture) {
		$logger = Logger::getInstance();

		// 1. We check that the file exists
		if(!file_exists($picture)) {
			$logger->addLog("ImgUtils:not exist = ".$picture);
			return new Error(9601);
		}

		// 2. We check that we have valid extension
		$extension = FileUtils::getFileExtension($picture);
		if(!preg_match('/jpg|jpeg|png|gif/i',$extension)){
			$logger->addLog("wrong extension = ".$picture);
			return new Error(9603,2);
		}

		return "ok";
	}

	public static function extract_exif_from_pscs_xmp ($filename,$printout=0) {

		// very straightforward one-purpose utility function which
		// reads image data and gets some EXIF data (what I needed) out from its XMP tags (by Adobe Photoshop CS)
		// returns an array with values
		// code by Pekka Saarinen http://photography-on-the.net
    
		ob_start();
		readfile($filename);
		$source = ob_get_contents();
		ob_end_clean();

		$xmpdata_start = strpos($source,"<x:xmpmeta");
		$xmpdata_end = strpos($source,"</x:xmpmeta>");
		$xmplenght = $xmpdata_end-$xmpdata_start;
		$xmpdata = substr($source,$xmpdata_start,$xmplenght+12);

		$xmp_parsed = array();

		$regexps = array(
		array("name" => "DC creator", "regexp" => "/<dc:creator>\s*<rdf:Seq>\s*<rdf:li>(.+)<\/rdf:li>\s*<\/rdf:Seq>\s*<\/dc:creator>/"),
		array("name" => "TIFF camera model", "regexp" => "/<tiff:Model>(.+)<\/tiff:Model>/"),
		array("name" => "TIFF maker", "regexp" => "/<tiff:Make>(.+)<\/tiff:Make>/"),
		array("name" => "EXIF exposure time", "regexp" => "/<exif:ExposureTime>(.+)<\/exif:ExposureTime>/"),
		array("name" => "EXIF f number", "regexp" => "/<exif:FNumber>(.+)<\/exif:FNumber>/"),
		array("name" => "EXIF aperture value", "regexp" => "/<exif:ApertureValue>(.+)<\/exif:ApertureValue>/"),
		array("name" => "EXIF exposure program", "regexp" => "/<exif:ExposureProgram>(.+)<\/exif:ExposureProgram>/"),
		array("name" => "EXIF iso speed ratings", "regexp" => "/<exif:ISOSpeedRatings>\s*<rdf:Seq>\s*<rdf:li>(.+)<\/rdf:li>\s*<\/rdf:Seq>\s*<\/exif:ISOSpeedRatings>/"),
		array("name" => "EXIF datetime original", "regexp" => "/<exif:DateTimeOriginal>.+<\/exif:DateTimeOriginal>/"),
		array("name" => "EXIF exposure bias value", "regexp" => "/<exif:ExposureBiasValue>.+<\/exif:ExposureBiasValue>/"),
		array("name" => "EXIF metering mode", "regexp" => "/<exif:MeteringMode>.+<\/exif:MeteringMode>/"),
		array("name" => "EXIF focal lenght", "regexp" => "/<exif:FocalLength>.+<\/exif:FocalLength>/"),
		array("name" => "AUX lens", "regexp" => "/<aux:Lens>.+<\/aux:Lens>/")
		);

		foreach ($regexps as $key => $k) {
				$name         = $k["name"];
				$regexp     = $k["regexp"];
				unset($r);
				preg_match ($regexp, $xmpdata, $r);
				$xmp_item = "";
				$xmp_item = @$r[1];
				array_push($xmp_parsed,array("item" => $name, "value" => $xmp_item));
		}

		if ($printout == 1) {
			foreach ($xmp_parsed as $key => $k) {
					$item         = $k["item"];
					$value         = $k["value"];
					print "<br><b>" . $item . ":</b> " . $value;
			}
		}

	return ($xmp_parsed);

	}

	private static function setMemoryForImage( $filename ) {

		$imageInfo = getimagesize($filename);

		$MB = Pow(1024,2);   // number of bytes in 1M
		$K64 = Pow(2,16);    // number of bytes in 64K
		$TWEAKFACTOR = 1.8;   // Or whatever works for you
		$memoryNeeded = round( ( $imageInfo[0] * $imageInfo[1]
                                        * $imageInfo['bits']
                                        * $imageInfo['channels'] / 8
        			                  + $K64
                    			    ) * $TWEAKFACTOR
                     			);
		$memoryHave = memory_get_usage();
		$memoryLimitMB = (integer) ini_get('memory_limit');
		$memoryLimit = $memoryLimitMB * $MB;

		echo "Memory used: ".round($memoryHave / $MB)." / Mermory needed: ".round($memoryNeeded / $MB)." / Mermory limit: ".$memoryLimitMB;

		if ( function_exists('memory_get_usage')
		     && $memoryHave + $memoryNeeded > $memoryLimit
		   ) {
		   $newLimit = $memoryLimitMB + ceil( ( $memoryHave
                                      + $memoryNeeded
                                      - $memoryLimit
                                      ) / $MB
                                    );
		   ini_set( 'memory_limit', $newLimit . 'M' );
		   	echo "Memory set: ".$newLimit." / Mermory limit: ".$memoryLimitMB;
		}
	}
}
ImgUtils::init();
?>
