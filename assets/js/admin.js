/* global jQuery, shilpiOsAdmin */
( function ( $ ) {
	'use strict';

	function formatMoney( value ) {
		var s = shilpiOsAdmin,
			parts = Math.abs( value ).toFixed( s.decimals ).split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, s.thousands );
		return ( value < 0 ? '-' : '' ) + s.symbol + parts.join( s.decimal );
	}

	$( function () {
		var $eta = $( '#shilpi_os_eta' ),
			etaStart = $eta.val();
		$eta.on( 'change input', function () {
			$( '.shilpi-os-eta-notify' ).toggleClass( 'is-visible', $eta.val() !== etaStart );
		} );
	} );

	$( function () {
		var $profit = $( '.shilpi-os-profit' );
		if ( ! $profit.length ) {
			return;
		}
		$( '#shilpi_os_cost' ).on( 'input', function () {
			var raw = String( $( this ).val() ).split( shilpiOsAdmin.thousands ).join( '' ).replace( shilpiOsAdmin.decimal, '.' ),
				cost = parseFloat( raw );
			if ( ! isNaN( cost ) ) {
				$profit.find( 'span' ).first().text( formatMoney( parseFloat( $profit.data( 'total' ) ) - cost ) );
			}
		} );
	} );
}( jQuery ) );
