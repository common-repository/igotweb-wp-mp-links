<?php

/**
 *	Class: RawItem
 *	Version: 0.1
 *	This class represents a raw item (line of configuration file).
 *
 *	requires:
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\configuration;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;

class RawItem extends GenericBean {
	
	private $key;
	private $value;
	
	private $comment;
	
	private $isEmptyLine;
	
	private $sections;
	
	function __construct($key = NULL, $value = NULL) {
		
		$this->isEmptyLine = false;
		$this->sections = array();
		
		if(!isset($key)) {
			$this->isEmptyLine = true;
		}
		
		$this->key = $key;
		$this->value = $value;
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function setKey($key) {
		$this->key = $key;
		
		if(isset($key)) {
			$this->isEmptyLine = false;
		}
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function getComment() {
		return $this->comment;
	}
	
	public function setComment($comment) {
		$this->comment = $comment;
		
		if(isset($comment)) {
			$this->isEmptyLine = false;
		}
	}
	
	public function getIsEmptyLine() {
		return $this->isEmptyLine;
	}
	
	public function setIsEmptyLine($isEmptyLine) {
		$this->isEmptyLine = $isEmptyLine;
	}
	
	public function getSections() {
		return $this->sections;
	}
	
	public function setSections($sections) {
		$this->sections = $sections;
	}
	
}
?>