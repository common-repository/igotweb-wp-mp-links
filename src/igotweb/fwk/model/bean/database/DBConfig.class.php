<?php
/**
 *	Class: DBConfig
 *	Version: 0.1
 *	This class handle DataBase configuration.
 *
 *	Requires:
 *		- suffix variable,
 *		- Error object,
 *		- Logger,
 *		- request,
 *		- Table and Column object,
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\database;

use igotweb_wp_mp_links\igotweb\fwk\model\manager\ConfigurationManager;
use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Platform;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericObject;

class DBConfig extends GenericObject {

  private static $SQL_FILE_PATH = "config/configSQL.ini";

  protected $user;
  protected $password;
  protected $server;
  protected $dataBase;
  protected $charset;
  protected $prefixTables;
  protected $suffixTables;

  protected function __construct() {}
  
  /*
   *	getFwkConfig
   *	This method get the framework DB config for platform in parameter
   *
   *  parameters:
   *    - $platform - the platform.
   *    - $request - the request object.
   *  return:
   *    - DBConfig object if found or NULL.
   */
  public static function getFwkConfig(Platform $platform, Request $request) {
    // We get the framework DB configuration for current platform
	  $config = NULL;
    $fwkPath = $request->getFwkRootPath().DIRECTORY_SEPARATOR.static::$SQL_FILE_PATH;
    if(file_exists($fwkPath)) {
      // We get the configuration
      $config = static::getFromPath($fwkPath, $platform);
    }
	  return $config;
  }
  
  /*
   *	getFromSiteContext
   *	This method get the DB config for site context in parameter
   *
   *  parameters:
   *    - $siteContext - the siteContext.
   *  return:
   *    - DBConfig object if found or NULL.
   */
  public static function getFromSiteContext(SiteContext $siteContext) {
    $config = NULL;
    //1. We get the config path
    $path = static::getConfigPath($siteContext);
    if(isset($path)) {
      $config = static::getFromPath($path, $siteContext->getPlatform());
    }
    
    // 2. In case of admin mode, we add the admin suffix to the table name
    if($siteContext->getAdminMode() && $config instanceof DBConfig) {
      $config->setSuffixTables("-admin");
    }
    return $config;
  }
  
  /*
   *	function: getConfigPath
   *	This function get the configuration path.
   *	It looks in webapp config and in site specific config.
   */
  private static function getConfigPath(SiteContext $siteContext) {
    // We get the webapp configuration file.
    $webapp = $siteContext->getApplication()->getWebapp();
    $site = $siteContext->getApplication()->getSite();
    $defaultPath = $webapp->getRootPath().static::$SQL_FILE_PATH;
    $path = $defaultPath;
    if($site != NULL) {
      // If there is a site specific configuration.
      $sitePath = $site->getRootPath().static::$SQL_FILE_PATH;
      if(file_exists($sitePath)) {
        $path = $sitePath;
      }
    }

    if(file_exists($path)) {
      return $path;
    }
    return NULL;
  }

  private static function getFromPath($path, Platform $platform) {
    // We get the configuration
    $config = IniFilesUtils::getConfiguration($path);
    
    // We check if there is a platform specific config
    $config = ConfigurationManager::getPlatformConfiguration($config,$platform);
    
    // We build the object
    $obj = new DBConfig();
    $obj->setUser($config["db_user"]);
    $obj->setPassword($config["db_pass"]);
    $obj->setServer($config["db_server"]);
    $obj->setDataBase($config["db_base"]);
    $obj->setCharset($config["charset"]);
    
    $prefix = "";
    if(isset($config["prefixTables"])) {
      $prefix = $config["prefixTables"];
    }
    $obj->setPrefixTables($prefix);

    return $obj;
  }
  
  public function equals($dbConfig) {
    // 1. We check if the object is a DBConfig;
    if(!$dbConfig instanceof DBConfig) {
      return false;
    }
    
    return $this->user == $dbConfig->getUser() &&
      $this->password == $dbConfig->getPassword() &&
      $this->server == $dbConfig->getServer() &&
      $this->dataBase == $dbConfig->getDataBase() &&
      $this->charset == $dbConfig->getCharset() &&
      $this->prefixTables == $dbConfig->getPrefixTables() &&
      $this->suffixTables == $dbConfig->getSuffixTables();
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
