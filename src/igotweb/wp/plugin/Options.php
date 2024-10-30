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

use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\OptionsUtils;
use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;

class Options {

	public static $OPTIONS_PREFIX = 'iw_mp_links_';
	public static $PAGE_SLUG = 'iw_mp_links_options';

	private $optionsUtils;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	// INSTANCE METHODS

    public function registerActions(Plugin $plugin) {

		// We register settings used by the plugin
		$plugin->getLoader()->add_action("admin_init", $this, 'registerSettings');
        
    }

	public function getPrefix() {
		return static::$OPTIONS_PREFIX;
	}

	/**
	 * getOption
	 * This method get the option value by adding the prefix to the name
	 */
	public static function getOption($optionName) {
		$settingName = static::$OPTIONS_PREFIX.$optionName;
		return get_option($settingName);
	}

	/**
	 * registerSettings
	 * This method registers settings based on the tab to be displayed on Options page.
	 */
	public function registerSettings() {
		
		register_setting( static::$PAGE_SLUG, static::$OPTIONS_PREFIX.'list_pages', array($this, 'list_pages_sanitize'));
		
	}

	/**
	 * configureSettingsPage
	 * This method is called before we render the options page to configure it.
	 */
	public function configureSettingsPage() {
		// We define section and page to be used.
		$section = static::$OPTIONS_PREFIX.'general';
		$page = static::$PAGE_SLUG;

		// Create section of Page
		add_settings_section( 
			$section, 
			__('Links', 'igotweb-wp-mp-links'), 
			array($this, 'general_section_renderer'), 
			$page
		);

		// Add list pages added to tabs
		add_settings_field(
			static::$OPTIONS_PREFIX.'list_pages', 
			__('List of pages', 'igotweb-wp-mp-links' ),
			array($this, 'list_pages_renderer'),
			$page,
			$section
		);

	}

	public function general_section_renderer() {
		$plugin = Plugin::getInstance();
		$name = __($plugin->getName(), 'igotweb-wp-mp-links');
		printf( esc_html__( 'Welcome in settings for %s plugin','igotweb-wp-mp-links'), $name);
		echo "<br/>";
		_e('This section allows you to configure pages which will be added to the menu of the MemberPress account page.','igotweb-wp-mp-links');
	}

	public function list_pages_sanitize ($input) {
		$data = array();
		if(is_array($input)) {
			$data = array_values($input);
		}
		return $data;
	}

	/**
	 * getListPages
	 * This method get the list of pages configured
	 */
	public function getListPages() {
		$settingName = static::$OPTIONS_PREFIX.'list_pages';
		$settings = get_option($settingName);
		if($settings == "") {
			// In case nothing is set, we put a default value to avoid issue.
			$settings = array();
		}
		return $settings;
	}

	public function list_pages_renderer() {

		$data = array(
			"settingName" => static::$OPTIONS_PREFIX.'list_pages'
		);

		// We include the list of pages
		ViewsUtils::renderView('admin/options-list-pages', $data);
		
	}
}
