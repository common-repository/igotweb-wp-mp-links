<?php
/**
 * Help to manage Connectors of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;

class ConnectorsUtils {

    /**
	 * The unique instance of activation utils to be available for all classes.
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
            static::$instance = new ConnectorsUtils();
        }
		return static::$instance;
    }
    

    // INSTANCE METHODS

    public function sendRequestTest($endpoint, $args=array()) {
        $reponse = array();
        
        /*
         *  /license/info
         *  This endpoint returns information in regards to license for current domain and plugin
         *      version - the version of the plugin available for license
         *      downloadUrl - the URL to download the version of plugin available for license
         */
        if($endpoint == "/license/info") {
            $response = array(
                'version' => '1.0.1',
                'downloadUrl' => ''
            );
        }

        return $response;
    }

    public function sendRequest( $endpoint, $args=array(), $method='get', $blocking=true ) {
        $plugin = Plugin::getInstance();
        $domain = $plugin->getAPIServer();

        $uri = "{$domain}/rest/wordpress{$endpoint}";

        $arg_array = array(
        'method'    => strtoupper($method),
        'headers'   => array( "Content-type" => "application/json" ),
        'body'      => json_encode($args),
        'timeout'   => 5,
        'blocking'  => $blocking,
        'sslverify' => false,
        );

        $resp = wp_remote_post($uri, $arg_array);

        // If we're not blocking then the response is irrelevant
        // So we'll just return true.
        if($blocking == false) {
            return true;
        }

        if(is_wp_error($resp)) {
            $message = $resp->get_error_message();
            $plugin->errorLog("ConnectorsUtils: error in Send request - ".$message." - Endpoint: ".$endpoint);
            throw new \Exception(__('HTTP error while trying to access to iGot-Web API', 'igotweb-wp'));
        }
        else {
            if($resp['response']['code'] == 404) {
                $plugin->errorLog("ConnectorsUtils: 404 error in Send request - Endpoint: ".$endpoint);
                throw new \Exception(__('HTTP error while trying to access to iGot-Web API', 'igotweb-wp'));
            }
            else if(null !== ($json_res = json_decode($resp['body'], true))) {
                if($json_res['errors'] !== null && count($json_res['errors']) > 0) {
                    $message = $json_res['errors'][0]['formattedMessage'];
                    $plugin->errorLog("ConnectorsUtils: errors in output of response - ".$message." - Endpoint: ".$endpoint);
                    throw new \Exception($message);
                }
                else {
                    return $json_res['response'];
                }
            }
            else {
                $plugin->errorLog("ConnectorsUtils: empty response - Endpoint: ".$endpoint);
                throw new \Exception(__('HTTP error while trying to access to iGot-Web API', 'igotweb-wp'));
            }
        }

        return false;
    }
}