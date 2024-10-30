<?php
/**
 *	Class: DBManager
 *	Version: 0.1
 *	This class handle DataBase connections.
 *
 *	Requires:
 *		- suffix variable,
 *		- Error object,
 *		- Logger,
 *		- request,
 *		- Table and Column object,
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Webapp;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\DBConfig;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;

class DBManager {

  private $config; // map which contains DBConfig for site contexts
  private $fwkConfig; // DBConfig for the platform / framework

  public function __construct(Request $request) {
    // We init the config map
    $this->config = array();
	  $this->fwkConfig = array();
    
    // We get the framework DB configuration for current platform
	  $platform = $request->getPlatform();
    // We get the configuration
    $this->fwkConfig[$platform->getName()] = DBConfig::getFwkConfig($platform, $request);
  }
  
    /*
   *	getConfig
   *	This method get the DB config for site context in parameter
   *
   *  parameters:
   *    - $siteContext - the siteContext.
   *    - $request - the request object.
   *  return:
   *    - DBConfig object if found or NULL.
   */
  private function getConfig(SiteContext $siteContext, Request $request) {
    $config = NULL;
    $contextShortName = $siteContext->getShortName();
  
    // We check if the config does not already exist.  
    if(!isset($this->config[$contextShortName])) {
      $this->populateConfig($siteContext, $request);
    }
    
	// We get the config
    $config = $this->config[$contextShortName];
    if($config == "noconfig") {
      return NULL;
    }
    
    return $config;
  }
  
  private function populateConfig(SiteContext $siteContext, Request $request) {
	// We get the configuration
	$config = DBConfig::getFromSiteContext($siteContext, $request);
	if(!isset($config)) {
	  $config = "noconfig";
	}
    
    // We store the config.
    $this->updateConfig($siteContext, $config);
  }
  
  /*
   *	function: updateConfig
   *	This function set a config for a corresponding site context.
   */
  private function updateConfig(SiteContext $siteContext, $config) {
    $contextShortName = $siteContext->getShortName();
    $this->config[$contextShortName] = $config;
  }
  
  /*
   *  function: connect
   *  This function check the connection to be connected to siteContext DB.
   *
   *  parameters:
   *    - $webapp - the webapp object (optional).
   *    - $site - the site object (optional).
   *  return:
   *    - "ok" if connected.
   */
  public function connect(SiteContext $siteContext = NULL, Request $request) {

    // We use the current site context if not provided
    if(!isset($siteContext)) {
      $siteContext = $request->getSiteContext();
    }
    
    // 1. We check if the config is set.
    $config = $this->getConfig($siteContext, $request);
    if(!isset($config) || !$config instanceof DBConfig) {
      // We disconnect the current connexion if any to avoid any operation on wrong DB.
      DBUtils::disconnect();
      if($config instanceof Error) {
        // In case we have an error
        return new Error(9751);
      }
      // There is no configuration
      return "ok";
    }
    
    // 2. We check if we are already connected.
    if($config->equals(DBUtils::getCurrentConfig())) {
      return "ok";
    }
    
    // We disconnect the current connexion if any.
    DBUtils::disconnect();
    // We connect to the DB
    return DBUtils::connect($config);
  }
  
  /*
   * function: connectFwk
   * This function check the connection to be connected to framework DB for current platform.
   */
  public function connectFwk(Request $request) {
    $platformName = $request->getPlatform()->getName();
	
    // 1. We check if the config is set.
    if(!isset($this->fwkConfig) || !isset($this->fwkConfig[$platformName]) || !$this->fwkConfig[$platformName] instanceof DBConfig) {
      return new Error(9752);
    }
    
    // 2. We check if we are already connected.
    if($this->fwkConfig[$platformName]->equals(DBUtils::getCurrentConfig())) {
      return "ok";
    }
    
    // We connect to the DB
    return DBUtils::connect($this->fwkConfig[$platformName]);
  }
}

?>
