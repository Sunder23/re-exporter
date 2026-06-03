/* global reExporter, RECityWidget, jQuery */
/**
 * RE Exporter — Admin JS
 *
 * Handles:
 *  - City search widget init (ACF-style, shared via re-city-widget.js)
 *  - Settings tab: collapsible B4 sections, platform sub-tab toggle
 *  - Export tab: 3-step wizard (platform → review → export)
 *
 * @package RE_Exporter
 */
( function ( $ ) {
	'use strict';

	// =========================================================================
	// Shared Utilities
	// =========================================================================

	function escHtml( str ) {
		return $( '<div>' ).text( String( str ) ).html();
	}

	function showNotice( msg, type ) {
		type = type || 'error';
		$( '#re-notice-area' )
			.html( '<div class="notice notice-' + type + ' is-dismissible"><p>' + escHtml( msg ) + '</p></div>' )
			.show();
		window.scrollTo( 0, 0 );
	}

	function clearNotices() {
		$( '#re-notice-area' ).empty().hide();
	}

	function showSpinner( msg ) {
		$( '.re-spinner-msg' ).text( msg || reExporter.i18n.loading );
		$( '#re-spinner-overlay' ).addClass( 'is-visible' );
	}

	function hideSpinner() {
		$( '#re-spinner-overlay' ).removeClass( 'is-visible' );
	}

	// =========================================================================
	// Settings Tab
	// =========================================================================

	// ── B4: collapsible required-fields blocks ────────────────────────────────
	$( document ).on( 'click', '.re-collapse-toggle', function () {
		var $btn  = $( this );
		var $body = $btn.closest( '.re-collapse-block' ).find( '.re-collapse-body' );
		$body.slideToggle( 160 );
		$btn.attr( 'aria-expanded', $body.is( ':visible' ) ? 'false' : 'true' );
	} );

	// ── B4: inline warning when a required row is still on __skip__ ───────────
	$( document ).on( 'change', '.re-req-source-select', function () {
		var $row  = $( this ).closest( 'tr' );
		$row.find( '.re-map-warning' ).toggle( $( this ).val() === '__skip__' );
	} );

	// ── Settings: warn on required OLX columns left at __skip__ ───────────────
	$( document ).on( 'change', '.re-source-select', function () {
		var $row     = $( this ).closest( 'tr.re-required-row' );
		if ( $row.length ) {
			$row.find( '.re-map-warning' ).toggle( $( this ).val() === '__skip__' );
		}
	} );

	// =========================================================================
	// Export Tab — 3-step Wizard
	// =========================================================================

	var wizard = {
		step:     1,
		platform: ''
	};

	// ── Step helpers ──────────────────────────────────────────────────────────

	function showStep( n ) {
		$( '.re-exp-step' ).hide().attr( 'aria-hidden', 'true' );
		$( '#re-exp-step-' + n ).show().attr( 'aria-hidden', 'false' );

		$( '.re-progress-step' ).removeClass( 'is-active is-done' );
		for ( var i = 1; i <= 3; i++ ) {
			if ( i < n )  { $( '#re-progress-' + i ).addClass( 'is-done' ); }
			if ( i === n ) { $( '#re-progress-' + i ).addClass( 'is-active' ); }
		}

		wizard.step = n;
		window.scrollTo( 0, 0 );
	}

	// ── Step 1 → Step 2 ───────────────────────────────────────────────────────

	$( document ).on( 'click', '#re-exp-step1-next', function () {
		clearNotices();

		wizard.platform = $( 'input[name="re_exp_platform"]:checked' ).val();
		if ( ! wizard.platform ) {
			showNotice( reExporter.i18n.selectPlatform );
			return;
		}

		loadReview();
	} );

	// ── Step 2 ← ─────────────────────────────────────────────────────────────

	$( document ).on( 'click', '#re-exp-step2-back', function () {
		clearNotices();
		showStep( 1 );
	} );

	// ── Step 2 → Step 3: Export ───────────────────────────────────────────────

	$( document ).on( 'click', '#re-exp-btn-export', function () {
		clearNotices();
		showSpinner( reExporter.i18n.exporting );

		$.post( reExporter.ajaxUrl, {
			action:   're_exp_run',
			nonce:    reExporter.nonce,
			platform: wizard.platform
		} )
		.done( function ( res ) {
			hideSpinner();
			if ( ! res.success ) {
				showNotice( res.data || reExporter.i18n.error );
				return;
			}
			$( '#re-exp-result' ).html( res.data.html ).show();
			$( '#re-exp-btn-export' ).hide();
			$( '#re-exp-btn-new' ).show();
			showStep( 3 );
		} )
		.fail( function () {
			hideSpinner();
			showNotice( reExporter.i18n.error );
		} );
	} );

	// ── Step 3 back ───────────────────────────────────────────────────────────

	$( document ).on( 'click', '#re-exp-step3-back', function () {
		clearNotices();
		$( '#re-exp-result' ).empty().hide();
		$( '#re-exp-btn-export' ).show();
		$( '#re-exp-btn-new' ).hide();
		showStep( 2 );
	} );

	// ── New Export ────────────────────────────────────────────────────────────

	$( document ).on( 'click', '#re-exp-btn-new', function () {
		clearNotices();
		wizard = { step: 1, platform: '' };
		$( '#re-exp-result' ).empty().hide();
		$( '#re-exp-btn-export' ).show();
		$( '#re-exp-btn-new' ).hide();
		$( 'input[name="re_exp_platform"]' ).prop( 'checked', false );
		showStep( 1 );
	} );

	// =========================================================================
	// Init
	// =========================================================================

	$( function () {
		// Dismiss saved-settings notices on click.
		$( document ).on( 'click', '.notice-dismiss', function () {
			$( this ).closest( '.notice' ).fadeOut( 200 );
		} );

		// Init ACF-style city search widget (shared via re-city-widget.js).
		if ( typeof RECityWidget !== 'undefined' ) {
			RECityWidget.init( {
				ajaxUrl: reExporter.ajaxUrl,
				nonce:   reExporter.nonce,
				i18n:    reExporter.i18n
			} );
		}

		// If we are on the Export tab, show Step 1 immediately.
		if ( $( '#re-exp-step-1' ).length ) {
			showStep( 1 );
		}
	} );

	// ── Review helper (Step 1 → Step 2) ──────────────────────────────────────

	function loadReview() {
		showSpinner( reExporter.i18n.loadingReview );

		$.post( reExporter.ajaxUrl, {
			action:    're_exp_review',
			nonce:     reExporter.nonce,
			platform:  wizard.platform
		} )
		.done( function ( res ) {
			hideSpinner();
			if ( ! res.success ) {
				showNotice( res.data || reExporter.i18n.error );
				return;
			}
			$( '#re-exp-review-wrap' ).html( res.data.html );
			$( '#re-review-count' ).text( res.data.count );
			$( '#re-review-platform' ).text( wizard.platform.toUpperCase() + '.bg' );
			showStep( 2 );
		} )
		.fail( function () {
			hideSpinner();
			showNotice( reExporter.i18n.error );
		} );
	}

} )( jQuery );
