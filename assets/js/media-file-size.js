/**
 * Filename: media-file-size.js
 * Author: Krafty Sprouts Media, LLC
 * Created: 14/11/2025
 * Version: 1.8.3
 * Last Modified: 30/12/2025
 * Description: Media Library file size indexing controls and variant viewer for KSM Extensions.
 */
/* global ksmExtensionsMediaSize */
( function ( $ ) {
	'use strict';

	const settings = window.ksmExtensionsMediaSize || {};
	window.ksmExtensionsMediaSizeVariants = window.ksmExtensionsMediaSizeVariants || {};
	const dialogs = window.ksmAdminDialogs || window.KSMAdminDialogs;

	function notify( type, message ) {
		const notices = window.wp?.notices;
		if ( notices && typeof notices[ type ] === 'function' ) {
			notices[ type ]( message );
			return;
		}

		const icon = 'error' === type ? 'error' : 'success';
		if ( dialogs && typeof dialogs.alert === 'function' ) {
			dialogs.alert( message, { icon } );
			return;
		}

		window.alert( message );
	}

	function request( action, data = {} ) {
		return fetch( settings.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(
				Object.assign(
					{
						action,
						nonce: settings.nonce,
					},
					data
				)
			),
		} ).then( ( response ) => response.json() );
	}

	function renderSummary( totalHuman, tooltip ) {
		const content = '(' + totalHuman + ')' + ( tooltip ? '<span class="tooltiptext">' + tooltip + '</span>' : '' );
		let container = document.querySelector( '.ksm-media-size-summary' );

		if ( container ) {
			container.innerHTML = content;
		} else {
			const span = document.createElement( 'span' );
			span.className = 'ksm-media-size-summary';
			span.innerHTML = content;

			const heading = document.querySelector( 'h1, h2' );
			if ( heading ) {
				heading.appendChild( span );
			}
		}
	}

	function toggleButtonLoading( button, isLoading ) {
		if ( isLoading ) {
			button.dataset.originalText = button.textContent;
			button.innerHTML = '<div class="ksm-media-size-button-loading"><div></div><div></div><div></div><div></div></div>';
			button.disabled = true;
		} else {
			button.textContent = button.dataset.originalText || settings.strings.indexMedia;
			button.disabled = false;
		}
	}

	function injectIndexButton() {
		if ( document.querySelector( '.ksm-index-media' ) ) {
			return;
		}

		const button = document.createElement( 'button' );
		button.className = 'page-title-action ksm-index-media';
		button.textContent = settings.strings.indexMedia;

		const header = document.querySelector( 'hr.wp-header-end' ) || document.querySelector( '.page-title-action' )?.parentElement;
		if ( header ) {
			header.parentNode.insertBefore( button, header );
		}

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			runIndex( button, false );
		} );
	}

	function injectReindexMenu() {
		const submenu = document.querySelector( '#menu-media ul' );
		if ( ! submenu || submenu.querySelector( '.ksm-reindex-media' ) ) {
			return;
		}

		const li = document.createElement( 'li' );
		const a = document.createElement( 'a' );
		a.href = '#';
		a.textContent = settings.strings.reindexMedia;
		a.className = 'ksm-reindex-media';
		li.appendChild( a );
		submenu.appendChild( li );

		a.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			runIndex( a, true );
		} );
	}

	function runIndex( trigger, isReindex ) {
		toggleButtonLoading( trigger, true );

		request( 'ksm_extensions_media_size_index', { reindex: isReindex ? 1 : 0 } )
			.then( ( response ) => {
				if ( response.success ) {
					if ( response.data.message ) {
						notify( 'success', response.data.message );
					}

					if ( Array.isArray( response.data.html ) ) {
						response.data.html.forEach( ( item ) => {
							const row = document.querySelector( '#post-' + item.attachment_id );
							if ( row ) {
								const cell = row.querySelector( '.ksm_extensions_media_file_size' );
								if ( cell ) {
									cell.innerHTML = item.html;
								}
							}
						} );
					}
					refreshSummary();
					if ( response.data.variants ) {
						window.ksmExtensionsMediaSizeVariants = Object.assign(
							window.ksmExtensionsMediaSizeVariants || {},
							response.data.variants
						);
					}
					initVariantButtons();
				} else {
					const message = response?.data?.body || settings.strings.indexError;
					notify( 'error', message );
				}
			} )
			.catch( () => {
				notify( 'error', settings.strings.indexError );
			} )
			.finally( () => toggleButtonLoading( trigger, false ) );
	}

	function refreshSummary() {
		request( 'ksm_extensions_media_size_index_count' ).then( ( response ) => {
			if ( response.success && response.data.TotalMLSize ) {
				renderSummary( response.data.TotalMLSize, response.data.TotalMLSize_Title );
			}
		} );
	}

	function showVariantsModal( data ) {
		if ( ! data || ! data.length ) {
			return;
		}
		const overlay = document.createElement( 'div' );
		overlay.className = 'ksm-media-size-modal-overlay';
		const modal = document.createElement( 'div' );
		modal.className = 'ksm-media-size-modal';
		overlay.appendChild( modal );

		data.sort( ( a, b ) => a.width - b.width );

		data.forEach( ( variant ) => {
			modal.innerHTML += `
				<div class="ksm-media-size-modal-card">
					<span class="preview">
						<img src="${ variant.filename }" alt="${ variant.size }" loading="lazy" />
						<a href="${ variant.filename }" target="_blank" rel="noopener">View</a>
					</span>
					<span class="filename">${ variant.filename.split( /[\\/]/ ).pop() }</span>
					<span class="size-name">${ variant.size }</span>
					<span class="dimensions">${ variant.width } × ${ variant.height }</span>
					<span>${ variant.filesize_hr }</span>
				</div>
			`;
		} );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				overlay.remove();
			}
		} );

		document.body.appendChild( overlay );
	}

	let variantsHandlerBound = false;
	function initVariantButtons() {
		if ( variantsHandlerBound ) {
			return;
		}

		$( document ).on( 'click', '.ksm-media-size-variants-button', function ( event ) {
			event.preventDefault();
			const button = this;
			const attachmentId = button.dataset.attachmentId;
			let data = ( window.ksmExtensionsMediaSizeVariants || {} )[ attachmentId ];

			if ( data ) {
				showVariantsModal( data );
				return;
			}

			// Fallback: fetch variant data via AJAX (e.g. when not in page payload or after grid/list switch)
			const action = settings.getVariantsAction || 'ksm_extensions_media_size_get_variants';
			request( action, { attachment_id: attachmentId } )
				.then( function ( response ) {
					if ( response.success && response.data && response.data.variants ) {
						data = response.data.variants;
						if ( ! window.ksmExtensionsMediaSizeVariants ) {
							window.ksmExtensionsMediaSizeVariants = {};
						}
						window.ksmExtensionsMediaSizeVariants[ attachmentId ] = data;
						showVariantsModal( data );
					} else {
						notify( 'error', settings.strings.variantsError || 'Could not load variant data.' );
					}
				} )
				.catch( function () {
					notify( 'error', settings.strings.variantsError || 'Could not load variant data.' );
				} );
		} );

		variantsHandlerBound = true;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		const hasListTable = document.querySelector( '.wp-list-table.media' );
		if ( hasListTable ) {
			injectIndexButton();
			injectReindexMenu();
			refreshSummary();
		}
		initVariantButtons();
	} );
}( jQuery ) );

