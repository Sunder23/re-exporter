/**
 * ALO Location — section_string row toggle.
 *
 * When the section city-widget value is set to "NULL", shows the free-text
 * section_string input row. Clears and hides it on any other value or clear.
 *
 * Relies on re-city-widget.js triggering a `change` event on `.re-city-val`
 * after each selection or clear.
 *
 * @package RE_Exporter
 */
/* global jQuery */
( function ( $ ) {
	'use strict';

	$( document ).on( 'change', '.re-alo-section-city-widget .re-city-val', function () {
		var $widget  = $( this ).closest( '.re-alo-section-city-widget' );
		var $strRow  = $widget.closest( 'table' ).find( '.re-alo-section-string-row' );
		var val      = $( this ).val();

		if ( 'NULL' === val ) {
			$strRow.show();
		} else {
			$strRow.hide();
			$strRow.find( 'input[name="re_alo_location[section_string]"]' ).val( '' );
		}
	} );

}( jQuery ) );
