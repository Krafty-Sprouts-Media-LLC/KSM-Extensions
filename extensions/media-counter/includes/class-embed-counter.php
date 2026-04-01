<?php
/**
 * Filename: class-embed-counter.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 18/08/2025
 * Version: 2.0.3
 * Last Modified: 16/11/2025
 * Description: Advanced embed/oEmbed counting, caching, and bulk initialization.
 *
 * @package KSM_Extensions
 * @subpackage Modules\MediaCounter
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Embed/oEmbed counting functionality with bulk initialization and admin UI support.
 *
 * @since 1.1.0
 */
class KSM_Extensions_MediaCounter_EmbedCounter {

	/**
	 * Option key indicating initialization is required.
	 *
	 * @since 1.1.0
	 */
	const OPTION_NEEDS_INIT = 'ksm_extensions_media_counter_embed_needs_init';

	/**
	 * Option key indicating initialization is complete.
	 *
	 * @since 1.1.0
	 */
	const OPTION_INITIALIZED = 'ksm_extensions_media_counter_embed_initialized';

	/**
	 * Cron hook for bulk initialization batches.
	 *
	 * @since 1.1.0
	 */
	const CRON_HOOK = 'ksm_extensions_media_counter_embed_bulk_init';

	/**
	 * AJAX action used to dismiss initialization notice.
	 *
	 * @since 1.1.0
	 */
	const AJAX_ACTION_DISMISS_NOTICE = 'ksm_extensions_dismiss_embed_counter_notice';

	/**
	 * Cache group for embed counts.
	 *
	 * @since 1.1.0
	 */
	const CACHE_GROUP = 'ksm_extensions_media_counter_embed';

	/**
	 * Core instance.
	 *
	 * @var KSM_Extensions_MediaCounter_Core
	 * @since 1.1.0
	 */
	private $core;

	/**
	 * Meta key used to store embed counts.
	 *
	 * @var string
	 * @since 1.1.0
	 */
	private $meta_key;

	/**
	 * Constructor.
	 *
	 * @param KSM_Extensions_MediaCounter_Core $core Core instance.
	 * @since 1.1.0
	 */
	public function __construct( $core ) {
		$this->core     = $core;
		$this->meta_key = KSM_Extensions_MediaCounter_Core::META_KEYS['embed'];
		$this->register_hooks();
	}

	/**
	 * Handle plugin activation requirements.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function handle_activation() {
		if ( ! get_option( self::OPTION_INITIALIZED ) ) {
			update_option( self::OPTION_NEEDS_INIT, true );
		}

		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Handle plugin deactivation cleanup.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function handle_deactivation() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Register runtime hooks.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function register_hooks() {
		// Don't register hooks if the main counter module is disabled
		if ( ! $this->core->get_option( 'enabled', false ) ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION_DISMISS_NOTICE, array( $this, 'dismiss_notice' ) );
		add_action( self::CRON_HOOK, array( $this, 'bulk_initialize_embed_counts' ) );
		add_action( 'delete_post', array( $this, 'cleanup_embed_count_meta' ) );

		// Only register admin columns if embeds are enabled and admin columns are enabled
		if ( $this->core->get_option( 'count_embeds', true ) && $this->core->get_option( 'show_in_admin_columns', true ) ) {
			$post_types = $this->core->get_option( 'post_types', array( 'post', 'page' ) );

			foreach ( $post_types as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_embed_count_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'display_embed_count_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_embed_count_sortable' ) );
			}

			add_action( 'pre_get_posts', array( $this, 'handle_embed_count_sorting' ) );
		}
	}

	/**
	 * Initialize runtime requirements.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function init() {
		if ( ! $this->core->get_option( 'count_embeds', true ) ) {
			return;
		}

		if ( get_option( self::OPTION_NEEDS_INIT ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 30, self::CRON_HOOK );
		}
	}

	/**
	 * Enqueue inline admin script for notice dismissal.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		if ( ! current_user_can( 'manage_options' ) || ! get_option( self::OPTION_NEEDS_INIT ) ) {
			return;
		}

		wp_add_inline_script(
			'jquery',
			sprintf(
				'
				jQuery( document ).ready( function( $ ) {
					$( document ).on( "click", ".ksm-embed-counter-notice .notice-dismiss", function() {
						$.post( ajaxurl, {
							action: "%1$s",
							nonce: "%2$s"
						} );
					} );
				} );
				',
				self::AJAX_ACTION_DISMISS_NOTICE,
				wp_create_nonce( self::AJAX_ACTION_DISMISS_NOTICE )
			)
		);
	}

	/**
	 * Dismiss initialization notice via AJAX.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function dismiss_notice() {
		check_ajax_referer( self::AJAX_ACTION_DISMISS_NOTICE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'ksm-extensions' ), 403 );
		}

		delete_option( self::OPTION_NEEDS_INIT );

		wp_send_json_success();
	}

	/**
	 * Add embed count column to admin list tables.
	 *
	 * @param array $columns Existing columns.
	 * @since 1.1.0
	 * @return array
	 */
	public function add_embed_count_column( $columns ) {
		// Check if embeds are enabled and admin columns are enabled
		if ( ! $this->core->get_option( 'count_embeds', true ) || ! $this->core->get_option( 'show_in_admin_columns', true ) ) {
			return $columns;
		}
		
		$columns['ksm_extensions_media_counter_embed_count'] = __( 'Embeds', 'ksm-extensions' );
		return $columns;
	}

	/**
	 * Display embed count column value.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @since 1.1.0
	 * @return void
	 */
	public function display_embed_count_column( $column, $post_id ) {
		if ( 'ksm_extensions_media_counter_embed_count' !== $column ) {
			return;
		}

		// Check if embeds are enabled and admin columns are enabled
		if ( ! $this->core->get_option( 'count_embeds', true ) || ! $this->core->get_option( 'show_in_admin_columns', true ) ) {
			return;
		}

		$count = $this->get_embed_count( $post_id );

		if ( $count > 0 ) {
			printf( '<span class="ksm-embed-count">%s</span>', esc_html( number_format_i18n( $count ) ) );
		} else {
			echo '<em>—</em>';
		}
	}

	/**
	 * Make embed count column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @since 1.1.0
	 * @return array
	 */
	public function make_embed_count_sortable( $columns ) {
		$columns['ksm_extensions_media_counter_embed_count'] = 'ksm_extensions_media_counter_embed_count';
		return $columns;
	}

	/**
	 * Handle sorting requests for embed count column.
	 *
	 * @param WP_Query $query Query instance.
	 * @since 1.1.0
	 * @return void
	 */
	public function handle_embed_count_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'ksm_extensions_media_counter_embed_count' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', $this->meta_key );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Show admin notice while initialization is running.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) || ! get_option( self::OPTION_NEEDS_INIT ) ) {
			return;
		}

		echo '<div class="notice notice-info is-dismissible ksm-embed-counter-notice">';
		echo '<p>' . esc_html__( 'KSM Extensions is initializing existing embed counts. This will run in the background and may take a few moments.', 'ksm-extensions' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Retrieve cached embed count for a post.
	 *
	 * @param int $post_id Post ID.
	 * @since 1.1.0
	 * @return int
	 */
	public function get_embed_count( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return 0;
		}

		$cache_key = 'ksm_extensions_media_counter_embed_count_' . $post_id;
		$count     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $count ) {
			return (int) $count;
		}

		$count = get_post_meta( $post_id, $this->meta_key, true );

		if ( '' === $count ) {
			$count = $this->count_embeds_for_post( $post_id );
		}

		wp_cache_set( $cache_key, (int) $count, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return (int) $count;
	}

	/**
	 * Count embeds for a specific post and persist the result.
	 *
	 * @param int $post_id Post ID.
	 * @since 1.1.0
	 * @return int
	 */
	private function count_embeds_for_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || ! $this->core->is_post_type_enabled( $post->post_type ) ) {
			return 0;
		}

		$count = self::count_embeds_from_content( $post->post_content );
		update_post_meta( $post_id, $this->meta_key, $count );

		return $count;
	}

	/**
	 * Count embeds/oEmbeds in content using DOMDocument, regex, block, and shortcode detection.
	 * Now includes ALL videos and embeds together - videos are counted as embeds.
	 *
	 * @param string $content Content to analyze.
	 * @since 1.1.0
	 * @return int
	 */
	public static function count_embeds_from_content( $content ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return 0;
		}

		// Count all iframes and embeds (including videos)
		// DOM and regex already count video tags, so we only add video blocks and shortcodes separately
		$dom_count       = self::count_embeds_with_dom( $content );
		$regex_count     = self::count_embeds_with_regex( $content );
		$block_count     = self::count_embed_blocks( $content );
		$shortcode_count = self::count_embed_shortcodes( $content );
		
		// Video blocks and shortcodes need to be added separately (not counted in embed blocks/shortcodes)
		$video_block_count = self::count_video_blocks( $content );
		$video_shortcode_count = self::count_video_shortcodes( $content );

		return max( $dom_count, $regex_count, $block_count + $video_block_count ) + $shortcode_count + $video_shortcode_count;
	}

	/**
	 * Count embeds via DOMDocument for high accuracy.
	 * Now counts ALL iframes and video tags (including YouTube/Vimeo).
	 *
	 * @param string $content Content to analyze.
	 * @since 1.1.0
	 * @return int
	 */
	private static function count_embeds_with_dom( $content ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return 0;
		}

		$dom = new DOMDocument();

		libxml_use_internal_errors( true );

		$loaded = $dom->loadHTML(
			'<?xml encoding="UTF-8">' . wp_kses_post( $content ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();

		if ( ! $loaded ) {
			return 0;
		}

		// Count ALL iframes (including YouTube/Vimeo videos) and video tags
		$iframes = $dom->getElementsByTagName( 'iframe' );
		$videos = $dom->getElementsByTagName( 'video' );

		return $iframes->length + $videos->length;
	}

	/**
	 * Count embeds using regex fallback.
	 * Now counts ALL iframes and video tags (including YouTube/Vimeo).
	 *
	 * @param string $content Content to analyze.
	 * @since 1.1.0
	 * @return int
	 */
	private static function count_embeds_with_regex( $content ) {
		$content = wp_kses_post( $content );

		// Count all iframes
		$iframe_count = preg_match_all( '/<iframe[^>]*>.*?<\/iframe>/is', $content );
		$iframe_count = is_int( $iframe_count ) ? $iframe_count : 0;

		// Count all video tags
		$video_tag_count = preg_match_all( '/<video[^>]*>.*?<\/video>/is', $content );
		$video_tag_count = is_int( $video_tag_count ) ? $video_tag_count : 0;

		return $iframe_count + $video_tag_count;
	}

	/**
	 * Count Gutenberg embed blocks.
	 * Now counts ALL embed blocks (including YouTube/Vimeo videos).
	 *
	 * @param string $content Content to analyze.
	 * @since 1.1.0
	 * @return int
	 */
	private static function count_embed_blocks( $content ) {
		$embed_block_count = preg_match_all( '/<!-- wp:embed[^>]*-->.*?<!-- \\/wp:embed -->/s', $content );
		return is_int( $embed_block_count ) ? $embed_block_count : 0;
	}

	/**
	 * Count embed/oEmbed shortcodes.
	 * Now counts ALL embed shortcodes including YouTube/Vimeo videos.
	 *
	 * @param string $content Content to analyze.
	 * @since 1.1.0
	 * @return int
	 */
	private static function count_embed_shortcodes( $content ) {
		// Count all embed shortcodes including videos
		$embed_shortcodes = array( 'youtube', 'vimeo', 'twitter', 'instagram', 'facebook', 'soundcloud', 'spotify', 'embed' );
		$shortcode_count = 0;

		foreach ( $embed_shortcodes as $shortcode ) {
			$match_count = preg_match_all( '/\\[' . preg_quote( $shortcode, '/' ) . '[^\\]]*\\]/', $content );
			if ( is_int( $match_count ) ) {
				$shortcode_count += $match_count;
			}
		}

		return $shortcode_count;
	}

	/**
	 * Count video blocks.
	 *
	 * @param string $content Content to analyze.
	 * @since 2.0.2
	 * @return int
	 */
	private static function count_video_blocks( $content ) {
		$match_count = preg_match_all( '/<!-- wp:video[^>]*-->.*?<!-- \\/wp:video -->/s', $content );
		return is_int( $match_count ) ? $match_count : 0;
	}

	/**
	 * Count video shortcodes.
	 *
	 * @param string $content Content to analyze.
	 * @since 2.0.2
	 * @return int
	 */
	private static function count_video_shortcodes( $content ) {
		$match_count = preg_match_all( '/\\[video[^\\]]*\\]/', $content );
		return is_int( $match_count ) ? $match_count : 0;
	}


	/**
	 * Initialize embed counts for posts missing metadata.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function bulk_initialize_embed_counts() {
		$post_types = $this->core->get_option( 'post_types', array( 'post', 'page' ) );

		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => $this->meta_key,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$posts = get_posts( $query_args );

		foreach ( $posts as $post_id ) {
			$this->count_embeds_for_post( $post_id );
		}

		if ( count( $posts ) === $query_args['posts_per_page'] ) {
			wp_schedule_single_event( time() + 10, self::CRON_HOOK );
		} else {
			update_option( self::OPTION_INITIALIZED, true );
			delete_option( self::OPTION_NEEDS_INIT );
		}
	}

	/**
	 * Clean up embed count metadata and caches when a post is deleted.
	 *
	 * @param int $post_id Post ID.
	 * @since 1.1.0
	 * @return void
	 */
	public function cleanup_embed_count_meta( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return;
		}

		wp_cache_delete( 'ksm_extensions_media_counter_embed_count_' . $post_id, self::CACHE_GROUP );
		delete_post_meta( $post_id, $this->meta_key );
	}

	/**
	 * Count embeds in content (legacy method for compatibility).
	 *
	 * @param string $content Content to analyze.
	 * @return int Embed count
	 * @since 1.0.0
	 */
	public function count( $content ) {
		return self::count_embeds_from_content( $content );
	}
}
