<?php
/**
 * Core functionality for Missed Scheduled Posts module.
 *
 * @package KSM_Extensions
 * @subpackage Modules\MissedScheduledPosts
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.1.0
 * @last_modified 25/11/2025
 * 
 * @description Ported and adapted from WPBeginner's Missed Scheduled Posts Publisher plugin.
 * Original plugin: https://wordpress.org/plugins/missed-scheduled-posts-publisher/
 * Original version: 2.1.0
 * 
 * CREDITS:
 * This code is based on the "Missed Scheduled Posts Publisher" plugin by WPBeginner.
 * Original plugin contributors: WPbeginner, smub, jaredatch, peterwilsoncc, tommcfarlin
 */

namespace KSM_Extensions\Modules\MissedScheduledPosts;

/**
 * Core class for Missed Scheduled Posts module.
 *
 * @since 1.1.0
 */
class KSM_Extensions_MissedScheduledPosts_Core
{

	/**
	 * Action name for AJAX requests.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const ACTION = 'ksm_extensions_missed_scheduled_posts';

	/**
	 * Batch limit for processing posts.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const BATCH_LIMIT = 20;

	/**
	 * Fallback multiplier for timing calculations.
	 *
	 * @since 1.1.0
	 * @var float
	 */
	const FALLBACK_MULTIPLIER = 1.1;

	/**
	 * Option name for storing last run time.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const OPTION_NAME = 'ksm_extensions_missed_scheduled_posts_last_run';

	/**
	 * Bootstrap the module functionality.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function bootstrap()
	{
		add_action('send_headers', array(__CLASS__, 'send_headers'));
		add_action('shutdown', array(__CLASS__, 'loopback'));
		add_action('wp_ajax_nopriv_' . self::ACTION, array(__CLASS__, 'admin_ajax'));
		add_action('wp_ajax_' . self::ACTION, array(__CLASS__, 'admin_ajax'));
	}

	/**
	 * Get the run frequency in seconds.
	 *
	 * Filters the frequency to allow programmaticaly control.
	 *
	 * @since 1.1.0
	 * @return int Frequency in seconds.
	 */
	public static function get_run_frequency()
	{
		$frequency = 900; // 15 minutes default.
		return (int) apply_filters('ksm_extensions_missed_scheduled_posts_frequency', $frequency);
	}

	/**
	 * Generate a nonce without the UID and session components.
	 *
	 * As this is a loopback request, the user will not be registered as logged in
	 * so the generic WP Nonce function will not work.
	 *
	 * @since 1.1.0
	 * @return string Nonce based on action name and tick.
	 */
	public static function get_no_priv_nonce()
	{
		$uid = 'n/a';
		$token = 'n/a';
		$i = wp_nonce_tick();

		return substr(wp_hash($i . '|' . self::ACTION . '|' . $uid . '|' . $token, 'nonce'), -12, 10);
	}

	/**
	 * Verify a nonce without the UID and session components.
	 *
	 * As this comes from a loopback request, the user will not be registered as
	 * logged in so the generic WP Nonce function will not work.
	 *
	 * The goal here is to mainly to protect against database reads in the event
	 * of both full page caching and falling back to the ajax request in place of
	 * a successful loopback request.
	 *
	 * @since 1.1.0
	 * @param string $nonce Nonce based on action name and tick.
	 * @return false|int False if nonce invalid. Integer containing tick if valid.
	 */
	public static function verify_no_priv_nonce($nonce)
	{
		$nonce = (string) $nonce;

		if (empty($nonce)) {
			return false;
		}

		$uid = 'n/a';
		$token = 'n/a';
		$i = wp_nonce_tick();

		// Nonce generated 0-12 hours ago.
		$expected = substr(wp_hash($i . '|' . self::ACTION . '|' . $uid . '|' . $token, 'nonce'), -12, 10);
		if (hash_equals($expected, $nonce)) {
			return 1;
		}

		// Nonce generated 12-24 hours ago.
		$expected = substr(wp_hash(($i - 1) . '|' . self::ACTION . '|' . $uid . '|' . $token, 'nonce'), -12, 10);
		if (hash_equals($expected, $nonce)) {
			return 2;
		}

		return false;
	}

	/**
	 * Prevent caching of requests including the AJAX script.
	 *
	 * Includes the no-caching headers if the response will include the
	 * AJAX fallback script. This is to prevent excess calls to the
	 * admin-ajax.php action.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function send_headers()
	{
		$last_run = (int) get_option(self::OPTION_NAME, 0);
		if ($last_run >= (time() - (self::FALLBACK_MULTIPLIER * self::get_run_frequency()))) {
			return;
		}

		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
		nocache_headers();
	}

	/**
	 * Enqueue inline AJAX request to allow for failing loopback requests.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function enqueue_scripts()
	{
		$last_run = (int) get_option(self::OPTION_NAME, 0);
		if ($last_run >= (time() - (self::FALLBACK_MULTIPLIER * self::get_run_frequency()))) {
			return;
		}

		// Shutdown loopback request is not needed.
		remove_action('shutdown', array(__CLASS__, 'loopback'));

		// Null script for inline script to come afterward.
		wp_register_script(
			self::ACTION,
			null,
			array(),
			null,
			true
		);

		$request = array(
			'url' => add_query_arg('action', self::ACTION, admin_url('admin-ajax.php')),
			'args' => array(
				'method' => 'POST',
				'body' => self::ACTION . '_nonce=' . self::get_no_priv_nonce(),
			),
		);

		$script = '
		(function( request ){
			if ( ! window.fetch ) {
				return;
			}
			request.args.body = new URLSearchParams( request.args.body );
			fetch( request.url, request.args );
		}( ' . wp_json_encode($request) . ' ));
		';

		wp_add_inline_script(
			self::ACTION,
			$script
		);

		wp_enqueue_script(self::ACTION);
	}

	/**
	 * Make a loopback request to publish posts with a missed schedule.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function loopback()
	{
		$last_run = (int) get_option(self::OPTION_NAME, 0);
		if ($last_run >= (time() - self::get_run_frequency())) {
			return;
		}

		// Do loopback request.
		$request = array(
			'url' => add_query_arg('action', self::ACTION, admin_url('admin-ajax.php')),
			'args' => array(
				'timeout' => 0.01,
				'blocking' => false,
				/** This filter is documented in wp-includes/class-wp-http-streams.php */
				'sslverify' => apply_filters('https_local_ssl_verify', false),
				'body' => array(
					self::ACTION . '_nonce' => self::get_no_priv_nonce(),
				),
			),
		);

		wp_remote_post($request['url'], $request['args']);
	}

	/**
	 * Handle HTTP request for publishing posts with a missed schedule.
	 *
	 * Always response with a success result to allow for full page caching
	 * retaining the inline script. The visitor does not need to see error
	 * messages in their browser.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function admin_ajax()
	{
		if (!isset($_POST[self::ACTION . '_nonce']) || !self::verify_no_priv_nonce($_POST[self::ACTION . '_nonce'])) {
			wp_send_json_success();
		}

		$last_run = (int) get_option(self::OPTION_NAME, 0);
		if ($last_run >= (time() - self::get_run_frequency())) {
			wp_send_json_success();
		}

		self::publish_missed_posts();
		wp_send_json_success();
	}

	/**
	 * Publish posts with a missed schedule.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function publish_missed_posts()
	{
		global $wpdb;

		update_option(self::OPTION_NAME, time());

		$scheduled_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_date <= %s AND post_status = 'future' LIMIT %d",
				current_time('mysql', 0),
				self::BATCH_LIMIT
			)
		);
		if (!count($scheduled_ids)) {
			return;
		}
		if (count($scheduled_ids) === self::BATCH_LIMIT) {
			// There's a bit to do.
			update_option(self::OPTION_NAME, 0);
		}

		array_map('wp_publish_post', $scheduled_ids);
	}
}

