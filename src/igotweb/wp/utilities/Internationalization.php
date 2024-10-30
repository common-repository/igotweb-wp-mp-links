<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;

class Internationalization {

	private static $BASE_DOMAIN = "igotweb-wp";

	/**
	 * The domain of the plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $domain;

	/**
	 * Set the domain for internationalization.
	 *
	 * @since    1.0.0
	 */
	public function __construct($domain) {

		$this->domain = $domain;

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		$plugin = Plugin::getInstance();
		$folder = '/' . dirname( $plugin->getSlug() ) . '/languages';

		// we load the plugin specific localization
		load_plugin_textdomain(
			$this->domain,
			false,
			$folder
		);

		// We load the generic plugin localization
		load_plugin_textdomain(
			static::$BASE_DOMAIN,
			false,
			$folder
		);

	}



}
