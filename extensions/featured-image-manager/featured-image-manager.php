<?php
/**
 * Module Name: Featured Image Manager
 * Module URI: https://kraftysprouts.com
 * Description: Comprehensive featured image management including fallbacks, RSS injection, admin thumbnails, and bulk initialization.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module FeaturedImageManager
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 * @last_modified 30/12/2025
 */

// Note: No namespace - module loader expects global init functions

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('KSM_EXTENSIONS_VERSION')) {
    return;
}

// Require MediaCounter Core class (dependency for featured image manager)
require_once KSM_EXTENSIONS_PLUGIN_DIR . 'extensions/media-counter/includes/class-core.php';

require_once __DIR__ . '/includes/class-featured-image-manager.php';

function ksm_extensions_module_featured_image_manager_init()
{
    $core = new KSM_Extensions_MediaCounter_Core();
    $module = new KSM_Extensions_FeaturedImageManager($core);
    return true;
}
