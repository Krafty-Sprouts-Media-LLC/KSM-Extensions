<?php
/**
 * Module Name: Auto Upload Images
 * Module URI: https://kraftysprouts.com
 * Description: Automatically imports external images referenced in post content, attaches them to Media Library, and rewrites src/srcset/alt attributes. Based on Auto Upload Images by Ali Irani.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module AutoUploadImages
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

require_once __DIR__ . '/includes/class-auto-upload-image-handler.php';
require_once __DIR__ . '/includes/class-auto-upload-images.php';

function ksm_extensions_module_auto_upload_images_init()
{
    $module = new KSM_Extensions_AutoUploadImages();
    return true;
}
