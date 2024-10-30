<?php

/**
 *	Class: Logger
 *	Version: 0.1
 *	This class handle logs.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger {

  private static $instance;
  
  private $listLogs;

	private function __construct() {
		$this->listLogs = array();
  }
  
  /**
    * This method get the instance of Email.
    * @param void
    * @return FileLogger instance
    */
  public static function getInstance() {
    if(is_null(static::$instance)) {
      static::$instance = new Logger();  
    }
    return static::$instance;
  }

	public function getListLogs() {
		return $this->listLogs;
	}

	public function addLog($object, $logInPhp = false, $display = true) {
		if(isset($object)) {
			array_push($this->listLogs,$object);
		}
		if($logInPhp && is_string($object)) {
		  if($display && ini_get('display_errors') == "stderr" || ini_get('display_errors') === "1") {
		    echo "<br/><b>Logger</b>: ".$object."<br/>";
		  }
		  error_log((string) $object, 0);
		}
	}

	public function addErrorLog(Error $error, $logInPhp = false, $display = true) {
		if(isset($error) && $error instanceof Error) {
		  $this->addLog($error->getFormattedMessage(), $logInPhp, $display);
		}
	}
	
	public function displayLogs() {
    $htmlLogs = '<div id="logs"><h3>Logs</h3><ul>';
		for( $i = 0 ; $i < count($this->listLogs) ; $i++) {
			$log = $this->listLogs[$i];
			if(!is_string($log)) {
				$log = JSONUtils::buildJSONObject($log);
			}
			$htmlLogs .= '<li>'.$log.'</li>';
		}
		$htmlLogs .= '</ul></div>';
		echo $htmlLogs;
	}

  /*
   *  This is the PSR-3 log method of the AbstractLogger class.
   */
  public function log($logLevel, $message, array $context = array()) {
    $this->addLog($logLevel . $message . " / " . implode ( " - " , $context ));
  }

}
?>