<?php

/**
 *	Class: MailinglistUtils
 *	Version: 0.1
 *	This class handle mailing list.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\EMail;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
 
class MailinglistUtils {
	
	public static $SQL_TABLE = "mailingList";
	
	private $list;
	
	function __construct() {
		$this->list = NULL;
	}
	
	/*
	 *	getList
	 *	This function returns the list of emails.
	 */
	public function getList() {
		if(!isset($this->list)) {
			$this->list = $this->getListFromDB();
		}
		return $this->list;
	}
	
	/*
	 *	addEmail
	 *	This function adds an email in the list.
	 */
	public function addEmail($email) {
		// We check the email address
		if(!isset($email) || $email == "") {
			return new Error(9251);	
		}
		
		// We validate the email address
		$valid = EMail::validateEmail($email);
		if($valid instanceof Error) {
			return $valid;
		}
		
		// We check if email is already subscribed
		$emails = $this->getList();
    if($emails instanceof Error) {
      return $emails;
    }
		if(in_array($email, $emails)) {
			return new Error(9252);
		}
		
		// We add the email to the list in DB
		$added = $this->addEmailInDB($email);
		if($added instanceof Error) {
			return $added;
		}
		
		// We add the email in the list
		array_push($this->list, $email);
		
		return "ok";
	}
	
	/*
	 *	addEmailInDB
	 *	This function adds an email address in DB.
	 */
	private function addEmailInDB($email) {
		$logger = Logger::getInstance();
		
    $tableName = DBUtils::getCurrentConfigTableName(static::$SQL_TABLE);
		
		// 1.1. We store the Gallery
		$queryInsertEMail = "INSERT INTO `".$tableName."` ";
		$queryInsertEMail .= "(`email`) VALUES";
		$queryInsertEMail .= "('".$email."')";
		$resultInsertEMail = DBUtils::query($queryInsertEMail);
	
		if(!$resultInsertEMail) {
			$logger->addLog("queryInsertEMail: ".$queryInsertEMail);
			return new Error(9253,1);
		}
		
		return "ok";
	}
	
	/*
	 *	removeEmail
	 *	This function removes an email from the list.
	 */
	public function removeEmail($email) {
		// We check the email address
		if(!isset($email) || $email == "") {
			return new Error(9251);	
		}
		
		// We validate the email address
		$valid = EMail::validateEmail($email);
		if($valid instanceof Error) {
			return $valid;
		}
		
		// We check that email is already subscribed
		$emails = $this->getList();
		if(!in_array($email, $emails)) {
			return new Error(9254);
		}
		
		// We add the email to the list in DB
		$removed = $this->removeEmailFromDB($email);
		if($removed instanceof Error) {
			return $removed;
		}
		
		// We remove the email from the list
		Utils::removeFromArray($this->list, $email);
		
		return "ok";
	}
	
	/*
	 *	removeEmailFromDB
	 *	This function removes an email address from DB.
	 */
	private function removeEmailFromDB($email) {
		$logger = Logger::getInstance();
		
    $tableName = DBUtils::getCurrentConfigTableName(static::$SQL_TABLE);
		
		// 1.1. We store the Gallery
		$queryDeleteEmail = "DELETE FROM `".$tableName."` ";
		$queryDeleteEmail .= "WHERE `email`='".$email."'";
		$resultDeleteEMail = DBUtils::query($queryDeleteEmail);
	
		if(!$resultDeleteEMail) {
			$logger->addLog("resultDeleteEMail: ".$resultDeleteEMail);
			return new Error(9255,1);
		}
		
		return "ok";
	}
	
	/*
	 *	getListFromDB
	 *	This function returns the list of emails stored in DB.
	 */
	private function getListFromDB() {
		$logger = Logger::getInstance();
		
    $tableName = DBUtils::getCurrentConfigTableName(static::$SQL_TABLE);
		
		// 1. We get the list from DB.
		$queryGetList = "SELECT * FROM `".$tableName."` ";		
		$resultGetList = DBUtils::query($queryGetList);
			
		if(!$resultGetList) {
			$logger->addLog("queryGetList: ".$queryGetList);
			return new Error(9250,1);
		}
		
		// 3. We create the list of email.
		$emails = array();
		while($mailingListDatas = DBUtils::fetchAssoc($resultGetList)) {
			$email = $mailingListDatas["email"];
			array_push($emails,$email);
		}
		
		return $emails;
	}

	
}
?>