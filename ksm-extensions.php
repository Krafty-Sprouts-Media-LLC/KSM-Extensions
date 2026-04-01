<?php
/**
 * Plugin Name: KSM Extensions
 * Plugin URI: https://kraftysprouts.com
 * Description: A comprehensive WordPress extension framework by Krafty Sprouts Media, LLC that houses modular plugins and core extensions for enhanced WordPress functionality.
 * Version: 2.0.11
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ksm-extensions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package KSM_Extensions
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Current plugin version.
 *
 * @since 1.0.0
 */
define( 'KSM_EXTENSIONS_VERSION', '2.0.11' );

/**
 * Plugin directory path.
 *
 * @since 1.0.0
 */
define('KSM_EXTENSIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 *
 * @since 1.0.0
 */
define('KSM_EXTENSIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 *
 * @since 1.0.0
 */
define('KSM_EXTENSIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin file path.
 *
 * @since 1.0.0
 */
define('KSM_EXTENSIONS_PLUGIN_FILE', __FILE__);

/**
 * Optional: update source for plugin-update-checker (GitHub repo URL or JSON metadata URL).
 * Define in wp-config.php: define( 'KSM_EXTENSIONS_UPDATE_REPO', 'https://github.com/username/ksm-extensions' );
 *
 * @since 2.0.6
 */
if ( ! defined( 'KSM_EXTENSIONS_UPDATE_REPO' ) ) {
	define( 'KSM_EXTENSIONS_UPDATE_REPO', '' );
}

/**
 * Load plugin-update-checker from lib and bootstrap if an update repo is configured.
 *
 * @since 2.0.6
 */
if ( KSM_EXTENSIONS_UPDATE_REPO !== '' ) {
	$puc_path = KSM_EXTENSIONS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
	if ( file_exists( $puc_path ) ) {
		require $puc_path;
		$update_repo = apply_filters( 'ksm_extensions_update_repo', KSM_EXTENSIONS_UPDATE_REPO );
		if ( $update_repo !== '' && class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory', false ) ) {
			$my_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				$update_repo,
				__FILE__,
				'ksm-extensions'
			);
			$my_update_checker->setBranch( apply_filters( 'ksm_extensions_update_branch', 'master' ) );
		}
	}
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function activate_ksm_extensions()
{
    require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-activator.php';
    KSM_Extensions_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function deactivate_ksm_extensions()
{
    require_once KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-deactivator.php';
    KSM_Extensions_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_ksm_extensions');
register_deactivation_hook(__FILE__, 'deactivate_ksm_extensions');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since 1.0.0
 */
require KSM_EXTENSIONS_PLUGIN_DIR . 'includes/class-ksm-extensions-core.php';

/**
 * Begins execution of the plugin.
 *
 * Runs at init priority 1 so the text domain can be loaded at init priority 0,
 * avoiding "translation loaded too early" notices (WordPress 6.7+).
 *
 * @since 1.0.0
 */
function run_ksm_extensions()
{
    $plugin = KSM_Extensions_Core::get_instance();
    $plugin->run();
}
add_action( 'init', 'run_ksm_extensions', 1 );
