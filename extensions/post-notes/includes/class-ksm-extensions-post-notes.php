<?php
/**
 * Post Notes module functionality.
 *
 * @package KSM_Extensions
 * @subpackage Modules\PostNotes
 * @author Krafty Sprouts Media, LLC
 * @version 1.0.0
 * @since 1.3.0
 * @last_modified 26/11/2025
 * 
 * @description Allows users to add notes to posts/articles for quick reference and organization.
 */

namespace KSM_Extensions\Modules\PostNotes;

/**
 * Post Notes module class.
 *
 * @since 1.3.0
 */
class KSM_Extensions_PostNotes
{

	/**
	 * The meta key used to store post notes.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	private $meta_key = '_ksm_extensions_post_note';

	/**
	 * Initialize the module.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function init()
	{
		// Register meta box for posts and pages.
		add_action('add_meta_boxes', array($this, 'register_meta_box'));

		// Save note when post is saved.
		add_action('save_post', array($this, 'save_note'), 10, 2);

		// Register Notes column for posts and pages.
		add_filter('manage_posts_columns', array($this, 'register_column'));
		add_filter('manage_pages_columns', array($this, 'register_column'));

		// Display Notes column content.
		add_action('manage_posts_custom_column', array($this, 'display_column'), 10, 2);
		add_action('manage_pages_custom_column', array($this, 'display_column'), 10, 2);

		// Add inline styles for badge.
		add_action('admin_head', array($this, 'add_admin_styles'));
	}

	/**
	 * Register the meta box for posts and pages.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function register_meta_box()
	{
		// Add meta box to posts.
		add_meta_box(
			'ksm_extensions_post_notes',
			__('Post Note', 'ksm-extensions'),
			array($this, 'render_meta_box'),
			'post',
			'side',
			'default'
		);

		// Add meta box to pages.
		add_meta_box(
			'ksm_extensions_post_notes',
			__('Post Note', 'ksm-extensions'),
			array($this, 'render_meta_box'),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @since 1.3.0
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function render_meta_box($post)
	{
		// Add nonce for security.
		wp_nonce_field('ksm_extensions_post_notes_save', 'ksm_extensions_post_notes_nonce');

		// Get existing note.
		$note = get_post_meta($post->ID, $this->meta_key, true);

		// Display textarea.
		?>
		<p>
			<label for="ksm_extensions_post_note">
				<?php esc_html_e('Add a note for quick reference:', 'ksm-extensions'); ?>
			</label>
		</p>
		<textarea id="ksm_extensions_post_note" name="ksm_extensions_post_note" rows="5" style="width: 100%;"
			placeholder="<?php esc_attr_e('Enter your note here...', 'ksm-extensions'); ?>"><?php echo esc_textarea($note); ?></textarea>
		<p class="description">
			<?php esc_html_e('This note will be visible in the post list table as an indicator.', 'ksm-extensions'); ?>
		</p>
		<?php
	}

	/**
	 * Save the note when post is saved.
	 *
	 * @since 1.3.0
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 * @return void
	 */
	public function save_note($post_id, $post)
	{
		// Check if nonce is set.
		if (!isset($_POST['ksm_extensions_post_notes_nonce'])) {
			return;
		}

		// Verify nonce.
		if (!wp_verify_nonce($_POST['ksm_extensions_post_notes_nonce'], 'ksm_extensions_post_notes_save')) {
			return;
		}

		// Check if this is an autosave.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check user capabilities.
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Check if post revision.
		if (wp_is_post_revision($post_id)) {
			return;
		}

		// Get the note value.
		$note = isset($_POST['ksm_extensions_post_note']) ? $_POST['ksm_extensions_post_note'] : '';

		// Sanitize the note.
		$note = sanitize_textarea_field($note);

		// Save or delete the note.
		if (!empty($note)) {
			update_post_meta($post_id, $this->meta_key, $note);
		} else {
			delete_post_meta($post_id, $this->meta_key);
		}
	}

	/**
	 * Register the Notes column.
	 *
	 * @since 1.3.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns array.
	 */
	public function register_column($columns)
	{
		// Insert Notes column after the title column.
		$new_columns = array();
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ('title' === $key) {
				$new_columns['ksm_extensions_notes'] = __('Notes', 'ksm-extensions');
			}
		}
		return $new_columns;
	}

	/**
	 * Display the Notes column content.
	 *
	 * @since 1.3.0
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public function display_column($column_name, $post_id)
	{
		if ('ksm_extensions_notes' !== $column_name) {
			return;
		}

		// Get the note.
		$note = get_post_meta($post_id, $this->meta_key, true);

		if (!empty($note)) {
			// Display badge indicator.
			$note_preview = wp_trim_words($note, 10);
			echo '<span class="ksm-note-badge" title="' . esc_attr($note) . '">';
			echo '<span class="dashicons dashicons-edit"></span>';
			echo '</span>';
		} else {
			// Display empty cell.
			echo '—';
		}
	}

	/**
	 * Add admin styles for the note badge.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function add_admin_styles()
	{
		?>
		<style type="text/css">
			.ksm-note-badge {
				display: inline-block;
				color: #2271b1;
				cursor: help;
			}

			.ksm-note-badge .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
				line-height: 1;
			}

			.ksm-note-badge:hover {
				color: #135e96;
			}
		</style>
		<?php
	}
}

