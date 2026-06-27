/**
 * Filename: admin-featured-thumbs.js
 * Author: Krafty Sprouts Media, LLC
 * Created: 27/06/2026
 * Version: 1.0.0
 * Last Modified: 27/06/2026
 * Description: Inline featured image set/change/remove controls for post list admin columns.
 */
/* global ksmExtensionsFeaturedThumbs, wp */
( function ( $ ) {
	'use strict';

	const settings = window.ksmExtensionsFeaturedThumbs || {};
	const l10n = settings.l10n || {};

	/**
	 * Find the thumb cell wrapper for a post ID.
	 *
	 * @param {number} postId Post ID.
	 * @return {jQuery}
	 */
	function getThumbCell( postId ) {
		return $( '.ksm-thumb-cell[data-ksm-thumb="' + postId + '"]' );
	}

	/**
	 * Toggle the loading spinner on a thumb cell.
	 *
	 * @param {jQuery} $cell Thumb cell element.
	 * @param {boolean} isLoading Whether loading is active.
	 */
	function toggleLoading( $cell, isLoading ) {
		$cell.toggleClass( 'is-loading', isLoading );
		$cell.find( '.spinner' ).toggleClass( 'is-active', isLoading );
		$cell.find( 'button' ).prop( 'disabled', isLoading );
	}

	/**
	 * Persist a featured image change via AJAX.
	 *
	 * @param {number} postId Post ID.
	 * @param {number} attachmentId Attachment ID, or 0 to remove.
	 * @param {jQuery} $cell Thumb cell element.
	 */
	function updateFeaturedImage( postId, attachmentId, $cell ) {
		const nonce = $cell.data( 'nonce' );

		if ( ! nonce ) {
			window.alert( l10n.error || 'Unable to update the featured image.' );
			return;
		}

		toggleLoading( $cell, true );

		$.ajax( {
			url: settings.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ksm_extensions_set_featured_image',
				post_id: postId,
				attachment_id: attachmentId,
				nonce: nonce,
			},
		} )
			.done( function ( response ) {
				if ( response.success && response.data && response.data.html ) {
					$cell.replaceWith( response.data.html );
					return;
				}

				window.alert(
					( response.data && response.data.message ) ||
						l10n.error ||
						'Unable to update the featured image.'
				);
			} )
			.fail( function () {
				window.alert( l10n.error || 'Unable to update the featured image.' );
			} )
			.always( function () {
				toggleLoading( $cell, false );
			} );
	}

	/**
	 * Open the media modal to pick a featured image.
	 *
	 * @param {number} postId Post ID.
	 * @param {number} attachmentId Current attachment ID, if any.
	 */
	function openMediaFrame( postId, attachmentId ) {
		if ( typeof wp === 'undefined' || ! wp.media ) {
			window.alert( l10n.error || 'Unable to update the featured image.' );
			return;
		}

		const $cell = getThumbCell( postId );
		const frame = wp.media( {
			title: l10n.title || 'Select featured image',
			button: {
				text: l10n.button || 'Use featured image',
			},
			library: {
				type: 'image',
			},
			multiple: false,
		} );

		frame.on( 'open', function () {
			if ( ! attachmentId ) {
				return;
			}

			const selection = frame.state().get( 'selection' );
			const attachment = wp.media.attachment( attachmentId );

			attachment.fetch();
			selection.add( attachment ? [ attachment ] : [] );
		} );

		frame.on( 'select', function () {
			const attachment = frame.state().get( 'selection' ).first().toJSON();

			if ( ! attachment || ! attachment.id ) {
				return;
			}

			updateFeaturedImage( postId, attachment.id, $cell );
		} );

		frame.open();
	}

	$( document ).on( 'click', '.ksm-featured-image-toggle', function ( event ) {
		event.preventDefault();
		event.stopPropagation();

		const postId = parseInt( $( this ).data( 'post-id' ), 10 );
		const $cell = getThumbCell( postId );
		const attachmentId = parseInt( $cell.data( 'attachment-id' ), 10 ) || 0;

		if ( ! postId || ! $cell.length ) {
			return;
		}

		openMediaFrame( postId, attachmentId );
	} );

	$( document ).on( 'click', '.ksm-featured-image-remove', function ( event ) {
		event.preventDefault();
		event.stopPropagation();

		const postId = parseInt( $( this ).data( 'post-id' ), 10 );
		const $cell = getThumbCell( postId );

		if ( ! postId || ! $cell.length ) {
			return;
		}

		const confirmMessage =
			l10n.remove || 'Remove this featured image?';

		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		updateFeaturedImage( postId, 0, $cell );
	} );
}( jQuery ) );
