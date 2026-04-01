<?php
/**
 * Fired during plugin activation.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-activator.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.3
 * Last Modified: 30/12/2025
 * Description: Plugin activation handler.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Activator
{

    /**
     * Activate the plugin.
     *
     * @since 1.0.0
     */
    public static function activate()
    {
        // Set default options
        $default_options = array(
            'disable_image_scaling' => false,
            'force_center_alignment' => false,
        );

        if (!get_option('ksm_extensions_settings')) {
            add_option('ksm_extensions_settings', $default_options);
        }

        // Set default module activation states (all disabled by default)
        $default_modules = array(
            'media-counter' => false,
            'duplicate-finder' => false,
            'featured-image-manager' => false,
            'auto-upload-images' => false,
            'image-title-alt' => false,
            'media-file-size' => false,
            'post-notes' => false,
            'missed-scheduled-posts' => false,
            'show-modified-date' => false,
        );

        if (!get_option('ksm_extensions_modules')) {
            add_option('ksm_extensions_modules', $default_modules);
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
