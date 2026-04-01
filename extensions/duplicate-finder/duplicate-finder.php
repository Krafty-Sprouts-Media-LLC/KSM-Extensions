<?php
/**
 * Module Name: Duplicate Finder
 * Module URI: https://kraftysprouts.com
 * Description: Media Library UI for locating and removing duplicate images. Helps clean up your media library by identifying duplicate files.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module DuplicateFinder
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

// Require MediaCounter Core class (dependency for duplicate finder)
require_once KSM_EXTENSIONS_PLUGIN_DIR . 'extensions/media-counter/includes/class-core.php';

require_once __DIR__ . '/includes/class-duplicate-finder.php';

function ksm_extensions_module_duplicate_finder_init()
{
    $core = new KSM_Extensions_MediaCounter_Core();
    $module = new KSM_Extensions_DuplicateFinder($core);
    return true;
}
