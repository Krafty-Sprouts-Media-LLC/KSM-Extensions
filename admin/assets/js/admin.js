/**
 * KSM Extensions Admin Scripts
 *
 * @package KSM_Extensions
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Module toggle functionality
        $('.ksm-module-toggle-input').on('change', function () {
            var $checkbox = $(this);
            var moduleSlug = $checkbox.data('module');
            var isActive = $checkbox.is(':checked');
            var $row = $checkbox.closest('tr');

            // Disable checkbox during AJAX request
            $checkbox.prop('disabled', true);

            $.ajax({
                url: ksmExtensionsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ksm_extensions_toggle_module',
                    nonce: ksmExtensionsAdmin.nonce,
                    module: moduleSlug,
                    activate: isActive
                },
                success: function (response) {
                    if (response.success) {
                        // Update row appearance
                        if (isActive) {
                            $row.removeClass('inactive');
                            // Reload page to load the newly activated module
                            window.location.reload();
                        } else {
                            $row.addClass('inactive');
                            // Show success message but don't reload (deactivation doesn't require reload)
                            var $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                            $('.wrap h1').after($notice);
                            setTimeout(function() {
                                $notice.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        }
                    } else {
                        // Revert checkbox on error
                        $checkbox.prop('checked', !isActive);
                        alert(response.data.message || 'An error occurred.');
                    }
                },
                error: function () {
                    // Revert checkbox on error
                    $checkbox.prop('checked', !isActive);
                    alert('An error occurred while updating the module.');
                },
                complete: function () {
                    // Re-enable checkbox
                    $checkbox.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
