<?php
/**
 * Help to manage Updates of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

use igotweb_wp_mp_links\igotweb\wp\Plugin;
use igotweb_wp_mp_links\igotweb\wp\utilities\ConnectorsUtils;

class UpdateUtils {

    /**
	 * The unique instance of update utils to be available for all classes.
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
            static::$instance = new UpdateUtils();
        }
		return static::$instance;
    }

    // INSTANCE METHODS

    public function registerActions(Plugin $plugin) {
        // We register the check transient action when application check for updated plugins
        $plugin->getLoader()->add_filter( "pre_set_site_transient_update_plugins", $this, "checkTransient");
        // We register the update of plugin information to be displayed in view details modal
        $plugin->getLoader()->add_filter( "plugins_api", $this, "setPluginInfo" , 10, 3 );
        //$plugin->getLoader()->add_filter( "upgrader_post_install", $this, "postInstall" , 10, 3 );
    }

    /**
     * getTransientLifeTime
     * This method gets the lifetime used for transient.
     * It is set to 1 second in case of debug.
     */
    private function getTransientLifeTime() {
        if( is_super_admin() && WP_DEBUG ) {
            return 1;
        } else {
            return DAY_IN_SECONDS;
        }
    }

    /**
     * manuallyCheckTransient
     * This method check the update plugins transient manually outside of any hook (pre_set_site_transient_update_plugins)
     */
    public function manuallyCheckTransient() {
        $plugin = Plugin::getInstance();
        $transient = get_site_transient('update_plugins');
        // We delete the specific update info to force the check.
        delete_site_transient($plugin->getOptions()->getPrefix() . 'update_info');
        // Calling the set_site_transient will trigger pre_set_site_transient_update_plugins
        set_site_transient('update_plugins', $transient);
    }

    /**
     * getUpdateInfo
     * This method get the information of latest version available and store is as plugin transient in update_info
     * It returns array with these keys:
     *  - curr_version - the latest version available
     *  - download_url - the URL to download package if available.
     *  - last_updated - The date of updated version.
     */
    public function getUpdateInfo($force = false) {
        $plugin = Plugin::getInstance();
        $activationUtils = $plugin->getActivationUtils();

        $updateInfoTransientName = $plugin->getOptions()->getPrefix() . 'update_info';
        $updateInfo = get_site_transient($updateInfoTransientName);

        if($force || (false === $updateInfo)) {
            // We will review update info from server.
            $updateInfo = array();

            if(!$activationUtils->getIsActivated()) {
                // In case there is no license number
                // We get the last version but do not provide download URL
                $result = $this->populateUpdateInfoFromPluginLatestVersion($updateInfo);
                if($result === false) {
                    return null;
                }
            }
            else {
                // In case there is license number, we get the last version available and provide download URL
                $result = $this->populateUpdateInfoFromPluginLicenseInfo($updateInfo);
                if($result === false) {
                    return null;
                }
            }

            // We update the update information
            set_site_transient(
                $updateInfoTransientName,
                $updateInfo,
                $this->getTransientLifeTime()
            );

        }
        return $updateInfo;
    }

    /**
     * checkTransient
     * This method checks if we have any updated version available and add it to the transient object used by Wordpress to highlight updates.
     */
    public function checkTransient( $transient , $force=false) {
        $plugin = Plugin::getInstance();
        $activationUtils = $plugin->getActivationUtils();

        // We get the update infor
        $updateInfo = $this->getUpdateInfo($force);

        // If nothing is return we remove plugin update information
        if($updateInfo == null) {
            if(isset($transient->response[$plugin->getSlug()])) {
                // We remove any information in regards to updates for the plugin
                unset($transient->response[$plugin->getSlug()]);
            }
            // We trigger an activation check if plugin is considered as activated
            if($activationUtils->getIsActivated()) {
                $activationUtils->checkLicenseActivation();
            }

            return $transient;
        }

        // If we have information, we check that the version is a new one compared to current one.
        if(isset($updateInfo['curr_version']) && version_compare($updateInfo['curr_version'], $plugin->getVersion(), '>')) {
            // We add the update information
            $transient->response[$plugin->getSlug()] = (object)array(
                'slug'        => $plugin->getSlug(),
                'new_version' => $updateInfo['curr_version'],
                'url'         => $plugin->getURI(),
                'package'     => $updateInfo['download_url']
            );
        }
        else {
            // There is no update to be raised
            unset( $transient->response[$plugin->getSlug()] );
        }

        // We trigger an activation check
        $activationUtils->checkLicenseActivation();
        return $transient;
    }

    /**
     * getPluginLatestVersion
     * This method check on server the latest version available for the plugin.
     * It returns an array with these keys:
     *  - currentVersion : the latest version available for the plugin
     *  - versionLastUpdated : the version updated date
     */
    private function getPluginLatestVersion() {
        $plugin = Plugin::getInstance();
        $versionInfo = null;
        try {
            // In case there is an issue to get license value
            // We get the last version but do not provide download URL
            $args = array(
                'pluginName' => $plugin->getShortName()
            );
            $versionInfo = ConnectorsUtils::getInstance()->sendRequest("/versions/latest", $args, 'post');

        }
        catch(\Exception $e) {
            $versionInfo = null;
        }
        return $versionInfo;
    }

    /**
     * populateUpdateInfoFromPluginLatestVersion
     * This method populate the updateInfo object from the plugin latest version output.
     */
    private function populateUpdateInfoFromPluginLatestVersion(&$updateInfo) {
        $versionInfo = $this->getPluginLatestVersion();
        if($versionInfo == null) {
            return false;
        }
        $updateInfo['curr_version'] = $versionInfo['currentVersion'];
        $updateInfo['last_updated'] = $versionInfo['versionLastUpdated'];
        $updateInfo['download_url'] = '';

        return true;
    }

    /**
     * getPluginLicenseInfo
     * This method check on server the license information for plugin, license key, domain.
     * It returns an array with these keys:
     *  - currentVersion : the version available for the plugin for license.
     *  - versionLastUpdated : the version updated date.
     *  - downloadUrl : the download URL for the plugin version.
     */
    private function getPluginLicenseInfo() {
        $plugin = Plugin::getInstance();
        $activationUtils = $plugin->getActivationUtils();
        $licenseInfo = null;
        try {
            $domain = Plugin::getSiteDomain();
            $args = compact('domain');
            $args['pluginName'] = $plugin->getShortName();
            $args['licenseKey'] = $activationUtils->getLicenseKey();

            $licenseInfo = ConnectorsUtils::getInstance()->sendRequest("/license/info", $args, 'post');
        }
        catch(\Exception $e) {
            $licenseInfo = null;
        }
        return $licenseInfo;
    }

    /**
     * populateUpdateInfoFromPluginLicenseInfo
     * This method populate the updateInfo object from the plugin license info output.
     */
    private function populateUpdateInfoFromPluginLicenseInfo(&$updateInfo) {
        $plugin = Plugin::getInstance();

        // In case there is license number, we get the last version available and provide download URL
        $licenseInfo = $this->getPluginLicenseInfo();
        if($licenseInfo == null) {
            // In case there is no license number
            // We get the last version but do not provide download URL
            $result = $this->populateUpdateInfoFromPluginLatestVersion($updateInfo);
            if($result === false) {
                return false;
            }
        }
        else {
            // We populate updateInfo with licenseInfo
            $updateInfo['curr_version'] = $licenseInfo['currentVersion'];
            $updateInfo['last_updated'] = $licenseInfo['versionLastUpdated'];
            $updateInfo['download_url'] = $licenseInfo['downloadUrl'];

            // We save the license information
            set_site_transient(
                $plugin->getOptions()->getPrefix() . 'license_info',
                $licenseInfo,
                $this->getTransientLifeTime()
            );
        }

        return true;
    }

    // Push in plugin version information to display in the details lightbox
    public function setPluginInfo( $false, $action, $response ) {
        $plugin = Plugin::getInstance();
        
        // We check that we request plugin information for this plugin
        if ( empty( $response->slug ) || $response->slug != $plugin->getSlug() ) {
            return false;
        }

        // We check the locale for plugin information
        $locale = $response->locale;

        print_r("TATA");

        $updateInfo = $this->getUpdateInfo();
        if($updateInfo == null) {
            print_r("TOTO");
            return $response;
        }

        // Add our plugin information
        $response->last_updated = $updateInfo['last_updated'];
        $response->slug = $plugin->getSlug();
        $response->plugin_name  = $plugin->getShortName();
        $response->version = $updateInfo['curr_version'];
        $response->author = $plugin->getAuthor();
        $response->homepage = $plugin->getURI();
        $response->download_link = $updateInfo['download_url'];

        // Create tabs in the lightbox for plugin
        $response->sections = array(
            'description' => $plugin->getDescription(),
            'changelog' => 'This is the version change log'
        );

        // Gets the required version of WP if available
        // TODO - Add the WP version in the requires of updateInfo
        if(isset($updateInfo['requires'])) {
            $response->requires = $updateInfo['requires'];
        }

        // Gets the tested version of WP if available
        // TODO - Add the WP version in the requires of updateInfo
        if(isset($updateInfo['tested'])) {
            $response->tested = $updateInfo['tested'];
        }

        return $response;
    }

    // Perform additional actions to successfully install our plugin
    public function postInstall( $true, $hook_extra, $result ) {
        // Get plugin information
        $this->initPluginData();

        // Remember if our plugin was previously activated
        $wasActivated = is_plugin_active( $this->slug );

        // Since we are hosted in GitHub, our plugin folder would have a dirname of
        // reponame-tagname change it to our original one:
        global $wp_filesystem;
        $pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
        $wp_filesystem->move( $result['destination'], $pluginFolder );
        $result['destination'] = $pluginFolder;

        // Re-activate plugin if needed
        if ( $wasActivated ) {
            $activate = activate_plugin( $this->slug );
        }

        return $result;
    }
}