<?php
/**
 * Module Name: Show Modified Date
 * Module URI: https://kraftysprouts.com
 * Description: Shows a new, sortable, column with the modified date in the lists of pages and posts in the WordPress admin panel. It also shows the username that did the last update. Based on Apasionados.es plugin.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module ShowModifiedDate
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 * @last_modified 30/12/2025
 * 
 * @description Ported and adapted from Apasionados.es "Show modified Date in admin lists" plugin.
 * Original plugin: https://wordpress.org/plugins/show-modified-date-in-admin-lists/
 * Original version: 1.4
 * 
 * CREDITS:
 * This module is based on the "Show modified Date in admin lists" plugin by Apasionados.es.
 * Original plugin author: Apasionados.es
 * Original plugin URI: https://wordpress.org/plugins/show-modified-date-in-admin-lists/
 * 
 * This code has been adapted to work as a KSM Extensions module with KSM Extensions naming conventions
 * while maintaining all original functionality and giving proper credit to the original authors.
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

require_once __DIR__ . '/includes/class-ksm-extensions-show-modified-date.php';

// Import the class from the namespace.
use KSM_Extensions\Modules\ShowModifiedDate\KSM_Extensions_ShowModifiedDate;

/**
 * Initialize the module.
 *
 * This function is called by the KSM Extensions module loader.
 *
 * @since 1.0.0
 * @return bool True on successful initialization.
 */
function ksm_extensions_module_show_modified_date_init()
{
	// Initialize the module.
	$module = new KSM_Extensions_ShowModifiedDate();
	$module->init();

	// Return true to indicate successful initialization.
	return true;
}

