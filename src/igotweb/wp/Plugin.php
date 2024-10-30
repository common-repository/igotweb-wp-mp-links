<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.igot-web.com
 * @since      1.0.0
 *
 * @package    igotweb\wp
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    igotweb\wp
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp;

use igotweb_wp_mp_links\igotweb\wp\plugin\Options;
use igotweb_wp_mp_links\igotweb\wp\plugin\Admin;
use igotweb_wp_mp_links\igotweb\wp\plugin\Web;
use igotweb_wp_mp_links\igotweb\wp\plugin\Activator;
use igotweb_wp_mp_links\igotweb\wp\plugin\Deactivator;
use igotweb_wp_mp_links\igotweb\wp\utilities\Loader;
use igotweb_wp_mp_links\igotweb\wp\utilities\Internationalization;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\OptionsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\RewriteUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\ActivationUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\UpdateUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\ConnectorsUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;
use igotweb_wp_mp_links\igotweb\wp\utilities\StaticsUtils;

class Plugin {

	/**
	 * The instance of plugin to be available for all classes.
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The admin that's responsible for all admin pages.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\plugin\Admin    $admin    responsible of admin pages for the plugin.
	 */
	protected $admin;

	/**
	 * The web that's responsible for all front end pages.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\plugin\Web    $web    responsible of front end pages for the plugin.
	 */
	protected $web;

	/**
	 * The views utils that is used to generate views in the output page
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\ViewsUtils    $viewsUtils    Generate views used by the plugin.
	 */
	protected $viewsUtils;

	/**
	 * The options utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\OptionsUtils    $optionsUtils    Utils for options.
	 */
	protected $optionsUtils;

	/**
	 * The activation utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\ActivationUtils    $activationUtils    Utils for plugin activation.
	 */
	protected $activationUtils;

	/**
	 * The update utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\UpdateUtils    $updateUtils    Utils for update.
	 */
	protected $updateUtils;

	/**
	 * The connectors utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\ConnectorsUtils    $connectorsUtils    Utils for connectors.
	 */
	protected $connectorsUtils;

	/**
	 * The rewrite URL utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\RewriteUtils    $rewriteUtils    Utils for rewrite URL.
	 */
	protected $rewriteUtils;

	/**
	 * The page utils
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      igotweb\wp\utilities\PageUtils    $pageUtils    Utils for Pages.
	 */
	protected $pageUtils;
	

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $shortName    The string used to uniquely identify this plugin.
	 */
	protected $shortName;

	/**
	 * The localized name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $name    The localized name for this plugin.
	 */
	protected $name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The version of this plugin.
	 */
	protected $version;

	/**
	 * The slug of the plugin (directory / php entry point).
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $slug    The string used to uniquely identify this plugin.
	 */
	protected $slug;

	/**
	 * The uri of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $uri    The string used to store the URI of the plugin.
	 */
	protected $uri;

	/**
	 * The author of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $author    The string used to store the author of the plugin.
	 */
	protected $author;

	/**
	 * The description of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $description    The string used to store the description of the plugin.
	 */
	protected $description;

	/**
	 * The root path of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $rootPath    The root path of the plugin.
	 */
	protected $rootPath;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct($info) {
		// We make sure that constructor is called only once.
		if(isset(static::$instance)) {
			return null;
		}

		$this->slug = $info['slug'];
		$this->shortName = $info['shortName'];
		$this->name = $info['name'];
		$this->version = $info['version'];
		$this->uri = $info['uri'];
		$this->author = $info['author'];
		$this->description = $info['description'];
		$this->rootPath = $info['rootPath'];
		$this->APIServer = 'https://node-api.igot-web.com';
		if(isset($info['APIServer'])) {
			$this->APIServer = $info['APIServer'];
		}

		// We register activation and deactivation hooks
		register_activation_hook( $info['slug'], array($this, 'activatePlugin'));
  		register_deactivation_hook( $info['slug'], array($this, 'deactivatePlugin'));

		// We set the options
		$this->options = new Options();

		$this->loader = new Loader();
		$this->viewsUtils = new ViewsUtils();
		$this->optionsUtils = OptionsUtils::getInstance();
		$this->rewriteUtils = RewriteUtils::getInstance();
		$this->activationUtils = ActivationUtils::getInstance();
		$this->updateUtils = UpdateUtils::getInstance();
		$this->connectorsUtils = ConnectorsUtils::getInstance();
		$this->pageUtils = PageUtils::getInstance();

		// We store the instance of this plugin
		static::$instance = $this;

		// We set the locale
		$this->setLocale($info['textdomain']);
		
		// We set the Admin and Web parts
		$this->setAdmin(new Admin());
  		$this->setWeb(new Web());

		// We register all actions
		$this->registerActions();
	}


	/**
	 * The code that runs during plugin activation.
	 * This action is documented in Activator class
	 */
	public function activatePlugin() {
		Activator::activate($this->version);
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-igotweb-cloud-deactivator.php
	 */
	public function deactivatePlugin() {
		Deactivator::deactivate();
	}

	/**
	 * registerActions
	 * This method registers actions for all utilities.
	 */
	public function registerActions() {
		// We register options action
		$this->options->registerActions($this);
		$this->optionsUtils->registerActions($this);
		// We register utils actions
		$this->viewsUtils->registerActions($this);
		$this->rewriteUtils->registerActions($this);
		$this->activationUtils->registerActions($this);
		$this->updateUtils->registerActions($this);
		$this->pageUtils->registerActions($this);
	}

	/**
	 * We get the instance of current plugin.
	 */
	public static function getInstance() {
		if(isset(static::$instance)) {
			return static::$instance;
		}
		return null;
	}

	public static function errorLog($log) {
		error_log(static::$instance->shortName." - ".$log, 0);
	}

	public function getAPIServer() {
		return $this->APIServer;
	}

	public function getRootPath() {
		return $this->rootPath;
	}

	public function getVersion() {
		return $this->version;
	}

	/**
     * getSiteDomain
     * This methods returnds the current site domain (used to track activations per domain)
     */
    public static function getSiteDomain() {
        return preg_replace('#^https?://(www\.)?([^\?\/]*)#', '$2', home_url());
    }

	/**
	 * We get the path to views of the plugin
	 */
	public function getViewsPath() {
		return $this->rootPath . "views" . DIRECTORY_SEPARATOR;
	}

	public function getAssetsPath() {
		return $this->rootPath . "assets" . DIRECTORY_SEPARATOR;
	}

	public function getRootURL() {
		return plugin_dir_url($this->rootPath . $this->shortName . ".php");
	}

	public function getViewsURL() {
		return plugin_dir_url($this->rootPath . $this->shortName . ".php") . "views/";
	}

	public function getAssetsURL() {
		return plugin_dir_url($this->rootPath . $this->shortName . ".php") . "assets/";
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Igotweb_Cloud_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function setLocale($textdomain) {
		$plugin_i18n = new Internationalization($textdomain);
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function setAdmin($admin) {
		// We store the admin instance.
		$this->admin = $admin;
		// We define hooks
		$admin->defineHooks();
		// We load common assets
		$this->getLoader()->add_action( 'admin_enqueue_scripts', $this, 'adminEnqueueScripts');
	}

	public function adminEnqueueScripts() {
		if(PageUtils::isPluginAdminPage()) {			
			// We load admin specific assets for the plugin
			$staticsUtils = StaticsUtils::getInstance();
			$staticsUtils->includeStyle("admin", "asset");
		}
	}

	public function getAdmin() {
		return $this->admin;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function setWeb($web) {
		// We store the admin instance.
		$this->web = $web;
		// We define hooks
		$web->defineHooks();
		// We define specific end points
		$web->defineEndPoints();	
	}

	public function getWeb() {
		return $this->web;
	}


	public function getOptions() {
		return $this->options;
	}

	

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The short name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The short name of the plugin.
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * The localized name of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * The slug of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The slug of the plugin.
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * The URI of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The URI of the plugin.
	 */
	public function getURI() {
		return $this->uri;
	}

	/**
	 * The Author of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The Author of the plugin.
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * The descriptioon of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The description of the plugin.
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function getLoader() {
		return $this->loader;
	}

	/**
	 * The instance of OptionsUtils.
	 *
	 * @since     1.0.0
	 * @return    OptionsUtils    The OptionsUtils.
	 */
	public function getOptionsUtils() {
		return $this->optionsUtils;
	}

	/**
	 * The instance of RewriteUtils.
	 *
	 * @since     1.0.0
	 * @return    RewriteUtils    The RewriteUtils.
	 */
	public function getRewriteUtils() {
		return $this->rewriteUtils;
	}

	/**
	 * The instance of UpdateUtils.
	 *
	 * @since     1.0.0
	 * @return    UpdateUtils    The UpdateUtils.
	 */
	public function getUpdateUtils() {
		return $this->updateUtils;
	}

	/**
	 * The instance of ActivationUtils.
	 *
	 * @since     1.0.0
	 * @return    ActivationUtils    The ActivationUtils.
	 */
	public function getActivationUtils() {
		return $this->activationUtils;
	}

	/**
	 * The instance of PageUtils.
	 *
	 * @since     1.0.0
	 * @return    PageUtils    The PageUtils.
	 */
	public function getPageUtils() {
		return $this->pageUtils;
	}

}
