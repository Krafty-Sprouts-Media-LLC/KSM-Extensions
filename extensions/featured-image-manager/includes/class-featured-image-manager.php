<?php
/**
 * Filename: class-featured-image-manager.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 2.1.7
 * Last Modified: 12/04/2026
 * Description: Featured image utilities for admin thumbnails, RSS injection, and fallback handling.
 *
 * Credits:
 * - Concepts inspired by Smart Featured Image Manager (Krafty Sprouts Media, LLC)
 * - Admin column UX influenced by Featured Image Admin Thumb (Sean Hayes)
 * - Fallback behaviour informed by Default Featured Image (Jan Willem Oostendorp)
 * - Original WordPress Codex snippets for RSS thumbnails
 *
 * @package KSM_Extensions
 * @subpackage Modules\FeaturedImageManager
 * @since 1.4.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Featured image manager module for KSM Extensions.
 *
 * @since 1.4.0
 */
class KSM_Extensions_FeaturedImageManager {

	const OPTION_KEY = 'ksm_extensions_featured_image_manager';

	/**
	 * Core instance.
	 *
	 * @var KSM_Extensions_MediaCounter_Core
	 */
	private $core;

	/**
	 * Cached options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Cached list of post types managed by the module.
	 *
	 * @var array
	 */
	private $managed_post_types = array();

	/**
	 * Constructor.
	 *
	 * @param KSM_Extensions_MediaCounter_Core $core Core instance.
	 */
	public function __construct( KSM_Extensions_MediaCounter_Core $core ) {
		$this->core = $core;
		$this->load_options();
		$this->register_hooks();
	}

	/**
	 * Inline SVG icons for featured image actions.
	 *
	 * @param string $icon Icon key.
	 * @return string
	 */
	private function get_thumb_action_icon( $icon ) {
		$filename = '';
		switch ( $icon ) {
			case 'trash':
				$filename = 'trash.svg';
				break;
			case 'edit':
			default:
				$filename = 'edit.svg';
				break;
		}

		$path = plugin_dir_path( __FILE__ ) . '../assets/icons/' . $filename;
		if ( file_exists( $path ) ) {
			return file_get_contents( $path );
		}

		// Fallback icons when SVG files are not present in assets/icons.
		if ( 'trash' === $icon ) {
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 6h2v9h-2V9zm4 0h2v9h-2V9zM7 9h2v9H7V9z"/></svg>';
		}

		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 000-1.42l-2.5-2.5a1.003 1.003 0 00-1.42 0l-1.83 1.83 3.75 3.75 2-1.66z"/></svg>';
	}

	/**
	 * Handle activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::get_default_options() );
		}
	}

	/**
	 * Handle deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Nothing explicit yet; placeholder for future cron cleanups.
	}

	/**
	 * Handle uninstall.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'register_post_type_columns' ) );
		add_action( 'save_post', array( $this, 'maybe_assign_fallback_featured_image' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'register_media_settings_bridge' ) );

		add_filter( 'the_content_feed', array( $this, 'inject_featured_image_into_feed' ), 10 );
		add_filter( 'the_excerpt_rss', array( $this, 'inject_featured_image_into_feed' ), 10 );
		add_action( 'rss2_item', array( $this, 'maybe_output_rss_item_enclosure' ), 10 );
		add_filter( 'default_post_metadata', array( $this, 'supply_fallback_thumbnail_meta' ), 10, 5 );
		add_filter( 'post_thumbnail_html', array( $this, 'maybe_render_fallback_thumbnail_html' ), 10, 5 );
		add_action( 'wp_ajax_ksm_extensions_set_featured_image', array( $this, 'handle_set_featured_image_request' ) );
		add_action( 'admin_head', array( $this, 'print_admin_column_styles' ) );

		if ( is_admin() ) {
			add_action( 'ksm_extensions_register_settings_page', array( $this, 'register_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Load stored options.
	 *
	 * @return void
	 */
	private function load_options() {
		$defaults     = self::get_default_options();
		$stored       = get_option( self::OPTION_KEY, $defaults );
		$this->options = wp_parse_args( $stored, $defaults );
		// Module is only loaded when activated on KSM Extensions → Modules; no separate "enable" needed.
		$this->options['enabled'] = true;
	}

	/**
	 * Get default option values.
	 *
	 * @return array
	 */
	public static function get_default_options() {
		$post_types = array_keys( get_post_types_by_support( array( 'thumbnail' ) ) );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		return array(
			'enabled'            => false,
			'post_types'         => $post_types,
			'admin_column'       => true,
			'admin_column_size'  => 'thumbnail',
			'fallback_image_id'  => 0,
			'fallback_image_url' => '',
			'auto_assign'            => true,
			'feed_enabled'           => true,
			'feed_include_enclosure' => true,
			'feed_image_size'        => 'full',
			'feed_caption_field'     => 'caption',
		);
	}

	/**
	 * Retrieve the post types managed by the module.
	 *
	 * @return array
	 */
	private function get_managed_post_types() {
		if ( empty( $this->managed_post_types ) ) {
			$post_types = isset( $this->options['post_types'] ) ? (array) $this->options['post_types'] : array();
			if ( empty( $post_types ) ) {
				$post_types = array_keys( get_post_types_by_support( array( 'thumbnail' ) ) );
			}

			$this->managed_post_types = apply_filters( 'KSM_Extensions_featured_image_post_types', $post_types );
		}

		return $this->managed_post_types;
	}

	/**
	 * Register admin settings page.
	 * Only registers if the module is activated.
	 *
	 * @param KSM_Extensions_Admin $admin The admin instance.
	 * @return void
	 */
	public function register_settings_page( $admin ) {
		// Register via KSM Extensions admin class (adds to WordPress Settings menu)
		// Only registers if module is activated
		$admin->register_module_settings_page(
			__( 'Featured Image Manager', 'ksm-extensions' ),
			__( 'Featured Image Manager', 'ksm-extensions' ),
			'ksm-extensions-featured-images',
			array( $this, 'render_settings_page' ),
			'featured-image-manager'
		);
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Bridge into Settings → Media for familiarity.
	 *
	 * @return void
	 */
	public function register_media_settings_bridge() {
		add_settings_field(
			'KSM_Extensions_fallback_featured_image',
			__( 'KSM Extensions fallback featured image', 'ksm-extensions' ),
			array( $this, 'render_media_settings_bridge_field' ),
			'media',
			'default'
		);
	}

	/**
	 * Render the Media settings bridge field.
	 *
	 * @return void
	 */
	public function render_media_settings_bridge_field() {
		printf(
			'<p>%s</p><p><a class="button" href="%s">%s</a></p>',
			esc_html__( 'Configure the default featured image via the KSM Extensions Featured Image Manager.', 'ksm-extensions' ),
			esc_url( admin_url( 'options-general.php?page=ksm-extensions-featured-images' ) ),
			esc_html__( 'Open Featured Image Manager', 'ksm-extensions' )
		);
	}

	/**
	 * Enqueue admin assets for the settings page.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Check for the correct hook suffix for options pages (Settings menu)
		if ( 'settings_page_ksm-extensions-featured-images' === $hook ) {
			wp_enqueue_media();
			wp_add_inline_script(
				'jquery',
				'
				jQuery( function ( $ ) {
					var frame;

					$( "#ksm-select-fallback" ).on( "click", function ( e ) {
						e.preventDefault();

						if ( frame ) {
							frame.open();
							return;
						}

						frame = wp.media({
							title: "' . esc_js( __( 'Select fallback image', 'ksm-extensions' ) ) . '",
							button: { text: "' . esc_js( __( 'Use this image', 'ksm-extensions' ) ) . '" },
							multiple: false
						});

						frame.on( "select", function () {
							var attachment = frame.state().get( "selection" ).first().toJSON();
							$( "#fallback_image_id" ).val( attachment.id );
							$( "#fallback_image_url" ).val( attachment.url );
							$( "#ksm-fallback-preview" ).attr( "src", attachment.sizes?.thumbnail ? attachment.sizes.thumbnail.url : attachment.url ).show();
						});

						frame.open();
					});

					$( "#ksm-clear-fallback" ).on( "click", function ( e ) {
						e.preventDefault();
						$( "#fallback_image_id" ).val( "" );
						$( "#fallback_image_url" ).val( "" );
						$( "#ksm-fallback-preview" ).hide();
					});
				} );
				'
			);
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		$post_type = $screen->post_type ?: 'post';
		if ( ! in_array( $post_type, $this->get_managed_post_types(), true ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'ksm-featured-thumb-admin',
			KSM_EXTENSIONS_PLUGIN_URL . 'assets/js/admin-featured-thumbs.js',
			array( 'jquery', 'wp-util', 'media-editor' ),
			KSM_EXTENSIONS_VERSION,
			true
		);

		wp_localize_script(
			'ksm-featured-thumb-admin',
			'KSM ExtensionsFeaturedThumbs',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'l10n'    => array(
					'title'      => __( 'Select featured image', 'ksm-extensions' ),
					'button'     => __( 'Use featured image', 'ksm-extensions' ),
					'set'        => __( 'Set image', 'ksm-extensions' ),
					'change'     => __( 'Change image', 'ksm-extensions' ),
					'remove'     => __( 'Remove this featured image?', 'ksm-extensions' ),
					'updating'   => __( 'Updating…', 'ksm-extensions' ),
					'error'      => __( 'Unable to update the featured image. Please try again.', 'ksm-extensions' ),
				),
			)
		);
	}

	/**
	 * Render settings page markup.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ksm-extensions' ) );
		}

		$this->load_options();
		$options     = $this->options;
		$post_types  = get_post_types( array( 'public' => true ), 'objects' );
		$image_sizes = $this->get_available_image_sizes();
		
		// Helper to check if a module is enabled
		$check_module = function( $option_key ) {
			$opt = get_option( $option_key, array() );
			return ! empty( $opt['enabled'] );
		};
		?>
		<div class="wrap ksm-wrap ksm-settings-page">
			<div class="ksm-admin ksm-admin-layout">
				<!-- Sidebar Navigation -->
				<!-- Sidebar removed - KSM Extensions uses standard WordPress admin layout -->

				<!-- Main Content Area -->


				<main class="ksm-main-content">
					<header class="ksm-admin__hero">
						<div>
							<h1><?php esc_html_e( 'KSM Extensions Featured Image Manager', 'ksm-extensions' ); ?></h1>
							<p class="ksm-admin__subhead">
								<?php esc_html_e( 'Control featured imagery across every post type, enforce fallbacks, and populate feeds — all from one consistent surface.', 'ksm-extensions' ); ?>
							</p>
						</div>
					</header>

					<form method="post" action="options.php" class="ksm-card">
						<?php settings_fields( 'ksm_extensions_featured_image_manager' ); ?>

						<div class="ksm-card__body">
							<section class="ksm-section">
								<div class="ksm-form-grid">
									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Post types', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Pick the content types that should inherit thumbnail columns and fallback behaviour.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<div class="ksm-pill-list">
											<?php foreach ( $post_types as $post_type ) : ?>
												<label class="ksm-checkbox">
													<input
														type="checkbox"
														name="<?php echo esc_attr( self::OPTION_KEY ); ?>[post_types][]"
														value="<?php echo esc_attr( $post_type->name ); ?>"
														<?php checked( in_array( $post_type->name, $options['post_types'], true ) ); ?>
													/>
													<span class="ksm-checkbox__checkmark"></span>
													<span><?php echo esc_html( $post_type->labels->name ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>

									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Admin column', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Add sortable thumbnail previews with hover actions directly inside the Posts list.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<label class="ksm-checkbox-row" for="ksm-featured-admin-column">
											<input
												type="checkbox"
												id="ksm-featured-admin-column"
												name="<?php echo esc_attr( self::OPTION_KEY ); ?>[admin_column]"
												value="1"
												<?php checked( $options['admin_column'] ); ?>
											/>
											<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Show featured image previews in list tables', 'ksm-extensions' ); ?></span>
										</label>
									</div>
								</div>
							</section>

							<section class="ksm-section">
								<div class="ksm-fieldset">
									<div class="ksm-fieldset__header">
										<h3><?php esc_html_e( 'Default artwork', 'ksm-extensions' ); ?></h3>
										<p class="ksm-field__description">
											<?php esc_html_e( 'Choose a universal image for posts that publish without a featured image and auto-assign it on save.', 'ksm-extensions' ); ?>
										</p>
									</div>
									<div class="ksm-fallback-preview">
										<?php if ( ! empty( $options['fallback_image_url'] ) ) : ?>
											<img id="ksm-fallback-preview" src="<?php echo esc_url( $options['fallback_image_url'] ); ?>" alt="" />
										<?php else : ?>
											<img id="ksm-fallback-preview" src="" alt="" style="display:none;" />
										<?php endif; ?>
									</div>
									<input type="hidden" id="fallback_image_id" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fallback_image_id]" value="<?php echo esc_attr( $options['fallback_image_id'] ); ?>" />
									<label class="ksm-input-block" for="fallback_image_url">
										<span class="ksm-input-label"><?php esc_html_e( 'Fallback image URL', 'ksm-extensions' ); ?></span>
										<input
											type="text"
											id="fallback_image_url"
											class="regular-text"
											name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fallback_image_url]"
											value="<?php echo esc_attr( $options['fallback_image_url'] ); ?>"
											placeholder="<?php esc_attr_e( 'https://example.com/image.jpg', 'ksm-extensions' ); ?>"
										/>
									</label>
									<div class="ksm-fallback-actions">
										<button type="button" class="button" id="ksm-select-fallback"><?php esc_html_e( 'Select image', 'ksm-extensions' ); ?></button>
										<button type="button" class="button button-link-delete" id="ksm-clear-fallback"><?php esc_html_e( 'Clear', 'ksm-extensions' ); ?></button>
									</div>
									<label class="ksm-checkbox-row" for="ksm-featured-auto-assign">
										<input
											type="checkbox"
											id="ksm-featured-auto-assign"
											name="<?php echo esc_attr( self::OPTION_KEY ); ?>[auto_assign]"
											value="1"
											<?php checked( $options['auto_assign'] ); ?>
										/>
										<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Automatically apply fallback when no featured image exists', 'ksm-extensions' ); ?></span>
									</label>
								</div>
							</section>

							<section class="ksm-section">
								<div class="ksm-fieldset">
									<div class="ksm-fieldset__header">
										<h3><?php esc_html_e( 'RSS feed output', 'ksm-extensions' ); ?></h3>
										<p class="ksm-field__description">
											<?php esc_html_e( 'Push the featured image into feeds with consistent sizing, placement, and captions so subscribers always see the hero.', 'ksm-extensions' ); ?>
										</p>
									</div>
									<label class="ksm-checkbox-row" for="ksm-featured-feed-enabled">
										<input
											type="checkbox"
											id="ksm-featured-feed-enabled"
											name="<?php echo esc_attr( self::OPTION_KEY ); ?>[feed_enabled]"
											value="1"
											<?php checked( $options['feed_enabled'] ); ?>
										/>
										<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Include featured images in RSS feeds', 'ksm-extensions' ); ?></span>
									</label>
									<label class="ksm-checkbox-row" for="ksm-featured-feed-enclosure">
										<input
											type="checkbox"
											id="ksm-featured-feed-enclosure"
											name="<?php echo esc_attr( self::OPTION_KEY ); ?>[feed_include_enclosure]"
											value="1"
											<?php checked( $options['feed_include_enclosure'] ); ?>
										/>
										<span class="ksm-checkbox-row__label"><?php esc_html_e( 'Add RSS 2.0 media enclosure (url, length, MIME type) for the feed image', 'ksm-extensions' ); ?></span>
									</label>
									<label>
										<span class="ksm-input-label"><?php esc_html_e( 'Image size', 'ksm-extensions' ); ?></span>
										<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[feed_image_size]">
											<?php foreach ( $image_sizes as $size => $label ) : ?>
												<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $options['feed_image_size'], $size ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>
									<label>
										<span class="ksm-input-label"><?php esc_html_e( 'Caption source', 'ksm-extensions' ); ?></span>
										<p class="ksm-field__description" style="margin-top: 4px; margin-bottom: 8px;">
											<?php esc_html_e( 'Captions will automatically appear in feeds if available from the selected field.', 'ksm-extensions' ); ?>
										</p>
										<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[feed_caption_field]">
											<option value="caption" <?php selected( $options['feed_caption_field'], 'caption' ); ?>><?php esc_html_e( 'Caption (default)', 'ksm-extensions' ); ?></option>
											<option value="title" <?php selected( $options['feed_caption_field'], 'title' ); ?>><?php esc_html_e( 'Image title', 'ksm-extensions' ); ?></option>
											<option value="alt" <?php selected( $options['feed_caption_field'], 'alt' ); ?>><?php esc_html_e( 'Alt text', 'ksm-extensions' ); ?></option>
											<option value="description" <?php selected( $options['feed_caption_field'], 'description' ); ?>><?php esc_html_e( 'Description', 'ksm-extensions' ); ?></option>
										</select>
									</label>
								</div>
							</section>
						</div>

						<div class="ksm-card__footer">
							<?php submit_button( __( 'Save Settings', 'ksm-extensions' ), 'primary ksm-button', 'submit', false ); ?>
						</div>
					</form>
				</main>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize settings prior to saving.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults      = self::get_default_options();
		$sanitized     = array();
		$boolean_keys  = array(
			'admin_column',
			'auto_assign',
			'feed_enabled',
			'feed_include_enclosure',
		);

		foreach ( $boolean_keys as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}

		// Module is only activated via KSM Extensions → Modules; no separate "enable" in settings.
		$sanitized['enabled'] = true;

		$sanitized['post_types']        = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_text_field', $input['post_types'] ) : $defaults['post_types'];
		$sanitized['admin_column_size'] = isset( $input['admin_column_size'] ) ? sanitize_text_field( $input['admin_column_size'] ) : $defaults['admin_column_size'];

		$sanitized['fallback_image_id']  = isset( $input['fallback_image_id'] ) ? absint( $input['fallback_image_id'] ) : 0;
		$sanitized['fallback_image_url'] = isset( $input['fallback_image_url'] ) ? esc_url_raw( $input['fallback_image_url'] ) : '';

		$sanitized['feed_image_size']    = isset( $input['feed_image_size'] ) ? sanitize_text_field( $input['feed_image_size'] ) : $defaults['feed_image_size'];
		$sanitized['feed_caption_field'] = isset( $input['feed_caption_field'] ) ? sanitize_text_field( $input['feed_caption_field'] ) : $defaults['feed_caption_field'];

		$this->options = wp_parse_args( $sanitized, $defaults );

		return $this->options;
	}

	/**
	 * Register admin columns across enabled post types.
	 *
	 * @return void
	 */
	public function register_post_type_columns() {
		if ( empty( $this->options['enabled'] ) || empty( $this->options['admin_column'] ) ) {
			return;
		}

		$post_types              = $this->get_managed_post_types();
		$this->managed_post_types = $post_types;

		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_thumbnail_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_thumbnail_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable_column' ) );
		}

		add_action( 'pre_get_posts', array( $this, 'handle_admin_sorting' ) );
	}

	/**
	 * Add thumbnail column definition.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_thumbnail_column( $columns ) {
		$insertion = array( 'KSM_Extensions_featured_image' => __( 'Thumb', 'ksm-extensions' ) );

		return $this->array_insert_after( $columns, 'title', $insertion );
	}

	/**
	 * Register sortable column.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array
	 */
	public function register_sortable_column( $columns ) {
		$columns['KSM_Extensions_featured_image'] = 'KSM_Extensions_featured_image';
		return $columns;
	}

	/**
	 * Adjust query when sorting by featured image column.
	 *
	 * @param WP_Query $query Query instance.
	 * @return void
	 */
	public function handle_admin_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'KSM_Extensions_featured_image' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', '_thumbnail_id' );
		$query->set( 'orderby', 'meta_value_num' );
	}

	/**
	 * Render the thumbnail column.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_thumbnail_column( $column, $post_id ) {
		if ( 'KSM_Extensions_featured_image' !== $column ) {
			return;
		}

		echo $this->get_thumbnail_column_markup( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Insert array after specific key.
	 *
	 * @param array  $array Array to modify.
	 * @param string $key Key to insert after.
	 * @param array  $to_insert Data to insert.
	 * @return array
	 */
	private function array_insert_after( $array, $key, $to_insert ) {
		$result = array();

		foreach ( $array as $array_key => $array_value ) {
			$result[ $array_key ] = $array_value;
			if ( $array_key === $key ) {
				foreach ( $to_insert as $insert_key => $insert_value ) {
					$result[ $insert_key ] = $insert_value;
				}
			}
		}

		return $result;
	}

	/**
	 * Build the thumbnail column markup.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_thumbnail_column_markup( $post_id ) {
		$size          = $this->options['admin_column_size'] ?: 'thumbnail';
		$has_thumbnail = has_post_thumbnail( $post_id );
		$nonce         = wp_create_nonce( 'ksm_extensions_set_featured_image_' . $post_id );
		$attachment_id = $has_thumbnail ? (int) get_post_thumbnail_id( $post_id ) : 0;
		$preview       = '';

		if ( $has_thumbnail ) {
			$preview = wp_get_attachment_image( get_post_thumbnail_id( $post_id ), $size );
		} else {
			$fallback_preview = $this->get_image_for_post( $post_id, $size );
			if ( $fallback_preview ) {
				$preview = sprintf(
					'<img src="%s" alt="" style="max-width:80px;height:auto;" />',
					esc_url( $fallback_preview['url'] )
				);
			}
		}

		if ( empty( $preview ) ) {
			$preview = '<span class="ksm-thumb-placeholder">&mdash;</span>';
		}

		ob_start();
		?>
		<div class="ksm-thumb-cell" data-ksm-thumb="<?php echo esc_attr( $post_id ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>">
			<div class="ksm-thumb-preview">
				<button type="button" class="ksm-thumb-preview-button ksm-featured-image-toggle" data-post-id="<?php echo esc_attr( $post_id ); ?>" aria-label="<?php echo esc_attr( sprintf( _x( 'Set featured image for “%s”', 'KSM Extensions admin thumb', 'ksm-extensions' ), get_the_title( $post_id ) ) ); ?>">
					<?php echo wp_kses_post( $preview ); ?>
				</button>
				<div class="ksm-thumb-overlay" role="group" aria-label="<?php esc_attr_e( 'Featured image actions', 'ksm-extensions' ); ?>">
					<button
						type="button"
						class="ksm-icon-button ksm-featured-image-toggle"
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						aria-label="<?php echo esc_attr( $has_thumbnail ? __( 'Change featured image', 'ksm-extensions' ) : __( 'Set featured image', 'ksm-extensions' ) ); ?>"
					>
						<span class="screen-reader-text"><?php echo esc_html( $has_thumbnail ? __( 'Change featured image', 'ksm-extensions' ) : __( 'Set featured image', 'ksm-extensions' ) ); ?></span>
						<?php echo $this->get_thumb_action_icon( 'edit' ); ?>
					</button>
					<?php if ( $has_thumbnail ) : ?>
						<button
							type="button"
							class="ksm-icon-button ksm-featured-image-remove"
							data-post-id="<?php echo esc_attr( $post_id ); ?>"
							aria-label="<?php echo esc_attr__( 'Remove featured image', 'ksm-extensions' ); ?>"
						>
							<span class="screen-reader-text"><?php esc_html_e( 'Remove featured image', 'ksm-extensions' ); ?></span>
							<?php echo $this->get_thumb_action_icon( 'trash' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<span class="spinner"></span>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle AJAX featured image assignments from the list table.
	 *
	 * @return void
	 */
	public function handle_set_featured_image_request() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( __( 'Missing post ID.', 'ksm-extensions' ) );
		}

		check_ajax_referer( 'ksm_extensions_set_featured_image_' . $post_id, 'nonce' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( __( 'You do not have permission to edit this post.', 'ksm-extensions' ), 403 );
		}

		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		wp_send_json_success(
			array(
				'html' => $this->get_thumbnail_column_markup( $post_id ),
			)
		);
	}

	/**
	 * Output lightweight styles for the admin column UI.
	 *
	 * @return void
	 */
	public function print_admin_column_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		if ( ! in_array( $screen->post_type, $this->get_managed_post_types(), true ) ) {
			return;
		}

		?>
		<style id="ksm-featured-image-column">
			.column-KSM_Extensions_featured_image {
				width: 150px;
			}
			th.column-KSM_Extensions_featured_image {
				white-space: nowrap;
			}
			.ksm-thumb-cell {
				display: flex;
				flex-direction: column;
				gap: 4px;
				align-items: flex-start;
			}
			.ksm-thumb-preview {
				position: relative;
				display: inline-block;
				width: 100%;
			}
			.ksm-thumb-preview img {
				display: block;
				max-width: 100%;
				height: auto;
				border-radius: 4px;
				box-shadow: 0 1px 2px rgba(0,0,0,0.08);
			}
			.ksm-thumb-preview-button {
				background: none;
				border: 0;
				padding: 0;
				cursor: pointer;
				line-height: 0;
				display: block;
			}
			.ksm-thumb-preview-button:focus-visible {
				outline: 2px solid #2271b1;
				outline-offset: 2px;
				border-radius: 6px;
			}
			.ksm-thumb-overlay {
				position: absolute;
				inset: 0;
				background: rgba(0, 0, 0, 0.55);
				border-radius: 4px;
				display: flex;
				justify-content: center;
				align-items: center;
				opacity: 0;
				transition: opacity 0.2s ease;
			}
			.ksm-thumb-preview:hover .ksm-thumb-overlay,
			.ksm-thumb-preview:focus-within .ksm-thumb-overlay {
				opacity: 1;
			}
			.ksm-icon-button {
				border: none;
				background: rgba(255, 255, 255, 0.9);
				border-radius: 4px;
				padding: 6px;
				cursor: pointer;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				transition: background-color 0.15s ease, transform 0.15s ease;
				color: #1d2327;
			}
			.ksm-featured-image-toggle {
				color: #1d4ed8;
			}
			.ksm-featured-image-remove {
				color: #b91c1c;
			}
			.ksm-icon-button:hover {
				background: #fff;
				transform: translateY(-1px);
			}
			.ksm-icon-button:focus-visible {
				outline: 2px solid #2271b1;
				outline-offset: 2px;
			}
			.ksm-icon-button svg {
				width: 18px;
				height: 18px;
				fill: currentColor;
			}
			.ksm-icon-button .screen-reader-text {
				position: absolute;
				width: 1px;
				height: 1px;
				padding: 0;
				margin: -1px;
				overflow: hidden;
				clip: rect(0, 0, 0, 0);
				border: 0;
			}
			.ksm-thumb-cell .spinner {
				float: none;
				margin-top: 4px;
			}
			.ksm-thumb-placeholder {
				color: #757575;
			}
		</style>
		<?php
	}

	/**
	 * Maybe assign fallback featured image on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function maybe_assign_fallback_featured_image( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $this->options['enabled'] || ! $this->options['auto_assign'] ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->options['post_types'], true ) ) {
			return;
		}

		if ( get_post_thumbnail_id( $post_id ) ) {
			return;
		}

		if ( empty( $this->options['fallback_image_id'] ) ) {
			return;
		}

		set_post_thumbnail( $post_id, (int) $this->options['fallback_image_id'] );
	}

	/**
	 * Supply fallback thumbnail meta when no `_thumbnail_id` exists.
	 *
	 * @param mixed  $value    Existing metadata value.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single   Whether a single value was requested.
	 * @return mixed
	 */
	public function supply_fallback_thumbnail_meta( $value, $object_id, $meta_key, $single, $meta_type = 'post' ) {
		if ( 'post' !== $meta_type ) {
			return $value;
		}

		if ( '_thumbnail_id' !== $meta_key && ! empty( $meta_key ) ) {
			return $value;
		}

		if ( empty( $this->options['enabled'] ) || empty( $this->options['fallback_image_id'] ) ) {
			return $value;
		}

		$post_type = get_post_type( $object_id );
		if ( ! $post_type || ! in_array( $post_type, $this->get_managed_post_types(), true ) ) {
			return $value;
		}

		$fallback_id = (int) $this->options['fallback_image_id'];
		if ( ! $fallback_id ) {
			return $value;
		}

		$resolved = apply_filters( 'KSM_Extensions_featured_image_fallback_id', $fallback_id, $object_id );

		if ( $single ) {
			return $resolved;
		}

		return array( $resolved );
	}

	/**
	 * Ensure fallback HTML renders when needed.
	 *
	 * @param string       $html Existing HTML.
	 * @param int          $post_id Post ID.
	 * @param int          $post_thumbnail_id Thumbnail ID.
	 * @param string|array $size Size.
	 * @param array|string $attr Attributes.
	 * @return string
	 */
	public function maybe_render_fallback_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( $html ) {
			return $html;
		}

		$image = $this->get_image_for_post( $post_id, $size );
		if ( ! $image ) {
			return $html;
		}

		if ( ! empty( $image['id'] ) ) {
			return wp_get_attachment_image( $image['id'], $size, false, $attr );
		}

		$attrs = '';
		if ( is_array( $attr ) ) {
			foreach ( $attr as $attr_key => $attr_value ) {
				$attrs .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( $attr_value ) );
			}
		}

		return sprintf(
			'<img src="%s" alt="%s"%s />',
			esc_url( $image['url'] ),
			esc_attr( $image['alt'] ?? '' ),
			$attrs
		);
	}

	/**
	 * Inject featured image into RSS feed content/excerpt.
	 *
	 * @param string $content Feed content.
	 * @return string
	 */
	public function inject_featured_image_into_feed( $content ) {
		if ( empty( $this->options['enabled'] ) || empty( $this->options['feed_enabled'] ) ) {
			return $content;
		}

		global $post;

		if ( ! $post || ! in_array( $post->post_type, $this->options['post_types'], true ) ) {
			return $content;
		}

		$image_data = $this->get_image_for_post( $post->ID, $this->options['feed_image_size'] ?: 'full' );

		if ( ! $image_data ) {
			return $content;
		}

		$image_html = $this->build_feed_image_html( $image_data );

		// Always place featured image before content (standard RSS feed behavior)
		return $image_html . $content;
	}

	/**
	 * Output an RSS 2.0 enclosure for the same image used in feed content (featured or fallback).
	 *
	 * @since 2.0.12
	 *
	 * @return void
	 */
	public function maybe_output_rss_item_enclosure() {
		if ( empty( $this->options['enabled'] ) || empty( $this->options['feed_enabled'] ) || empty( $this->options['feed_include_enclosure'] ) ) {
			return;
		}

		global $post;

		if ( ! $post || ! in_array( $post->post_type, $this->options['post_types'], true ) ) {
			return;
		}

		$size       = $this->options['feed_image_size'] ? $this->options['feed_image_size'] : 'full';
		$image_data = $this->get_image_for_post( $post->ID, $size );

		if ( ! $image_data || empty( $image_data['url'] ) ) {
			return;
		}

		$url    = $image_data['url'];
		$length = 0;
		$mime   = '';

		if ( ! empty( $image_data['id'] ) ) {
			$file = $this->get_local_path_for_image_size( (int) $image_data['id'], $size );
			if ( $file && file_exists( $file ) && is_readable( $file ) ) {
				$length = (int) filesize( $file );
				$ft     = wp_check_filetype( basename( $file ) );
				if ( ! empty( $ft['type'] ) ) {
					$mime = $ft['type'];
				}
			}

			if ( '' === $mime ) {
				$mime = (string) get_post_mime_type( $image_data['id'] );
			}
		} else {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			$ft   = wp_check_filetype( basename( (string) $path ) );
			if ( ! empty( $ft['type'] ) ) {
				$mime = $ft['type'];
			}
		}

		if ( '' === $mime ) {
			$mime = 'application/octet-stream';
		}

		printf(
			"<enclosure url=\"%s\" length=\"%d\" type=\"%s\" />\n",
			esc_attr( esc_url( $url ) ),
			$length,
			esc_attr( $mime )
		);
	}

	/**
	 * Resolve the local filesystem path for an attachment image size.
	 *
	 * @since 2.0.12
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size Registered image size slug.
	 * @return string|false Absolute path, or false if unavailable.
	 */
	private function get_local_path_for_image_size( $attachment_id, $size ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return false;
		}

		if ( 'full' === $size || '' === $size ) {
			return $file;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $meta['sizes'][ $size ]['file'] ) ) {
			$sized = path_join( dirname( $file ), $meta['sizes'][ $size ]['file'] );
			if ( file_exists( $sized ) ) {
				return $sized;
			}
		}

		return $file;
	}

	/**
	 * Build feed-ready image HTML.
	 *
	 * @param array $image_data Image data array.
	 * @return string
	 */
	private function build_feed_image_html( $image_data ) {
		$url    = $image_data['url'];
		$width  = (int) $image_data['width'];
		$height = (int) $image_data['height'];
		$alt    = esc_attr( $image_data['alt'] );

		$html = sprintf(
			'<img src="%s" alt="%s" width="%d" height="%d" style="max-width:100%%;height:auto;" />',
			esc_url( $url ),
			$alt,
			$width,
			$height
		);

		// Automatically add caption if available from the selected field
		$caption = $this->get_caption_from_image( $image_data );
		if ( $caption ) {
			$html = sprintf(
				'<figure>%s<figcaption>%s</figcaption></figure>',
				$html,
				esc_html( $caption )
			);
		}

		return $html . "\n\n";
	}

	/**
	 * Retrieve caption text from image data.
	 *
	 * @param array $image_data Image data.
	 * @return string
	 */
	private function get_caption_from_image( $image_data ) {
		switch ( $this->options['feed_caption_field'] ) {
			case 'alt':
				return $image_data['alt'] ?? '';
			case 'caption':
				return $image_data['caption'] ?? '';
			case 'description':
				return $image_data['description'] ?? '';
			case 'title':
			default:
				return $image_data['title'] ?? '';
		}
	}

	/**
	 * Retrieve featured image or fallback data.
	 *
	 * @param int $post_id Post ID.
	 * @return array|false
	 */
	private function get_image_for_post( $post_id, $size = null ) {
		if ( null === $size ) {
			$size = $this->options['feed_image_size'] ?: 'full';
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			$data = $this->build_image_data_from_attachment( $thumbnail_id, $size );
			if ( $data ) {
				return $data;
			}
		}

		if ( $this->options['fallback_image_id'] ) {
			return $this->build_image_data_from_attachment( (int) $this->options['fallback_image_id'], $size );
		}

		if ( $this->options['fallback_image_url'] ) {
			return array(
				'id'          => 0,
				'url'         => $this->options['fallback_image_url'],
				'width'       => 0,
				'height'      => 0,
				'alt'         => '',
				'title'       => '',
				'caption'     => '',
				'description' => '',
			);
		}

		return false;
	}

	/**
	 * Build image data array from attachment ID.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size Image size.
	 * @return array|false
	 */
	private function build_image_data_from_attachment( $attachment_id, $size ) {
		$image = wp_get_attachment_image_src( $attachment_id, $size );

		if ( ! $image ) {
			return false;
		}

		$attachment = get_post( $attachment_id );

		return array(
			'id'          => $attachment_id,
			'url'         => $image[0],
			'width'       => $image[1],
			'height'      => $image[2],
			'alt'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'title'       => $attachment ? $attachment->post_title : '',
			'caption'     => $attachment ? $attachment->post_excerpt : '',
			'description' => $attachment ? $attachment->post_content : '',
		);
	}

	/**
	 * Retrieve available image sizes keyed by slug.
	 *
	 * @return array
	 */
	private function get_available_image_sizes() {
		$sizes  = array();
		$labels = array(
			'thumbnail'    => __( 'Thumbnail', 'ksm-extensions' ),
			'medium'       => __( 'Medium', 'ksm-extensions' ),
			'medium_large' => __( 'Medium Large', 'ksm-extensions' ),
			'large'        => __( 'Large', 'ksm-extensions' ),
			'full'         => __( 'Full Size', 'ksm-extensions' ),
		);

		// Get all intermediate image sizes
		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = $labels[ $size ] ?? ucwords( str_replace( '_', ' ', $size ) );
		}

		// Always include "full" size (get_intermediate_image_sizes() doesn't include it)
		$sizes['full'] = $labels['full'];

		return $sizes;
	}
}

