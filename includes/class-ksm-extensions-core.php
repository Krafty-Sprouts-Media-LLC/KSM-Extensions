<?php
/**
 * The core plugin class.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-core.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.0
 * Last Modified: 30/12/2025
 * Description: Main framework class that orchestrates plugin functionality.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The core plugin class.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Core
{

    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Core
     */
    protected static $instance = null;

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Module_Loader
     */
    protected $module_loader;

    /**
     * The extensions manager for core extensions.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Manager
     */
    protected $extensions_manager;

    /**
     * The admin-specific functionality.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Admin
     */
    protected $admin;

    /**
     * The asset management functionality.
     *
     * @since 1.0.0
     * @var KSM_Extensions_Assets
     */
    protected $assets;

    /**
     * Main KSM_Extensions_Core Instance.
     *
     * Ensures only one instance of KSM_Extensions_Core is loaded or can be loaded.
     *
     * @since 1.0.0
     * @return KSM_Extensions_Core Main instance.
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define the core functionality of the plugin.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->set_locale();
        $this->load_extensions();
        $this->load_modules();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since 1.0.0
     */
    private function load_dependencies()
    {
        require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-module-loader.php';
        require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-manager.php';
        require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-admin.php';
        require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-assets.php';

        $this->module_loader = new KSM_Extensions_Module_Loader();
        $this->extensions_manager = new KSM_Extensions_Manager();
        $this->admin = new KSM_Extensions_Admin();
        $this->assets = new KSM_Extensions_Assets();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Load at init priority 0 so translations are available before any plugin
     * code that runs at init or later (WordPress 6.7+ requirement).
     *
     * @since 1.0.0
     */
    private function set_locale()
    {
        add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'ksm-extensions',
            false,
            dirname(KSM_EXTENSIONS_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since 1.0.0
     */
    private function define_admin_hooks()
    {
        $this->admin->set_module_loader($this->module_loader);
        $this->admin->set_extensions_manager($this->extensions_manager);
        $this->admin->set_assets($this->assets);
        $this->assets->init();
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since 1.0.0
     */
    private function define_public_hooks()
    {
        // Public hooks can be added here in the future
    }

    /**
     * Load all core extensions.
     *
     * @since 1.0.0
     */
    private function load_extensions()
    {
        $this->extensions_manager->load_extensions();
    }

    /**
     * Load all registered modules.
     *
     * @since 1.0.0
     */
    private function load_modules()
    {
        $this->module_loader->load_modules();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        // The plugin is initialized through the constructor
    }

    /**
     * Get the module loader.
     *
     * @since 1.0.0
     * @return KSM_Extensions_Module_Loader Module loader instance.
     */
    public function get_module_loader()
    {
        return $this->module_loader;
    }

    /**
     * Get the extensions manager.
     *
     * @since 1.0.0
     * @return KSM_Extensions_Manager Extensions manager instance.
     */
    public function get_extensions_manager()
    {
        return $this->extensions_manager;
    }

    /**
     * Get the admin instance.
     *
     * @since 1.0.0
     * @return KSM_Extensions_Admin Admin instance.
     */
    public function get_admin()
    {
        return $this->admin;
    }

    /**
     * Get the assets instance.
     *
     * @since 1.0.0
     * @return KSM_Extensions_Assets Assets instance.
     */
    public function get_assets()
    {
        return $this->assets;
    }
}
