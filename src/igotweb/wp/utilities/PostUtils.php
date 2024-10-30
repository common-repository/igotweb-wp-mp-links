<?php
/**
 * Help to manage Posts of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;

class PostUtils {

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
            static::$instance = new PostUtils();
        }
		return static::$instance;
    }

    /**
     * getCurrentPost
     * This method get the current post
     */
    public static function getCurrentPost() {
        global $post;
        if(in_the_loop()) {
            return get_post(get_the_ID());
        }
        else {
            return (isset($post) && $post instanceof \WP_Post)?$post:false;
        }
    }

    /**
     * getURL
     * This method get the URL to the post.
     */
    public static function getURL($postID) {
        $permalink = get_post_permalink($postID);
        return $permalink;
    }
    

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        
    }
}