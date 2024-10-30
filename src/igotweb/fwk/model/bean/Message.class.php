<?php

/**
 *	Class: Message
 *	Version: 0.3
 *	This class handle Message objects.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;

class Message {

	private $key;
	
	private $message; // The associated message.
	private $parameters; // parameters are used to generate the message in case it is a template.

	private $type; // Can be error, warning, info

	function __construct($key,$parameters = NULL, $type = "message") {
	  $this->key = $key;  
	  $this->type = $type;
	  
	  $this->parameters = $parameters;
	}
	
	public function getKey() {
	  return $this->key;
	}

	public function getType() {
		return $this->type;
	}

	public function setType($type) {
		$this->type = $type;
	}
	
	private function populateMessage($parameters) {
	  global $request;
	  
	  if(is_string($parameters)) {
	    $parameters = array($parameters);	
	  }

	  $message = "";

	  // We include the array corresponding to messages with type
	  $var = $this->type . "_msg";
	  if(isset($request)) {
  	    $languageManager = $request->getLanguageManager();
      	  if($languageManager->getResourceFileType() == "php") {
      	    $index = $this->type . "_" . $this->key;
      	    $message = $languageManager->getStringFromList($var, $index, $this->parameters);
      	  }
      	  else {
      	    $var .= ".".$this->key;
      	    $message = $languageManager->getString($var, $this->parameters);
      	  }
  	  }
	  else {
	    $message = $this->key;
	  }

	  $this->message = $message;	
	}
	
	protected function setMessage($message) {
	  $this->message = $message;	
	}

	protected function getMessage() {
	    if(!isset($this->message)) {
	      $this->populateMessage($this->parameters);	
	    }
	  return $this->message;
	}

	public function getFormattedMessage() {
		$message = $this->getMessage();
		if(isset($this->key)) {
			$message .= " (".$this->key.")";
		}
		return $message;
	}

	public function toArray() {
		$message = array();
		$message["key"] = $this->getKey();
		$message["formattedMessage"] = $this->getFormattedMessage();

		return $message;
	}

	public function toJSON() {

		$bean = $this->toArray();
		$JSONBean = JSONUtils::buildJSONObject($bean);

		return $JSONBean;
	}

	/*
	 *	static function: generateMessagePanel
	 *	This function generate the HTML code for an message panel.
	 *	If there are messages, there are displayed with the panel.
	 *
	 *	parameters:
	 *		- $panelId : the html id of the panel.
	 *		- $title : the title of the message panel.
	 *		- $messages : the list of messages to display by default.
	 *		- $type : the type of messages in panel.
	 *	return:
	 *		- none.
	 */
	public static function generateMessagePanel($panelId,$title,$messages = array(),$type) {
	  global $request;

	  $isMessage = (isset($messages) && count($messages) > 0);
	  $data = array(
	      "title" => $title,
	      "type" => $type,
	      "messages" => $messages
	  );

	  echo '<div class="messages-container" id="'.$panelId.'">';
	  if($isMessage) {
	    $request->includeTemplate("messages",$data);
	  }
	  echo '</div> '."\n";
	}

}

?>
