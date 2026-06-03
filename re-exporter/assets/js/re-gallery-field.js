/* global jQuery, wp, reExporterGallery */
/**
 * RE Exporter — Gallery field media picker.
 *
 * Opens the WP media library in multi-select mode. Selected images are shown
 * as sortable thumbnails. Attachment IDs are written to a hidden input as a
 * comma-separated list on every change.
 */
( function ( $ ) {
	'use strict';

	var frame;

	/**
	 * Read current IDs from the DOM, write them to the hidden input,
	 * and update the count label / button visibility.
	 */
	function syncIds() {
		var ids = [];
		$( '#re-gallery-items .re-gallery-item' ).each( function () {
			ids.push( $( this ).data( 'id' ) );
		} );
		$( '#re-gallery-ids' ).val( ids.join( ',' ) );

		var count = ids.length;
		var $count = $( '#re-gallery-count' );
		if ( count > 0 ) {
			$count.text( count + ( count === 1 ? ' image' : ' images' ) );
		} else {
			$count.text( '' );
		}

		if ( count === 0 ) {
			$( '#re-gallery-empty' ).show();
			$( '#re-gallery-clear' ).hide();
		} else {
			$( '#re-gallery-empty' ).hide();
			$( '#re-gallery-clear' ).show();
		}
	}

	/**
	 * Build a gallery item element from a WP media attachment JSON object.
	 *
	 * @param  {Object} att  Attachment JSON (from frame.state().get('selection')).
	 * @return {jQuery}
	 */
	function buildItem( att ) {
		var thumbUrl = ( att.sizes && att.sizes.thumbnail )
			? att.sizes.thumbnail.url
			: att.url;

		return $( '<div>' )
			.addClass( 're-gallery-item' )
			.attr( 'data-id', att.id )
			.append(
				$( '<img>' ).attr( { src: thumbUrl, alt: '' } )
			)
			.append(
				$( '<button>' )
					.attr( { type: 'button', title: 'Remove' } )
					.addClass( 're-gallery-remove' )
					.html( '&#x2715;' )
			)
			.append(
				$( '<span>' )
					.attr( { title: 'Drag to reorder' } )
					.addClass( 're-gallery-drag-handle' )
					.html( '&#9776;' )
			);
	}

	$( document ).ready( function () {

		// ── Sortable ─────────────────────────────────────────────────────────
		$( '#re-gallery-items' ).sortable( {
			handle: '.re-gallery-drag-handle',
			tolerance: 'pointer',
			update: function () {
				syncIds();
			},
		} );

		// ── Remove single image ───────────────────────────────────────────────
		$( document ).on( 'click', '.re-gallery-remove', function () {
			$( this ).closest( '.re-gallery-item' ).remove();
			syncIds();
		} );

		// ── Clear all ─────────────────────────────────────────────────────────
		$( '#re-gallery-clear' ).on( 'click', function () {
			/* eslint-disable-next-line no-alert */
			if ( ! window.confirm( 'Remove all images from the gallery?' ) ) {
				return;
			}
			$( '#re-gallery-items' ).empty();
			syncIds();
		} );

		// ── Open media library ────────────────────────────────────────────────
		$( '#re-gallery-add' ).on( 'click', function () {
			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: reExporterGallery.i18n.selectMedia,
				button: { text: reExporterGallery.i18n.addToGallery },
				multiple: true,
				library: { type: 'image' },
			} );

			frame.on( 'select', function () {
				var selection = frame.state().get( 'selection' );

				selection.each( function ( attachment ) {
					var att = attachment.toJSON();

					// Skip duplicates already in the gallery.
					if ( $( '#re-gallery-items .re-gallery-item[data-id="' + att.id + '"]' ).length ) {
						return;
					}

					$( '#re-gallery-items' ).append( buildItem( att ) );
				} );

				syncIds();
			} );

			frame.open();
		} );

	} );

}( jQuery ) );
