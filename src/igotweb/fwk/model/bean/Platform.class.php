<?php

/**
 *	Class: Platform
 *	Version: 0.1
 *	This class store all information related to current platform.
 *
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;

class Platform extends GenericObject {

  protected $name; // The platform name (local, pine64-production ...).
  protected $contentPath; // The path to dynamic content.
  protected $rootPath; // The platform root path.

  function __construct() {
    $this->name = "local";
    $this->contentPath = NULL;
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
