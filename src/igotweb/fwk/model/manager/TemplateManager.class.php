<?php

/**
 *	Class: TemplateManager
 *	Version: 0.1
 *	This class handle the templating.
 *
 *	requires:
 *		- Request.
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\manager;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\DwoojQueryCompiler;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\SiteContext;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Site;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\PageUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class TemplateManager {

  private static $CONTAINER_CLASS = "iw-template-containter";
  public static $POST_PROCESS_KEY = "postProcess";

  // js templating engine (tmpl or jsrender)
  private $engine;

  private static $engineVar;

  // required template during the page prepocess to be loaded
  // in the page automatically.
  private $requiredTemplates;

  // This boolean is set to true when the required templates are
  // loaded in the page. Once true, the new required templates will
  // be loaded directly.
  private $requiredTemplatesLoaded;

  // availableTemplates is the list of templateNames that
  // are already available in the page.
  private $availableTemplates;

  // We store the context associated to included templates (data, includedId, templateName, callback, isInitialized)
  private $includedTemplates;

  // We store the templates associated informations ()
  private $templatesInfo;

  // We store the dwooCompiler
  private $dwooCompiler;

  static function init() {
    self::$engineVar = array(
        "tmpl" => array(
            "script-type" => "text/x-jquery-tmpl"
        ),
        "jsrender" => array(
            "script-type" => "text/x-jsrender"
        ),
        "vue" => array(
            "script=type" => "text/x-template"
        )

    );
  }

  function __construct(Request $request) {
    $this->availableTemplates = array();
    $this->includedTemplates = array();
    $this->requiredTemplates = array();

    $this->requiredTemplatesLoaded = false;

    $this->engine = $request->getConfig("fwk-jsTemplateEngine");

    // We check the cache for templates
    $this->checkCache($request);

    // We populate the Dwoo Compiler
    $this->populateDwooCompiler($request);
  }

  private function populateDwooCompiler($request) {
    // We include Dwoo library
    include_once($request->getConfig("fwk-dwooDirectory")."Dwoo.php");

    // Create the compiler instance
    $compiler = new DwoojQueryCompiler();
    $compiler->setDebug($request->getConfig("fwk-dwooDebug"));
    $compiler->addPreProcessor('Dwoo_Processor_jQuery',true);
    $this->dwooCompiler = $compiler;
  }

  /*
   *  requireTemplates
  *  This method adds several templates to be loaded with the page.
  *  The options are common to all templates in parameter.
  */
  public function requireTemplates($templateNames, $options = array()) {
    foreach($templateNames as $templateName) {
      $this->requireTemplate($templateName, $options);
    }
  }

  /*
   *  requireTemplate
   *  This method adds a template to be loaded with the page.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate method).
   *			options.siteContext - SiteContext to use to load the template (by default current request SiteContext is used). This option is only available for templates required in ajax request.
   *
   *	return:
   *		- none.
   */
  public function requireTemplate($templateName, $options = array()) {

    if($this->requiredTemplatesLoaded) {
      // We load the template directly in the page.
      $this->loadTemplate($templateName, $options);
    }
    else if(!$this->isTemplateRequired($templateName)) {
      // We add the template to the required templates
      $template = array(
          "name" => $templateName,
          "options" => $options
      );
      array_push($this->requiredTemplates,$template);
    }
  }

  /*
   *  isTemplateRequired
  *  This method checks if a template is already required.
  */
  private function isTemplateRequired($templateName) {
    foreach($this->requiredTemplates as $template) {
      if($template["name"] == $templateName) {
        return true;
      }
    }
    return false;
  }

  /*
   *  loadRequiredTemplates
  *  This method loads all required templates in the page.
  */
  public function loadRequiredTemplates() {
    foreach($this->requiredTemplates as $template) {
      $this->loadTemplate($template["name"], $template["options"]);
    }
    $this->requiredTemplatesLoaded = true;
  }

  /*
   *  getJSONRequiredTemplates
   *  This method get the JSON structure of the required templates and the associated js resources.
   */
  public function getJSONRequiredTemplates() {
    global $request;
     
    $json = NULL;
     
    if(count($this->requiredTemplates) > 0) {
       
      // We require all sub templates first
      foreach($this->requiredTemplates as $template) {
        $subTemplates = $this->getListSubTemplates($template["name"], $template["options"]);
        if($subTemplates instanceof Error) {
          $request->addError($subTemplates);
        }
        else {
          $this->requireTemplates($subTemplates, $template["options"]);
        }
      }
       
      $jsonTemplates = array();
      $jsResources = array();
      foreach($this->requiredTemplates as $template) {
        $jsonTemplate = array();
        $jsonTemplate["name"] = $template["name"];
		
		    // We switch the context of the request. We do it here instead of in getTemplate to optimize.
	      if(isset($template["options"]["siteContext"]) && $template["options"]["siteContext"] instanceof SiteContext) {
          $context = $template["options"]["siteContext"];
		      $request->switchSiteContext($context);
	      }
		
        $src = $this->getTemplate($template["name"], $template["options"]);
        if($src instanceof Error) {
          // We add the error
          $request->addError($src);
        }
        else {
          // We put the source in the template
          $jsonTemplate["src"] = $src;
          // We check if script is associated
          $scriptPath = $this->getTemplateScriptFilesPath($template["name"], $template["options"]);
          if($scriptPath instanceof Error) {
            $jsonTemplate["hasScript"] = false;
          }
          else {
            // In case of webpack chunk, we have an object with path to templates folder and path to template script
            if(is_array($scriptPath) && isset($scriptPath["templatePath"])) {
              $jsonTemplate["webpack"] = $scriptPath;
            }
            $jsonTemplate["hasScript"] = true;
          }
          // We check if style is associated
          $stylePath = $this->getTemplateStyle($template["name"], $template["options"]);
          if($stylePath instanceof Error) {
            $jsonTemplate["hasStyle"] = false;
          }
          else {
            $jsonTemplate["hasStyle"] = true;
          }
          
          // We check if we have the context as option
          if(isset($template["options"]["siteContext"]) && $template["options"]["siteContext"] instanceof SiteContext) {
            if(!isset($jsonTemplate["options"])) {
              $jsonTemplate["options"] = array();
            }
            if(!isset($jsonTemplate["options"]["siteContext"])) {
              $jsonTemplate["options"]["siteContext"] = array();
            }
            
            $context = $template["options"]["siteContext"];
            $jsonTemplate["options"]["siteContext"]["applicationShortName"] = $context->getApplication()->getShortName();
          }
		  
          array_push($jsonTemplates, $jsonTemplate);
           
          // We get the associated js resources
          $jsResources = array_merge($jsResources, $this->getListJsResources($template["name"], $template["options"]));
        }
      }
	  
	  // We switch back to initial context
	  $request->switchInitialSiteContext();
       
      $json = array(
          "templates" => $jsonTemplates,
          "resources" => $jsResources
      );
    }
     
    return $json;
  }

  /*
   *  getTemplateInfo
   *  This method get the template associated info.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate method).
   *			options.siteContext - SiteContext to use to load the template (by default current request SiteContext is used). This option is only available for templates required in ajax request.
   *
   *	return:
   *		- none.
   */
  public function getTemplateInfo($templateName, $options = array()) {
    
    if(!isset($this->templatesInfo[$templateName])) {
      // We store dependencies information
      $infos = array();

      // We check if the template has associated script
      $scripts = $this->getTemplateScriptFilesPath($templateName, $options);
      $infos["hasScript"] = !$scripts instanceof Error;
      // In case of webpack chunk, we have an object with path to templates folder and path to template script
      if(is_array($scripts) && isset($scripts["templatePath"])) {
        $infos["webpack"] = true;
      }
      else {
        $infos["webpack"] = false;
      }

      // We check if the template has associated style
      $style = $this->getTemplateStyleFilesPath($templateName, $options);
      $infos["hasStyle"] = !$style instanceof Error;
      $infos["isStyleLoaded"] = false;
      if(isset($options["loadStyle"]) && $options["loadStyle"]) {
        $infos["isStyleLoaded"] = true;
      }

      $this->templatesInfo[$templateName] = $infos;
    }

    return $this->templatesInfo[$templateName];
  }

  /*
   *  includeTemplate
   *  This method include a template in the page with the data in parameter.
   *  TODO - Solve the discrepency with TemplateUtils->tmpl which do not always generate container for sub templates.
   */
  public function includeTemplate($templateName, array $datas = array(), $callback = NULL) {
    global $request;

    // In case data is an array, we include the template for each item of the array
    if(is_array($datas) && count($datas) > 0 && isset($datas[count($datas)-1])) {
      foreach($datas as $data) {
        $this->includeTemplate($templateName,$data,$callback);
      }
      return;
    }

    // In case we have an object, it must be converted into associative array
    if(!is_array($datas) && is_object($datas)) {
      $datas = Utils::buildArrayFromObject($datas);
    }

    // We merge the post processing of data to be used within the template.
    $datas = static::mergePostProcessData($datas);

    // We get the generated template
    $generatedTemplate = $this->generateTemplate($templateName, $datas);
    if($generatedTemplate instanceof Error) {
      if($request->sessionExistsElement("trace") && $request->sessionGetElement("trace") == "all") {
        echo $generatedTemplate->getFormattedMessage();
      }
      return $generatedTemplate;
    }

    // We require the template to have it available in javascript engine
    // This loads the scripts as processed in the loadRequiredTemplates method.
    // It does not load the style which is loaded within TemplateUtils._processTemplateInit
    $this->requireTemplate($templateName);

    $includedId = count($this->includedTemplates);
    $infos = $this->getTemplateInfo($templateName);

    // We add the container
    echo "<div class=\"".static::$CONTAINER_CLASS."\" data-template-name=\"".$templateName."\" data-included=\"true\" ";
    echo "data-included-id=\"".$includedId."\" ";
    if($infos["hasStyle"]) {
      // We set the visibility as hidden (the visibility is set back in TemplateUtils._processTemplateInit)
      echo "style=\"visibility:hidden;\" ";
    }
    echo ">";

    echo $generatedTemplate;

    echo "</div>";

    // We keep the included template context
    $includeTemplateContext = array(
      "templateName" => $templateName,
      "datas" => $datas,
      "includedId" => $includedId,
      "callback" => $callback,
      "isInitialized" => false
    );
    $this->includedTemplates[$includedId] = $includeTemplateContext;
  }

  /*
   *	generateTemplate
   *	This method generate a template with the data in parameter.
   */
  public function generateTemplate($templateName, $datas = array()) {
    global $request;

    // We get the template path
    $filePath = $this->getTemplateFilePath($templateName);
    if($filePath instanceof Error) {
      return $filePath;
    }
    
    // We make sure that the specific template resources are available
    $languageManager = $request->getLanguageManager();
    // We set the context of the current template for language manager
    $languageManager->addTemplateContext($templateName);

    // We generate the template
    $dwoo = new \Dwoo(TemplateManager::getCachePath($request));
    $content = $dwoo->get($filePath, $datas, $this->dwooCompiler, false);
    
    // We remove the template context
    $languageManager->removeTemplateContext($templateName);
    
    return $content;
  }

  /*
   *  initIncludedTemplates
  *  This method init the included tempates in the page and generates associated scripts.
  * This method must be called before the </body> of the page.
  * It is possible to init included templates several time in the page. Only the one not yet initialized will be taken in account.
  */
  public function initIncludedTemplates() {

    if(count($this->includedTemplates) > 0) {

      echo "<script language=\"javascript\" type=\"text/javascript\">";
      
      foreach($this->includedTemplates as &$includeTemplateContext) {
        // We get the information from the includeTemplateContext
        $templateName = $includeTemplateContext["templateName"];
        $datas = $includeTemplateContext["datas"];
        $includedId = $includeTemplateContext["includedId"];
        $callback = $includeTemplateContext["callback"];
        $isInitialized = $includeTemplateContext["isInitialized"];

        // If the included template context is already initialized, we move to the next one.
        if($isInitialized) {
          continue;
        }

        // We load the associated resources if any
        $this->loadTemplateResources($templateName);

        // We call the callback once page is loaded
        echo "jQuery(document).ready(function() { ";
        
        // We include the data linked to the template in javascript to initialize the script
        if(isset($datas)) {
          echo "var d=".JSONUtils::buildJSONObject($datas).";";
        }
        else {
          echo "var d=null;";
        }
        echo "igotweb.TemplateUtils.includeTemplate( \"".$templateName."\", ".$includedId." , d , ".$callback.");";

        echo "}); ";

        $includeTemplateContext["isInitialized"] = true;
      }

      // We init the included templates for TemplateUtils
      echo "jQuery(document).ready(function() { ";
	    echo "igotweb.TemplateUtils.initIncludedTemplates(); ";
      echo "}); ";

      echo "</script>";
    }
  }

  /*
   *	_echo
  */
  public static function _echo($value) {
    if(!isset($value)) {
      $value = "NULL";
    }
    if(is_bool($value)) {
      $value = $value ? "true" : "false";
    }
    echo htmlspecialchars($value);
  }

  /*
   *	evaluate
  */
  public static function evaluate($var, $properties) {
    $logger = Logger::getInstance();
    $value = $var;
    if(count($properties) > 0) {
      // We get the first property and reduce the array
      $property = array_shift($properties);
      
      if(is_object($var)) {
        $methodName = "get".ucfirst($property);
        // We check if the method is to get object id
        if($property == "id".ucfirst(get_class($var))) {
          $methodName = "getSQLid";
        }
        $nextVar = null;
        if($var instanceof \stdClass && property_exists($var, $property)) {
          $nextVar = $var->$property;
        }
        else if(is_callable(array($var,$methodName))) {
          $nextVar = $var->$methodName();
        }
        else {
          $error = Error::getGeneric("TemplateManager: the method (" . $methodName . ") does not exist for object (" . get_class($var) . ").");
          $logger->addErrorLog($error,true);
          return $error->getFormattedMessage();
        }

        if($nextVar instanceof IwDateTime) {
          // We check if getFormattedProperty method exists
          $formattedMethodName = "getFormatted".ucfirst($property);
          if(method_exists($var, $formattedMethodName)) {
            $nextVar = $var->$formattedMethodName();
          }
          else {
            // We use a default format
            $nextVar = $nextVar->format("d/m/Y H:i");
          }
        }
        // We evalue the next object
        $value = TemplateManager::evaluate($nextVar,$properties);
      }
      else if(is_array($var)) {
        // We remove quotes arround property if any
        $property = trim($property,"'");
        if($property == "length") {
          $value = count($var);
        }
        else if(!isset($var[$property])) {
          $value = NULL;
        }
        else {
          // We evaluate the next object
          $value = TemplateManager::evaluate($var[$property],$properties);
        }
      }
    }
    return $value;
  }

  /*
   *	message
  *  This method is used to handle msg tag in template.
  */
  public static function message($key, $scope, $parameters) {
    global $request;
    $params = array();
    if(is_array($parameters) && count($parameters) > 0) {
      foreach($parameters as $parameter) {
        $params[] = TemplateManager::evaluate($scope, explode(".", $parameter));
      }
    }
    echo $request->getString($key, $params);
  }

  /*
   *  loadTemplates
  *  This method loads several templates in the page.
  *  The options are common to all templates in parameter.
  */
  public function loadTemplates($templateNames, $options = array()) {
    if(!is_array($templateNames)) {
      return new Error(9403,1,$templateNames);
    }
    foreach($templateNames as $templateName) {
      $this->loadTemplate($templateName, $options);
    }
  }

  /*
   *	loadTemplate
   *	This method loads the template in the page to be used by javascript.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement.
   *			options.loadStyle - this loads the template style within the page.
   *	return:
   *		- Array - The array of Error objects.
   */
  public function loadTemplate($templateName, $options = array()) {
    global $request;

    $template = $this->getTemplate($templateName, $options);
    if($template instanceof Error) {
      return $template;
    }

    // We load the sub templates.
    $subTemplates = $this->getListSubTemplates($templateName, $options);
    if($subTemplates instanceof Error) {
      $request->addError($subTemplates);
    }
    else {
      // We only load the templates not already available.
      $subTemplates = array_diff($subTemplates, $this->availableTemplates, array($templateName));
      if(count($subTemplates > 0)) {
        $this->loadTemplates($subTemplates, $options);
      }
    }

    // In case the template is already available we do not include it again.
    if(in_array($templateName,$this->availableTemplates)) {
      return;
    }

    echo "<script id=\"".$templateName."Template\" type=\"".$this->getEngineVar("script-type")."\">";
    echo $template;
    echo "</script>";
    echo "<script language=\"javascript\">";
    // We get the jQuery template element
    $templateSelector = "jQuery(\"#".preg_replace("/\./i","\\\\\\.",$templateName)."Template\")";
    // We compile the template and remove it from DOM
    if($this->engine == "tmpl") {
      echo $templateSelector.".template(\"".$templateName."\");";
      echo $templateSelector.".remove();";
    }
    else if($this->engine == "jsrender") {
      echo "jQuery.templates(\"".$templateName."\", ".$templateSelector.".html());";
      echo $templateSelector.".remove();";
    }

    // we get the template info
    $infos = $this->getTemplateInfo($templateName, $options);

    echo "igotweb.TemplateUtils.setTemplateInfo(\"".$templateName."\",".JSONUtils::buildJSONObject($infos).");";

    // We load the associated resources if any
    $this->loadTemplateResources($templateName);
    
    echo "</script>";

    if($infos["hasStyle"] && isset($options["loadStyle"]) && $options["loadStyle"]) {
      $url = "./styles/template/".$templateName;
      $dependsKey = "g_".preg_replace("/[^a-zA-Z 0-9]+/",'',strtoLower($url));
      echo "<link href=\"".$url."\" rel=\"stylesheet\" type=\"text/css\" key=\"".$dependsKey."\" />";
    }

    // We include the associated script if not part of the webpack bundle
    // In case script is within the bundle, it has to be loaded within the page script as an import.
    if($infos["hasScript"] && !$infos["webpack"]) {
      echo "<script src=\"./scripts/template/".$templateName."\" language=\"javascript\" type=\"text/javascript\"></script>";
    }

    // This template is now available
    array_push($this->availableTemplates, $templateName);
  }

  /*
   *	loadTemplateResource
   *	This method loads the template resourcesin the page.
   *  It extends the JSONData.resources dictionnary.
   *  Note: The resources loaded can be template specific so it is important while name keys not to override resources used by other templates.
   *  Note: The method must be included between <script> tag.
   *
   *	parameters:
   *		- templateName : the template name.
   *	return:
   *		- none.
   */
  private function loadTemplateResources($templateName) {
    global $request;
    $languageManager = $request->getLanguageManager();

    // We update the JSONData.resources
    $resources = $this->getListJsResources($templateName);
    if(!$resources instanceof Error && count($resources) > 0) {
      // We make sure that the specific template resources are available
      
      // We set the context of the current template for language manager
      $languageManager->addTemplateContext($templateName);
      
      // We get the resources value
      $jsResources = array();
      foreach($resources as $key) {
        if(!array_key_exists($key, $jsResources)) {
          $jsResources[$key] = $request->getString($key);
        } 
      }
      
      // We remove the template context
      $languageManager->removeTemplateContext($templateName);
    
      echo "var resources=".JSONUtils::buildJSONObject($jsResources).";";
      echo "jQuery.extend(JSONData.resources, resources);";
    }

  }

  /*
   *	getTemplate
   *	This function returns the template content as string.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   */
  public function getTemplate($templateName, $options = array()) {
    global $request;

    $filePath = $this->getTemplateFilePath($templateName, $options);
    if($filePath instanceof Error) {
      return $filePath;
    }
    
    // We make sure that the specific template resources are available
    $languageManager = $request->getLanguageManager();
    // We set the context of the current template for language manager
    $languageManager->addTemplateContext($templateName, $options);
	
    ob_start();
    include($filePath);
    $template = ob_get_contents();
    ob_end_clean();
    
    // We remove the template context
    $languageManager->removeTemplateContext($templateName);

    if(!$request->getConfigAsBoolean("fwk-jsDebug")) {
      $template = preg_replace("/[\r\t\n]/", "", $template);
    }

    return $template;
  }

  /*
   *	getListSubTemplates
   *	This function parses the corresponding template and sub templates to retrieve a complete list of sub templates.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   *		- subTemplates : pointer to the array containing the list of sub templates only used by getListSubTemplates method.
  */
  public function getListSubTemplates($templateName, $options = array(), &$subTemplates = array()) {
    // 1. We get the template
    $path = $this->getTemplateFilePath($templateName, $options);
    if($path instanceof Error) {
      return $path;
    }

    $content = file_get_contents($path);

    // 2. We parse the content to get the list of included templates
    $nbSubTemplates = preg_match_all("/\{\{tmpl\s*[^\}\s]*\s*tmpl=\"([^\s\"]*)\"\s*\}\}\{\{\/tmpl\}\}/" , $content , $matches);
    if($nbSubTemplates > 0) {
      foreach ($matches[1] as $subTemplate) {
        // We check if the sub template found is not already included.
        if(!in_array($subTemplate, $subTemplates)) {
          // We add the sub template to the list
          $subTemplates[] = $subTemplate;
          // We look into the sub template.
          $subSubTemplates = $this->getListSubTemplates($subTemplate, $options, $subTemplates);
          if($subSubTemplates instanceof Error) {
            return $subSubTemplates;
          }
        }
      }
    }

    return $subTemplates;
  }

  /*
   *	getListJsResources
   *	This function parses the corresponding template and retrieves the list of js resources used.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   */
  public function getListJsResources($templateName, $options = array()) {
    // 1. We get the template
    $path = $this->getTemplateFilePath($templateName, $options);
    if($path instanceof Error) {
      return $path;
    }

    $content = file_get_contents($path);
     
    $jsResources = array();

    // 2. We parse the content to get the list of included templates
    $nbJsResources = preg_match_all("/\{\{msg\s*[^\}\s]*\s*key=\"([^\s\"]*)\"\s*[^\}]*\}\}\{\{\/msg\}\}/" , $content , $matches);
    if($nbJsResources > 0) {
      foreach ($matches[1] as $jsResource) {
        // We check if the js resource found is not already included.
        if(!in_array($jsResource, $jsResources)) {
          // We add the js resource to the list
          $jsResources[] = $jsResource;
        }
      }
    }

    return $jsResources;
  }

  /*
   *	getTemplateScript
   *	This function returns the template script content as string.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   */
  public function getTemplateScript($templateName, $options = array()) {
    global $request;

    $files = $this->getTemplateScriptFilesPath($templateName, $options);
    if($files instanceof Error) {
      return $files;
    }

    return $request->getStaticsManager()->getMergedScript($files);
  }

  /*
   *	getTemplateStyle
   *	This function returns the template style content as string.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   */
  public function getTemplateStyle($templateName, $options = array()) {
    global $request;

    $files = $this->getTemplateStyleFilesPath($templateName, $options);
    if($files instanceof Error) {
      return $files;
    }

    return $request->getStaticsManager()->getMergedStyle($files);
  }

  /*
   *	getTemplateNamesHierarchy
   *	As it is possible to extends templates, this is done in the templateName by using -
   *	Ex: template1-version1 extends template1
   *	This function returns an array of template name from the most generic to most specific one.
   *	Ex: [template1, template1-version1] for templateName = template1-version1.
   */
  private function getTemplateNamesHierarchy($templateName) {
    // We init the list of names
    $templateNamesHierarchy = array($templateName);

    $pos = strrpos($templateName, "-");
    while($pos !== false) {
      $templateName = substr($templateName, 0, $pos);
      array_push($templateNamesHierarchy, $templateName);
      $pos = strrpos($templateName, "-");
    }

    return array_reverse($templateNamesHierarchy);
  }

  /*
   *	function: getTemplateData
   *	This function retrieve the datas needed for any template.
   *  The format of what is returned is containing objects.
   *  Post processed data are available within postProcess key in the data map.
   *  The post processed data is merged:
   *    - In the Request->toJSON method in case of ajax call.
   *    - In the Request->jsonDataAddTemplateData when adding template data for JSON object.
   *    - In TemplateManager->includeTemplate when including a template with data provided.
   *
   *	parameters:
   *		- templateName : the name of the template.
   *    - params : map of parameters to be used to generate the panel data.
   *	return:
   *		- Array - an array of data.
   */
  public function getTemplateData($templateName, $params = NULL) {
    global $request;
     
    $pageUtils = new PageUtils($request->getSiteContext(), NULL);
	
    if(!isset($params) || !is_array($params)) {
      $params = array();
    }
	
    // We set the template name
    $templateData = array("templateName" => $templateName);
    
    // We make sure that the specific template resources are available
    $languageManager = $request->getLanguageManager();
    // We set the context of the current template for language manager
    $languageManager->addTemplateContext($templateName);
     
    // We get the common pages data
    $templateData = $pageUtils->getItemData(PageUtils::$TEMPLATE_PATH, "common", $params, $templateData);
     
    // We get the template names hierarchy
    $templateNamesHierarchy = $this->getTemplateNamesHierarchy($templateName);
	
    // We get the template specific data.
    // We loop over templateNamesHierarchy to get specific data from the more generic to the most specific one.
    foreach ($templateNamesHierarchy as $templateName) {
      $templateData = $pageUtils->getItemData(PageUtils::$TEMPLATE_PATH, $templateName, $params, $templateData);
    }
    
    // We remove the template context
    $languageManager->removeTemplateContext($templateName);

    return $templateData;
  }

  /**
   *	static function: addPostProcessData
   *	This function adds post processed data within existing templateData.
   *  If already existing, it merges it.
   *
   *	parameters:
   *		- @param: templateData : the existing templateData.
   *    - @param: postProcessData : the post process data to add.
   *	return:
   *		- @return: updated templateData.
   */
  public static function addPostProcessData(array $templateData, array $postProcessData) {
    $logger = Logger::getInstance();

    // We check that templateData is an array.
    if(!is_array($templateData)) {
      $logger->addLog("TemplateManager->addPostProcessData : The template data is not an array.", true, false);
      $templateData = array();
    }

    // We check if there is already post process data
    if(isset($templateData[static::$POST_PROCESS_KEY])) {
      // We check that post process data is an array
      if(!is_array($templateData[static::$POST_PROCESS_KEY])) {
        $logger->addLog("TemplateManager->addPostProcessData : The existing post process data is not an array.", true, false);
        $templateData[static::$POST_PROCESS_KEY] = array();
      }

      // We merge the postProcessData within existing one.
      $templateData[static::$POST_PROCESS_KEY] = Utils::arrayMergeRecursiveFull($templateData[static::$POST_PROCESS_KEY], $postProcessData);
    }
    else {
      // We set the post process data.
      $templateData[static::$POST_PROCESS_KEY] = $postProcessData;
    }
    return $templateData;
  }

  /**
   *	static function: mergePostProcessData
   *	This function merges all postProcess data within the array in parameter.
   *
   *	parameters:
   *		- @param: data : the existing data.
   *	return:
   *		- @return: updated data.
   */
  public static function mergePostProcessData(array $data) {
    // We first make sure that the data is converted into array / map
    $data = Utils::buildArrayFromObject($data);
    // We merge then the post process data
    return Utils::arrayMergeFromKey($data, static::$POST_PROCESS_KEY);
  }

  /*
   *	getTemplateFilePath
   *	This method get the template file path.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->requireTemplate).
   */
  private function getTemplateFilePath($templateName, $options = array()) {
    global $request;

    // We get the site context
    $siteContext = $request->getSiteContext();
    // In case we get the template in a specific context.
    if(isset($options["siteContext"]) && $options["siteContext"] instanceof SiteContext) {
      $siteContext = $options["siteContext"];
    }

    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
    
    $pageUtils = new PageUtils($siteContext, NULL);

    // We get the template names hierarchy
    // We reverse the array to have the names from the specific one to the most generic one as we will look first
    // for specific templates.
    $templateNamesHierarchy = array_reverse($this->getTemplateNamesHierarchy($templateName));

    // We loop over templateNamesHierarchy
    foreach ($templateNamesHierarchy as $templateName) {
      // We get the template path
      $templatePath = $pageUtils->getItemPath($templateName);

      if(!Site::isNoSite($site)) {
        // site/templateName.engine.php => the template name to use (customized for site) engine specific
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".".$this->engine.".php";
        if(file_exists($filePath)) {
          return $filePath;
        }

        // site/templateName.tpl.php => the template name to use (customized for site)
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".tpl.php";
        if(file_exists($filePath)) {
          return $filePath;
        }
      }

      // templateName.engine.php => the template name to use engine specific
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".".$this->engine.".php";
      if(file_exists($filePath)) {
        return $filePath;
      }

      // templateName.tpl.php => the template name to use
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".tpl.php";
      if(file_exists($filePath)) {
        return $filePath;
      }

      // templateName.engine.php => the template name to use (version in framework) engine specific
      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath.".".$this->engine.".php";
      if(file_exists($filePath)) {
        return $filePath;
      }

      // templateName.tpl.php => the template name to use (version in framework)
      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath.".tpl.php";
      if(file_exists($filePath)) {
        return $filePath;
      }

      if(!Site::isNoSite($site)) {
        // site/_templateName.php => deprecated
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."_".$templatePath.".php";
        if(file_exists($filePath)) {
          return $filePath;
        }
      }

      // _templateName.php => deprecated
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."_".$templatePath.".php";
      if(file_exists($filePath)) {
        return $filePath;
      }
    }

    return new Error(9401,1,$templateName);
  }

  /*
   *	getTemplateScriptFilesPath
   *	This method get the template script file path.
   *	As scripts are merged for templates, we can have several files.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
   *  return
   *    - It returns an array of files path from the most generic one to the most specific one.
   *    - In case of webpack chunk, it returns an object with two information to import the script from TemplateUtils
   *        - pathToTemplates
   *        - templatePath
   *      Here is the full path used: `@root/${dependency.webpack.pathToTemplates}/templates/${dependency.webpack.templatePath}.script.ts`
   */
  private function getTemplateScriptFilesPath($templateName, $options = array()) {
    global $request;
	
    // We get the site context
    $siteContext = $request->getSiteContext();
    // In case we get the template in a specific context.
    if(isset($options["siteContext"]) && $options["siteContext"] instanceof SiteContext) {
      $siteContext = $options["siteContext"];
    }

    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
    
    $pageUtils = new PageUtils($siteContext, NULL);

    $files = array();
    $webpack = array();

    // We get the template names hierarchy
    $templateNamesHierarchy = $this->getTemplateNamesHierarchy($templateName);

    // We loop over templateNamesHierarchy
    foreach ($templateNamesHierarchy as $templateName) {

      $templatePath = $pageUtils->getItemPath($templateName);

      // templateName.script.js => the template name to use (version in framework)
      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath.".script.js";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath.".script.ts";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
        $webpack = array(
          "pathToTemplates" => "igotweb-fwk",
          "templatePath" => $templatePath
        );
      }

      // templateName.script.js => the template name to use
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.js";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.ts";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
        $webpack = array(
          "pathToTemplates" => $webapp->getShortName(),
          "templatePath" => $templatePath
        );
      }

      if(!Site::isNoSite($site)) {
        // site/templateName.script.js => the template name to use (customized for site)
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.js";
        if(file_exists($filePath)) {
          array_push($files, $filePath);
        }

        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.ts";
        if(file_exists($filePath)) {
          array_push($files, $filePath);
          $webpack = array(
            "pathToTemplates" => $webapp->getShortName().DIRECTORY_SEPARATOR."sites".DIRECTORY_SEPARATOR.$site->getShortName(),
            "templatePath" => $templatePath
          );
        }
      }

      // templateName.script.php => the template name to use
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.php";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      if(!Site::isNoSite($site)) {
        // site/templateName.script.php => the template name to use (customized for site)
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath.".script.php";
        if(file_exists($filePath)) {
          array_push($files, $filePath);
        }
      }
    }

    if(count($files) == 0) {
      return new Error(9402, 1, $templateName);
    }

    // In case we have several files, we need to keep the script files starting from the latest constructor found.
    if(count($files) > 1) {
      for($i = count($files) - 1; $i >= 0; $i--) {
        // We look for the constructor in the file.
        $filePath = $files[$i];
        if($this->hasTemplateScriptConstructor($filePath, $templateNamesHierarchy[0])) {
          // We keep the script from this file.
          $files = array_splice($files,$i);
          break;
        }
      }
    }

    // We return the webpack object if found
    if(isset($webpack["templatePath"])) {
      return $webpack;
    }
    return $files;
  }

  /*
   *	hasTemplateScriptConstructor
  *	This method checks in a script file path if there is a constructor method for
  *  the template name in parameter.
  */
  private function hasTemplateScriptConstructor($filePath, $templateName) {
    $templateNamePattern = preg_replace("/\./i","\\\\.",$templateName);
    $constructorPattern = "/igotweb\.templates\.".$templateNamePattern."\s*=\s*function/i";
    $script = file_get_contents($filePath);
    if(preg_match($constructorPattern,$script)) {
      return true;
    }
    return false;
  }

  /*
   *	getTemplateStyleFilesPath
   *	This method get the template style files path.
   *	As styles are merged for templates, we can have several files.
   *	It returns an array of files path from the most generic one to the
   *	most specific one.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
  */
  private function getTemplateStyleFilesPath($templateName, $options = array()) {
    global $request;
	
    // We get the site context
    $siteContext = $request->getSiteContext();
    // In case we get the template in a specific context.
    if(isset($options["siteContext"]) && $options["siteContext"] instanceof SiteContext) {
      $siteContext = $options["siteContext"];
    }

    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
    
    $pageUtils = new PageUtils($siteContext, NULL);

    $files = array();

    // We get the template names hierarchy
    $templateNamesHierarchy = $this->getTemplateNamesHierarchy($templateName);

    // We loop over templateNamesHierarchy
    foreach ($templateNamesHierarchy as $templateName) {

      $templatePath = $pageUtils->getItemPath($templateName);

      // templateName.style.css => the template name to use (version in framework)
      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath.".style.css";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      // templateName.style.css => the template name to use
      $filePath = $webapp->getRootPath()."templates".DIRECTORY_SEPARATOR.$templatePath.".style.css";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      if(!Site::isNoSite($site)) {
        // site/templateName.style.css => the template name to use (customized for site)
        $filePath = $site->getRootPath()."templates".DIRECTORY_SEPARATOR.$templatePath.".style.css";
        if(file_exists($filePath)) {
          array_push($files, $filePath);
        }
      }

    }

    if(count($files) == 0) {
      return new Error(9402, 2, $templateName);
    }

    // In case we have several files, we need to keep the style files starting from the latest which does not extend style.
    if(count($files) > 1) {
      for($i = count($files) - 1; $i >= 0; $i--) {
        // We look for the extend annotation in the file.
        $filePath = $files[$i];
        if(!$this->hasTemplateStyleExtendAnnotation($filePath)) {
          // We keep the style from this file.
          $files = array_splice($files,$i);
          break;
        }
      }
    }

    return $files;
  }

  /*
   *	hasTemplateStyleExtendAnnotation
  *	This method checks in a style file path if there is an @extend-template-style annotation.
  */
  private function hasTemplateStyleExtendAnnotation($filePath) {
    $annotationPattern = "/@extend-template-style/i";
    $script = file_get_contents($filePath);
    if(preg_match($annotationPattern,$script)) {
      return true;
    }
    return false;
  }
  
  /*
   *	getTemplateResourcesFilesPath
   *	This method get the template resources files path.
   *	As resources are merged for templates, we can have several files.
   *	It returns an array of files path from the most generic one to the
   *	most specific one.
   *
   *	parameters:
   *		- templateName : the template name.
   *		- options : options to be used for template requirement (cf. TemplateManager->loadTemplate).
  */
  public function getTemplateResourcesFilesPath($templateName, $options = array()) {
    global $request;
    
    // We get the site context
    $siteContext = $request->getSiteContext();
    // In case we get the template in a specific context.
    if(isset($options["siteContext"]) && $options["siteContext"] instanceof SiteContext) {
      $siteContext = $options["siteContext"];
    }

    $application = $siteContext->getApplication();
    $webapp = $application->getWebapp();
    $site = $application->getSite();
    
    $pageUtils = new PageUtils($siteContext, NULL);

    $files = array();
    $languageCode = $request->getLanguageCode();
    
    // We get the template names hierarchy
    $templateNamesHierarchy = $this->getTemplateNamesHierarchy($templateName);

    // We loop over templateNamesHierarchy
    foreach ($templateNamesHierarchy as $templateName) {

      $templatePath = $pageUtils->getItemPath($templateName);

      // templateName-languageCode.properties => version in framework
      $filePath = $request->getConfig("fwk-templatesDirectory").$templatePath."-".$languageCode.".properties";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      // templateName-languageCode.properties => version in webapp
      $filePath = $webapp->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath."-".$languageCode.".properties";
      if(file_exists($filePath)) {
        array_push($files, $filePath);
      }

      if(!Site::isNoSite($site)) {
        // templateName-languageCode.properties => version customized for site
        $filePath = $site->getRootPath().DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR.$templatePath."-".$languageCode.".properties";
        if(file_exists($filePath)) {
          array_push($files, $filePath);
        }
      }
    }
    
    // In case we have several files, we need to keep the resource files starting from the latest which does not extend resources.
    if(count($files) > 1) {
      for($i = count($files) - 1; $i >= 0; $i--) {
        // We look for the extend annotation in the file.
        $filePath = $files[$i];
        if(!$this->hasTemplateResourcesExtendAnnotation($filePath)) {
          // We keep the resources from this file.
          $files = array_splice($files,$i);
          break;
        }
      }
    }

    return $files;
  }
  
  /*
   *	hasTemplateResourcesExtendAnnotation
   *	This method checks in a resources file path if there is an @extend-template-resources annotation.
   */
  private function hasTemplateResourcesExtendAnnotation($filePath) {
    $annotationPattern = "/@extend-template-resources/i";
    $script = file_get_contents($filePath);
    if(preg_match($annotationPattern,$script)) {
      return true;
    }
    return false;
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
    return $request->getApplication()->getRootPath().DIRECTORY_SEPARATOR.$request->getConfig("app-cacheDirectory").DIRECTORY_SEPARATOR."templates";
  }

  /*
   *	private checkCache
  *	This method check that cache directory exists or create it.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- none.
  */
  private function checkCache($request) {
    $tmpDirectory = static::getCachePath($request);

    // We check if minify cache directory exists
    if(!is_dir($tmpDirectory)) {
      mkdir($tmpDirectory, 0777, true);
    }
  }

  /*
   *	private getEngineVar
  *	This method get the variable value depending on the js templating engine used.
  *
  *	parameters:
  *		- var - the var name.
  *	return:
  *		- the corresponding value based on engine.
  */
  private function getEngineVar($var) {
    return self::$engineVar[$this->engine][$var];
  }


}

// We init the static of the class
TemplateManager::init();
?>