<?php
/**
 * Help to manage views.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\StaticsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;

class ViewsUtils {

    public static $VIEW_ID_TAG = "viewID";
    public static $VIEW_JS_DATA_VAR = "igotweb_wp_viewData";
    public static $VIEW_JS_DATA_INDEX_TAG = "viewDataIndex";
    public static $VIEW_JS_RESOURCES_TAG = "viewResources";

    /**
	 * The unique instance of plugin to be available for all classes.
	 */
    private static $instance;

    /**
	 * The viewData generated for views script. Map for each view with index for each instance of the view generated.
	 */
    private $viewData;
    
    public function __construct() {
		// We make sure that constructor is called only once.
		if(static::$instance != null) {
			return null;
        }

        // We initialize the viewData
        $this->viewData = array();

        // We set the instance
        static::$instance = $this;
    }

    // STATIC METHODS

    /**
	 * We get the shared instance of Plugin
	 */
	public static function getInstance() {
		return static::$instance;
	}

    public static function getViewFromAjaxRequest() {
        $data = $_POST['data'];
        $view = $_POST['view'];

        $response = array();
        $response = static::getView($view,$data);
	
		wp_send_json($response);
    }

    public static function renderView($view, $data = array()) {
        $view = static::getView($view, $data);
        echo $view['html'];
    }

	/**
	 *
	 * @since    1.0.0
	 */
	public static function getView($view, $data = array()) {
        $viewsUtils = static::getInstance();
        $staticsUtils = StaticsUtils::getInstance();

        if($viewsUtils == null) {
			echo "ViewUtils instance is not created";
        }

        $plugin = Plugin::getInstance();

        $templatePath = $viewsUtils->getViewTemplatePath($view);
        // We check if the component exists
        if(!file_exists($templatePath)) {
            return;
        }

        // We load the data
        $dataPath = $viewsUtils->getViewDataPath($view);
        if(file_exists($dataPath)) {
            include($dataPath);
        }

        // We add the view id to the data
        $viewsUtils->populateDataWithID($view, $data);

        // We load the script
        $data = $viewsUtils->includeViewScript($view, $data);

        // We load the style
        $staticsUtils->includeStyle($view, "view");

        ob_start();
        include($templatePath);
        $html = ob_get_clean();
        
        return array(
            'html' => $html,
            'data' => $viewsUtils->viewData
        );
    }

    public static function getAssetURL($path) {
        $plugin = Plugin::getInstance();
        return $plugin->getAssetsURL() . $path;
    }

    public static function generateViewContainerAttributes($data) {
        // we generate the container class
        echo "class=\"".$data[static::$VIEW_ID_TAG]."\"";
        echo " data-viewid=\"".$data[static::$VIEW_ID_TAG]."\"";

        // We generate the view data index attribute
        if(isset($data[static::$VIEW_JS_DATA_INDEX_TAG])) {
            echo " data-viewdataindex=\"".$data[static::$VIEW_JS_DATA_INDEX_TAG]."\"";
        }
    }

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {

        // The search user ajax action
        $getViewAction = $plugin->getShortName() . '_get_view';
        $plugin->getLoader()->add_action( 'wp_ajax_' . $getViewAction, 'igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils' , 'getViewFromAjaxRequest' );
        
        // We provide the ajax_url
        $this->viewData['ajax_url'] = admin_url( 'admin-ajax.php' );
        $this->viewData['get_view_action'] = $getViewAction;

        // We register action to add viewsUtils
        $plugin->getLoader()->add_action( 'admin_enqueue_scripts', $this, 'enqueueScripts');
        $plugin->getLoader()->add_action( 'wp_enqueue_scripts', $this, 'enqueueScripts');

        // We add the viewData
        $plugin->getLoader()->add_action( 'admin_footer', $this, 'generateViewData' );
        $plugin->getLoader()->add_action( 'wp_footer', $this, 'generateViewData' );
    }

    public function enqueueScripts($hook) {
        if(PageUtils::isPluginAdminPage() || PageUtils::isPluginWebPage()) {
            $plugin = Plugin::getInstance();
            // we add the ViewsUtils script
            $scriptName = "script-" . $plugin->getShortName() . "-views-utils";
            wp_enqueue_script($scriptName, $plugin->getAssetsURL()."js/ViewsUtils.js", array(), $plugin->getVersion(), true );
        }
    }

    public function generateViewData() {
        if(PageUtils::isPluginAdminPage() || PageUtils::isPluginWebPage()) {
            $plugin = Plugin::getInstance();
            $scriptName = "script-" . $plugin->getShortName() . "-views-utils";
            wp_localize_script($scriptName, static::$VIEW_JS_DATA_VAR, $this->viewData);
        }
    }

    private function includeViewScript($view, $data) {
        $plugin = Plugin::getInstance();
        $scriptPath = static::getViewScriptPath($view);
        if(file_exists($scriptPath)) {
            $dependencies = static::getScriptDependencies($view);
            foreach($dependencies["views"] as $dependency) {
                $this->includeViewScript($dependency, $data);
            }
            $scriptName = StaticsUtils::getScriptName($view);
            // Script are added before </body> as they are included during the body generation.
            wp_enqueue_script($scriptName, static::getViewScriptURL($view), $dependencies["wp"], $plugin->getVersion(), true );
            $data = $this->addViewData($data);
        }
        return $data;
    }

    private function populateDataWithID($view, &$data) {
        $id = StaticsUtils::getIDFromPath($view);
        $data[static::$VIEW_ID_TAG] = $id;
    }

    private function addViewData($data) {
        $id = $data[static::$VIEW_ID_TAG];

        // We create array of instances if not already included.
        if(!isset($this->viewData[$id])) {
            $this->viewData[$id] = array();
        }
        $index = count($this->viewData[$id]);
        $this->viewData[$id][$index] = $data;
        
        // We add the index to the data to be used in the view.
        $data[static::$VIEW_JS_DATA_INDEX_TAG] = $index;
        return $data;
    }

    private static function getViewPath($view) {
        $plugin = Plugin::getInstance();
        return $plugin->getViewsPath() . $view . DIRECTORY_SEPARATOR;
    }

    private static function getViewTemplatePath($view) {
        return static::getViewPath($view) . "view.php";
    }

    private static function getViewDataPath($view) {
        return static::getViewPath($view) . "data.php";
    }

    public static function getViewStylePath($view) {
        return static::getViewPath($view) . "style.css";
    }

    public static function getViewStyleURL($view) {
        $plugin = Plugin::getInstance();
        return $plugin->getViewsURL() . $view . "/style.css";
    }

    public static function getViewScriptPath($view) {
        return static::getViewPath($view) . "script.js";
    }

    public static function getViewScriptURL($view) {
        $plugin = Plugin::getInstance();
        return $plugin->getViewsURL() . $view . "/script.js";
    }

    private static function getScriptDependencies($view) {
        $scriptPath = static::getViewScriptPath($view);
        $script = file_get_contents($scriptPath);
        preg_match_all('/@import (.*)?;/', $script, $imports);
        $dependencies = $imports[1];

        $result = array(
            "views" => array(),
            "wp" => array()
        );

        foreach ($dependencies as $dependency) {
            if(preg_match('/^view:(.*)/',$dependency, $matches)) {
                $result["views"][] = $matches[1];
            }
            else {
                $result["wp"][] = $dependency;
            }
        }

        return $result;
    }
}
