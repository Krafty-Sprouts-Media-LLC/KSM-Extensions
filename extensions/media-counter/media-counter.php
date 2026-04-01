<?php
/**
 * Module Name: Media Counter
 * Module URI: https://kraftysprouts.com
 * Description: Comprehensive media counting for WordPress posts. Counts images, videos, and embeds with advanced detection methods, caching, and sortable admin columns.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module MediaCounter
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 * @last_modified 30/12/2025
 * 
 * @description Counts all media types (images, videos, embeds) in post content using multiple detection methods.
 */

// Note: No namespace - module loader expects global init functions

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Prevent loading if KSM Extensions is not active.
if (!defined('KSM_EXTENSIONS_VERSION')) {
    return;
}

require_once __DIR__ . '/includes/class-core.php';
require_once __DIR__ . '/includes/class-image-counter.php';
require_once __DIR__ . '/includes/class-embed-counter.php';

/**
 * Initialize the module.
 *
 * This function is called by the KSM Extensions module loader.
 *
 * @since 1.0.0
 * @return bool True on successful initialization.
 */
function ksm_extensions_module_media_counter_init()
{
    // Initialize the core (handles settings and auto-counting)
    $core = new KSM_Extensions_MediaCounter_Core();

    // Initialize image counter
    $image_counter = new KSM_Extensions_MediaCounter_ImageCounter($core);

    // Initialize embed counter
    $embed_counter = new KSM_Extensions_MediaCounter_EmbedCounter($core);

    // Return true to indicate successful initialization
    return true;
}
