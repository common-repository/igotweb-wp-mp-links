<?php

/**
 *	Class: Error
 *	Version: 0.3
 *	This class handle Error objects.
 *
 *	requires:
 *		- Language file with errors label.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

class Error extends Message {
	
  private static $GENERIC_ERROR_NUMBER = 3006;

  private $number;
  private $secondNumber;

  function __construct($number,$secondNumber = NULL, $parameters = NULL) {
    $this->number = $number;
    $this->secondNumber = $secondNumber;
    parent::__construct($number,$parameters,"error");
  }
  
  /*
   *	static function: getGeneric
   *	This function returns a generic message object.
   *
   *	parameters:
   *		- $key : the message key.
   *		- $message : the associated message.
   *		- $parameters : parameters used to generate the message.
   *		- $type : the type of message.
   *	return:
   *		- Message object.
   */
  public static function getGeneric($message) {
    $error = new Error(self::$GENERIC_ERROR_NUMBER,NULL, NULL);
    $error->setMessage($message);
    return $error;
  }

  public function getNumber() {
    return $this->number;
  }

  public function getSecondNumber() {
    if($this->secondNumber!=NULL && $this->secondNumber!="") {
      return $this->secondNumber;
    }
    else {
      return "";
    }
  }

  public function getFormattedMessage() {
    if($this->number != NULL && $this->secondNumber != NULL && $this->secondNumber != "") {
      $message = $this->getMessage();
      return $message." (".$this->number." - ".$this->secondNumber.")";
    }
    return parent::getFormattedMessage();
  }

  public function toArray() {
    $message = parent::toArray();
    $message["number"] = $this->getNumber();
    $message["secondNumber"] = $this->getSecondNumber();
    return $message;
  }

  public static function generateErrorPanel($panelId,$title,$messages) {
    Message::generateMessagePanel($panelId,$title,$messages,"error");
  }
}

?>
