<?php
/**
 * Module Name: Media File Size
 * Module URI: https://kraftysprouts.com
 * Description: Displays sortable file sizes in Media Library, totals the entire library, and surfaces variant previews with indexing helpers. Based on Media Library File Size by SS88 LLC.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module MediaFileSize
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

require_once __DIR__ . '/includes/class-media-file-size.php';

function ksm_extensions_module_media_file_size_init()
{
    $module = new KSM_Extensions_MediaFileSize();
    return true;
}
