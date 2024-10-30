<?php

/**
 *	Class: Webapp
 *	Version: 0.1
 *	This class handle the webapp.
 *
 *	requires:
 *		- suffix.
 *		- IniFilesUtils.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\ConfigurationManager;

class Webapp extends GenericObject {
  
  public static $DESCRIPTION_PATH = "./config/webapp.ini";

  protected $shortName; // The short name of the webapp is the name used as folder name.
  protected $name; // The name of the webapp is an understandable label (stored in DESCRIPTION_PATH).
  
  protected $rootPath; // The server side webapp absolute root path.
  
  /*
	 *	Constructor
	 *	It creates an Webapp with no SQLid.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Webapp object.
	 */	
	public function __construct() {
		$this->shortName = NULL;
		$this->name = NULL;
    $this->rootPath = NULL;
	}
	
	/*
	 * function: __call
	 * Generic getter for properties.
	 */
	public function __call($method, $params) {
	  return $this->handleGetterSetter($method, $params);
	}
}
?>
