<?php
/**
 * Help to manage Rewrite of URLs.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\EndPoint;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;

class RewriteUtils {

    /**
	 * The unique instance of utils to be available for all classes.
	 */
    private static $instance;

    private $endpoints;
    
    public function __construct() {
        // We make sure that constructor is called only once.
		if(static::$instance != null) {
			return null;
        }

		$this->endpoints = array();
    }

    // STATIC METHODS

    /**
	 * We get the shared instance of utils
	 */
	public static function getInstance() {
        if(!isset(static::$instance)) {
            static::$instance = new RewriteUtils();
        }
		return static::$instance;
    }

    public static function redirect404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
    }

    public static function redirect500($message) {
        status_header(500);
        print_r($message);
        exit();
    }

    public static function redirectLoginPage() {
        wp_redirect( wp_login_url() ); 
        exit();
    }

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {

        // The search user ajax action
        $plugin->getLoader()->add_action('init', $this, 'initEndPoints');
		$plugin->getLoader()->add_action('template_redirect', $this,'checkTemplateRedirect' );
    }

    public function addEndPoint(EndPoint $endpoint) {
        if(is_callable($endpoint->getCallback())) {
            $this->endpoints[$endpoint->getPath()] = $endpoint;
        } 
    }

    public function initEndPoints() {
        foreach($this->endpoints as $path => $endpoint) {
            add_rewrite_endpoint($endpoint->getPath(),$endpoint->getLevel());
        }

		//Ensure the $wp_rewrite global is loaded
		global $wp_rewrite;
		//Call flush_rules() as a method of the $wp_rewrite object
		$wp_rewrite->flush_rules();
    }

    public function checkTemplateRedirect() {
        global $wp_query;
        foreach($this->endpoints as $path => $endpoint) {
            $path = $endpoint->getPath();
            $query = @$wp_query->query_vars[$path];
            if ( isset( $query )) {
                $params = array();
                if($query != "" ) {
                    $params = explode("/",$query);
                }
                call_user_func($endpoint->getCallback(), $params);
                return;
            }
        }
        
    }
    
}