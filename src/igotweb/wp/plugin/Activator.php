<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    igotweb\wp
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */
namespace igotweb_wp_mp_links\igotweb\wp\plugin;

class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate($version) {

		update_option('iw_mp_links_version', $version);

	}

}
