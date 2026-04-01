<?php
/**
 * Asset management functionality.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-assets.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.3
 * Last Modified: 30/12/2025
 * Description: Handles asset registration and enqueuing.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The assets class.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Assets
{

    /**
     * Initialize assets.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Load on KSM Extensions pages and dashboard
        $ksm_pages = array(
            'toplevel_page_ksm-extensions',
            'ksm-extensions_page_ksm-extensions-settings',
            'ksm-extensions_page_ksm-extensions-core',
        );

        if (strpos($hook, 'ksm-extensions') === false && $hook !== 'index.php') {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'ksm-extensions-admin',
            KSM_EXTENSIONS_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            KSM_EXTENSIONS_VERSION
        );

        // Enqueue settings page design on KSM Extensions Settings and extension settings pages
        $is_settings_page = (strpos($hook, 'settings_page_') === 0 && strpos($hook, 'ksm-extensions') !== false)
            || $hook === 'ksm-extensions_page_ksm-extensions-settings';
        if ($is_settings_page) {
            wp_enqueue_style(
                'ksm-extensions-settings',
                KSM_EXTENSIONS_PLUGIN_URL . 'admin/assets/css/settings.css',
                array('ksm-extensions-admin'),
                KSM_EXTENSIONS_VERSION
            );
        }

        // Enqueue admin scripts
        wp_enqueue_script(
            'ksm-extensions-admin',
            KSM_EXTENSIONS_PLUGIN_URL . 'admin/assets/js/admin.js',
            array('jquery'),
            KSM_EXTENSIONS_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            'ksm-extensions-admin',
            'ksmExtensionsAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ksm_extensions_admin'),
            )
        );
    }
}
