<?php

/**
 * The web-facing functionality of the plugin.
 *
 * @link       http://www.igot-web.com
 * @since      1.0.0
 *
 * @package    Igotweb_Cloud
 * @subpackage Igotweb_Cloud
 */

/**
 * The web-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the web-facing stylesheet and JavaScript.
 *
 * @package    Igotweb_Cloud
 * @subpackage Igotweb_Cloud
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */
namespace igotweb_wp_mp_links\igotweb\wp\plugin;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\generic\Web as GenericWeb;
use igotweb_wp_mp_links\igotweb\wp\utilities\PluginUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;

class Web extends GenericWeb {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	public function defineHooks() {
		// We get the plugin loader
		$plugin = Plugin::getInstance();
		$loader = $plugin->getLoader();

		// We check if memberpress is activated
		$isMemberPressActivated = PluginUtils::isMemberPressActivated();
		if($isMemberPressActivated) {
			$loader->add_action('mepr_account_nav', $this ,'checkMemberPressTabs');
			//$loader->add_action('mepr_account_nav_content', $this , 'checkMemberPressContent');
		}
	}

	/**
	 * getPages
	 * This method returns an array of page ID for all pages with specific plugin content.
	 */
	public function getPages() {
		// We get the plugin loader
		$plugin = Plugin::getInstance();
		$options = $plugin->getOptions();

		$pages = array();

		$listPages = $options->getListPages();
		foreach($listPages as $key => $page) {
			if(isset($page["ID"]) && $page["ID"] != "") {
				$pages[] = $page["ID"];
			}
		}

		return $pages;
	}

	/**
	 * checkMemberPressTabs
	 * This method check the 
	 */
	public function checkMemberPressTabs($user) {

		$plugin = Plugin::getInstance();
		$options = $plugin->getOptions();
		$listPages = $options->getListPages();

		foreach($listPages as $page) {
			$url = PageUtils::getURL($page["ID"]);
			?>
	
			<span class="mepr-nav-item documents">
				<a href="<?php echo $url; ?>"><?php echo $page["menuTitle"]; ?></a>
			</span>
			
			<?php
		}

	}

}
