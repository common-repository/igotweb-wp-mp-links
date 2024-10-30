<?php
/**
 * Help to manage Statics of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;

class StaticsUtils {

    /**
	 * The unique instance of page utils to be available for all classes.
	 */
    private static $instance;

    private function __construct() {
        // We make sure that constructor is called only once.
		if(static::$instance != null) {
			return null;
        }

        static::$instance = $this;
    }

    // STATIC METHODS

    /**
	 * We get the shared instance of ActivationUtils
	 */
	public static function getInstance() {
        if(!isset(static::$instance)) {
            static::$instance = new StaticsUtils();
        }
		return static::$instance;
    }

    /**
     * getIDFromPath
     * This generates an ID from a folder + file name path (without extension)
     */
    public static function getIDFromPath($path) {
        return preg_replace('/\//','-',$path);
    }

    /**
     * getStyleName
     * This methods get unique identifier for style based on the name (including folder path from root)
     */
    public static function getStyleName($styleName) {
        $plugin = Plugin::getInstance();
        return "style-" . $plugin->getShortName() . "-" . static::getIDFromPath($styleName);
    }

    /**
     * getScriptName
     * This methods get unique identifier for script based on the name (including folder path from root)
     */
    public static function getScriptName($scriptName) {
        $plugin = Plugin::getInstance();
        return "script-" . $plugin->getShortName() . "-" . static::getIDFromPath($scriptName);
    }

    /**
     * getStyleDependencies
     * This methods returns the style dependencies declared in a style file.
     */
    public static function getStyleDependencies($stylePath) {
        $style = file_get_contents($stylePath);
        preg_match_all('/@import (.*)?;/', $style, $imports);
        $dependencies = $imports[1];
        
        $result = array(
            "view" => array(),
            "asset" => array(),
            "jquery-ui" => array(),
            "wp" => array()
        );

        foreach ($dependencies as $dependency) {
            if(preg_match('/^view:(.*)/',$dependency, $matches)) {
                $result["view"][] = $matches[1];
            }
            else if(preg_match('/^asset:(.*)/',$dependency, $matches)) {
                $result["asset"][] = $matches[1];
            }
            else if(preg_match('/^jquery-ui:(.*)/',$dependency, $matches)) {
                $result["jquery-ui"][] = $matches[1];
            }
            else {
                $result["wp"][] = $dependency;
            }
        }
        return $result;
    }

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        
    }

    /**
     * includeStyle
     * This method includes style based on the type
     * @param styleName the style name
     * @param type the style type (asset, view)
     */
    public function includeStyle($styleName, $type) {
        $plugin = Plugin::getInstance();

        // We check the path and URL depending on the type
        $stylePath = null;
        $styleURL = null;
        switch ($type) {
            case 'view':
                $stylePath = ViewsUtils::getViewStylePath($styleName);
                $styleURL = ViewsUtils::getViewStyleURL($styleName);
                break;
            
            case 'asset':
                $path = "css/" . $styleName . ".css";
                $stylePath = $plugin->getAssetsPath() . $path;
                $styleURL = $plugin->getAssetsURL() . $path;
                break;

            case 'jquery-ui':
                $this->includeJQueryUIStyle($styleName);
                break;
        }

        if($stylePath != null && file_exists($stylePath)) {
            $styleName = static::getStyleName($styleName);
            wp_enqueue_style($styleName, $styleURL, array(), $plugin->getVersion(), false );

            $dependencies = static::getStyleDependencies($stylePath);
            foreach ($dependencies as $type => $styles) {
                foreach ($styles as $style) {
                    $this->includeStyle($style, $type);
                }
            }
        }
    }

    private function includeJQueryUIStyle($name) {
        global $wp_scripts;
        
        // Create a handle for the jquery-ui-core css.
        $handle = $name;
        if($name == "core") {
            $handle = 'jquery-ui';
        }

		// Path to stylesheet, based on the jquery-ui-core version used in core.
		$src = "http://ajax.googleapis.com/ajax/libs/jqueryui/{$wp_scripts->registered['jquery-ui-core']->ver}/themes/smoothness/{$handle}.css";
		// Required dependencies
		$deps = array();
		// Add stylesheet version.
		$ver = $wp_scripts->registered['jquery-ui-core']->ver;
		// Register the stylesheet handle.
		wp_register_style( $handle, $src, $deps, $ver );
		// Enqueue the style.
		wp_enqueue_style( $handle );
    }
}