<?php
/**
 * Filename: class-duplicate-finder.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 2.1.4
 * Last Modified: 20/11/2025
 * Description: Duplicate image finder and remediation toolkit for KSM Extensions.
 *
 * @package KSM_Extensions
 * @subpackage Modules\DuplicateFinder
 * @since 1.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides duplicate image detection and cleanup inside the WordPress admin.
 *
 * @since 1.2.0
 */
class KSM_Extensions_DuplicateFinder {

	/**
	 * Media page slug.
	 *
	 * @since 1.2.0
	 */
	const PAGE_SLUG = 'ksm-extensions-duplicate-finder';

	/**
	 * AJAX nonce action.
	 *
	 * @since 1.2.0
	 */
	const NONCE_ACTION = 'ksm_extensions_duplicate_finder';

	/**
	 * Option key storing cached results.
	 *
	 * @since 1.2.0
	 */
	const OPTION_RESULTS = 'ksm_extensions_duplicate_scan_results';

	/**
	 * Option key storing dismissed group indexes.
	 *
	 * @since 1.2.0
	 */
	const OPTION_DISMISSED = 'ksm_extensions_duplicate_dismissed_groups';

	/**
	 * Option key storing last scan timestamp.
	 *
	 * @since 1.2.0
	 */
	const OPTION_TIMESTAMP = 'ksm_extensions_duplicate_scan_timestamp';

	/**
	 * Core instance.
	 *
	 * @var KSM_Extensions_MediaCounter_Core
	 * @since 1.2.0
	 */
	private $core;

	/**
	 * Stored admin page hook suffix.
	 *
	 * @var string
	 * @since 1.2.0
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param KSM_Extensions_MediaCounter_Core $core Core instance.
	 * @since 1.2.0
	 */
	public function __construct( KSM_Extensions_MediaCounter_Core $core ) {
		$this->core = $core;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_media_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_KSM_Extensions_scan_duplicate_images', array( $this, 'scan_duplicates' ) );
		add_action( 'wp_ajax_KSM_Extensions_save_duplicate_scan', array( $this, 'save_scan_results' ) );
		add_action( 'wp_ajax_KSM_Extensions_get_duplicate_scan', array( $this, 'get_scan_results' ) );
		add_action( 'wp_ajax_KSM_Extensions_clear_duplicate_scan', array( $this, 'clear_scan_cache' ) );
		add_action( 'wp_ajax_KSM_Extensions_delete_duplicate_images', array( $this, 'delete_images' ) );
	}

	/**
	 * Register the media library submenu page.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_media_page() {
		$this->page_hook = add_media_page(
			__( 'Duplicate Image Finder', 'ksm-extensions' ),
			__( 'Find Duplicates', 'ksm-extensions' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue assets for the duplicate finder interface.
	 *
	 * @param string $hook Current admin page hook.
	 * @since 1.2.0
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'ksm-extensions-duplicate-finder',
			KSM_EXTENSIONS_PLUGIN_URL . 'assets/css/duplicate-finder.css',
			array(),
			KSM_EXTENSIONS_VERSION
		);

		wp_enqueue_script(
			'ksm-extensions-duplicate-finder',
			KSM_EXTENSIONS_PLUGIN_URL . 'assets/js/duplicate-finder.js',
			array( 'jquery' ),
			KSM_EXTENSIONS_VERSION,
			true
		);

		wp_localize_script(
			'ksm-extensions-duplicate-finder',
			'KSM ExtensionsDuplicateFinder',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'strings' => array(
					'noDuplicates'      => __( 'No duplicate images found!', 'ksm-extensions' ),
					'noDuplicatesFound' => __( 'No duplicates found', 'ksm-extensions' ),
					'groupTitle'        => __( 'Similar to: %s', 'ksm-extensions' ),
					'dismissGroup'      => __( 'Dismiss Group', 'ksm-extensions' ),
					'original'          => __( 'Original', 'ksm-extensions' ),
					'copy'              => __( 'Copy %d', 'ksm-extensions' ),
					'title'             => __( 'Title: ', 'ksm-extensions' ),
					'filename'          => __( 'Filename: ', 'ksm-extensions' ),
					'size'              => __( 'Size: ', 'ksm-extensions' ),
					'deleteSingle'      => __( 'Delete This', 'ksm-extensions' ),
					'loadedFromCache'   => __( 'Loaded cached results – showing %d group(s)', 'ksm-extensions' ),
					'foundGroups'       => __( 'Found %d group(s) of duplicates', 'ksm-extensions' ),
					'loadingCache'      => __( 'Loading cached results…', 'ksm-extensions' ),
					'noCache'           => __( 'No cached scan available.', 'ksm-extensions' ),
					'cacheTimestamp'    => __( 'Loaded scan from %s', 'ksm-extensions' ),
					'cacheParseError'   => __( 'Unable to parse cached scan data.', 'ksm-extensions' ),
					'scanning'          => __( 'Scanning for duplicate images…', 'ksm-extensions' ),
					'error'             => __( 'Error: %s', 'ksm-extensions' ),
					'genericError'      => __( 'An unexpected error occurred. Please try again.', 'ksm-extensions' ),
					'confirmClear'      => __( 'This will clear the cached results and run a fresh scan. Continue?', 'ksm-extensions' ),
					'noSelection'       => __( 'Please select images to delete.', 'ksm-extensions' ),
					'confirmDelete'     => __( 'Are you sure you want to delete %d image(s)?', 'ksm-extensions' ),
					'deleting'          => __( 'Deleting...', 'ksm-extensions' ),
					'deletingImages'    => __( 'Deleting images, please wait...', 'ksm-extensions' ),
					'deletionComplete'  => __( 'Deletion complete', 'ksm-extensions' ),
					'allGroupsCleared'  => __( 'All duplicate groups cleared! ✓', 'ksm-extensions' ),
				),
			)
		);
	}

	/**
	 * Render the media page interface.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div class="wrap ksm-wrap">
			<div class="ksm-admin">
				<header class="ksm-admin__hero">
					<div>
						<h1><?php esc_html_e( 'Duplicate Image Finder', 'ksm-extensions' ); ?></h1>
						<p class="ksm-admin__subhead">
							<?php esc_html_e( 'Scan the Media Library for identical filenames, review grouped matches, and bulk delete copies with confidence.', 'ksm-extensions' ); ?>
						</p>
					</div>
				</header>

				<div class="ksm-dif-container">
					<section class="ksm-card ksm-dif-card">
						<div class="ksm-card__body ksm-dif-actions">
							<div class="ksm-dif-buttons">
								<button id="ksm-dif-scan-btn" class="button button-primary ksm-button">
									<?php esc_html_e( 'Scan for Duplicates', 'ksm-extensions' ); ?>
								</button>
								<button id="ksm-dif-load-cache-btn" class="button button-secondary">
									<?php esc_html_e( 'Load Last Scan', 'ksm-extensions' ); ?>
								</button>
								<button id="ksm-dif-clear-cache-btn" class="button button-secondary">
									<?php esc_html_e( 'Clear Cache & Rescan', 'ksm-extensions' ); ?>
								</button>
								<button id="ksm-dif-delete-selected-btn" class="button button-secondary" style="display:none;">
									<?php esc_html_e( 'Delete Selected', 'ksm-extensions' ); ?>
								</button>
							</div>
							<span id="ksm-dif-scan-status" class="ksm-dif-status"></span>
						</div>
					</section>

					<section class="ksm-card">
						<div class="ksm-card__body">
							<div id="ksm-dif-results"></div>
						</div>
					</section>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Verify nonce and permissions for AJAX calls.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function validate_request() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'ksm-extensions' ) );
		}
	}

	/**
	 * Scan the media library for duplicate images based on filename heuristics.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function scan_duplicates() {
		$this->validate_request();

		// Increase time limit for large scans
		@set_time_limit( 300 ); // 5 minutes max
		
		$start_time = microtime( true ); // Use microtime for more accurate timing
		$max_execution_time = 200; // Stop 100 seconds before PHP limit to be safe
		$paged        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page     = 1000; // Process 1000 images per batch for better performance
		$accumulated_raw = isset( $_POST['accumulated'] ) ? wp_unslash( $_POST['accumulated'] ) : '{}';
		$accumulated_decoded = json_decode( $accumulated_raw, true );
		$grouped      = is_array( $accumulated_decoded ) ? $accumulated_decoded : array();
		$total_images = 0;

		// Get total count on first page
		if ( 1 === $paged ) {
			$count_query = new WP_Query(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'post_mime_type' => 'image',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
				)
			);
			$total_images = $count_query->found_posts;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => false,
			)
		);

		// Get total on first query if not already set (or update if query provides it)
		if ( 0 === $total_images && $query->found_posts > 0 ) {
			$total_images = $query->found_posts;
		}

		if ( empty( $query->posts ) ) {
			// No more images, return final results
			$groups = $this->format_groups( $grouped );
			wp_send_json_success(
				array(
					'groups'        => $groups,
					'total'         => count( $groups ),
					'partial'       => false,
					'complete'      => true,
					'accumulated'   => $grouped,
					'processed'     => ( $paged - 1 ) * $per_page,
					'total_images'  => $total_images,
					'message'       => sprintf( __( 'Scan complete! Found %d duplicate group(s).', 'ksm-extensions' ), count( $groups ) ),
				)
			);
			return;
		}

		foreach ( $query->posts as $attachment_id ) {
			$base_name = $this->get_base_filename( $attachment_id );

			if ( '' === $base_name ) {
				continue;
			}

			if ( ! isset( $grouped[ $base_name ] ) ) {
				$grouped[ $base_name ] = array();
			}

			$grouped[ $base_name ][] = $this->prepare_image_payload( $attachment_id );
		}

		// Check elapsed time AFTER processing this batch
		$elapsed = microtime( true ) - $start_time;
		
		// Check if there are more pages
		// Use found_posts from query if available, otherwise use stored total
		$actual_total = $query->found_posts > 0 ? $query->found_posts : $total_images;
		$processed_count = $paged * $per_page;
		
		// Check if we have more pages:
		// 1. Current page returned full batch (suggests more exist)
		// 2. Processed count is less than total
		// 3. Query indicates more pages exist
		$has_more = ( $query->post_count >= $per_page ) && 
		            ( $processed_count < $actual_total || $actual_total === 0 ) &&
		            ( $paged < $query->max_num_pages || $query->max_num_pages === 0 );
		
		// If we're approaching time limit, continue but warn
		// Only stop if we've been running for a very long time (close to PHP limit)
		if ( $elapsed > $max_execution_time && $has_more ) {
			// Time limit approaching, but continue with next batch
			// The JavaScript will handle continuation
			$next_page = $paged + 1;
			wp_send_json_success(
				array(
					'groups'        => $this->format_groups( $grouped ),
					'total'         => count( $this->format_groups( $grouped ) ),
					'partial'       => true,
					'complete'      => false,
					'next_page'     => $next_page,
					'accumulated'   => $grouped,
					'processed'     => $processed_count,
					'total_images'  => $total_images,
					'message'       => sprintf( __( 'Processed %d of %d images...', 'ksm-extensions' ), min( $processed_count, $total_images ), $total_images ),
				)
			);
			return;
		}

		// Return results for this batch
		if ( $has_more ) {
			// More pages to process
			$next_page = $paged + 1;
			wp_send_json_success(
				array(
					'groups'        => $this->format_groups( $grouped ),
					'total'         => count( $this->format_groups( $grouped ) ),
					'partial'       => true,
					'complete'      => false,
					'next_page'     => $next_page,
					'accumulated'   => $grouped,
					'processed'     => $processed_count,
					'total_images'  => $total_images,
					'message'       => sprintf( __( 'Processed %d of %d images...', 'ksm-extensions' ), min( $processed_count, $total_images ), $total_images ),
				)
			);
		} else {
			// All done
			$groups = $this->format_groups( $grouped );
			wp_send_json_success(
				array(
					'groups'        => $groups,
					'total'         => count( $groups ),
					'partial'       => false,
					'complete'      => true,
					'accumulated'   => $grouped,
					'processed'     => $total_images,
					'total_images'  => $total_images,
					'message'       => sprintf( __( 'Scan complete! Found %d duplicate group(s).', 'ksm-extensions' ), count( $groups ) ),
				)
			);
		}
	}

	/**
	 * Format grouped images into duplicate groups.
	 *
	 * @param array $grouped Grouped images by base filename.
	 * @return array Formatted groups.
	 * @since 2.1.3
	 */
	private function format_groups( $grouped ) {
		$groups = array();

		foreach ( $grouped as $base_name => $images ) {
			if ( count( $images ) < 2 ) {
				continue;
			}

			usort(
				$images,
				function ( $a, $b ) {
					return $a['id'] <=> $b['id'];
				}
			);

			$groups[] = array(
				'base_name' => $base_name,
				'images'    => $images,
			);
		}

		return $groups;
	}

	/**
	 * Save scan results and dismissed groups.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function save_scan_results() {
		$this->validate_request();

		$results   = isset( $_POST['results'] ) ? wp_unslash( $_POST['results'] ) : '';
		$dismissed = isset( $_POST['dismissed'] ) ? (array) wp_unslash( $_POST['dismissed'] ) : array();
		$dismissed = array_map( 'absint', $dismissed );

		update_option( self::OPTION_RESULTS, $results );
		update_option( self::OPTION_DISMISSED, $dismissed );
		update_option( self::OPTION_TIMESTAMP, current_time( 'mysql' ) );

		wp_send_json_success();
	}

	/**
	 * Retrieve cached scan results.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function get_scan_results() {
		$this->validate_request();

		$results = get_option( self::OPTION_RESULTS, '' );

		if ( empty( $results ) ) {
			wp_send_json_error( __( 'No cached results found.', 'ksm-extensions' ) );
		}

		wp_send_json_success(
			array(
				'results'   => $results,
				'dismissed' => get_option( self::OPTION_DISMISSED, array() ),
				'timestamp' => get_option( self::OPTION_TIMESTAMP, '' ),
			)
		);
	}

	/**
	 * Clear cached scan data.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function clear_scan_cache() {
		$this->validate_request();
		self::cleanup_options();
		wp_send_json_success();
	}

	/**
	 * Delete selected attachment IDs permanently.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function delete_images() {
		$this->validate_request();

		$image_ids_raw = isset( $_POST['image_ids'] ) ? wp_unslash( $_POST['image_ids'] ) : array();
		$image_ids     = array_filter( array_map( 'absint', (array) $image_ids_raw ) );

		if ( empty( $image_ids ) ) {
			wp_send_json_error( __( 'No images selected.', 'ksm-extensions' ) );
		}

		$deleted = array();
		$failed  = array();

		foreach ( $image_ids as $image_id ) {
			if ( wp_delete_attachment( $image_id, true ) ) {
				$deleted[] = $image_id;
			} else {
				$failed[] = $image_id;
			}
		}

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'failed'  => $failed,
				'message' => sprintf(
					_n( '%d image deleted successfully.', '%d images deleted successfully.', count( $deleted ), 'ksm-extensions' ),
					count( $deleted )
				),
			)
		);
	}

	/**
	 * Remove stored options during uninstall or cache clearing.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function cleanup_options() {
		delete_option( self::OPTION_RESULTS );
		delete_option( self::OPTION_DISMISSED );
		delete_option( self::OPTION_TIMESTAMP );
	}

	/**
	 * Prepare the payload for a given attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @since 1.2.0
	 * @return array
	 */
	private function prepare_image_payload( $attachment_id ) {
		$post = get_post( $attachment_id );

		return array(
			'id'       => $attachment_id,
			'title'    => $post ? $post->post_title : '',
			'filename' => basename( get_attached_file( $attachment_id ) ),
			'url'      => wp_get_attachment_url( $attachment_id ),
			'thumb'    => $this->get_image_thumbnail( $attachment_id ),
			'size'     => $this->get_file_size( $attachment_id ),
		);
	}

	/**
	 * Retrieve a thumbnail URL for the attachment with graceful fallback.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @since 1.2.0
	 * @return string
	 */
	private function get_image_thumbnail( $attachment_id ) {
		$thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		if ( ! $thumb ) {
			$thumb = wp_get_attachment_image_url( $attachment_id, 'medium' );
		}

		if ( ! $thumb ) {
			$thumb = wp_get_attachment_url( $attachment_id );
		}

		return $thumb;
	}

	/**
	 * Generate the base filename used for duplicate comparisons.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @since 1.2.0
	 * @return string
	 */
	private function get_base_filename( $attachment_id ) {
		$file = basename( get_attached_file( $attachment_id ) );
		$name = pathinfo( $file, PATHINFO_FILENAME );

		$name = preg_replace( '/-\d+$/', '', $name );
		$name = preg_replace( '/-scaled$/', '', $name );
		$name = preg_replace( '/-\d+x\d+$/', '', $name );

		return strtolower( $name );
	}

	/**
	 * Human readable file size for attachments.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @since 1.2.0
	 * @return string
	 */
	private function get_file_size( $attachment_id ) {
		$path = get_attached_file( $attachment_id );

		if ( ! $path || ! file_exists( $path ) ) {
			return __( 'Unknown', 'ksm-extensions' );
		}

		return $this->format_bytes( filesize( $path ) );
	}

	/**
	 * Convert a byte value into a human-readable string.
	 *
	 * @param int $bytes Byte value.
	 * @since 1.2.0
	 * @return string
	 */
	private function format_bytes( $bytes ) {
		if ( $bytes >= 1073741824 ) {
			return round( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			return round( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			return round( $bytes / 1024, 2 ) . ' KB';
		}

		return $bytes . ' ' . __( 'bytes', 'ksm-extensions' );
	}
}

