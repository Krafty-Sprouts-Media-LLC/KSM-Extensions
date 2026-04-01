<?php
/**
 * Show Modified Date module functionality.
 *
 * @package KSM_Extensions
 * @subpackage Modules\ShowModifiedDate
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.2.0
 * @last_modified 25/11/2025
 * 
 * @description Ported and adapted from Apasionados.es "Show modified Date in admin lists" plugin.
 * Original plugin: https://wordpress.org/plugins/show-modified-date-in-admin-lists/
 * Original version: 1.4
 * 
 * CREDITS:
 * This code is based on the "Show modified Date in admin lists" plugin by Apasionados.es.
 * Original plugin author: Apasionados.es
 * Original plugin URI: https://wordpress.org/plugins/show-modified-date-in-admin-lists/
 */

namespace KSM_Extensions\Modules\ShowModifiedDate;

/**
 * Show Modified Date module class.
 *
 * @since 1.2.0
 */
class KSM_Extensions_ShowModifiedDate
{

	/**
	 * Initialize the module.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function init()
	{
		// Load text domain.
		add_action('admin_init', array($this, 'load_textdomain'));

		// Register modified date column for posts, pages, and media.
		add_filter('manage_posts_columns', array($this, 'register_column'));
		add_filter('manage_pages_columns', array($this, 'register_column'));
		add_filter('manage_media_columns', array($this, 'register_column'));

		// Display modified date column content.
		add_action('manage_posts_custom_column', array($this, 'display_column'), 10, 2);
		add_action('manage_pages_custom_column', array($this, 'display_column'), 10, 2);
		add_action('manage_media_custom_column', array($this, 'display_column'), 10, 2);

		// Make column sortable.
		add_filter('manage_edit-post_sortable_columns', array($this, 'register_sortable_column'));
		add_filter('manage_edit-page_sortable_columns', array($this, 'register_sortable_column'));
		add_filter('manage_upload_sortable_columns', array($this, 'register_sortable_column'));
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function load_textdomain()
	{
		// Text domain is loaded by main KSM Extensions plugin.
		// This method is kept for consistency but uses the main plugin's text domain.
		if (!is_textdomain_loaded('ksm-extensions')) {
			load_plugin_textdomain(
				'ksm-extensions',
				false,
				dirname(KSM_EXTENSIONS_PLUGIN_BASENAME) . '/languages/'
			);
		}
	}

	/**
	 * Register the Modified Date column.
	 *
	 * @since 1.2.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns array.
	 */
	public function register_column($columns)
	{
		$columns['Modified'] = __('Modified Date', 'ksm-extensions');
		return $columns;
	}

	/**
	 * Display the modified date column content.
	 *
	 * @since 1.2.0
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public function display_column($column_name, $post_id)
	{
		if ('Modified' !== $column_name) {
			return;
		}

		global $post;

		echo '<p class="mod-date">';
		echo '<em>' . esc_html(get_the_modified_date()) . ' ' . esc_html(get_the_modified_time()) . '</em><br />';

		$modified_author = get_the_modified_author();
		if (!empty($modified_author)) {
			echo '<small>' . esc_html__('by', 'ksm-extensions') . ' <strong>' . esc_html($modified_author) . '</strong></small>';
		} else {
			echo '<small>' . esc_html__('by', 'ksm-extensions') . ' <strong>' . esc_html__('UNKNOWN', 'ksm-extensions') . '</strong></small>';
		}

		echo '</p>';
	}

	/**
	 * Register the Modified Date column as sortable.
	 *
	 * @since 1.2.0
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns array.
	 */
	public function register_sortable_column($columns)
	{
		$columns['Modified'] = 'modified';
		return $columns;
	}
}

