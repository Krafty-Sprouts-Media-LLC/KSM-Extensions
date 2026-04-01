<?php
/**
 * Module Name: Image Title & Alt Optimizer
 * Module URI: https://kraftysprouts.com
 * Description: Cleans filenames to create SEO-friendly titles, captions, descriptions, and alt text with configurable capitalization and filename renaming. Based on Auto Image Title & Alt by Diego de Guindos.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module ImageTitleAlt
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

require_once __DIR__ . '/includes/class-image-title-alt.php';

function ksm_extensions_module_image_title_alt_init()
{
    $module = new KSM_Extensions_ImageTitleAlt();
    return true;
}
