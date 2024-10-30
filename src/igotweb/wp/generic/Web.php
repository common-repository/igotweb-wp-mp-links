<?php

/**
 * The web-facing functionality of the plugin.
 *
 * @link       http://www.igot-web.com
 * @since      1.0.0
 *
 * @package    Igotweb
 * @subpackage Igotweb
 */

/**
 * The web-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the web-facing stylesheet and JavaScript.
 *
 * @package    Igotweb
 * @subpackage Igotweb
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */
namespace igotweb_wp_mp_links\igotweb\wp\generic;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\EndPoint;

class Web {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	/**
	 * defineHooks
	 * This method must be overriden to define frontend specific hooks
	 * Examples:
	 * 		$loader->add_action('mepr_account_nav', $this ,'checkMemberPressTabs');
	 *		$loader->add_action('mepr_account_nav_content', $this , 'checkMemberPressContent');
	 *		$loader->add_filter('the_content', $this, 'checkPostContent');
	 *		$loader->add_action('template_redirect', $this, 'checkPostRedirection');
	 *		$loader->add_filter('template_include', $this, 'checkPageTemplate');
	 */
	public function defineHooks() {
		
	}

	/**
	 * getPages
	 * This method returns an array of page ID for all pages with specific plugin content.
	 * It is used to include assets only on plugin frontend pages.
	 */
	public function getPages() {
		$pages = array();
		return $pages;
	}


	/**
	 * defineEndPoints
	 * This method defines specific endpoint to be used by the plugin
	 * Example
	 * 		$rewriteUtils = Plugin::getInstance()->getRewriteUtils();
	 *		$download = new EndPoint('iw-cloud-download',array($this, 'handleDownloadEndpoint'));
	 *		$rewriteUtils->addEndPoint($download);
	 */
	public function defineEndPoints() {
		
	}

}
