<?php
/**
 * Help to manage Plugins.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\OptionsUtils;

class PluginUtils {

    /**
	 * The unique instance of activation utils to be available for all classes.
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
	 * We get the shared instance of PluginUtils
	 */
	public static function getInstance() {
        if(!isset(static::$instance)) {
            static::$instance = new PluginUtils();
        }
		return static::$instance;
    }

    /**
     * isPluginActivated
     * Check if a plugin is activated
     * @param pluginName the plugin name.
     */
    public static function isPluginActivated($pluginName) {
        // We need to get the plugin file as not available on Frontend side.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active($pluginName . DIRECTORY_SEPARATOR . $pluginName . ".php");
    }

    /**
     * MEMBERPRESS SPECIFIC
     * isMemberPressActivated
     * Check if Memberpress is activated.
     */
    public static function isMemberPressActivated() {
        return static::isPluginActivated("memberpress");
    }

    /**
     * MEMBERPRESS SPECIFIC
     * getMemberPressAccountPageID
     * Get the account page ID.
     */
    public static function getMemberPressAccountPageID() {
        return get_option('mepr_options')['account_page_id'];
    }
    

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        
    }

}