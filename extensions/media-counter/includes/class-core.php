<?php
/**
 * Filename: class-core.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 18/08/2025
 * Version: 2.1.3
 * Last Modified: 18/11/2025
 * Description: Core utilities for KSM Extensions media analytics.
 *
 * @package KSM_Extensions
 * @subpackage Modules\MediaCounter
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core functionality for Media Counter module
 *
 * @since 1.0.0
 */
class KSM_Extensions_MediaCounter_Core {

	/**
	 * Plugin options
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $options;

	/**
	 * Meta keys for different media types
	 *
	 * @var array
	 * @since 1.0.0
	 */
	const META_KEYS = array(
		'image' => 'ksm_extensions_media_counter_image_count',
		'video' => 'ksm_extensions_media_counter_video_count',
		'embed' => 'ksm_extensions_media_counter_embed_count',
		'total' => 'ksm_extensions_media_counter_total_media_count',
	);

	/**
	 * Option key for settings.
	 *
	 * @since 1.0.0
	 */
	const OPTION_KEY = 'ksm_extensions_media_counter_settings';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_options();
		$this->init_hooks();
		add_action( 'ksm_extensions_register_settings_page', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Load plugin options
	 *
	 * @since 1.0.0
	 */
	private function load_options() {
		$default_options = $this->get_default_options();
		$this->options = get_option( self::OPTION_KEY, $default_options );
		$this->options = wp_parse_args( $this->options, $default_options );
		// Module is only loaded when activated on KSM Extensions → Modules; no separate "enable" in settings.
		$this->options['enabled'] = true;
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Auto count on save if enabled
		if ( $this->get_option( 'enabled', false ) ) {
			add_action( 'save_post', array( $this, 'auto_count_media' ), 10, 2 );
		}

		// Disable image scaling and Force center alignment are set globally on KSM Extensions → Settings (Core Extensions), not here.
		// Admin columns are now handled by individual counter classes (Image Counter, Embed Counter)
	}

	/**
	 * Get default options
	 *
	 * @return array Default options
	 * @since 1.0.0
	 */
	public function get_default_options() {
		return array(
			'enabled' => false,
			'post_types' => array( 'post', 'page' ),
			'count_images' => true,
			'count_videos' => true,
			'count_embeds' => true,
			'show_in_admin_columns' => true,
			'dashboard_widget' => true,
			'historical_tracking' => true,
			'cache_duration' => 12 * HOUR_IN_SECONDS,
			'debug_mode' => false,
		);
	}

	/**
	 * Check if plugin is enabled
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_enabled() {
		return (bool) $this->get_option( 'enabled', false );
	}

	/**
	 * Get plugin option
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed Option value
	 * @since 1.0.0
	 */
	public function get_option( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * Get all options
	 *
	 * @return array All options
	 * @since 1.0.0
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Update options
	 *
	 * @param array $options New options.
	 * @return bool Success status
	 * @since 1.0.0
	 */
	public function update_options( $options ) {
		// Merge with existing options to preserve settings not in the update
		// Use array_merge to ensure all values (including false) are preserved
		$this->options = array_merge( $this->options, $options );
		$result = update_option( self::OPTION_KEY, $this->options );
		
		// Reload options from database to ensure cache consistency
		if ( $result ) {
			$this->load_options();
		}
		
		return $result;
	}

	/**
	 * Reload options from database.
	 *
	 * @since 2.1.3
	 * @return void
	 */
	public function reload_options() {
		$this->load_options();
	}

	/**
	 * Check if post type is enabled
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_post_type_enabled( $post_type ) {
		$enabled_post_types = $this->get_option( 'post_types', array( 'post', 'page' ) );
		return in_array( $post_type, $enabled_post_types, true );
	}

	/**
	 * Get available post types
	 *
	 * @return array Post types
	 * @since 1.0.0
	 */
	public function get_available_post_types() {
		return get_post_types( array( 'public' => true ), 'objects' );
	}

	/**
	 * Auto count media on post save
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @since 1.0.0
	 */
	public function auto_count_media( $post_id, $post ) {
		// Skip if not enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Skip if post type not enabled
		if ( ! $this->is_post_type_enabled( $post->post_type ) ) {
			return;
		}

		// Skip revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Count different media types
		$counts = $this->count_all_media( $post->post_content );

		// Update post meta (used for display and admin columns)
		update_post_meta( $post_id, self::META_KEYS['image'], $counts['images'] );
		update_post_meta( $post_id, self::META_KEYS['video'], $counts['videos'] );
		update_post_meta( $post_id, self::META_KEYS['embed'], $counts['embeds'] );
		update_post_meta( $post_id, self::META_KEYS['total'], $counts['total'] );

		// Note: Database tables (KSM_Extensions_media_counts, KSM_Extensions_media_history) are created
		// but not currently used. Data is stored in post meta for better performance.
		// Tables are kept for potential future analytics/reporting features.

		$this->debug_log( "Media counts updated for post {$post_id}: {$counts['images']} images, {$counts['videos']} videos, {$counts['embeds']} embeds" );
	}

	/**
	 * Count all media types in content
	 *
	 * @param string $content Content to analyze.
	 * @return array Media counts
	 * @since 1.0.0
	 */
	public function count_all_media( $content ) {
		$counts = array(
			'images' => 0,
			'videos' => 0,
			'embeds' => 0,
			'total' => 0,
		);

		if ( empty( $content ) ) {
			return $counts;
		}

		// Count images
		if ( $this->get_option( 'count_images', true ) ) {
			$counts['images'] = $this->count_images( $content );
		}

		// Count videos and embeds together - they're treated as the same (videos are counted as embeds)
		// count_embeds() already includes videos, so we just use that count
		if ( $this->get_option( 'count_embeds', true ) || $this->get_option( 'count_videos', true ) ) {
			$counts['embeds'] = $this->count_embeds( $content );
		} else {
			$counts['embeds'] = 0;
		}

		// Videos are now counted as embeds, so set to 0
		$counts['videos'] = 0;

		// Calculate total
		$counts['total'] = $counts['images'] + $counts['embeds'];

		return $counts;
	}

	/**
	 * Count images in content
	 *
	 * @param string $content Content to analyze.
	 * @return int Image count
	 * @since 1.0.0
	 */
	public function count_images( $content ) {
		if ( class_exists( 'KSM_Extensions_MediaCounter_ImageCounter' ) && method_exists( 'KSM_Extensions_MediaCounter_ImageCounter', 'count_images_from_content' ) ) {
			return KSM_Extensions_MediaCounter_ImageCounter::count_images_from_content( $content );
		}

		// Fallback logic retained for safety if the image counter class is unavailable.
		preg_match_all( '/<img[^>]+>/i', $content, $matches );
		$img_count = count( $matches[0] );

		preg_match_all( '/<!-- wp:image[^>]*-->.*?<!-- \/wp:image -->/s', $content, $block_matches );
		$block_count = count( $block_matches[0] );

		preg_match_all( '/\[gallery[^\]]*\]/', $content, $gallery_matches );
		$gallery_count = 0;
		foreach ( $gallery_matches[0] as $gallery ) {
			if ( preg_match( '/ids=["\']([^"\']*)["\']/i', $gallery, $id_matches ) ) {
				$ids = explode( ',', $id_matches[1] );
				$gallery_count += count( array_filter( $ids ) );
			}
		}

		return max( $img_count, $block_count ) + $gallery_count;
	}

	/**
	 * Count videos in content
	 * Note: Videos are now counted as embeds. This method returns 0 as videos are merged into embeds.
	 *
	 * @param string $content Content to analyze.
	 * @return int Always returns 0 (videos are counted as embeds)
	 * @since 1.0.0
	 */
	public function count_videos( $content ) {
		// Videos are now counted as embeds, so this always returns 0
		// The actual video counting is handled by count_embeds()
		return 0;
	}

	/**
	 * Count embeds in content
	 * Now includes all videos and embeds together - videos are counted as embeds.
	 *
	 * @param string $content Content to analyze.
	 * @return int Embed count (includes videos)
	 * @since 1.0.0
	 */
	public function count_embeds( $content ) {
		// Count all videos and embeds together - videos are counted as embeds
		// This includes self-hosted videos, YouTube, Vimeo, and social media embeds
		// Use embed counter which now counts everything (videos included)
		if ( class_exists( 'KSM_Extensions_MediaCounter_EmbedCounter' ) && method_exists( 'KSM_Extensions_MediaCounter_EmbedCounter', 'count_embeds_from_content' ) ) {
			// Embed counter now counts ALL embeds including videos
			return KSM_Extensions_MediaCounter_EmbedCounter::count_embeds_from_content( $content );
		}
		
		// Fallback: count all iframes and video tags
		$embed_count = 0;
		preg_match_all( '/<iframe[^>]*>.*?<\/iframe>/is', $content, $iframe_matches );
		$embed_count += count( $iframe_matches[0] );

		preg_match_all( '/<video[^>]*>.*?<\/video>/is', $content, $video_matches );
		$embed_count += count( $video_matches[0] );

		preg_match_all( '/<!-- wp:embed[^>]*-->.*?<!-- \/wp:embed -->/s', $content, $embed_matches );
		$embed_count += count( $embed_matches[0] );

		preg_match_all( '/<!-- wp:video[^>]*-->.*?<!-- \/wp:video -->/s', $content, $video_block_matches );
		$embed_count += count( $video_block_matches[0] );

		// Count all video and embed shortcodes
		$embed_shortcodes = array( 'youtube', 'vimeo', 'twitter', 'instagram', 'facebook', 'soundcloud', 'spotify', 'video', 'embed' );
		foreach ( $embed_shortcodes as $shortcode ) {
			preg_match_all( '/\[' . $shortcode . '[^\]]*\]/', $content, $matches );
			$embed_count += count( $matches[0] );
		}

		return $embed_count;
	}

	/**
	 * Get media counts for a post
	 *
	 * @param int $post_id Post ID.
	 * @return array Media counts
	 * @since 1.0.0
	 */
	public function get_media_counts( $post_id ) {
		return array(
			'images' => (int) get_post_meta( $post_id, self::META_KEYS['image'], true ),
			'videos' => (int) get_post_meta( $post_id, self::META_KEYS['video'], true ),
			'embeds' => (int) get_post_meta( $post_id, self::META_KEYS['embed'], true ),
			'total' => (int) get_post_meta( $post_id, self::META_KEYS['total'], true ),
		);
	}

	/**
	 * Store media counts in database
	 *
	 * @deprecated 2.0.11 Not currently used - data is stored in post meta instead for better performance.
	 *                   Kept for potential future analytics/reporting features.
	 * @param int   $post_id Post ID.
	 * @param array $counts Media counts.
	 * @since 1.0.0
	 */
	private function store_media_counts( $post_id, $counts ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'KSM_Extensions_media_counts';
		$current_date = current_time( 'mysql' );

		$wpdb->replace(
			$table_name,
			array(
				'post_id' => $post_id,
				'image_count' => $counts['images'],
				'video_count' => $counts['videos'],
				'embed_count' => $counts['embeds'],
				'total_media_count' => $counts['total'],
				'last_updated' => $current_date,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%s' )
		);
	}

	/**
	 * Store historical data
	 *
	 * @deprecated 2.0.11 Not currently used - data is stored in post meta instead for better performance.
	 *                   Kept for potential future analytics/reporting features.
	 * @param int   $post_id Post ID.
	 * @param array $counts Media counts.
	 * @since 1.0.0
	 */
	private function store_historical_data( $post_id, $counts ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ksm_extensions_media_counter_history';
		$current_date = current_time( 'mysql' );
		$date_parts = explode( '-', $current_date );

		$wpdb->replace(
			$table_name,
			array(
				'post_id' => $post_id,
				'image_count' => $counts['images'],
				'video_count' => $counts['videos'],
				'embed_count' => $counts['embeds'],
				'total_media_count' => $counts['total'],
				'report_year' => (int) $date_parts[0],
				'report_month' => (int) $date_parts[1],
				'report_day' => (int) substr( $date_parts[2], 0, 2 ),
				'created_at' => $current_date,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
		);
	}


	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data
	 * @since 1.0.0
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Boolean options
		$boolean_options = array(
			'count_images',
			'count_videos',
			'count_embeds',
			'show_in_admin_columns',
			'dashboard_widget',
			'historical_tracking',
			'debug_mode',
		);

		foreach ( $boolean_options as $option ) {
			$sanitized[ $option ] = isset( $input[ $option ] ) ? (bool) $input[ $option ] : false;
		}

		// Module is only activated via KSM Extensions → Modules; no separate "enable" in settings.
		$sanitized['enabled'] = true;

		// Array options: when none are checked, form sends no post_types key — save empty array, not default
		$sanitized['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_text_field', $input['post_types'] ) : array();

		// Numeric options
		$sanitized['cache_duration'] = isset( $input['cache_duration'] ) ? absint( $input['cache_duration'] ) : 12 * HOUR_IN_SECONDS;

		return $sanitized;
	}

	/**
	 * Reset settings to defaults
	 *
	 * @return bool Success status
	 * @since 1.0.0
	 */
	public function reset_settings() {
		$default_options = $this->get_default_options();
		$this->options = $default_options;
		return update_option( self::OPTION_KEY, $default_options );
	}

	/**
	 * Register the module settings page with KSM Extensions admin.
	 *
	 * @since 1.0.0
	 * @param KSM_Extensions_Admin $admin Admin instance.
	 */
	public function register_settings_page( $admin ) {
		if ( ! method_exists( $admin, 'register_module_settings_page' ) ) {
			return;
		}
		$admin->register_module_settings_page(
			__( 'Media Counter', 'ksm-extensions' ),
			__( 'Media Counter', 'ksm-extensions' ),
			'ksm-extensions-media-counter',
			array( $this, 'render_settings_page' ),
			'media-counter'
		);
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Render the Media Counter settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options    = $this->get_options();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="wrap ksm-wrap ksm-settings-page">
			<div class="ksm-admin ksm-admin-layout">
				<main class="ksm-main-content">
					<header class="ksm-admin__hero">
						<div>
							<h1><?php esc_html_e( 'Media Counter', 'ksm-extensions' ); ?></h1>
							<p class="ksm-admin__subhead">
								<?php esc_html_e( 'Count images, videos, and embeds in post content. Show sortable columns and control what gets tracked.', 'ksm-extensions' ); ?>
							</p>
						</div>
					</header>

					<form method="post" action="options.php" class="ksm-card">
						<?php settings_fields( self::OPTION_KEY ); ?>

						<div class="ksm-card__body">
							<section class="ksm-section">
								<h3 class="ksm-section-title"><?php esc_html_e( 'Post types', 'ksm-extensions' ); ?></h3>
								<p class="ksm-field__description"><?php esc_html_e( 'Count media only for these post types.', 'ksm-extensions' ); ?></p>
								<div class="ksm-pill-list">
									<?php foreach ( $post_types as $post_type ) : ?>
										<label class="ksm-checkbox">
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, (array) $options['post_types'], true ) ); ?> />
											<span class="ksm-checkbox__checkmark"></span>
											<span><?php echo esc_html( $post_type->labels->name ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</section>

							<section class="ksm-section">
								<h3 class="ksm-section-title"><?php esc_html_e( 'What to count', 'ksm-extensions' ); ?></h3>
								<p class="ksm-field__description"><?php esc_html_e( 'Include these in the total count and in admin columns.', 'ksm-extensions' ); ?></p>
								<label class="ksm-checkbox-row" for="ksm-count-images">
									<input type="checkbox" id="ksm-count-images" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[count_images]" value="1" <?php checked( ! empty( $options['count_images'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Count images', 'ksm-extensions' ); ?></span>
								</label>
								<label class="ksm-checkbox-row" for="ksm-count-videos">
									<input type="checkbox" id="ksm-count-videos" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[count_videos]" value="1" <?php checked( ! empty( $options['count_videos'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Count videos', 'ksm-extensions' ); ?></span>
								</label>
								<label class="ksm-checkbox-row" for="ksm-count-embeds">
									<input type="checkbox" id="ksm-count-embeds" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[count_embeds]" value="1" <?php checked( ! empty( $options['count_embeds'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Count embeds', 'ksm-extensions' ); ?></span>
								</label>
							</section>

							<section class="ksm-section">
								<h3 class="ksm-section-title"><?php esc_html_e( 'Display', 'ksm-extensions' ); ?></h3>
								<label class="ksm-checkbox-row" for="ksm-show-admin-columns">
									<input type="checkbox" id="ksm-show-admin-columns" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_admin_columns]" value="1" <?php checked( ! empty( $options['show_in_admin_columns'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Show Images/Embeds columns in list tables', 'ksm-extensions' ); ?></span>
								</label>
								<label class="ksm-checkbox-row" for="ksm-dashboard-widget">
									<input type="checkbox" id="ksm-dashboard-widget" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[dashboard_widget]" value="1" <?php checked( ! empty( $options['dashboard_widget'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Show dashboard widget', 'ksm-extensions' ); ?></span>
								</label>
							</section>

							<section class="ksm-section">
								<h3 class="ksm-section-title"><?php esc_html_e( 'Tracking', 'ksm-extensions' ); ?></h3>
								<label class="ksm-checkbox-row" for="ksm-historical-tracking">
									<input type="checkbox" id="ksm-historical-tracking" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[historical_tracking]" value="1" <?php checked( ! empty( $options['historical_tracking'] ) ); ?> />
									<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Historical tracking', 'ksm-extensions' ); ?></span>
								</label>
								<div class="ksm-field">
									<label class="ksm-input-block" for="ksm-cache-duration">
										<span class="ksm-input-label"><?php esc_html_e( 'Cache duration (seconds)', 'ksm-extensions' ); ?></span>
										<input type="number" id="ksm-cache-duration" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cache_duration]" value="<?php echo esc_attr( (int) $options['cache_duration'] ); ?>" min="0" step="1" class="small-text" />
									</label>
								</div>
							</section>
						</div>

						<?php submit_button(); ?>
					</form>
				</main>
			</div>
		</div>
		<?php
	}

	/**
	 * Debug log
	 *
	 * @param string $message Log message.
	 * @param string $level Log level (info, warning, error).
	 * @since 1.0.0
	 */
	public function debug_log( $message, $level = 'info' ) {
		$debug_mode = $this->get_option( 'debug_mode', false );
		$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		
		// Log if debug mode is enabled OR if WP_DEBUG is on (for errors)
		if ( $debug_mode || ( $wp_debug && 'error' === $level ) ) {
			$prefix = '[KSM Extensions - Media Counter]';
			if ( 'error' === $level ) {
				$prefix .= ' [ERROR]';
			} elseif ( 'warning' === $level ) {
				$prefix .= ' [WARNING]';
			}
			error_log( $prefix . ' ' . $message );
		}
	}

	/**
	 * Log error with context
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 * @since 2.0.9
	 */
	public function log_error( $message, $context = array() ) {
		$log_message = $message;
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context );
		}
		$this->debug_log( $log_message, 'error' );
	}
}