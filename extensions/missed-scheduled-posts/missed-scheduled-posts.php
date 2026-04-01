<?php
/**
 * Module Name: Missed Scheduled Posts
 * Module URI: https://kraftysprouts.com
 * Description: Catches scheduled posts that have been missed and publishes them automatically. Based on WPBeginner's Missed Scheduled Posts Publisher plugin.
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * Requires KSM Extensions: 1.0.0
 * 
 * @package KSM_Extensions
 * @subpackage Modules
 * @module MissedScheduledPosts
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 * @last_modified 30/12/2025
 * 
 * @description Ported and adapted from WPBeginner's Missed Scheduled Posts Publisher plugin.
 * Original plugin: https://wordpress.org/plugins/missed-scheduled-posts-publisher/
 * Original version: 2.1.0
 * 
 * CREDITS:
 * This module is based on the "Missed Scheduled Posts Publisher" plugin by WPBeginner.
 * Original plugin contributors: WPbeginner, smub, jaredatch, peterwilsoncc, tommcfarlin
 * Original plugin URI: https://wordpress.org/plugins/missed-scheduled-posts-publisher/
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

require_once __DIR__ . '/includes/class-ksm-extensions-missed-scheduled-posts-core.php';
require_once __DIR__ . '/includes/class-ksm-extensions-missed-scheduled-posts-review.php';

// Import the classes from the namespace.
use KSM_Extensions\Modules\MissedScheduledPosts\KSM_Extensions_MissedScheduledPosts_Core;
use KSM_Extensions\Modules\MissedScheduledPosts\KSM_Extensions_MissedScheduledPosts_Review;

/**
 * Initialize the module.
 *
 * This function is called by the KSM Extensions module loader.
 *
 * @since 1.0.0
 * @return bool True on successful initialization.
 */
function ksm_extensions_module_missed_scheduled_posts_init()
{
	// Load review hooks.
	add_action('plugins_loaded', function () {
		(new KSM_Extensions_MissedScheduledPosts_Review())->load_hooks();
	});

	// Bootstrap the module.
	KSM_Extensions_MissedScheduledPosts_Core::bootstrap();

	// Return true to indicate successful initialization.
	return true;
}

