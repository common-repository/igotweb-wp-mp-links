<?php

/**
 *	Class: IwDateTime
 *	This class handle the IwDateTime object which is a DateTime (standard php5 object) extension.
 *
 *	requires:
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use DateTime;

class IwDateTime extends DateTime {

	private $_str;
	public static $SQLFormat = "Y-m-d H:i:s";
  public static $JSONFormatDateTime = "Y-m-d H:i:s";
  public static $JSONFormatDate = "Y-m-d";
	
	/*
	 *	static function: getNow
	 *	This function return a new IwDateTime initialized with now date & time.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- IwDate.
	 */
	public static function getNow() {
		return new IwDateTime(date("Y-m-d H:i:s"));	
	}
	
	/*
	 *	static function: getFromDate
	 *	This function return a new IwDateTime initialized with date parameters.
	 *	
	 *	parameters:
	 *		- $year - the year number.
	 *		- $month - the month number.
	 *		- $day - the day number.
	 *	return:
	 *		- IwDateTime.
	 */
	public static function getFromDate($year,$month,$day) {
		$iwDateTime = new IwDateTime();
		$iwDateTime->setTime(0,0,0);
		$iwDateTime->setDate(intval($year),intval($month),intval($day));
		return $iwDateTime;
  }
  
  /*
	 *	static function: createFromFormat
	 *	This function return a new IwDateTime based on string and pattern.
	 *	
	 *	parameters:
   *    - $format - the pattern used to get the date object. (https://secure.php.net/manual/fr/datetime.createfromformat.php).
   *    - $time - the date string formatted.
	 *	return:
	 *		- IwDateTime.
	 */
  public static function createFromFormat($format, $time, $object = NULL) {
    return new IwDateTime(parent::createFromFormat($format, $time, $object)->format("Y-m-d H:i:s"));
  }
  
	
	/*
	 *	static function: getFromSQLDateTime
	 *	This function return a new IwDateTime initialized with SQL DateTime formatted.
	 *	
	 *	parameters:
	 *		- $SQLDateTime - date formatted in SQL.
	 *	return:
	 *		- IwDateTime.
	 */
	public static function getFromSQLDateTime($SQLDateTime) {
		if(!isset($SQLDateTime) || $SQLDateTime == "NULL" || $SQLDateTime == "" || $SQLDateTime == "0000-00-00") {
			return NULL;	
		}
		return new IwDateTime($SQLDateTime);
	}
	
	/*
	 *	function: format
   *	This function use the classic format that is not localized.
   *  http://php.net/manual/en/function.date.php
	 *	
	 *	parameters:
	 *		- format - the format of the date.
	 *	return:
	 *		- formatted date.
	 */
	public function format($format) {
		return parent::format($format);
  }
  
  /*
	 *	function: localeFormat
   *	This function format the date in the current Locale.
   *	http://php.net/manual/en/function.strftime.php
	 *	
	 *	parameters:
	 *		- format - the format of the date.
	 *	return:
	 *		- formatted date.
	 */
	public function localeFormat($format) {
		return strftime($format, $this->getTimestamp());
	}
	
	/*
	 *	function: getSQLDateTime
	 *	This function get the date in SQL DateTime format.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- JSON representation of the user.
	 */
	public function getSQLDateTime() {
		return $this->format(IwDateTime::$SQLFormat);	
	}
	
	/*
	 *	function: getDayNumber
	 *	This function get the int value of the day number.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int corresponding to day number.
	 */
	public function getDayNumber() {
		return intval($this->format("j"));
	}
	
	/*
	 *	function: getMonthNumber
	 *	This function get the int value of the month number.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int corresponding to month number.
	 */
	public function getMonthNumber() {
		return intval($this->format("n"));
	}
	
	/*
	 *	function: getYearNumber
	 *	This function get the int value of the year number.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int corresponding to year number.
	 */
	public function getYearNumber() {
		return intval($this->format("Y"));
	}
	
	/*
	 *	function: isBefore
	 *	This function return true if the object is before the DateTime in parameter.
	 *	
	 *	parameters:
	 *		- DateTime - a DateTime object.
	 *		- equals - if set to true, then if equals DateTime will return true.
	 *	return:
	 *		- true if the object is before the DateTime in parameter.
	 */
	public function isBefore($dateTime,$equals = false) {
		if($this->format('U') < $dateTime->format('U')) {
			return true;	
		}
		if($equals && $this->format('U') == $dateTime->format('U')) {
			return true;
		}
		return false;
	}
	
	/*
	 *	function: isAfter
	 *	This function return true if the object is after the DateTime in parameter.
	 *	
	 *	parameters:
	 *		- DateTime - a DateTime object.
	 *		- equals - if set to true, then if equals DateTime will return true.
	 *	return:
	 *		- true if the object is after the DateTime in parameter.
	 */
	public function isAfter($dateTime,$equals = false) {
		if($this->format('U') > $dateTime->format('U')) {
			return true;	
		}
		if($equals && $this->format('U') == $dateTime->format('U')) {
			return true;
		}
		return false;
	}
	
	/*
	 *	function: toJSON
	 *	This function convert the object in json format.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- JSON representation of the user.
	 */
	public function toJSON() {
		global $Session;
		
		$iwDate = $this->format(IwDateTime::$JSONFormatDateTime);

		$JSONIwDate = JSONUtils::buildJSONObject($iwDate);
		
		return $JSONIwDate;
	}


	public function __sleep(){
		$this->_str = $this->format('c');
		return array('_str');
	}
   
	public function __wakeup() {
		$this->__construct($this->_str);
	} 

}

?>
