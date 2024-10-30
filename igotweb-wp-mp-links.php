<?php

/**
 * Plugin Bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       MemberPress Menu
 * Description:       Manage links in the menu of MemberPress account page.
 * Version:           1.0.0
 * Author:            iGot-Web
 * Author URI:        http://www.igot-web.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       igotweb-wp-mp-links
 * Domain Path:       /languages
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

// We include the debug panel
include plugin_dir_path(__FILE__) . 'panels/debug.php';
// We require the autoload
require_once plugin_dir_path(__FILE__) . 'panels/autoload.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function igotweb_wp_mp_links() {

  // The following lines are used by the build to populate information elsewhere in the code.
  $PLUGIN_VERSION = '1.0.0';
  $PLUGIN_URI = 'http://www.igot-web.com';
  $PLUGIN_NAME = __('MemberPress Menu', 'igotweb-wp-mp-links');
  $PLUGIN_AUTHOR = 'iGot-Web';
  $TEST_DESCRIPTION = __("Bonjour, \n"
    . "Voici la description \n"
    . "Sur plusieurs lignes"
    , 'igotweb-wp-mp-links');
  $PLUGIN_DESCRIPTION = __('Manage links in the menu of MemberPress account page.', 'igotweb-wp-mp-links');

  $pluginAPIServer = 'http://node-api.igot-web.com';

  $info = array(
    'slug' => plugin_basename(__FILE__),
    'shortName' => 'igotweb-wp-mp-links',
    'name' => $PLUGIN_NAME,
    'rootPath' => plugin_dir_path(__FILE__),
    'version' => '1.0.0',
    'uri' => 'http://www.igot-web.com',
    'author' => 'iGot-Web',
    'description' => $PLUGIN_DESCRIPTION,
    'textdomain' => 'igotweb-wp-mp-links',
    'APIServer' => $pluginAPIServer
  );

  $plugin = new igotweb_wp_mp_links\igotweb\wp\Plugin($info);
	$plugin->run();

}
igotweb_wp_mp_links();

?>