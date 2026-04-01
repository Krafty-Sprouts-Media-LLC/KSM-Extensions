<?php
/**
 * Core Extensions Manager.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-manager.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.0
 * Last Modified: 30/12/2025
 * Description: Manages core extensions (global snippets/hooks) that don't fit into modules.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The extensions manager class.
 *
 * Handles loading and managing core extensions (global snippets).
 *
 * @since 1.0.0
 */
class KSM_Extensions_Manager
{

    /**
     * Array of registered extensions.
     *
     * @since 1.0.0
     * @var array
     */
    private $extensions = array();

    /**
     * Plugin options.
     *
     * @since 1.0.0
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->load_options();
        $this->extensions = array();
    }

    /**
     * Load plugin options.
     *
     * @since 1.0.0
     */
    private function load_options()
    {
        $default_options = $this->get_default_options();
        $this->options = get_option('ksm_extensions_settings', $default_options);
        $this->options = wp_parse_args($this->options, $default_options);
    }

    /**
     * Get default options.
     *
     * @since 1.0.0
     * @return array Default options.
     */
    public function get_default_options()
    {
        return array(
            'disable_image_scaling' => false,
            'force_center_alignment' => false,
        );
    }

    /**
     * Get option value.
     *
     * @since 1.0.0
     * @param string $key Option key.
     * @param mixed  $default Default value.
     * @return mixed Option value.
     */
    public function get_option($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Update options.
     *
     * @since 1.0.0
     * @param array $options New options.
     * @return bool Success status.
     */
    public function update_options($options)
    {
        $this->options = array_merge($this->options, $options);
        $result = update_option('ksm_extensions_settings', $this->options);

        if ($result) {
            $this->load_options();
        }

        return $result;
    }

    /**
     * Load all core extensions from the extensions directory.
     *
     * @since 1.0.0
     */
    public function load_extensions()
    {
        $extensions_dir = KSM_EXTENSIONS_PLUGIN_DIR . 'extensions';

        // Check if extensions directory exists
        if (!is_dir($extensions_dir)) {
            wp_mkdir_p($extensions_dir);
            return;
        }

        // Register core extensions
        $this->register_core_extensions();
    }

    /**
     * Register core extensions (global snippets).
     *
     * @since 1.0.0
     */
    private function register_core_extensions()
    {
        // Extension 1: Disable Image Scaling
        if ($this->get_option('disable_image_scaling', false)) {
            add_filter('big_image_size_threshold', '__return_false');
            $this->extensions['disable_image_scaling'] = array(
                'name' => __('Disable Image Scaling', 'ksm-extensions'),
                'description' => __('Prevents WordPress from automatically creating scaled versions of large images (useful for infographics and high-resolution images).', 'ksm-extensions'),
                'enabled' => true,
            );
        }

        // Extension 2: Force Center Alignment
        if ($this->get_option('force_center_alignment', false)) {
            add_filter('image_default_align', array($this, 'set_default_image_align'));
            add_filter('the_content', array($this, 'auto_center_images'));
            $this->extensions['force_center_alignment'] = array(
                'name' => __('Force Center Alignment', 'ksm-extensions'),
                'description' => __('Automatically center-aligns all images in post content by setting default alignment and applying the aligncenter class.', 'ksm-extensions'),
                'enabled' => true,
            );
        }
    }

    /**
     * Set default image alignment to center.
     *
     * @since 1.0.0
     * @return string Alignment value.
     */
    public function set_default_image_align()
    {
        return 'center';
    }

    /**
     * Force center alignment for all images in post content.
     *
     * @since 1.0.0
     * @param string $content Post content.
     * @return string Modified content.
     */
    public function auto_center_images($content)
    {
        // Add aligncenter class to images that already have a class attribute
        $content = preg_replace('/<img(.*?)class="([^"]*)"(.*?)>/i', '<img$1class="$2 aligncenter"$3>', $content);

        // Add aligncenter class to images without a class attribute
        $content = preg_replace('/<img(?![^>]*class)([^>]*)>/i', '<img class="aligncenter"$1>', $content);

        return $content;
    }

    /**
     * Get all registered extensions.
     *
     * @since 1.0.0
     * @return array Array of registered extensions.
     */
    public function get_extensions()
    {
        return $this->extensions;
    }

    /**
     * Check if an extension is enabled.
     *
     * @since 1.0.0
     * @param string $extension_name Extension name.
     * @return bool True if enabled, false otherwise.
     */
    public function is_extension_enabled($extension_name)
    {
        return isset($this->extensions[$extension_name]) && $this->extensions[$extension_name]['enabled'];
    }
}
