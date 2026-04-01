<?php
/**
 * Filename: class-media-file-size.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 1.8.4
 * Last Modified: 30/12/2025
 * Description: Adds file-size analytics, indexing, and variant previews to the Media Library.
 *
 * Credits:
 * - Based on "Media Library File Size" by SS88 LLC (GPLv2+). Logic and UI were reimplemented and modernised for KSM Extensions.
 *
 * @package KSM_Extensions
 * @subpackage Modules\MediaFileSize
 * @since 1.8.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Library file-size utilities.
 *
 * @since 1.8.0
 */
class KSM_Extensions_MediaFileSize {

	const META_PRIMARY = 'SS88MLFS';
	const META_VARIANTS = 'SS88MLFSV';

	/**
	 * Cached variant metadata.
	 *
	 * @var array
	 */
	private $variant_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'manage_media_columns', array( $this, 'register_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_upload_sortable_columns', array( $this, 'register_sortable_column' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer-upload.php', array( $this, 'print_variant_data_script' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'update_attachment_metadata' ), PHP_INT_MAX, 2 );
		add_filter( 'wp_update_attachment_metadata', array( $this, 'update_attachment_metadata' ), PHP_INT_MAX, 2 );
		add_action( 'wp_ajax_ksm_extensions_media_size_index', array( $this, 'ajax_index' ) );
		add_action( 'wp_ajax_ksm_extensions_media_size_index_count', array( $this, 'ajax_index_count' ) );
		add_action( 'wp_ajax_ksm_extensions_media_size_get_variants', array( $this, 'ajax_get_variants' ) );
	}

	/**
	 * Register column in the media list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function register_column( $columns ) {
		$columns['ksm_extensions_media_file_size'] = __( 'File Size', 'ksm-extensions' );
		return $columns;
	}

	/**
	 * Mark column sortable.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function register_sortable_column( $columns ) {
		$columns['ksm_extensions_media_file_size'] = 'ksm_extensions_media_file_size';
		return $columns;
	}

	/**
	 * Handle sorting requests.
	 *
	 * @param WP_Query $query Query instance.
	 * @return void
	 */
	public function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'ksm_extensions_media_file_size' === ( $_REQUEST['orderby'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query->set( 'meta_key', self::META_PRIMARY );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Render column contents.
	 *
	 * @param string $column Column name.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 */
	public function render_column( $column, $attachment_id ) {
		if ( 'ksm_extensions_media_file_size' !== $column ) {
			return;
		}

		echo wp_kses_post( $this->build_cell_markup( $attachment_id ) );
	}

	/**
	 * Update attachment metadata with size info.
	 *
	 * @param array $data Metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function update_attachment_metadata( $data, $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) && empty( $data['filesize'] ) ) {
			return $data;
		}

		$this->store_sizes( $data, $attachment_id );
		return $data;
	}

	/**
	 * Store primary + variant sizes.
	 *
	 * @param array $metadata Metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return void
	 */
	private function store_sizes( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		$size = ! empty( $metadata['filesize'] ) ? (int) $metadata['filesize'] : ( file_exists( $file ) ? (int) filesize( $file ) : 0 );

		if ( $size <= 0 ) {
			return;
		}

		update_post_meta( $attachment_id, self::META_PRIMARY, $size );
		update_post_meta( $attachment_id, self::META_VARIANTS, $this->calculate_variant_size( $metadata, $file ) );
	}

	/**
	 * Calculate total variant size.
	 *
	 * @param array  $metadata Metadata.
	 * @param string $file Path.
	 * @return int
	 */
	private function calculate_variant_size( $metadata, $file ) {
		if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
			return 0;
		}

		$total = 0;
		$dir   = pathinfo( $file, PATHINFO_DIRNAME );

		foreach ( $metadata['sizes'] as $variant ) {
			if ( ! empty( $variant['filesize'] ) ) {
				$total += (int) $variant['filesize'];
				continue;
			}

			if ( ! empty( $variant['file'] ) ) {
				$path = trailingslashit( $dir ) . $variant['file'];
				if ( file_exists( $path ) ) {
					$total += (int) filesize( $path );
				}
			}
		}

		return $total;
	}

	/**
	 * Build the HTML markup for the media column cell.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private function build_cell_markup( $attachment_id ) {
		$metadata    = wp_get_attachment_metadata( $attachment_id );
		$primary     = (int) get_post_meta( $attachment_id, self::META_PRIMARY, true );
		$variant_sum = (int) get_post_meta( $attachment_id, self::META_VARIANTS, true );

		if ( ! $primary && isset( $metadata['filesize'] ) ) {
			$primary = (int) $metadata['filesize'];
		}

		if ( ! $primary ) {
			return '<em>' . esc_html__( 'Unknown', 'ksm-extensions' ) . '</em>';
		}

		$markup = size_format( $primary );

		if ( $variant_sum > 0 ) {
			$markup .= sprintf( '<small>(+%s)</small>', size_format( $variant_sum ) );
		}

		if ( ! empty( $metadata['sizes'] ) ) {
			$markup .= sprintf(
				' <button class="ksm-media-size-variants-button" data-attachment-id="%1$d">%2$s</button>',
				$attachment_id,
				esc_html__( 'View Variants', 'ksm-extensions' )
			);

			$this->variant_data[ $attachment_id ] = $this->collect_variant_payload( $metadata, $attachment_id );
		}

		return $markup;
	}

	/**
	 * Collect variant payload for JS modal.
	 *
	 * @param array $metadata Metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	private function collect_variant_payload( $metadata, $attachment_id ) {
		if ( empty( $metadata['sizes'] ) ) {
			return array();
		}

		$file     = get_attached_file( $attachment_id );
		$dir      = pathinfo( $file, PATHINFO_DIRNAME );
		$base_url = wp_get_attachment_url( $attachment_id );
		$base_url = str_replace( basename( $base_url ), '', $base_url );
		$payload  = array();

		foreach ( $metadata['sizes'] as $size_key => $variant ) {
			$size_path = trailingslashit( $dir ) . $variant['file'];
			$variant_size = isset( $variant['filesize'] ) ? (int) $variant['filesize'] : ( file_exists( $size_path ) ? (int) filesize( $size_path ) : 0 );

			// Use WordPress function to get the proper image URL for this size
			$image_url = wp_get_attachment_image_src( $attachment_id, $size_key );
			$variant_url = $image_url ? $image_url[0] : trailingslashit( $base_url ) . $variant['file'];

			$payload[] = array(
				'size'        => $size_key,
				'width'       => (int) ( $variant['width'] ?? 0 ),
				'height'      => (int) ( $variant['height'] ?? 0 ),
				'filesize_hr' => $variant_size ? size_format( $variant_size ) : __( 'Unknown', 'ksm-extensions' ),
				'filename'    => $variant_url,
			);
		}

		return $payload;
	}

	/**
	 * AJAX: Return variant data for a single attachment (for View Variants when not in page payload).
	 *
	 * @return void
	 */
	public function ajax_get_variants() {
		check_ajax_referer( 'ksm_extensions_media_file_size', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$attachment_id = isset( $_REQUEST['attachment_id'] ) ? absint( $_REQUEST['attachment_id'] ) : 0;
		if ( ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment.', 'ksm-extensions' ) ) );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $metadata['sizes'] ) ) {
			wp_send_json_success( array( 'variants' => array() ) );
		}

		$variants = $this->collect_variant_payload( $metadata, $attachment_id );
		wp_send_json_success( array( 'variants' => $variants ) );
	}

	/**
	 * Enqueue scripts/styles when viewing the media library list view.
	 *
	 * @param string $hook Current screen hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ksm-media-file-size',
			KSM_EXTENSIONS_PLUGIN_URL . 'assets/css/media-file-size.css',
			array(),
			KSM_EXTENSIONS_VERSION
		);

		wp_enqueue_script(
			'ksm-media-file-size',
			KSM_EXTENSIONS_PLUGIN_URL . 'assets/js/media-file-size.js',
			array( 'jquery' ),
			KSM_EXTENSIONS_VERSION,
			true
		);

		wp_localize_script(
			'ksm-media-file-size',
			'ksmExtensionsMediaSize',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'ksm_extensions_media_file_size' ),
				'getVariantsAction' => 'ksm_extensions_media_size_get_variants',
				'strings'           => array(
					'indexMedia'   => __( 'Index Media', 'ksm-extensions' ),
					'reindexMedia' => __( 'Reindex Media', 'ksm-extensions' ),
					'indexError'   => __( 'Unable to index your media library.', 'ksm-extensions' ),
					'variantsError' => __( 'Could not load variant data.', 'ksm-extensions' ),
				),
			)
		);

	}

	/**
	 * Print variant JSON for the modal.
	 *
	 * @return void
	 */
	public function print_variant_data_script() {
		$data = $this->variant_data;
		if ( empty( $data ) ) {
			$data = array();
		}

		printf(
			'<script>window.ksmExtensionsMediaSizeVariants = %s;</script>',
			wp_json_encode( $data )
		);
	}

	/**
	 * AJAX: index attachment sizes.
	 *
	 * @return void
	 */
	public function ajax_index() {
		check_ajax_referer( 'ksm_extensions_media_file_size', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'body' => __( 'Insufficient permissions.', 'ksm-extensions' ) ), 403 );
		}

		$reindex      = ! empty( $_POST['reindex'] );
		$batch_size   = 100;
		$page         = 1;
		$processed    = 0;
		$updated_rows = array();
		$found_posts  = null; // Track total found posts

		do {
			$args = array(
				'post_type'      => 'attachment',
				'posts_per_page' => $batch_size,
				'paged'          => $page,
				'fields'         => 'ids',
				'no_found_rows'  => false, // We need found_posts to check for limits
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => self::META_PRIMARY,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => self::META_VARIANTS,
						'compare' => 'NOT EXISTS',
					),
				),
			);

			if ( $reindex ) {
				unset( $args['meta_query'] );
			}

			// Use WP_Query to get found_posts count and ensure we can process all items
			$query = new WP_Query( $args );
			$attachments = $query->posts;
			
			// On first iteration, check total count that need indexing
			if ( 1 === $page ) {
				$found_posts = $query->found_posts;
			}

			if ( empty( $attachments ) ) {
				break;
			}

			foreach ( $attachments as $attachment_id ) {
				$metadata = wp_get_attachment_metadata( $attachment_id );
				$this->store_sizes( $metadata, $attachment_id );
				$processed++;

				if ( $processed <= 1000 ) {
					$updated_rows[] = array(
						'attachment_id' => $attachment_id,
						'html'          => $this->build_cell_markup( $attachment_id ),
					);
				}
			}

			$page++;
			
			// Safety check: prevent infinite loops, but allow up to 200 pages (20,000 items)
			if ( $page > 200 ) {
				break;
			}
		} while ( count( $attachments ) === $batch_size );

		if ( ! $processed ) {
			wp_send_json_error(
				array(
					'httpcode' => 99,
					'body'     => __( 'No attachments were indexed. This usually means the files are not stored locally.', 'ksm-extensions' ),
				)
			);
		}

		$variants = array();
		foreach ( $updated_rows as $row ) {
			$attachment_id = $row['attachment_id'];
			if ( isset( $this->variant_data[ $attachment_id ] ) ) {
				$variants[ $attachment_id ] = $this->variant_data[ $attachment_id ];
			}
		}

		// Get total attachment count for comparison
		$total_attachments = wp_count_posts( 'attachment' );
		$total_count = isset( $total_attachments->inherit ) ? (int) $total_attachments->inherit : 0;
		
		$message = $reindex
			? sprintf( __( 'Re-indexed %s attachments.', 'ksm-extensions' ), number_format_i18n( $processed ) )
			: sprintf( __( 'Indexed %s attachments. Your media library is now up to date.', 'ksm-extensions' ), number_format_i18n( $processed ) );
		
		// Warn if we processed fewer than expected (might indicate a query limit was hit)
		if ( ! $reindex && isset( $found_posts ) && $found_posts > $processed ) {
			$remaining = $found_posts - $processed;
			$message .= ' ' . sprintf( __( 'Note: %s more attachments may need indexing. Click "Reindex Media" to process all items.', 'ksm-extensions' ), number_format_i18n( $remaining ) );
		} elseif ( ! $reindex && $total_count > 0 && $processed < $total_count && $page > 50 ) {
			// If we processed many pages but still have more, suggest reindex
			$message .= ' ' . __( 'If more attachments need indexing, click "Reindex Media" to process all items.', 'ksm-extensions' );
		}

		wp_send_json_success(
			array(
				'html'      => $updated_rows,
				'variants'  => $variants,
				'message'   => $message,
				'total'     => $total_count,
				'processed' => $processed,
				'found'     => isset( $found_posts ) ? $found_posts : null,
			)
		);
	}

	/**
	 * AJAX: Return total media library size.
	 *
	 * @return void
	 */
	public function ajax_index_count() {
		check_ajax_referer( 'ksm_extensions_media_file_size', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array(), 403 );
		}

		global $wpdb;
		$total_primary  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META_PRIMARY ) );
		$total_variants = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META_VARIANTS ) );

		if ( ! $total_primary && ! $total_variants ) {
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'TotalMLSize'       => size_format( $total_primary + $total_variants ),
				'TotalMLSize_Title' => $total_variants ? size_format( $total_primary ) . ' + ' . size_format( $total_variants ) . '<br>' . __( 'variants', 'ksm-extensions' ) : '',
			)
		);
	}

	/**
	 * Delete metadata keys on uninstall.
	 *
	 * @return void
	 */
	public static function cleanup() {
		delete_post_meta_by_key( self::META_PRIMARY );
		delete_post_meta_by_key( self::META_VARIANTS );
	}
}

