<?php

/**
 *	Class: StaticsManager
 *	Version: 0.1
 *	This class handle the statics resources.
 *
 *	requires:
 *		- Request.
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\utilities\IniFilesUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\WebappUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Application;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class StaticsManager {

  private static $STATICS_FILE_PATH = "config/statics.ini";

  public static $styleContentType = 'text/css';
  public static $scriptContentType = 'text/javascript';
  public static $jsonContentType = 'application/json';

  const TYPE_CSS = 'css';
  const TYPE_JS = 'js';

  private $staticsConfig; // This is the static config from statics.ini
  private $customStaticsConfig; // This is the additional config added dynamically.
  private $pageStatics;

  private $minify; // The Minify instance.
  private $minifyCache; // The Minify Cache instance.

  function __construct(Request $request) {
  
  }

  /*
   *	function: getStaticRelativePath
  *	This method get a specific static file relative path from the application statics path.
  * This can be used to access specific static file server side or 
  * to generate static path directly to the file on client side (used in debug mode).
  *
  *	parameters:
  *		- path : the path to the static file.
  *   - scope : the scope (only works for site, webapp, fwk)
  *		- type : the type (scripts, styles)
  *   - separator : the path separator.
  *	return:
  *		- none.
  */
  public function getStaticRelativePath($path, $scope, $type, $separator) {
    global $request; 

    $typePath = "";
    switch($type) {
      case "styles":
        $typePath = $request->getConfig("fwk-cssDirectory") . $separator;
        break;
      case "scripts":
        $typePath = $request->getConfig("fwk-javascriptsDirectory") . $separator;
        break;
    }

    $application = $request->getApplication();
    $scopePath = "";
    switch($scope) {
      case "site":
        if(!Site::isNoSite($application->getSite())) {
          $scopePath = $application->getSite()->getShortName() . $separator;
        }
        break;
      case "webapp":
        $scopePath = "";
        break;
      case "fwk":
        $scopePath = Application::$FWK_SHORT_NAME . $separator;
        break;
    }
    
    return $typePath . $scopePath . $path;
  }

  /*
   *	function: includeStatic
  *	This method includes a specific static file in the page stored in webapp or site static folder.
  * It generates the HTML content link.
  * In case of fwk-jsDebug we generate direct link to static file. 
  * In case of no debug, we generate a link to application entry point which will use the loadStatic method (allow minify...).
  *
  *	parameters:
  *		- path : the path to the static file.
  *   - scope : the scope (only works for site, webapp)
  *		- type : the type (scripts, styles, styles-print)
  *	return:
  *		- none.
  */
  public function includeStatic($path, $scope, $type) {
    $logger = Logger::getInstance();
    global $request;

    
    
    // By default the path are the one for the webapp
    $webRelativePath = $this->getStaticRelativePath($path, $scope, $type, DIRECTORY_SEPARATOR);
    $webRootPath = $request->getApplication()->getWebRootPath() . $webRelativePath;
    
    if(file_exists($webRootPath)) {
      // In case we are not in debug, we update the path to be handle via php
      $staticPath = UrlUtils::getSiteRoot($request) . $type . "/" . $scope . "/" .$path;
      if($request->getConfigAsBoolean("fwk-jsDebug")) {
        $staticPath = UrlUtils::getSiteRoot($request) . $this->getStaticRelativePath($path, $scope, $type,"/");
      }
      
      switch($type) {
        case "styles":
          echo "<link href=\"" . $staticPath . "\" rel=\"stylesheet\" type=\"text/css\" /> \n";
          break;
        case "styles-print":
          echo "<link href=\"" . $staticPath . "\" rel=\"stylesheet\" type=\"text/css\" media=\"print\" /> \n";
          break;
        case "scripts":
          echo "<script language=\"javascript\" type=\"text/javascript\" src=\"" . $staticPath . "\"></script>";
          break;
      }
    }
    else {
      $logger->addLog("StaticsManager->includeStatic : cannot include path: " . $webRootPath, true, false);
    }
  }

  /*
   *	loadStatic
  *	This method generate http response for static (script, style, template)
  *	for specific context (page, template).
  *
  *	parameters:
  *		- name : The name of the static.
  *		- scope : the scope (template, page, site, webapp)
  *		- type : the type (scripts, styles, template)
  *	return:
  *		- none.
  */
  public function loadStatic($name, $scope, $type) {
    $logger = Logger::getInstance();
    global $request;

    // We generate the header
    switch($type) {
      case "scripts":
        $request->sendCustomHeader('Content-type', static::$scriptContentType);
        break;

      case "styles":
      case "styles-print":
        $request->sendCustomHeader('Content-type', static::$styleContentType);
        break;

      case "template":
        $request->sendCustomHeader('Content-type', static::$jsonContentType);
        break;
    }

    // We generate the static
    $content = $this->getStatic($name, $scope, $type);
    if($content instanceof Error) {
      header("HTTP/1.0 404 Not Found");
      $logger->addErrorLog($content, true);
    }
    else {
      if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
        ob_start("ob_gzhandler");
      }
      else {
        ob_start();
      }
      echo $content;
      ob_flush();
    }
  }

  /*
   *	getStatic
  *	This method returns string with static content (scripts, styles, styles-print, templates)
  *	for specific scope (page, template, webapp, site).
  *
  *	parameters:
  *		- name - the name of identifier corresponding to the scope.
  *   - scope - the scope of the statics to be included.
  *   - type - the type of expected static for the scope and name.
  *	return:
  *		- String - String containing all style content.
  */
  public function getStatic($name, $scope, $type) {

    global $request;

    switch($type) {

      case "scripts":
        switch($scope) {
          case "page":

            $pageStatics = $this->getPageStatics($name);
            $script = "";

            /* YAHOO UI SCRIPT */
            if(isset($pageStatics["scripts"]["yui"]) && count($pageStatics["scripts"]["yui"] > 0)) {
              // We include component script
              $script .= $this->getMergedScript($pageStatics["scripts"]["yui"]);
              // We include component inline script
              if(isset($pageStatics["inlineScript"]["yui"])) {
                $script .= $pageStatics["inlineScript"]["yui"];
              }
            }

            /* JQUERY SCRIPT */
            if(isset($pageStatics["scripts"]["jQuery"]) && count($pageStatics["scripts"]["jQuery"] > 0)) {
              // We include component script
              $script .= $this->getMergedScript($pageStatics["scripts"]["jQuery"]);
              // We include component inline script
              if(isset($pageStatics["inlineScript"]["jQuery"])) {
                $script .= $pageStatics["inlineScript"]["jQuery"];
              }
            }

            /* FRAMEWORK SCRIPT */
            if(isset($pageStatics["scripts"]["fwk"]) && count($pageStatics["scripts"]["fwk"] > 0)) {
              // We include component script
              $script .= $this->getMergedScript($pageStatics["scripts"]["fwk"]);
            }

            /* EXT SCRIPT */
            if(isset($pageStatics["scripts"]["ext"]) && count($pageStatics["scripts"]["ext"] > 0)) {
              // We include component script
              $script .= $this->getMergedScript($pageStatics["scripts"]["ext"]);
            }

            /* SITE SPECIFIC SCRIPT */
            if(isset($pageStatics["scripts"]["site"]) && count($pageStatics["scripts"]["site"] > 0)) {
              // We include component script
              $script .= $this->getMergedScript($pageStatics["scripts"]["site"]);
            }

            return $script;
          break;

          case "template":
            return $request->getTemplateManager()->getTemplateScript($name);
          break;
          
          case "webapp":
            $path = $request->getApplication()->getWebapp()->getWebRootPath() . $request->getConfig("fwk-javascriptsDirectory") . DIRECTORY_SEPARATOR . $name;
            return $this->getMergedScript($path);
          break;
          
          case "site":
            $path = $request->getApplication()->getSite()->getWebRootPath() . $request->getConfig("fwk-javascriptsDirectory") . DIRECTORY_SEPARATOR . $name;
            return $this->getMergedScript($path);
          break;
        }
        break;

      case "styles":
        switch($scope) {
          case "page":
            $pageStatics = $this->getPageStatics($name);
            if(count($pageStatics["styles"]["screen"]) > 0) {
              return $this->getMergedStyle($pageStatics["styles"]["screen"]);
            }
          break;

          case "template":
            return $request->getTemplateManager()->getTemplateStyle($name);
          break;
          
          case "webapp":
            $path = $request->getApplication()->getWebapp()->getWebRootPath() . $request->getConfig("fwk-cssDirectory") . DIRECTORY_SEPARATOR . $name;
            return $this->getMergedStyle($path);
          break;
          
          case "site":
            $path = $request->getApplication()->getSite()->getWebRootPath() . $request->getConfig("fwk-cssDirectory") . DIRECTORY_SEPARATOR . $name;
            return $this->getMergedStyle($path);
          break;
        }
        break;

      case "styles-print":
        switch($scope) {
          case "page":
            $pageStatics = $this->getPageStatics($name);
            if(count($pageStatics["styles"]["print"]) > 0) {
              return $this->getMergedStyle($pageStatics["styles"]["print"]);
            }
          break;
        }
        break;

      case "template":
        global $request;

        $request->requireTemplate($name);

        return $request->toJSON();
        break;
    }
  }

  /*
   *	checkPageStaticsRequiredConfiguration
  *	This method check based on the statics included within the page the required configuration elements to be
  *	provided within the JSONData.
  *
  *	parameters:
  *		- pageName : the page name.
  */
  public function checkPageStaticsRequiredConfiguration($pageName) {
    global $request;

    $configKeys = array();

    // We get the js from framework
    $fwkJs = $this->getPageStaticsConfig($pageName, "fwk-js");

    if(in_array("ext.Recaptcha", $fwkJs)) {
      // We need the public key for Recaptcha
      array_push($configKeys, "fwk-recaptchaPublicKey");
    }
    array_push($configKeys, "fwk-jsTemplateEngine");

    $request->jsonDataAddConfigKeys($configKeys);
  }

  /*
   *	getStaticsConfig
  *	This method returns the complete configuration for statics via ConfigManager.
  */
  public function getStaticsConfig() {
    global $request;

    if(!isset($this->staticsConfig)) {
      $application = $request->getApplication();
      $webapp = $application->getWebapp();
      $site = $application->getSite();

      // We get the framework default statics on pages.
      $staticsConfig = IniFilesUtils::getConfiguration($request->getFwkRootPath().static::$STATICS_FILE_PATH);
      // We get the webapp default statics
      $staticsConfig = IniFilesUtils::getConfiguration($webapp->getRootPath().static::$STATICS_FILE_PATH,$staticsConfig);
      // We get the site specific statics
      if(!Site::isNoSite($site)) {
        $path = $site->getRootPath().static::$STATICS_FILE_PATH;
        if(file_exists($path)) {
          $siteStaticsConfig = IniFilesUtils::getConfiguration($path);
          // We merge the arrays of statics
          $staticsConfig = array_merge_recursive($staticsConfig,$siteStaticsConfig);
        }
      }
      $this->staticsConfig = $staticsConfig;
    }
    return $this->staticsConfig;
  }

  /*
   *	addStaticInPageConfig
  *	This method allows to update the page config on the fly by adding new statics.
  * !!! Be careful as this has to be done as well in the static request in case of production mode !!!
  * To be taken by loadStatic method and be available in the list of merged scripts.
  *
  *	parameters:
  *		- pageName : the page name.
  *		- type : the type of statics (fwk-js, yui-js, jquery-plugins-js...)
  *   - path : the path (as provided in the statics.ini)
  */
  public function addStaticInPageConfig($pageName, $path, $type) {
    $staticsConfig = $this->getStaticsConfig();

    // We check if we update for specific page or for default based on existing config.
    $key = $pageName;
    if(!isset($staticsConfig[$type][$pageName])) {
      $key = "default";
    }

    // We update the custom statics config
    if(!isset($this->customStaticsConfig[$type])) {
      $this->customStaticsConfig[$type] = array();
    }
    if(!isset($this->customStaticsConfig[$type][$key])) {
      $this->customStaticsConfig[$type][$key] = array();
    }
    $this->customStaticsConfig[$type][$key][] = $path;
    unset($this->pageStatics);
  }

  /*
   *	getPageStaticsConfig
  *	This method returns the configuration for statics for specific page name and type.
  *
  *	parameters:
  *		- pageName : the page name.
  *		- type : the type of statics (fwk-js, yui-js, jquery-plugins-js...)
  */
  public function getPageStaticsConfig($pageName, $type) {
    $staticsConfig = $this->getStaticsConfig();

    $key = $pageName;
    if(!isset($staticsConfig[$type][$pageName])) {
      $key = "default";
    }

    $listStatics = array();
    if(isset($staticsConfig[$type][$key])) {
      $listStatics = array_merge($listStatics, $staticsConfig[$type][$key]);
    }

    if(isset($this->customStaticsConfig[$type][$key])) {
      $listStatics = array_merge($listStatics, $this->customStaticsConfig[$type][$key]);
    }

    return $listStatics;
  }

  /*
   *	getPageStatic
  *	This method returns string with static (script, style) for a page.
  *
  *	parameters:
  *		- pageName : the page name.
  *	return:
  *		- Map - Map containing static content for the page.
  *				Map.styles - Map containing css files.
  *         Map.styles.screen - contains all css files used for screen display
  *         Map.styles.print - contains all css files used for print
  *				Map.scripts - Map containing javascript files.
  *					Map.scripts.jQuery - contains all jQuery related scripts
  *					Map.scripts.yui - contains all YAHOO ui related scripts
  *					Map.scripts.fwk - contains all framework scripts
  *         Map.scripts.ext - contains all framework  external lib scripts
  *         Map.scripts.site - contains all site specific scripts
  *				Map.inlineScript - Map containing specific inline scripts
  *					Map.inlineScript.jQuery - contains inline script to be executed after jQuery script
  *					Map.inlineScript.yui - contains inline script to be executed after YAHOO ui script
  *					Map.inlineScript.global - javascript to execute after loading the statics.
  */
  public function getPageStatics($pageName) {
    global $request;

    if(!isset($this->pageStatics)) {

      // We get statics configuration
      $staticsConfig = $this->getStaticsConfig();

      // We build the statics
      $jsFiles = array();
      $jsFwkFiles = array();
      $jsJQueryFiles = array();
      $jsYuiFiles = array();
      $jsExtFiles = array();
      $jsSiteFiles = array();

      $cssFiles = array();
      $cssScreenFiles = array();
      $cssPrintFiles = array();

      $inlineScript = array();
      $inlineScript["jQuery"] = "";
      $inlineScript["yui"] = "";
      $inlineScript["global"] = "";

      $useJQuery = false;
      $useFlashDetectionKit = false;

      // We get the path to web directory.
      // 	- relative web path in case of debug as retrieved in the html page
      //	- relative server path in case of debug disabled as retrieved server side.
      $sep = DIRECTORY_SEPARATOR;
      $webPath = $request->getApplication()->getWebRootPath();
      if($request->getConfigAsBoolean("fwk-jsDebug")) {
        $sep = "/";
        $webPath = $request->getApplication()->getStaticPath();
      }

      /*
       *	1. Framework statics
      */

      // We get the framework styles
      $fwkCss = $this->getPageStaticsConfig($pageName, "fwk-css");

      if(isset($fwkCss) && is_array($fwkCss)) {
        foreach($fwkCss as $name) {

          if($name == "ext.bootstrap") {
            $cssExtension = ".css";
            if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
              $cssExtension = ".min.css";
            }

            $bootstrapPath = $webPath."ext". $sep . "bootstrap-".$request->getConfig("fwk-bootstrapVersion");
             
            // We put the file
            $cssScreenFiles[] = $bootstrapPath . $sep . "css" . $sep . "bootstrap".$cssExtension;

            // We go to the next js
            continue;
          }

          $path = $webPath . $this->getStaticRelativePath($name . ".css", "fwk", "styles", $sep);

          // We put the file in the correct list.
          if(static::isPrintStyle($path)) {
            $cssPrintFiles[] = $path;
          }
          else {
            $cssScreenFiles[] = $path;
          }
        }
      }

      // We get the framework javascripts
      $fwkJs = $this->getPageStaticsConfig($pageName, "fwk-js");

      // We put the fwk required js
      array_push($jsFwkFiles,$webPath . $this->getStaticRelativePath("fwk.js", "fwk", "scripts", $sep));

      // We get the javascript files
      if(isset($fwkJs)) {
        foreach($fwkJs as $name) {

          if($name == "ext.jQuery") {
            // We include jQuery
            $useJQuery = true;

            $name = "jquery-".$request->getConfig("fwk-jqueryVersion").".js";
            if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
              $name = "jquery-".$request->getConfig("fwk-jqueryVersion").".min.js";
            }
            // We put the file
            array_push($jsJQueryFiles, $webPath . $request->getConfig("fwk-jqueryDirectory") . $name);

            // We put the inlineScript
            $inlineScript["jQuery"] .= "jQuery.noConflict();";

            // We go to the next js
            continue;
          }

          if($name == "ext.vuex") {
            // We include the vue runtime library
            $name = "vuex.js";
            if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
              $name = "vuex.min.js";
            }
            // We put the file
            array_push($jsExtFiles, $webPath . $request->getConfig("fwk-vuexDirectory").$name);

            // We put the inlineScript
            $inlineScript["global"] .= "const store = new Vuex.Store();";

            // We go to the next js
            continue;
          }

          if($name == "ext.vue") {
            // We include the vue runtime library
            $name = "vue.runtime.js";
            if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
              $name = "vue.runtime.min.js";
            }
            // We put the file
            array_push($jsExtFiles, $webPath . $request->getConfig("fwk-vueDirectory").$name);

            // We put the inlineScript
            $inlineScript["global"] .= "var vm = new Vue({});";

            // We go to the next js
            continue;
          }

          if($name == "ext.bootstrap") {
            $jsExtension = ".js";
            if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
              $jsExtension = ".min.js";
            }

            $bootstrapPath = $webPath . "/ext/" . "bootstrap-".$request->getConfig("fwk-bootstrapVersion");
             
            // We put the file
            $jsExtFiles[] = $bootstrapPath."/js/bootstrap".$jsExtension;

            // We go to the next js
            continue;
          }

          if($name == "ext.Scriptaculous") {
            // We include Scriptaculous

            // We put prototype and then scriptaculous
            array_push($jsFwkFiles, $webPath.$request->getConfig("fwk-javascriptsExtDirectory")."prototype.js");
            array_push($jsFwkFiles, $webPath.$request->getConfig("fwk-scriptaculousDirectory")."scriptaculous.js");
            array_push($jsFwkFiles, $webPath.$request->getConfig("fwk-scriptaculousDirectory")."effects.js");

            // We go to the next js
            continue;
          }

          $path = $webPath . $this->getStaticRelativePath("", "fwk", "scripts", $sep);
          // In case we include external javascript lib
          if(stripos($name,"ext.") !== false) {
            $path = $webPath.$request->getConfig("fwk-javascriptsExtDirectory");
            $name = str_ireplace("ext.","",$name);
          }

          // We put the file
          array_push($jsFwkFiles, $path.$name.".js");

          // We put the inlineScript if needed
          if($name == "FlashDetectionKit") {
            $inlineScript["global"] .="igotweb.ext.FlashDetectionKit._productInstallURL = \"".$request->getFwkStaticPath().$request->getConfig("fwk-javascriptsExtDirectory")."adobe/playerProductInstall.swf\";";
          }
        }

        if(count($jsFwkFiles) > 0) {
          $jsFiles["fwk"] = $jsFwkFiles;
        }
        if(count($jsExtFiles) > 0) {
          $jsFiles["ext"] = $jsExtFiles;
        }
      }

      /*
       *	2. Yahoo UI statics
      */
      $yuiJs = $this->getPageStaticsConfig($pageName, "yui-js");

      // We get the files
      if(isset($yuiJs)) {
        foreach($yuiJs as $name) {
          // We include the corresponding css if needed
          if($name == "container" || $name == "menu") {
            array_push($cssScreenFiles, $webPath.$request->getConfig("fwk-yuiCssDirectory").$name.".css");
          }

          if($name == "yahoo-dom-event" && $request->getConfigAsBoolean("fwk-jsDebug")) {
            // We put the corresponding dev files
            array_push($jsYuiFiles, $webPath.$request->getConfig("fwk-yuiDevDirectory")."yahoo.js");
            array_push($jsYuiFiles, $webPath.$request->getConfig("fwk-yuiDevDirectory")."dom.js");
            array_push($jsYuiFiles, $webPath.$request->getConfig("fwk-yuiDevDirectory")."event.js");

            // We go to the next js
            continue;
          }

          // We put the inlineScript if needed
          if($name == "uploader") {
            $inlineScript["yui"] .="YAHOO.widget.Uploader.SWFURL = \"".$request->getFwkStaticPath().$request->getConfig("fwk-yuiRootDirectory")."uploader.swf\";";
          }

          // When not in debug mode, we use the minified version
          if($name != "yahoo-dom-event" && !$request->getConfigAsBoolean("fwk-jsDebug")) {
            $name .= "-min";
          }


          // We get the directory
          $yuiDirectory = $request->getConfig("fwk-yuiMinDirectory");
          if($request->getConfigAsBoolean("fwk-jsDebug")) {
            $yuiDirectory = $request->getConfig("fwk-yuiDevDirectory");
          }

          // We put the file
          array_push($jsYuiFiles, $webPath.$yuiDirectory.$name.".js");
        }

        if(count($jsYuiFiles) > 0) {
          $jsFiles["yui"] = $jsYuiFiles;
        }
      }


      /*
       *	3. jQuery statics
      */
      if($useJQuery) {
        $jqueryPluginsJs = $this->getPageStaticsConfig($pageName, "jquery-plugins-js");

        // We get the files
        foreach($jqueryPluginsJs as $name) {

          // We get the jQuery ui full name
          if($name == "ui") {
            $name = "jquery-ui-".$request->getConfig("fwk-jqueryUIVersion").".custom.min";
          }
          else if($request->getConfigAsBoolean("fwk-jsDebug")) {
            $name = "jquery.".$name;
          }
          else {
            $name = "jquery.".$name.".min";
          }

          // We put the file
          array_push($jsJQueryFiles, $webPath.$request->getConfig("fwk-jqueryPluginsDirectory").$name.".js");
        }

        if(count($jsJQueryFiles) > 0) {
          $jsFiles["jQuery"] = $jsJQueryFiles;
        }
      }

      /*
       *	4. Site specific statics
      */

      // We get the site specific styles
      $siteCss = $this->getPageStaticsConfig($pageName, "site-css");

      // We get the javascript files
      if(isset($siteCss) && is_array($siteCss)) {
        foreach($siteCss as $name) {

          // We put the file
          $path = $webPath . $this->getStaticRelativePath("", "site", "styles", $sep);
          // In case we include external javascript lib
          if(stripos($name,"ext.") !== false) {
            $path = $webPath . $request->getConfig("fwk-extDirectory");
            $name = str_ireplace("ext.","",$name);
          }

          
          // We put the file in the correct list.
          if(static::isPrintStyle($path.$name.".css")) {
            $cssPrintFiles[] = $path.$name.".css";
          }
          else {
            $cssScreenFiles[] = $path.$name.".css";
          }
        }
      }

      $cssFiles["screen"] = $cssScreenFiles;
      $cssFiles["print"] = $cssPrintFiles;


      // We get the site specific javascripts
      $siteJs = $this->getPageStaticsConfig($pageName, "site-js");


      // We get the javascript files
      if(isset($siteJs) && is_array($siteJs)) {
        foreach($siteJs as $name) {

          // We put the file
          $path = $webPath . $this->getStaticRelativePath("", "site", "scripts", $sep);
          // In case we include external javascript lib
          if(stripos($name,"ext.") !== false) {
            $path = $webPath . $request->getConfig("fwk-extDirectory");
            $name = str_ireplace("ext.","",$name);
          }
          // We put the file
          $jsSiteFiles[] = $path.$name.".js";
        }
      }

      // We get the webapp specific javascripts
      $webappJs = $this->getPageStaticsConfig($pageName, "webapp-js");

      // We get the javascript files
      if(isset($webappJs) && is_array($webappJs)) {
        foreach($webappJs as $name) {

          // We put the file
          $path = $webPath . $this->getStaticRelativePath("", "webapp", "scripts", $sep);
          // In case we include external javascript lib
          if(stripos($name,"ext.") !== false) {
            $path = $webPath . $request->getConfig("fwk-extDirectory");
            $name = str_ireplace("ext.","",$name);
          }
          // We put the file
          $jsSiteFiles[] = $path.$name.".js";
        }
      }

      if(count($jsSiteFiles) > 0) {
        $jsFiles["site"] = $jsSiteFiles;
      }

      // We build the output map
      $pageStatics = array();
      $pageStatics["styles"] = $cssFiles;
      $pageStatics["scripts"] = $jsFiles;
      $pageStatics["inlineScript"] = $inlineScript;

      $this->pageStatics = $pageStatics;
    }
    return $this->pageStatics;
  }

  /*
   *	public static: hasStyle
  *	This method returns true if style is present in the page statics.
  *
  *	parameters:
  *		- pageStatics : pageStatics map.
  *	return:
  *		- boolean - true if style.
  */
  public static function hasStyle($pageStatics) {
    if(isset($pageStatics["styles"]) &&
        ((isset($pageStatics["styles"]["screen"]) && count($pageStatics["styles"]["screen"]) > 0) ||
            (isset($pageStatics["styles"]["print"]) && count($pageStatics["styles"]["print"]) > 0) )) {
      return true;
    }
    return false;
  }

  /*
   *	public static: isPrintStyle
  *	This method returns true if style in path is considered as style for media print.
  *
  *	parameters:
  *		- path : the absoute path to the style.
  *	return:
  *		- boolean - true if style for media print.
  */
  public static function isPrintStyle($path) {
    if(preg_match("/print/",$path)) {
      return true;
    }
    return false;
  }

  /*
   *	public static: hasScript
  *	This method returns true if script is present in the page statics.
  *
  *	parameters:
  *		- pageStatics : pageStatics map.
  *	return:
  *		- boolean - true if script.
  */
  public static function hasScript($pageStatics) {
    if(isset($pageStatics["scripts"]) &&
        ((isset($pageStatics["scripts"]["fwk"]) && count($pageStatics["scripts"]["fwk"]) > 0) ||
            (isset($pageStatics["scripts"]["ext"]) && count($pageStatics["scripts"]["ext"]) > 0) ||
            (isset($pageStatics["scripts"]["jQuery"]) && count($pageStatics["scripts"]["jQuery"]) > 0) ||
            (isset($pageStatics["scripts"]["site"]) && count($pageStatics["scripts"]["site"]) > 0) ||
            (isset($pageStatics["scripts"]["yui"]) && count($pageStatics["scripts"]["yui"]) > 0))) {
      return true;
    }
    return false;
  }

  /*
   *	getMergedStyle
  *	This method returns string with style from files list parameter.
  *
  *	parameters:
  *		- files : file name or list of files name.
  *	return:
  *		- String - String containing all style content.
  */
  public function getMergedStyle($files) {
    global $request;

    if(!is_array($files)) {
      $files = array($files);
    }

    $style = $this->minifyStatics($files, StaticsManager::TYPE_CSS);

    return $style;
  }

  /*
   *	getMergedScript
  *	This method returns string with script from files list parameter.
  *
  *	parameters:
  *		- files : file name or list of files name.
  *	return:
  *		- String - String containing all script content.
  */
  public function getMergedScript($files) {

    global $request;

    if(!is_array($files)) {
      $files = array($files);
    }

    $script = $this->minifyStatics($files, StaticsManager::TYPE_JS);

    return $script;
  }

  /*
   *	private static getCachePath
  *	This method returns the path to cache for generated statics.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- String - String containing path.
  */
  private static function getCachePath($request) {
    return $request->getApplication()->getRootPath().$request->getConfig("app-cacheDirectory").DIRECTORY_SEPARATOR."minify";
  }

  /*
   *	private getMinifyCache
  *	This method check that cache directory exists or create it.
  *	It returns the Minify Cache instance.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- cache - The Minify Cache instance.
  */
  private function getMinifyCache($request) {
    global $suffix;
    $logger = Logger::getInstance();

    $tmpDirectory = static::getCachePath($request);

    // We check if minify cache directory exists
    if(!is_dir($tmpDirectory)) {
      mkdir($tmpDirectory, 0777, true);
    }

    // We set the cache
    $cache = new \Minify_Cache_File($tmpDirectory, false, $logger);

    return $cache;
  }

  /*
   *  private minifyStatics
   *  This method return the minified statics.
   */
  private function minifyStatics(array $files, $type) {
    $logger = Logger::getInstance();
    global $request;

    // We check that cache is enabled.
    if(!isset($this->minifyCache)) {
      $this->minifyCache = $this->getMinifyCache($request);
    }
    
    if(!isset($this->minify)) {
      // We create the minify instance.
      $this->minify = new \Minify($this->minifyCache, $logger);
    }

    if($type == StaticsManager::TYPE_JS && !$request->getConfigAsBoolean("fwk-jsDebug")) {
      // For each file we check if there is a .min.js
      foreach($files as $index => $path) {
        $minPath = str_replace(".js", ".min.js",$path);
        if(file_exists($minPath)) {
          $files[$index] = $minPath;
        }
      }
    }
    
    $env = new \Minify_Env();
    $sourceOptions = array(
      'checkAllowDirs' => false
    );
    $sourceFactory = new \Minify_Source_Factory($env, $sourceOptions, $this->minifyCache);
    $controller = new \Minify_Controller_Files($env, $sourceFactory, $logger);

    $options = array(
        'files' => $files
        ,'quiet' => true
        ,'encodeOutput' => false
        ,'debug' => $request->getConfigAsBoolean("fwk-jsDebug")
    );

    if($request->getConfigAsBoolean("fwk-minifyDebug") && $request->isRequestForStatics() && $request->getCustomHeader('Content-type') !== static::$jsonContentType) {
        echo "/*";
        print_r($files);
        echo " */ \r\r";
      }

    if($request->getConfigAsBoolean("fwk-jsDebug")) {
      // We do not minify the output in case of jsDebug
      $options['minifiers'][\Minify::TYPE_CSS] = '';
      $options['minifiers'][\Minify::TYPE_JS] = '';
    }

    $output = null;

    if($type == StaticsManager::TYPE_CSS) {
      // We set specific options for styles
      $optionsForStyles = array(
        'rewriteCssUris' => false
        ,'minifierOptions' => array(
            \Minify::TYPE_CSS => array()
        )
      );

      $options = array_merge($options, $optionsForStyles);
      $output = "/* No Style */";

      $tmpOutput = "";
      $isMinificationSuccess = true;
      $optimizedStylesSets = $this->getOptimizedStylesSets($files);
      if($request->getConfigAsBoolean("fwk-minifyDebug") && $request->isRequestForStatics() && $request->getCustomHeader('Content-type') !== static::$jsonContentType) {
        echo "/* \r";
        print_r($optimizedStylesSets);
        echo " */ \r\r";
      }

      foreach($optimizedStylesSets as $set) {
        $options['files'] = $set['files'];

        $minifyOutput = $this->minify->serve($controller, $options);
        if($minifyOutput["success"]) {
          // We update the relative URI from the css
          $minifyOutput["content"] = \Minify_CSS_UriRewriter::prepend($minifyOutput["content"], UrlUtils::getCurrentBaseUrl() . $set['path']);
          $tmpOutput .= $minifyOutput["content"];
        }
        else {
          // We consider minifaction failing when one file is failing.
          $isMinificationSuccess = false;
        }
        if($request->getConfigAsBoolean("fwk-minifyDebug") && $request->isRequestForStatics() && $request->getCustomHeader('Content-type') !== static::$jsonContentType) {
          echo "/* \r";
          print_r($set['files']);
          print_r(array_merge($minifyOutput,array("content" => "removed from logs")));
          echo " */ \r\r";
        }  
      }

      if($isMinificationSuccess) {
        $output = $tmpOutput;
          if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
          // minifier goes to line in wrong places so we replace them by spaces...
          $output = preg_replace("/[\n|\r]/i"," ",$output);
        }
      }
      
    }
    else if($type == StaticsManager::TYPE_JS) {

      $output = "/* No Script */";
      $minifyOutput = $this->minify->serve($controller, $options);;
      if($minifyOutput["success"]) {
        $output = $minifyOutput["content"];
      }
    }
    else {
      $logger->addLog("StaticsManager->minifyStatics : Wrong type provided: " . $type, true, false);
    }

    return $output;
  }

  private function getOptimizedStylesSets($files) {
    global $request;

    $optimizedSets = array();
    if(count($files) < 1) {
      return $optimizedSets;
    }

    $currentSet = null;

    $webapp = $request->getApplication()->getWebapp();
    $site = $request->getApplication()->getSite();

    foreach($files as $file) {
      $path = $file;
      // In case of jsDebug, the path is already the relative static path from base href.
      // In case of not jsDebut, the path is the absolute server side path to the file.
      if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
        // In case of non jsDebug we need to replace absolute server side path to file to static paths. 
        // This is needed as we provide different path to statics in getPageStatics method.
        // We need to remove the servide side part of the path to statics (/Users/.../applicationShortName/web/ to be removed).
        $path = str_replace($request->getApplication()->getWebRootPath(), $request->getApplication()->getStaticPath(),$path);
      }
      if(substr_count($path,".style.css") > 0) {
        // If the style is coming from a template style, the path should be the web root path
        $path = str_replace($webapp->getRootPath(), "",$path);
        $path = substr($path, 0, stripos($path, "templates".DIRECTORY_SEPARATOR));
      }
      $path = FileUtils::getDirectoryPath($path);
      if(!isset($currentSet) || $path !== $currentSet['path']) {
        $currentSet = array(
          'files' => array()
          ,'path' => $path
        );
        array_push($optimizedSets, $currentSet);
      }
      if($path === $currentSet['path']) {
        array_push($optimizedSets[count($optimizedSets) - 1]['files'], $file);
      }
    }
    
    return $optimizedSets;
  }
}
?>
