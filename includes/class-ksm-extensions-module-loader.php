<?php
/**
 * Register all actions and filters for the module loader.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-module-loader.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.3
 * Last Modified: 30/12/2025
 * Description: Handles scanning, validating, and loading module plugins.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The module loader class.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Module_Loader
{

    /**
     * Array of registered modules.
     *
     * @since 1.0.0
     * @var array
     */
    private $modules = array();

    /**
     * Array of loaded module instances.
     *
     * @since 1.0.0
     * @var array
     */
    private $module_instances = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->modules = array();
    }

    /**
     * Load all modules from the modules directory.
     *
     * @since 1.0.0
     */
    public function load_modules()
    {
        $modules_dir = KSM_EXTENSIONS_PLUGIN_DIR . 'extensions';

        // Check if extensions directory exists
        if (!is_dir($modules_dir)) {
            wp_mkdir_p($modules_dir);
            return;
        }

        // Scan for module directories
        $module_dirs = array_filter(glob($modules_dir . '/*'), 'is_dir');

        foreach ($module_dirs as $module_dir) {
            $module_name = basename($module_dir);
            $module_file = $module_dir . '/' . $module_name . '.php';

            // Check if module main file exists
            if (file_exists($module_file)) {
                $this->load_module($module_file, $module_name);
            }
        }

        // Allow modules to be registered via filter
        do_action('ksm_extensions_register_modules', $this);
    }

    /**
     * Load a single module.
     *
     * @since 1.0.0
     * @param string $module_file Path to module main file.
     * @param string $module_name Module name/slug.
     */
    private function load_module($module_file, $module_name)
    {
        // Get module header data
        $module_data = $this->get_module_data($module_file);

        if (!$module_data) {
            return;
        }

        // Validate module
        if (!$this->validate_module($module_data, $module_name)) {
            return;
        }

        // Check if module is activated
        if (!$this->is_module_activated($module_name)) {
            // Register module but don't load it
            $this->register_module($module_name, $module_data, false);
            return;
        }

        // Include module file
        require_once $module_file;

        // Register module
        $this->register_module($module_name, $module_data, true);
    }

    /**
     * Get module header data from PHP file.
     *
     * @since 1.0.0
     * @param string $module_file Path to module file.
     * @return array|false Module data or false on failure.
     */
    private function get_module_data($module_file)
    {
        $default_headers = array(
            'Module Name' => 'Module Name',
            'Module URI' => 'Module URI',
            'Description' => 'Description',
            'Version' => 'Version',
            'Author' => 'Author',
            'Author URI' => 'Author URI',
            'Requires KSM Extensions' => 'Requires KSM Extensions',
        );

        return get_file_data($module_file, $default_headers, 'module');
    }

    /**
     * Validate module structure and requirements.
     *
     * @since 1.0.0
     * @param array  $module_data Module header data.
     * @param string $module_name Module name.
     * @return bool True if valid, false otherwise.
     */
    private function validate_module($module_data, $module_name)
    {
        // Check required fields
        if (empty($module_data['Module Name'])) {
            return false;
        }

        // Check KSM Extensions version requirement
        if (!empty($module_data['Requires KSM Extensions'])) {
            $required_version = $module_data['Requires KSM Extensions'];
            if (version_compare(KSM_EXTENSIONS_VERSION, $required_version, '<')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register a module.
     *
     * @since 1.0.0
     * @param string $module_name Module name/slug.
     * @param array  $module_data Module data.
     * @param bool   $active Whether the module is active (loaded).
     */
    public function register_module($module_name, $module_data, $active = true)
    {
        $this->modules[$module_name] = array(
            'name' => $module_data['Module Name'],
            'uri' => $module_data['Module URI'],
            'description' => $module_data['Description'],
            'version' => $module_data['Version'],
            'author' => $module_data['Author'],
            'author_uri' => $module_data['Author URI'],
            'active' => $active,
        );

        // Initialize module if it has an init function and is active
        if ($active) {
            $init_function = 'ksm_extensions_module_' . str_replace('-', '_', $module_name) . '_init';
            if (function_exists($init_function)) {
                $this->module_instances[$module_name] = call_user_func($init_function);
            }
        }
    }

    /**
     * Get all registered modules.
     *
     * @since 1.0.0
     * @return array Array of registered modules.
     */
    public function get_modules()
    {
        return $this->modules;
    }

    /**
     * Get a specific module instance.
     *
     * @since 1.0.0
     * @param string $module_name Module name.
     * @return mixed Module instance or null.
     */
    public function get_module_instance($module_name)
    {
        return isset($this->module_instances[$module_name]) ? $this->module_instances[$module_name] : null;
    }

    /**
     * Check if a module is registered.
     *
     * @since 1.0.0
     * @param string $module_name Module name.
     * @return bool True if registered, false otherwise.
     */
    public function is_module_registered($module_name)
    {
        return isset($this->modules[$module_name]);
    }

    /**
     * Check if a module is activated.
     *
     * @since 1.0.1
     * @param string $module_name Module name.
     * @return bool True if activated, false otherwise.
     */
    public function is_module_activated($module_name)
    {
        $modules = get_option('ksm_extensions_modules', array());
        return isset($modules[$module_name]) && $modules[$module_name] === true;
    }

    /**
     * Activate a module.
     *
     * @since 1.0.1
     * @param string $module_name Module name.
     * @return bool True on success, false on failure.
     */
    public function activate_module($module_name)
    {
        $modules = get_option('ksm_extensions_modules', array());
        $modules[$module_name] = true;
        return update_option('ksm_extensions_modules', $modules);
    }

    /**
     * Deactivate a module.
     *
     * @since 1.0.1
     * @param string $module_name Module name.
     * @return bool True on success, false on failure.
     */
    public function deactivate_module($module_name)
    {
        $modules = get_option('ksm_extensions_modules', array());
        $modules[$module_name] = false;
        return update_option('ksm_extensions_modules', $modules);
    }

    /**
     * Get all module activation states.
     *
     * @since 1.0.1
     * @return array Array of module activation states.
     */
    public function get_module_states()
    {
        return get_option('ksm_extensions_modules', array());
    }
}
