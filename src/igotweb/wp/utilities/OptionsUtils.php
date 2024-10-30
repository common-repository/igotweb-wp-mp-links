<?php
/**
 * Help to manage Options.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;

class OptionsUtils {

    /**
	 * The unique instance of utils to be available for all classes.
	 */
    private static $instance;

    
    public function __construct() {
        // We make sure that constructor is called only once.
		if(static::$instance != null) {
			return null;
        }
    }

    // STATIC METHODS

    /**
	 * We get the shared instance of utils
	 */
	public static function getInstance() {
        if(!isset(static::$instance)) {
            static::$instance = new OptionsUtils();
        }
		return static::$instance;
    }

    public static function exportSettings() {
        $plugin = Plugin::getInstance();
        
        $settings = array();

        // We get the options eligible for import / export
        $options = $plugin->getOptions();
        if(is_callable(array($options, "getOptionsForImport"))) {
            $optionsForImport = $options->getOptionsForImport();
            foreach($optionsForImport as $option) {
                $value = get_option($option);
                if(isset($value)) {
                    $settings[$option] = $value;
                }
            }
        }

        $jsonValue = json_encode($settings);
        $response = array(
            'json' => $jsonValue
        );
            
        wp_send_json($response);
    }

    

    public static function importSettings() {
        $plugin = Plugin::getInstance();
        $rootPath = $plugin->getRootPath();

        $formFile = $_FILES['file'];
        $tmpDirectory = $rootPath . "tmp" . DIRECTORY_SEPARATOR;
        $tmpPath = $tmpDirectory . $formFile['name'];
        
        // We require the vendor autoload
        require_once $rootPath . "vendor/autoload.php";

        $response = array();
        $errors = array();

        
        // We create the temporary folder if not exist.
		if(!is_dir($tmpDirectory)) {
			$created = FileUtils::createDirectory($tmpDirectory);
			if($created instanceof Error) {
				$errors[] = $created;
			}
		}

        $result = FileUtils::uploadFile($formFile, $tmpPath);
        if($result instanceof Error) {
          $errors[] = $result;
        }

        if(count($errors) == 0) {
            $content = FileUtils::readFile($tmpPath);
            $json = json_decode($content, true);
            if($json != null && is_array($json)) {
                $response['updated'] = array();
                $response['filtered'] = array();
                
                // We get the options eligible for import / export
                $options = $plugin->getOptions();
                if(is_callable(array($options, "getOptionsForImport"))) {
                    $optionsForImport = $options->getOptionsForImport();
                    foreach($json as $option => $value) {
                        if(in_array($option, $optionsForImport)) {
                            update_option( $option, $value );
                            $response['updated'][] = $option;
                        }
                        else {
                            $response['filtered'][] = $option;
                        }
                    }
                    $response['imported'] = true;
                }
                else {
                    $response['imported'] = false;
                }
            }
            else {
                $errors[] = Error::getGeneric(__('The file is not well formatted.','igotweb-wp'));
            }
        }

        // We remove the uploaded file.
        FileUtils::removeFile($tmpPath);

        if(count($errors) > 0) {
            $response['errors'] = JSONUtils::getObjectFromJSON(JSONUtils::buildJSONObject($errors));
            $response['imported'] = false;
        }
        
        wp_send_json($response);
    }
    

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        // We register import and export actions
        $plugin->getLoader()->add_action( 'wp_ajax_' . $this->getExportAction(), $this , 'exportSettings' );
        $plugin->getLoader()->add_action( 'wp_ajax_' . $this->getImportAction(), $this , 'importSettings' );
    }

    public function getExportAction() {
        $plugin = Plugin::getInstance();
        return $plugin->getShortName() . '_export_settings';
    }

    public function getImportAction() {
        $plugin = Plugin::getInstance();
        return $plugin->getShortName() . '_import_settings';
    }
    
}