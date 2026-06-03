/* global reExporterMeta, RECityWidget */
/**
 * RE Exporter — Post Metabox JS
 *
 * Tab switching, city-widget init, and number-field validation
 * for the CPT post edit screen.
 *
 * @package RE_Exporter
 */
( function ( $ ) {
	'use strict';

	// =========================================================================
	// Tab switching
	// =========================================================================

	var STORAGE_KEY = 're_exporter_active_tab';

	function activateTab( tabId ) {
		var $tabs    = $( '#re-exporter-tabs' );
		var $buttons = $tabs.find( '.re-tab-btn' );
		var $panels  = $tabs.find( '.re-tab-panel' );

		$buttons.removeClass( 'active' ).attr( 'aria-selected', 'false' );
		$panels.hide();

		var $btn   = $tabs.find( '.re-tab-btn[data-tab="' + tabId + '"]' );
		var $panel = $( '#' + tabId );

		if ( $btn.length && $panel.length ) {
			$btn.addClass( 'active' ).attr( 'aria-selected', 'true' );
			$panel.show();
			try { window.localStorage.setItem( STORAGE_KEY, tabId ); } catch ( e ) {}
		} else {
			// Fallback to first tab.
			$buttons.first().addClass( 'active' ).attr( 'aria-selected', 'true' );
			$panels.first().show();
		}
	}

	$( document ).on( 'click', '.re-tab-btn', function () {
		activateTab( $( this ).data( 'tab' ) );
	} );

	// Restore last-active tab on page load.
	$( function () {
		var stored = '';
		try { stored = window.localStorage.getItem( STORAGE_KEY ) || ''; } catch ( e ) {}

		var $tabs = $( '#re-exporter-tabs' );
		if ( ! $tabs.length ) { return; }

		var tabId = stored && $tabs.find( '#' + stored ).length ? stored : '';

		if ( tabId ) {
			activateTab( tabId );
		} else {
			// Show first tab (already visible by default via PHP).
			$tabs.find( '.re-tab-btn' ).first().addClass( 'active' ).attr( 'aria-selected', 'true' );
		}
	} );

	// =========================================================================
	// City widget
	// =========================================================================

	if ( typeof RECityWidget !== 'undefined' && typeof reExporterMeta !== 'undefined' ) {
		RECityWidget.init( {
			ajaxUrl: reExporterMeta.ajaxUrl,
			nonce:   reExporterMeta.nonce,
			i18n:    reExporterMeta.i18n,
		} );
	}

	// =========================================================================
	// Number field validation — clamp to min/max on blur
	// =========================================================================

	$( document ).on( 'blur', '.re-number-field', function () {
		var $input = $( this );
		var val    = $.trim( $input.val() );

		if ( '' === val ) { return; }

		var num = parseFloat( val );
		if ( isNaN( num ) ) {
			$input.val( '' ).addClass( 're-field-error' );
			return;
		}

		var min = $input.data( 'min' );
		var max = $input.data( 'max' );
		if ( typeof min !== 'undefined' && num < parseFloat( min ) ) { num = parseFloat( min ); }
		if ( typeof max !== 'undefined' && num > parseFloat( max ) ) { num = parseFloat( max ); }
		if ( '1' === $input.attr( 'step' ) ) { num = Math.round( num ); }

		$input.val( num ).removeClass( 're-field-error' );
	} );

	$( document ).on( 'input', '.re-number-field', function () {
		$( this ).removeClass( 're-field-error' );
	} );

} )( jQuery );
