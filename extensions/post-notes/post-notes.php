<?php
/**
 * Module Name: Post Notes
 * Module URI: https://kraftysprouts.com
 * Description: Allows adding a single editable note per post/article. Notes are displayed in both the post edit screen (as a meta box) and the post list table (as a column with a visual badge indicator).
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module PostNotes
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 * @last_modified 30/12/2025
 * 
 * @description Allows users to add notes to posts/articles for quick reference and organization.
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

require_once __DIR__ . '/includes/class-ksm-extensions-post-notes.php';

// Import the class from the namespace.
use KSM_Extensions\Modules\PostNotes\KSM_Extensions_PostNotes;

/**
 * Initialize the module.
 *
 * This function is called by the KSM Extensions module loader.
 *
 * @since 1.0.0
 * @return bool True on successful initialization.
 */
function ksm_extensions_module_post_notes_init()
{
	// Initialize the module.
	$module = new KSM_Extensions_PostNotes();
	$module->init();

	// Return true to indicate successful initialization.
	return true;
}

