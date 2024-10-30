<?php

/**
 *	Class: FileLogger
 *	Version: 0.1
 *	This class handle logs.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk;
 
class FileLogger {

  private static $instance;
	
	private $file;
	private $templateName;
	
	private function __construct() {
    global $request;
    $path = $request->getApplication()->getRootPath().$request->getConfig("app-cacheDirectory") . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR;
		if(!file_exists($path)) {
			mkdir($path);
		}
		$this->file = $path."logs.txt";
		$fileHandle = fopen($this->file, 'a+');
		fclose($fileHandle);
  }
  
  /**
    * This method get the instance of Email.
    * @param void
    * @return FileLogger instance
    */
  public static function getInstance() {
    if(is_null(static::$instance)) {
      static::$instance = new Email();  
    }
    return static::$instance;
  }
	
	public function addLog($log) {
		$fileHandle = fopen($this->file, 'a+');
		$completeLog = date("d/m/y H:i:s")." - ".$this->templateName." - ".$log." \n";
		fwrite($fileHandle,$completeLog); 
		fclose($fileHandle);
	}
	
	public function setTemplateName($templateName) {
		$this->templateName = $templateName;	
	}
}

?>