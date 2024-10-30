<?php

/**
 *	Class: RawConfiguration
 *	Version: 0.1
 *	This class represents a raw configuration.
 *
 *	requires:
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\configuration;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\configuration\RawItem;

class RawConfiguration extends GenericBean {
	private $list;
	private $map;
	
	function __construct($list = array(), $map = array()) {
		$this->list = $list;
		$this->map = $map;
	}
	
	public function addRawItem($raw) {
    if(!isset($raw)) {
      return;
    }
    
		$this->list[] = $raw;
		
		if($raw->getKey() !== NULL) {
			$this->map[$raw->getKey()] = count($this->list) - 1;
		}
	}
  
  /*
	 *	getLastRawItem
	 *	This function returns the last RawItem from the configuration.
	 */
  public function getLastRawItem() {
    return $this->list[count($this->list) - 1];
  }
	
	/*
	 *	removeRawItem
	 *	This function removes a raw item from configuration
	 *	based on the key in parameter.
	 */
	public function removeRawItem($key) {
		// 1. We check that the key exists
		if(isset($key) && $key != "" && !isset($this->map[$key])) {
			return;
		}
		
		// 2. We remove the item from the list
		unset($this->list[$this->map[$key]]);
		
		// 3. We reindex the list and regenerate the map
		$this->list = array_values($this->list);
		$this->map = array();
		foreach($this->list as $index => $raw) {
			if($raw->getKey() !== NULL) {
				$this->map[$raw->getKey()] = $index;
			}		
		}
	}
	
	public function getList() {
		return $this->list;
	}
	
	public function getMap() {
		return $this->map;
	}
}
?>
