<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    igotweb\wp
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */
namespace igotweb_wp_mp_links\igotweb\wp\plugin;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\plugin\Options;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;

class Admin {

	protected $hooks;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// We need to define the admin hooks in which scripts are loaded.
		$this->hooks = array(
			"memberpress_page_iw_mp_links_options"
		);

	}

	public function defineHooks() {
		// We get the plugin loader
		$plugin = Plugin::getInstance();
		$loader = $plugin->getLoader();

		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_styles' );

		// We add menu items to memberpress menu using their specific hook
		$loader->add_action('mepr_menu', $this, 'mepr_menu');

	}

	public function getHooks() {
		return $this->hooks;
	}
		
     
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if(PageUtils::isPluginAdminPage()) {
			$plugin = Plugin::getInstance();			
			// We load admin specific assets for the plugin
			wp_enqueue_style('font-awesome', 'https://use.fontawesome.com/releases/v5.0.10/css/all.css', array(), $plugin->getVersion(), 'all');
		}
	}

	/**
	 * Add the menu item in admin console.
	 *
	 * @since    1.0.0
	 */
	public function mepr_menu() {

		add_submenu_page('memberpress', __('IW - Links', 'igotweb-wp-mp-links'), __('Links', 'igotweb-wp-mp-links'), 'administrator', Options::$PAGE_SLUG, array($this, 'admin_options_content'));
	}

	public function admin_options_content() {
		$plugin = Plugin::getInstance();
		// We force the check of plugin version
		$plugin->getUpdateUtils()->manuallyCheckTransient();
		// We configure the settings to be displayed
		$plugin->getOptions()->configureSettingsPage();
		// We include the page
		ViewsUtils::renderView('admin/options-page');
	}

}
