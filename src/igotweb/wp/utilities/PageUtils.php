<?php
/**
 * Help to manage Pages of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\plugin\Admin;
use igotweb_wp_mp_links\igotweb\wp\plugin\Web;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\wp\utilities\PostUtils;

class PageUtils {

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
            static::$instance = new PageUtils();
        }
		return static::$instance;
    }

    /**
     * isAdminPage
     * This function returns true if current target is admin page or not.
     */
    public static function isAdminPage() {
        return is_admin();
    }

    /**
     * isPluginAdminPage
     * This function returns true if current target is plugin related admin page.
     * This method should not be called in admin_init hook.
     */
    public static function isPluginAdminPage() {
        if(static::isAdminPage()) {
            $screen = get_current_screen();
            $plugin = Plugin::getInstance();
            
            // We get the admin instance of the plugin
            $admin = $plugin->getAdmin();
            if(isset($admin) && 
                    $admin instanceof Admin && 
                    is_callable(array($admin, "getHooks"))) {
                $hooks = $admin->getHooks();
                if(in_array($screen->id, $hooks)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * isInPage
     * This method checks if current post is a page.
     */
    public static function isInPage() {
        $post = PostUtils::getCurrentPost();
        if(isset($post) && $post->post_type == "page") {
            return true;
        }
        return false;
    }

    /**
     * isPluginWebPage
     * This function returns true if current page is plugin related frontedn page.
     */
    public static function isPluginWebPage() {
        if(!static::isAdminPage() && static::isInPage()) {

            $plugin = Plugin::getInstance();
            $post = PostUtils::getCurrentPost();

            // We get the admin instance of the plugin
            $web = $plugin->getWeb();
            if(isset($web) && 
                    $web instanceof Web && 
                    is_callable(array($web, "getPages"))) {
                $pages = $web->getPages();
                if(in_array($post->ID, $pages)) {
                    return true;
                }
            }
        }
        
        return false;
        
    }

    /**
     * getURL
     * This method get the URL to the page.
     */
    public static function getURL($pageID) {
        $permalink = get_permalink($pageID);
		if(!$permalink) {
			$permalink = PostUtils::getURL($pageID);
        }
        return $permalink;
    }

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        
    }

    /**
     * createEmptyPage
     * This method creates a new empty page
     * @param $pageName - the page name.
     * @return WP_Post object or Error object
     */
    public function createEmptyPage($pageName) {
        $plugin = Plugin::getInstance();

        $my_post = array(
            'post_title' => $pageName,
            'post_status' => 'publish',
            'post_type' => 'page'
            );
            
        // Insert the post into the database
        $result = wp_insert_post( $my_post );
        if($result == 0) {
            $plugin->errorLog("PageUtils::createEmptyPage - The page could not be added (".$pageName.")");
            return Error::getGeneric(__('Error while trying to create new page.','igotweb-wp'));
        }
        else if(is_wp_error($result)) {
            $message = $result->get_error_message();
            $plugin->errorLog("PageUtils::createEmptyPage - The page could not be added (".$message.")");
            return Error::getGeneric(__('Error while trying to create new page.','igotweb-wp'));
        }

        // We get the page object
        return get_post($result);
    }

    /**
     * getListPages
     * This method get the list of pages.
     * @param $options - the options used in get_pages wordpress method.
     */
    public function getListPages($options = array()) {
        $defaultOptions = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        ); 
        $options = array_merge($defaultOptions, $options);
        $pages = get_pages($options);
        return $pages;
    }
}