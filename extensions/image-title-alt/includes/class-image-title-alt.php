<?php
/**
 * Filename: class-image-title-alt.php
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 2.1.6
 * Last Modified: 20/11/2025
 * Description: Cleans attachment titles, captions, descriptions, alt text, and filenames.
 *
 * Credits:
 * - Based on the "Auto Image Title & Alt" plugin by Diego de Guindos (GPLv2+).
 *   Functionality has been reimplemented, modernised, and integrated into KSM Extensions.
 *
 * @package KSM_Extensions
 * @subpackage Modules\ImageTitleAlt
 * @since 1.7.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image metadata optimizer.
 *
 * @since 1.7.0
 */
class KSM_Extensions_ImageTitleAlt {

	const OPTION_KEY = 'ksm_extensions_image_title_alt';

	/**
	 * Core instance (unused for now, reserved for future integrations).
	 *
	 * @var KSM_Extensions_MediaCounter_Core|null
	 */
	private $core;

	/**
	 * Stored options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor.
	 */
	public function __construct( $core = null ) {
		$this->core    = $core;
		$this->options = $this->get_options();
		add_action( 'update_option_' . self::OPTION_KEY, array( $this, 'refresh_options' ) );
		$this->register_settings_hooks();
		$this->register_runtime_hooks();
	}

	/**
	 * Refresh cached options.
	 *
	 * @return void
	 */
	public function refresh_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Get default option values.
	 *
	 * @return array
	 */
	private function get_default_options() {
		return array(
			'enabled'       => false,
			'fields'        => array( 'post_title', 'alt_text' ),
			'capitalization'=> 'ucwords',
			'rename_mode'   => 'images_only',
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
	 * Register settings hooks.
	 *
	 * @return void
	 */
	private function register_settings_hooks() {
		add_action( 'ksm_extensions_register_settings_page', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register runtime hooks when enabled.
	 *
	 * @return void
	 */
	private function register_runtime_hooks() {
		if ( empty( $this->options['enabled'] ) ) {
			return;
		}

		add_action( 'add_attachment', array( $this, 'handle_attachment' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'rename_uploaded_files' ) );
	}

	/**
	 * Register settings page.
	 * Only registers if the module is activated.
	 *
	 * @param KSM_Extensions_Admin $admin The admin instance.
	 * @return void
	 */
	public function register_settings_page( $admin ) {
		// Register via KSM Extensions admin class (adds to WordPress Settings menu)
		// Only registers if module is activated
		$admin->register_module_settings_page(
			__( 'Image Title & Alt', 'ksm-extensions' ),
			__( 'Image Title & Alt', 'ksm-extensions' ),
			'ksm-extensions-image-title-alt',
			array( $this, 'render_settings_page' ),
			'image-title-alt'
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ksm_extensions_image_title_alt',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_options(),
			)
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input Raw data.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = $this->get_default_options();

		$sanitized = array(
			'enabled'         => true, // Module is only activated via KSM Extensions → Modules.
			'fields'          => array(),
			'capitalization'  => $defaults['capitalization'],
			'rename_mode'     => $defaults['rename_mode'],
		);

		$allowed_fields = array( 'post_title', 'alt_text', 'post_excerpt', 'post_content' );
		if ( ! empty( $input['fields'] ) && is_array( $input['fields'] ) ) {
			$sanitized['fields'] = array_values(
				array_intersect( $allowed_fields, array_map( 'sanitize_text_field', $input['fields'] ) )
			);
		}

		if ( empty( $sanitized['fields'] ) ) {
			$sanitized['fields'] = array();
		}

		$allowed_caps = array( 'ucwords', 'lowercase', 'uppercase', 'none' );
		if ( ! empty( $input['capitalization'] ) ) {
			$cap = sanitize_text_field( $input['capitalization'] );
			$sanitized['capitalization'] = in_array( $cap, $allowed_caps, true ) ? $cap : $defaults['capitalization'];
		}

		$allowed_modes = array( 'images_only', 'all_files', 'none' );
		if ( ! empty( $input['rename_mode'] ) ) {
			$mode = sanitize_text_field( $input['rename_mode'] );
			$sanitized['rename_mode'] = in_array( $mode, $allowed_modes, true ) ? $mode : $defaults['rename_mode'];
		}

		return $sanitized;
	}

	/**
	 * Render settings UI.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$this->refresh_options();
		$fields = array(
			'post_title'   => __( 'Image Title', 'ksm-extensions' ),
			'alt_text'     => __( 'Alt Text', 'ksm-extensions' ),
			'post_excerpt' => __( 'Caption (Excerpt)', 'ksm-extensions' ),
			'post_content' => __( 'Description (Content)', 'ksm-extensions' ),
		);

		$capitalizations = array(
			'ucwords'   => __( 'Capitalized (Default)', 'ksm-extensions' ),
			'lowercase' => __( 'lowercase', 'ksm-extensions' ),
			'uppercase' => __( 'UPPERCASE', 'ksm-extensions' ),
			'none'      => __( 'Do not modify', 'ksm-extensions' ),
		);

		$rename_modes = array(
			'images_only' => __( 'Rename images only', 'ksm-extensions' ),
			'all_files'   => __( 'Rename all uploaded files', 'ksm-extensions' ),
			'none'        => __( 'Do not rename files', 'ksm-extensions' ),
		);

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
							<h1><?php esc_html_e( 'KSM Extensions Image Title & Alt', 'ksm-extensions' ); ?></h1>
							<p class="ksm-admin__subhead">
								<?php esc_html_e( 'Transform messy filenames into polished titles, captions, descriptions, and alt text automatically on upload.', 'ksm-extensions' ); ?>
							</p>
						</div>
					</header>

					<form method="post" action="options.php" class="ksm-card">
						<?php settings_fields( 'ksm_extensions_image_title_alt' ); ?>

						<div class="ksm-card__body">
							<section class="ksm-section">
								<div class="ksm-form-grid">
									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Fields to update', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Select which attachment fields inherit the cleaned filename.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<div class="ksm-stack">
											<?php foreach ( $fields as $key => $label ) : ?>
												<label class="ksm-checkbox ksm-checkbox--stacked">
													<input
														type="checkbox"
														name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fields][]"
														value="<?php echo esc_attr( $key ); ?>"
														<?php checked( in_array( $key, $this->options['fields'], true ) ); ?>
													/>
													<span class="ksm-checkbox__checkmark"></span>
													<span><?php echo esc_html( $label ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>

									<div class="ksm-fieldset">
										<div class="ksm-fieldset__header">
											<h3><?php esc_html_e( 'Capitalization', 'ksm-extensions' ); ?></h3>
											<p class="ksm-field__description">
												<?php esc_html_e( 'Choose how titles and alt text are cased before saving.', 'ksm-extensions' ); ?>
											</p>
										</div>
										<label class="ksm-input-block" for="ksm-image-meta-capitalization">
											<span class="ksm-input-label"><?php esc_html_e( 'Capitalization', 'ksm-extensions' ); ?></span>
											<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[capitalization]" id="ksm-image-meta-capitalization">
												<?php foreach ( $capitalizations as $value => $label ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $this->options['capitalization'], $value ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</label>
									</div>
								</div>
							</section>

							<section class="ksm-section">
								<div class="ksm-fieldset">
									<div class="ksm-fieldset__header">
										<h3><?php esc_html_e( 'Renaming mode', 'ksm-extensions' ); ?></h3>
										<p class="ksm-field__description">
											<?php esc_html_e( 'Keep uploads tidy by rewriting filenames into lowercase slugs, or skip renaming entirely.', 'ksm-extensions' ); ?>
										</p>
									</div>
									<div class="ksm-stack ksm-radio-list">
										<?php foreach ( $rename_modes as $value => $label ) : ?>
											<label>
												<input
													type="radio"
													name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rename_mode]"
													value="<?php echo esc_attr( $value ); ?>"
													<?php checked( $this->options['rename_mode'], $value ); ?>
												/>
												<span><?php echo esc_html( $label ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<p class="description"><?php esc_html_e( 'Renamed files are converted to lowercase alphanumeric slugs for SEO-friendly URLs.', 'ksm-extensions' ); ?></p>
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
	 * Handle attachment uploads.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function handle_attachment( $attachment_id ) {
		$this->apply_to_attachment( $attachment_id );
	}

	/**
	 * Apply metadata updates to an attachment.
	 *
	 * @param int        $attachment_id Attachment ID.
	 * @param array|null $fields_override Override fields.
	 * @param string|null $capitalization_override Override capitalization.
	 * @return void
	 */
	public function apply_to_attachment( $attachment_id, $fields_override = null, $capitalization_override = null ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$title = $this->build_clean_title( $attachment_id );
		if ( '' === $title ) {
			return;
		}

		$fields = is_array( $fields_override ) ? $fields_override : ( $this->options['fields'] ?? array() );
		if ( empty( $fields ) ) {
			return;
		}

		$format = $capitalization_override ?: ( $this->options['capitalization'] ?? 'ucwords' );
		$title  = $this->apply_capitalization( $title, $format );

		$update_args = array(
			'ID' => $attachment_id,
		);

		if ( in_array( 'post_title', $fields, true ) ) {
			$update_args['post_title'] = $title;
		}
		if ( in_array( 'post_excerpt', $fields, true ) ) {
			$update_args['post_excerpt'] = $title;
		}
		if ( in_array( 'post_content', $fields, true ) ) {
			$update_args['post_content'] = $title;
		}

		if ( count( $update_args ) > 1 ) {
			wp_update_post( $update_args );
		}

		if ( in_array( 'alt_text', $fields, true ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );
		}
	}

	/**
	 * Build the clean title from filename.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private function build_clean_title( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path ) {
			$post = get_post( $attachment_id );
			$path = $post ? $post->post_title : '';
		}

		if ( empty( $path ) ) {
			return '';
		}

		$filename = pathinfo( $path, PATHINFO_FILENAME );
		$clean    = preg_replace( '%\s*[-_\s]+\s*%', ' ', $filename );
		return trim( $clean );
	}

	/**
	 * Apply capitalization.
	 *
	 * @param string $title Title.
	 * @param string $format Format.
	 * @return string
	 */
	private function apply_capitalization( $title, $format ) {
		switch ( $format ) {
			case 'lowercase':
				return strtolower( $title );
			case 'uppercase':
				return strtoupper( $title );
			case 'ucwords':
				return ucwords( strtolower( $title ) );
			default:
				return $title;
		}
	}

	/**
	 * Rename uploaded files when configured.
	 *
	 * @param array $file Upload array.
	 * @return array
	 */
	public function rename_uploaded_files( $file ) {
		$mode = $this->options['rename_mode'] ?? 'images_only';

		if ( 'none' === $mode ) {
			return $file;
		}

		$is_image = 0 === strpos( $file['type'], 'image/' );
		if ( 'images_only' === $mode && ! $is_image ) {
			return $file;
		}

		$info      = pathinfo( $file['name'] );
		$filename  = strtolower( $info['filename'] ?? '' );
		$extension = isset( $info['extension'] ) ? '.' . $info['extension'] : '';

		$filename = remove_accents( $filename );
		$filename = preg_replace( '/[^a-z0-9]+/', '-', $filename );
		$filename = trim( $filename, '-' );

		if ( '' === $filename ) {
			$filename = 'file';
		}

		$file['name'] = $filename . $extension;
		return $file;
	}

}

