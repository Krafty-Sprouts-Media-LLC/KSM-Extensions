<?php
/**
 * Filename: class-auto-upload-images.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 2.1.6
 * Last Modified: 20/11/2025
 * Description: Automatically imports external images found in post content.
 *
 * Credits:
 * - Derived from the GPLv2+ Auto Upload Images plugin by Ali Irani (https://github.com/airani/wp-auto-upload).
 *   Completely refactored, modernised, and integrated into KSM Extensions with configuration options preserved.
 *
 * @package KSM_Extensions
 * @subpackage Modules\AutoUploadImages
 * @since 1.6.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto Upload Images module for KSM Extensions.
 *
 * @since 1.6.0
 */
class KSM_Extensions_AutoUploadImages {

	/**
	 * Option key.
	 */
	const OPTION_KEY = 'ksm_extensions_auto_upload_images';

	/**
	 * Cached options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = $this->get_options();
		$this->register_hooks();
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	private function get_default_options() {
		return array(
			'enabled'           => false,
			'base_url'          => home_url(),
			'image_name'        => '%filename%',
			'alt_name'          => '%image_alt%',
			'max_width'         => '',
			'max_height'        => '',
			'exclude_domains'   => '',
			'exclude_posttypes' => array(),
		);
	}

	/**
	 * Retrieve stored options.
	 *
	 * @return array
	 */
	private function get_options() {
		$stored = get_option( self::OPTION_KEY, array() );
		$opts   = wp_parse_args( $stored, $this->get_default_options() );
		// Module is only loaded when activated on KSM Extensions → Modules; no separate "enable" needed.
		$opts['enabled'] = true;
		return $opts;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_filter( 'wp_insert_post_data', array( $this, 'filter_post_data' ), 10, 2 );
		add_action( 'ksm_extensions_register_settings_page', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_submission' ) );
	}

	/**
	 * Whether the module is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return ! empty( $this->options['enabled'] );
	}

	/**
	 * Filter post data and import images if needed.
	 *
	 * @param array $data    Sanitized post data.
	 * @param array $postarr Raw post array.
	 * @return array
	 */
	public function filter_post_data( $data, $postarr ) {
		if ( ! $this->is_enabled() ) {
			return $data;
		}

		if ( $this->should_skip_post( $postarr ) ) {
			return $data;
		}

		$processed = $this->process_content_images( $postarr );
		if ( $processed ) {
			$data['post_content'] = $processed;
		}

		return $data;
	}

	/**
	 * Determine if the post should be skipped.
	 *
	 * @param array $postarr Post data.
	 * @return bool
	 */
	private function should_skip_post( $postarr ) {
		if ( empty( $postarr['post_content'] ) ) {
		 return true;
		}

		if ( wp_is_post_revision( $postarr['ID'] ?? 0 ) || wp_is_post_autosave( $postarr['ID'] ?? 0 ) ) {
			return true;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		$excluded = $this->options['exclude_posttypes'] ?? array();
		if ( is_array( $excluded ) && ! empty( $postarr['post_type'] ) && in_array( $postarr['post_type'], $excluded, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Process the post content and import images.
	 *
	 * @param array $postarr Post array.
	 * @return string|false
	 */
	private function process_content_images( $postarr ) {
		$content = isset( $postarr['post_content'] ) ? $postarr['post_content'] : '';
		if ( empty( $content ) ) {
			return false;
		}

		$images = $this->find_image_candidates( wp_unslash( $content ) );
		if ( empty( $images ) ) {
			return false;
		}

		$updated = $content;
		foreach ( $images as $image ) {
			$handler = new KSM_Extensions_AutoUploadImages_Handler(
				$image['url'],
				$image['alt'],
				$postarr,
				$this->options
			);

			$result = $handler->process();
			if ( empty( $result['url'] ) ) {
				continue;
			}

			$replacement_url = $this->build_final_image_url( $result['url'] );
			$updated         = preg_replace(
				'/' . preg_quote( $image['url'], '/' ) . '/',
				$replacement_url,
				$updated
			);

			if ( ! empty( $image['alt'] ) && ! empty( $result['alt'] ) ) {
				$updated = preg_replace(
					'/alt=["\']' . preg_quote( $image['alt'], '/' ) . '["\']/',
					'alt="' . esc_attr( $result['alt'] ) . '"',
					$updated,
					1
				);
			}
		}

		return $updated;
	}

	/**
	 * Build the final image URL, honouring base URL overrides.
	 *
	 * @param string $uploaded_url Uploaded URL.
	 * @return string
	 */
	private function build_final_image_url( $uploaded_url ) {
		$base_url = trim( $this->options['base_url'] );
		if ( empty( $base_url ) ) {
			return $uploaded_url;
		}

		$path = wp_parse_url( $uploaded_url, PHP_URL_PATH );
		if ( empty( $path ) ) {
			return $uploaded_url;
		}

		$base = trailingslashit( $base_url );

		return untrailingslashit( $base ) . $path;
	}

	/**
	 * Find image URLs and alt attributes within content.
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	private function find_image_candidates( $content ) {
		$matches = array();

		preg_match_all( '/<img[^>]+>/i', $content, $nodes );
		if ( empty( $nodes[0] ) ) {
			return array();
		}

		foreach ( $nodes[0] as $node ) {
			$src = $this->extract_attribute( $node, 'src' );
			if ( empty( $src ) ) {
				continue;
			}

			$matches[] = array(
				'url' => esc_url_raw( $src ),
				'alt' => $this->extract_attribute( $node, 'alt' ),
			);

			$srcset = $this->extract_attribute( $node, 'srcset' );
			if ( $srcset ) {
				$srcset_urls = preg_split( '/\s*,\s*/', $srcset );
				foreach ( $srcset_urls as $srcset_entry ) {
					$srcset_parts = preg_split( '/\s+/', trim( $srcset_entry ) );
					if ( ! empty( $srcset_parts[0] ) ) {
						$matches[] = array(
							'url' => esc_url_raw( $srcset_parts[0] ),
							'alt' => $this->extract_attribute( $node, 'alt' ),
						);
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Extract a single attribute from an HTML string.
	 *
	 * @param string $html HTML snippet.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function extract_attribute( $html, $attribute ) {
		$pattern = sprintf( '/%s=["\']([^"\']+)["\']/i', preg_quote( $attribute, '/' ) );
		if ( preg_match( $pattern, $html, $matches ) ) {
			return html_entity_decode( $matches[1], ENT_QUOTES );
		}

		return null;
	}

	/**
	 * Register the options page.
	 * Only registers if the module is activated.
	 *
	 * @param KSM_Extensions_Admin $admin The admin instance.
	 * @return void
	 */
	public function register_settings_page( $admin ) {
		// Register via KSM Extensions admin class (adds to WordPress Settings menu)
		// Only registers if module is activated
		$admin->register_module_settings_page(
			__( 'Auto Upload Images', 'ksm-extensions' ),
			__( 'Auto Upload Images', 'ksm-extensions' ),
			'ksm-extensions-auto-upload-images',
			array( $this, 'render_settings_page' ),
			'auto-upload-images'
		);
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	public function handle_settings_submission() {
		if ( empty( $_POST['ksm_extensions_auto_upload_images_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['ksm_extensions_auto_upload_images_nonce'] ), 'ksm_extensions_auto_upload_images' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$raw = wp_unslash( $_POST[ self::OPTION_KEY ] ?? array() );
		$this->options = $this->sanitize_settings( $raw );
		update_option( self::OPTION_KEY, $this->options );

		add_settings_error(
			'ksm_extensions_auto_upload_images',
			'ksm_extensions_auto_upload_images',
			esc_html__( 'Auto Upload Images settings saved.', 'ksm-extensions' ),
			'updated'
		);
	}

	/**
	 * Sanitize settings payload.
	 *
	 * @param array $input Raw values.
	 * @return array
	 */
	private function sanitize_settings( $input ) {
		$defaults = $this->get_default_options();

		$sanitized = array(
			'enabled'           => true, // Module is only activated via KSM Extensions → Modules.
			'base_url'          => esc_url_raw( $input['base_url'] ?? $defaults['base_url'] ),
			'image_name'        => sanitize_text_field( $input['image_name'] ?? $defaults['image_name'] ),
			'alt_name'          => sanitize_text_field( $input['alt_name'] ?? $defaults['alt_name'] ),
			'max_width'         => $input['max_width'] ? absint( $input['max_width'] ) : '',
			'max_height'        => $input['max_height'] ? absint( $input['max_height'] ) : '',
			'exclude_domains'   => sanitize_textarea_field( $input['exclude_domains'] ?? '' ),
			'exclude_posttypes' => array(),
		);

		if ( ! empty( $input['exclude_posttypes'] ) && is_array( $input['exclude_posttypes'] ) ) {
			$sanitized['exclude_posttypes'] = array_map( 'sanitize_text_field', $input['exclude_posttypes'] );
		}

		return $sanitized;
	}

	/**
	 * Render the options page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$all_post_types = get_post_types( array( 'public' => true ), 'objects' );

		settings_errors( 'ksm_extensions_auto_upload_images' );

		// Helper to check if a module is enabled
		$is_module_active = function( $option_key ) {
			$opt = get_option( $option_key, array() );
			return ! empty( $opt['enabled'] );
		};
		?>
		<div class="wrap ksm-wrap ksm-settings-page">
			<div class="ksm-admin ksm-admin-layout">
				<!-- Sidebar Navigation -->
				<!-- Sidebar Navigation -->
				<!-- Sidebar removed - KSM Extensions uses standard WordPress admin layout -->

				<main class="ksm-main-content">
					<header class="ksm-admin__hero">
						<div>
							<h1><?php esc_html_e( 'KSM Extensions Auto Upload Images', 'ksm-extensions' ); ?></h1>
							<p class="ksm-admin__subhead">
								<?php esc_html_e( 'Detect remote media inside post content, import it to your library, and rewrite URLs automatically with CDN-friendly naming.', 'ksm-extensions' ); ?>
							</p>
						</div>
					</header>

					<form method="post" action="" class="ksm-card">
						<?php wp_nonce_field( 'ksm_extensions_auto_upload_images', 'ksm_extensions_auto_upload_images_nonce' ); ?>

						<div class="ksm-card__body">
							<section class="ksm-section">
								<div class="ksm-form-grid">
									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Where uploads land', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Override the final URL to point at a CDN or leave empty to keep WordPress defaults.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<label class="ksm-input-block" for="ksm-auto-base-url">
											<span class="ksm-input-label"><?php esc_html_e( 'Base URL override', 'ksm-extensions' ); ?></span>
											<input
												type="text"
												id="ksm-auto-base-url"
												class="regular-text"
												name="<?php echo esc_attr( self::OPTION_KEY ); ?>[base_url]"
												value="<?php echo esc_attr( $this->options['base_url'] ); ?>"
												placeholder="https://cdn.example.com"
											/>
											<p class="description"><?php esc_html_e( 'Leave empty to use the default uploads URL.', 'ksm-extensions' ); ?></p>
										</label>
									</div>

									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Human-friendly names', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Use placeholders to craft consistent filenames and alt text as images are imported.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<label class="ksm-input-block" for="ksm-auto-image-name">
											<span class="ksm-input-label"><?php esc_html_e( 'Filename template', 'ksm-extensions' ); ?></span>
											<input
												type="text"
												id="ksm-auto-image-name"
												class="regular-text"
												name="<?php echo esc_attr( self::OPTION_KEY ); ?>[image_name]"
												value="<?php echo esc_attr( $this->options['image_name'] ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'Placeholders: %filename%, %image_alt%, %url%, %today_date%, %post_date%, %random%, %timestamp%, %postname%, %post_id%.', 'ksm-extensions' ); ?>
											</p>
										</label>
										<label class="ksm-input-block" for="ksm-auto-alt-name">
											<span class="ksm-input-label"><?php esc_html_e( 'Alt text template', 'ksm-extensions' ); ?></span>
											<input
												type="text"
												id="ksm-auto-alt-name"
												class="regular-text"
												name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alt_name]"
												value="<?php echo esc_attr( $this->options['alt_name'] ); ?>"
											/>
										</label>
									</div>
								</div>
							</section>

							<section class="ksm-section">
								<div class="ksm-form-grid">
									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Constrain imports', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Optionally throttle oversized media during import. Leave blank to keep originals.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<div class="ksm-input-row">
											<label>
												<span class="ksm-input-label"><?php esc_html_e( 'Max width (px)', 'ksm-extensions' ); ?></span>
												<input
													type="number"
													class="small-text"
													name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_width]"
													value="<?php echo esc_attr( $this->options['max_width'] ); ?>"
												/>
											</label>
											<label>
												<span class="ksm-input-label"><?php esc_html_e( 'Max height (px)', 'ksm-extensions' ); ?></span>
												<input
													type="number"
													class="small-text"
													name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_height]"
													value="<?php echo esc_attr( $this->options['max_height'] ); ?>"
												/>
											</label>
										</div>
									</div>

									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'What to skip', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Protect specific domains or post types from the importer to avoid surprises.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<label class="ksm-input-block" for="ksm-auto-exclude-domains">
											<span class="ksm-input-label"><?php esc_html_e( 'Exclude domains (one per line)', 'ksm-extensions' ); ?></span>
											<textarea
												class="large-text code"
												id="ksm-auto-exclude-domains"
												rows="4"
												name="<?php echo esc_attr( self::OPTION_KEY ); ?>[exclude_domains]"
											><?php echo esc_textarea( $this->options['exclude_domains'] ); ?></textarea>
										</label>

										<div class="ksm-pill-list">
											<?php foreach ( $all_post_types as $post_type ) : ?>
												<label class="ksm-checkbox">
													<input
														type="checkbox"
														name="<?php echo esc_attr( self::OPTION_KEY ); ?>[exclude_posttypes][]"
														value="<?php echo esc_attr( $post_type->name ); ?>"
														<?php checked( in_array( $post_type->name, $this->options['exclude_posttypes'], true ) ); ?>
													/>
													<span class="ksm-checkbox__checkmark"></span>
													<span><?php echo esc_html( $post_type->labels->singular_name ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>
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
}

