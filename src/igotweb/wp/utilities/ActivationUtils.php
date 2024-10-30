<?php
/**
 * Help to manage Activation of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\OptionsUtils;

class ActivationUtils {

    /**
	 * The unique instance of activation utils to be available for all classes.
	 */
    private static $instance;

    private $isActivated;
    private $licenseKey;

    private function __construct() {
        // We make sure that constructor is called only once.
		if(static::$instance != null) {
			return null;
        }

        $this->isActivated = true;
        $this->licenseKey = '';

        static::$instance = $this;
    }

    // STATIC METHODS

    /**
	 * We get the shared instance of ActivationUtils
	 */
	public static function getInstance() {
        if(!isset(static::$instance)) {
            static::$instance = new ActivationUtils();
        }
		return static::$instance;
    }
    

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        // We register the admin notices to check for activation warning
        $plugin->getLoader()->add_action('admin_notices', $this, "activationWarning" );

        // We register the activation check when user enter administration part
        $plugin->getLoader()->add_action('admin_init', $this, "checkActivation" );
    }

    public function getIsActivated() {
        return $this->isActivated;
    }

    public function getLicenseKey() {
        return $this->licenseKey;
    }

    /**
     * This method checks if option in regards to activation is set.
     */
    public function checkActivation() {
        $plugin = Plugin::getInstance();
        $optionName = $plugin->getOptions()->getPrefix() . "activated";
        $activated = get_option($optionName);
    
        if(!$activated) {
            self::checkLicenseActivation();
        }
    }

    /**
     * This method checks the license on server license to activate it
     */
    public function checkLicenseActivation() {
        $plugin = Plugin::getInstance();
        $optionName = $plugin->getOptions()->getPrefix() . "activated";

        // TODO - Add check on server for license activation (Mepr /license_keys/check);

        update_option($optionName, true);
    }


    /**
     * This method displays a warning in admin section if plugin is not activated
     */
    public function activationWarning() {
        if(!$this->isActivated) {
            // We include the panel
            ViewsUtils::renderView('admin/activation-warning');
        }
    }

}