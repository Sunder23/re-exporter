/* global inlineEditPost */
/**
 * RE Exporter — List Table JS
 *
 * Pre-populates the Quick Edit "Include in RE export" checkbox
 * from the current value shown in the Export column cell.
 *
 * @package RE_Exporter
 */
( function ( $ ) {
	'use strict';

	if ( typeof inlineEditPost === 'undefined' ) {
		return;
	}

	var origEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		origEdit.apply( this, arguments );

		var postId = ( typeof id === 'object' )
			? parseInt( $( id ).closest( 'tr' ).attr( 'id' ).replace( 'post-', '' ), 10 )
			: parseInt( id, 10 );

		var $row    = $( '#post-' + postId );
		var enabled = $row.find( '.re-export-col-flag' ).data( 'enabled' );
		var $cb     = $( '#edit-' + postId + ' .re-qe-export-flag' );

		$cb.prop( 'checked', '1' === String( enabled ) );
	};

} )( jQuery );
