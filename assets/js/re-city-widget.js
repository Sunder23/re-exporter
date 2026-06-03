/* global jQuery */
/**
 * RE Exporter — Reusable ACF-style city search widget.
 *
 * Initialise once per page:
 *   RECityWidget.init({ ajaxUrl, nonce, i18n: { loading, noResults, keepOriginal } });
 *
 * Markup expected on the page:
 *   .re-city-widget[data-empty]
 *     input.re-city-val[type=hidden]
 *     .re-city-trigger
 *       span.re-city-label[.is-placeholder][data-resolve?]
 *       span.re-city-actions
 *         button.re-city-clear-btn
 *         span.re-city-caret
 *     .re-city-panel[hidden]
 *       input.re-city-search
 *       .re-city-results
 *
 * @package RE_Exporter
 */
window.RECityWidget = ( function ( $ ) {
	'use strict';

	var timer = null;
	var cfg   = {};

	// =========================================================================
	// Public API
	// =========================================================================

	function init( config ) {
		// Normalise: ensure cfg.i18n always exists with fallbacks.
		cfg = {
			ajaxUrl: ( config && config.ajaxUrl ) ? config.ajaxUrl : '',
			nonce:   ( config && config.nonce )   ? config.nonce   : '',
			i18n: $.extend(
				{
					loading:      'Loading\u2026',
					noResults:    'No results',
					keepOriginal: '\u2014 Keep original \u2014'
				},
				( config && config.i18n ) ? config.i18n : {}
			)
		};
		$( function () {
			resolveSavedLabels();
		} );
	}

	// =========================================================================
	// Private
	// =========================================================================

	/** Resolve saved city IDs to readable labels via AJAX (page-load). */
	function resolveSavedLabels() {
		$( '.re-city-label[data-resolve]' ).each( function () {
			var $label  = $( this );
			var $widget = $label.closest( '.re-city-widget' );

			// ALO, imoti and realistimo location widgets pre-populate labels from PHP — no AJAX needed.
			if ( $widget.data( 'alo-field' ) || $widget.data( 'imoti-field' ) || $widget.data( 'realistimo-field' ) ) {
				return;
			}

			var val = $label.attr( 'data-resolve' );

			$.post( cfg.ajaxUrl, {
				action: 're_exp_search_cities',
				nonce:  cfg.nonce,
				val:    val
			} ).done( function ( res ) {
				var text = ( res.success && res.data.items.length )
					? res.data.items[0].label
					: val;
				$label.text( text ).removeClass( 'is-placeholder' );
			} );
		} );
	}

	/** Fire AJAX search and populate the results list. */
	function doSearch( $widget, q ) {
		if ( ! cfg.ajaxUrl ) {
			return; // init() was not called yet — skip silently.
		}

		var i18n     = cfg.i18n || {};
		var $results = $widget.find( '.re-city-results' );
		$results.html(
			'<div class="re-city-loading">' +
			escHtml( i18n.loading || 'Loading\u2026' ) +
			'</div>'
		);

		var aloField        = $widget.data( 'alo-field' );
		var imotiField      = $widget.data( 'imoti-field' );
		var realistimoField = $widget.data( 'realistimo-field' );
		var params          = { nonce: cfg.nonce, q: q };
		if ( aloField ) {
			params.action = 're_exp_alo_search_location';
			params.field  = aloField;
		} else if ( imotiField ) {
			params.action = 're_exp_imoti_search_locations';
			params.field  = imotiField;
		} else if ( realistimoField ) {
			params.action = 're_exp_realistimo_search_location';
			params.field  = realistimoField;
		} else {
			params.action = 're_exp_search_cities';
		}

		$.post( cfg.ajaxUrl, params ).done( function ( res ) {
			$results.empty();
			if ( ! res.success || ! res.data.items.length ) {
				$results.append(
					$( '<div class="re-city-no-results"></div>' )
						.text( ( cfg.i18n || {} ).noResults || 'No results' )
				);
				return;
			}
			$.each( res.data.items, function ( i, city ) {
				$( '<div class="re-city-option"></div>' )
					.text( city.label )
					.attr( 'data-val',         city.value       || '' )
					.attr( 'data-city-id',     city.city_id     || city.value || '' )
					.attr( 'data-district-id', city.district_id || '' )
					.appendTo( $results );
			} );
		} );
	}

	/** Close all open widgets. */
	function closeAll() {
		$( '.re-city-widget.is-open' )
			.removeClass( 'is-open' )
			.find( '.re-city-panel' ).hide().end()
			.find( '.re-city-search' ).val( '' ).end()
			.find( '.re-city-results' ).empty();
	}

	function escHtml( str ) {
		return $( '<div>' ).text( String( str ) ).html();
	}

	// =========================================================================
	// Event Handlers (delegated — safe for dynamically added widgets)
	// =========================================================================

	/** Trigger click → open / close panel. */
	$( document ).on( 'click', '.re-city-trigger', function ( e ) {
		// Ignore clicks on the clear button (it has its own handler).
		if ( $( e.target ).closest( '.re-city-clear-btn' ).length ) {
			return;
		}

		var $widget = $( this ).closest( '.re-city-widget' );
		var isOpen  = $widget.hasClass( 'is-open' );

		closeAll();

		if ( ! isOpen ) {
			$widget.addClass( 'is-open' ).find( '.re-city-panel' ).show();
			$widget.find( '.re-city-search' ).trigger( 'focus' );
			// Do not auto-search on open — wait for user to type.
		}

		e.stopPropagation();
	} );

	/** Search input → debounced AJAX. */
	$( document ).on( 'input', '.re-city-search', function () {
		var $widget = $( this ).closest( '.re-city-widget' );
		var q       = $.trim( $( this ).val() );
		clearTimeout( timer );
		timer = setTimeout( function () { doSearch( $widget, q ); }, 250 );
	} );

	/** Click an option → set value and close. */
	$( document ).on( 'click', '.re-city-option', function ( e ) {
		var $opt    = $( this );
		var $widget = $opt.closest( '.re-city-widget' );

		var storeMode = $widget.data( 'store' ) || 'city';
		var $distVal  = $widget.find( '.re-district-val' );

		if ( 'district' === storeMode ) {
			// location_district B2 widget: store district_id (fall back to city_id if no district).
			$widget.find( '.re-city-val' ).val(
				$opt.attr( 'data-district-id' ) || $opt.attr( 'data-city-id' ) || $opt.attr( 'data-val' )
			);
		} else if ( $distVal.length ) {
			// __city__ metabox widget: store city_id + district_id in companion input.
			$widget.find( '.re-city-val' ).val( $opt.attr( 'data-city-id' ) || $opt.attr( 'data-val' ) );
			$distVal.val( $opt.attr( 'data-district-id' ) || '' );
		} else {
			// Legacy __json__citys mode.
			$widget.find( '.re-city-val' ).val( $opt.attr( 'data-val' ) );
		}
		$widget.find( '.re-city-label-field' ).val( $opt.text() );
		$widget.find( '.re-city-label' )
			.text( $opt.text() )
			.removeClass( 'is-placeholder' )
			.removeAttr( 'data-resolve' );
		$widget.find( '.re-city-clear-btn' ).show();
		$widget.find( '.re-city-val' ).trigger( 'change' );

		closeAll();
		e.stopPropagation();
	} );

	/** Clear button → reset to empty label. */
	$( document ).on( 'click', '.re-city-clear-btn', function ( e ) {
		var $widget    = $( this ).closest( '.re-city-widget' );
		var emptyLabel = $widget.data( 'empty' ) || ( cfg.i18n && cfg.i18n.keepOriginal ) || '\u2014 Keep original \u2014';

		$widget.find( '.re-city-val' ).val( '' ).trigger( 'change' );
		$widget.find( '.re-district-val' ).val( '' );
		$widget.find( '.re-city-label-field' ).val( '' );
		$widget.find( '.re-city-label' )
			.text( emptyLabel )
			.addClass( 'is-placeholder' )
			.removeAttr( 'data-resolve' );
		$( this ).hide();

		e.stopPropagation();
	} );

	/** Outside click → close all. */
	$( document ).on( 'click', function () { closeAll(); } );

	return { init: init };

} )( jQuery );
