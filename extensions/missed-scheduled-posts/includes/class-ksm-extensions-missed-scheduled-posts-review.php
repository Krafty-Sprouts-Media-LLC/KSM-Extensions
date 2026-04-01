<?php
/**
 * Review prompt functionality for Missed Scheduled Posts module.
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
 * Review prompt class for Missed Scheduled Posts module.
 *
 * This class adds two actions to WordPress: `admin_notices` and `wp_ajax_ksm_extensions_mss_dismiss_review_prompt`.
 * The `admin_notices` action displays a notice in the WordPress admin area asking the user to review the module.
 * The `wp_ajax_ksm_extensions_mss_dismiss_review_prompt` action dismisses the review prompt when an AJAX request is made.
 *
 * @since 1.1.0
 */
class KSM_Extensions_MissedScheduledPosts_Review {

	/**
	 * Load hooks for review functionality.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function load_hooks() {
		add_action( 'admin_notices', array( $this, 'review_notice' ) );
		add_action( 'wp_ajax_ksm_extensions_mss_dismiss_review_prompt', array( $this, 'dismiss_review_prompt' ) );
	}

	/**
	 * Dismisses the review prompt for the module.
	 *
	 * It is called when an Ajax request is made with the
	 * wp_ajax_ksm_extensions_mss_dismiss_review_prompt action. This function updates the
	 * option to indicate that the review prompt has been dismissed and
	 * returns a JSON response with a success message.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function dismiss_review_prompt() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ksm_extensions_mss_dismiss_review_prompt' ) ) {
			die( 'Failed' );
		}

		if ( ! empty( $_POST['type'] ) ) {
			if ( 'remove' === $_POST['type'] ) {
				update_option( 'ksm_extensions_mss_review_prompt_removed', true );
				wp_send_json_success(
					array(
						'status' => 'removed',
					)
				);
			} elseif ( 'delay' === $_POST['type'] ) {
				update_option(
					'ksm_extensions_mss_review_prompt_delay',
					array(
						'delayed_until' => time() + WEEK_IN_SECONDS,
					)
				);
				wp_send_json_success(
					array(
						'status' => 'delayed',
					)
				);
			}
		}
	}

	/**
	 * Displays a notice in the WordPress admin area asking the user to review the module.
	 *
	 * This function is hooked to the `admin_notices` action and is called when the user visits the WordPress admin area.
	 * It checks if the user has dismissed the review prompt before and if not, displays a notice asking the user to review the module.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function review_notice() {

		// Only show to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Notice has been delayed.
		$delayed_option = get_option( 'ksm_extensions_mss_review_prompt_delay' );
		if ( ! empty( $delayed_option['delayed_until'] ) && time() < $delayed_option['delayed_until'] ) {
			return;
		}

		// Notice has been removed.
		if ( get_option( 'ksm_extensions_mss_review_prompt_removed' ) ) {
			return;
		}

		// Backwards compat.
		if ( get_transient( 'ksm_extensions_mss_review_prompt_delay' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible ksm-mss-review-notice" id="ksm_extensions_mss_review_notice">
			<div id="ksm_extensions_mss_review_intro">
				<p><?php esc_html_e( 'Are you enjoying KSM Extensions Missed Scheduled Posts?', 'ksm-extensions' ); ?></p>
				<p><a data-review-selection="yes" class="ksm-mss-review-selection" href="#"><?php esc_html_e( 'Yes, I love it.', 'ksm-extensions' ); ?></a> | <a data-review-selection="no" class="ksm-mss-review-selection" href="#"><?php esc_html_e( 'Not really.', 'ksm-extensions' ); ?></a></p>
			</div>
			<div id="ksm_extensions_mss_review_yes" style="display: none;">
				<p><?php esc_html_e( 'Awesome! Could you please do us a big favor and give KSM Extensions a 5-star rating on WordPress to help us spread the word?', 'ksm-extensions' ); ?></p>
				<p style="font-weight: bold;">~ Krafty Sprouts Media, LLC<br><?php esc_html_e( 'Developers of KSM Extensions', 'ksm-extensions' ); ?></p>
				<p>
				<a style="display: inline-block; margin-right: 10px;" href="https://wordpress.org/support/plugin/ksm-extensions/reviews/?filter=5" onclick="ksmExtensionsMssDelayReviewPrompt(event, 'remove', true, true)" target="_blank"><?php esc_html_e( 'Okay, you deserve it', 'ksm-extensions' ); ?></a>
				<a style="display: inline-block; margin-right: 10px;" href="#" onclick="ksmExtensionsMssDelayReviewPrompt(event, 'delay', true, false)"><?php esc_html_e( 'Nope, maybe later', 'ksm-extensions' ); ?></a>
				<a href="#" onclick="ksmExtensionsMssDelayReviewPrompt(event, 'remove', true, false)"><?php esc_html_e( 'I already did', 'ksm-extensions' ); ?></a>
				</p>
			</div>
			<div id="ksm_extensions_mss_review_no" style="display: none;">
				<p>
					<?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying KSM Extensions Missed Scheduled Posts. We would love a chance to improve. Could you take a minute and let us know what we can do better?', 'ksm-extensions' ); ?>
				</p>
				<p>
					<a style="display: inline-block; margin-right: 10px;" href="https://wordpress.org/support/plugin/ksm-extensions/" onclick="ksmExtensionsMssDelayReviewPrompt(event, 'remove', true, true)" target="_blank"><?php esc_html_e( 'Give Feedback', 'ksm-extensions' ); ?></a>
					<a href="#" onclick="ksmExtensionsMssDelayReviewPrompt(event, 'remove', true, false)"><?php esc_html_e( 'No thanks', 'ksm-extensions' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">

		  function ksmExtensionsMssDelayReviewPrompt(event, type, triggerClick = true, openLink = false) {
			event.preventDefault();
			if ( triggerClick ) {
			  jQuery('#ksm_extensions_mss_review_notice').fadeOut();
			}
			if ( openLink ) {
			  var href = event.target.href;
			  window.open(href, '_blank');
			}
			jQuery.ajax({
			  url: ajaxurl,
			  type: 'POST',
			  data: {
				action: 'ksm_extensions_mss_dismiss_review_prompt',
				nonce: "<?php echo esc_js( wp_create_nonce( 'ksm_extensions_mss_dismiss_review_prompt' ) ); ?>",
				type: type
			  },
			});
		  }

		  jQuery(document).ready(function($) {
			$('.ksm-mss-review-selection').click(function(event) {
			  event.preventDefault();
			  var $this = $(this);
			  var selection = $this.data('review-selection');
			  $('#ksm_extensions_mss_review_intro').hide();
			  $('#ksm_extensions_mss_review_' + selection).show();
			});
			$('body').on('click', '#ksm_extensions_mss_review_notice .notice-dismiss', function(event) {
			  ksmExtensionsMssDelayReviewPrompt(event, 'delay', false);
			});
		  });
		</script>
		<?php
	}
}

