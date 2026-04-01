<?php
/**
 * Fired during plugin deactivation.
 *
 * @package KSM_Extensions
 * @subpackage Includes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Filename: class-ksm-extensions-deactivator.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 30/12/2025
 * Version: 1.0.0
 * Last Modified: 30/12/2025
 * Description: Plugin deactivation handler.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin deactivation.
 *
 * @since 1.0.0
 */
class KSM_Extensions_Deactivator
{

    /**
     * Deactivate the plugin.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
