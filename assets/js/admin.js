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
		// The ETA checkbox is always shown (no layout shift) and only enabled once the ETA differs from the saved value.
		var $eta = $( '#shilpi_os_eta' ),
			$label = $( '.shilpi-os-eta-notify' ),
			$box = $label.find( 'input[type="checkbox"]' ),
			etaStart = $eta.val();
		$eta.on( 'change input', function () {
			var changed = $eta.val() !== etaStart;
			$box.prop( 'disabled', ! changed );
			if ( ! changed ) {
				$box.prop( 'checked', false );
			}
			$label.toggleClass( 'is-disabled', ! changed );
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

/* Confirmations before a stage change emails customers. */
( function ( $ ) {
	'use strict';

	var data = window.shilpiOsAdmin && window.shilpiOsAdmin.confirm;
	if ( ! data ) {
		return;
	}

	function format( text, args ) {
		var i = 0;
		return text.replace( /%(\d+\$)?[sd]/g, function ( match, pos ) {
			var index = pos ? parseInt( pos, 10 ) - 1 : i++;
			return args[ index ] !== undefined ? String( args[ index ] ) : match;
		} );
	}

	function stageIndex( key ) {
		for ( var i = 0; i < data.stages.length; i++ ) {
			if ( data.stages[ i ].key === key ) {
				return i;
			}
		}
		return -1;
	}

	// Order edit screen (HPOS form#order, legacy form#post).
	$( function () {
		var order = data.order,
			$form = $( '#shilpi-os-stage' ).closest( 'form' ),
			$status = $( '#order_status' ),
			startStatus = $status.val(),
			submitter = null;

		if ( ! order || ! $form.length ) {
			return;
		}

		$form.on( 'click', 'button[type="submit"], input[type="submit"]', function () {
			submitter = this;
		} );

		$form.on( 'submit', function ( event ) {
			var clicked = ( event.originalEvent && event.originalEvent.submitter ) || submitter,
				dropdownChanged = $status.length && $status.val() !== startStatus,
				target = null,
				from = stageIndex( order.status ),
				to,
				message,
				skipped;

			submitter = null;

			if ( ! $form.find( 'input[name="shilpi_os_notify"]' ).is( ':checked' ) ) {
				return true;
			}

			if ( dropdownChanged ) {
				// The server applies the dropdown and ignores the next stage button when both change.
				target = $status.val();
			} else if ( clicked && 'shilpi_os_next_stage' === clicked.name ) {
				target = from >= 0 && data.stages[ from + 1 ] ? data.stages[ from + 1 ].key : ( 'wc-processing' === order.status ? data.stages[ 0 ].key : null );
			}

			to = target ? stageIndex( target ) : -1;
			// Backwards, unchanged, not a stage, or already emailed for that stage: no email, no confirm.
			if ( to < 0 || to <= from || -1 !== order.sent.indexOf( target ) ) {
				return true;
			}

			message = format( data.single, [ order.name || data.customer, order.number, data.stages[ to ].label ] );
			// From Processing (not a stage yet) every stage before the target counts as skipped.
			skipped = from < 0 ? to : to - from - 1;
			if ( skipped > 0 ) {
				message += '\n' + format( 1 === skipped ? data.skipOne : data.skipMany, [ skipped ] );
			}

			if ( ! window.confirm( message ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				return false;
			}
			return true;
		} );
	} );

	// Order list bulk actions (HPOS form#wc-orders-filter, legacy form#posts-filter).
	$( function () {
		var $forms = $( '#wc-orders-filter, #posts-filter' ),
			submitter = null;

		if ( ! $forms.length || ! $( '#bulk-action-selector-top' ).length ) {
			return;
		}

		$forms.on( 'click', '#doaction, #doaction2', function () {
			submitter = this;
		} );

		$forms.on( 'submit', function ( event ) {
			var clicked = ( event.originalEvent && event.originalEvent.submitter ) || submitter,
				action,
				count,
				key;

			submitter = null;
			if ( ! clicked || ( 'doaction' !== clicked.id && 'doaction2' !== clicked.id ) ) {
				return true;
			}
			action = $( 'doaction2' === clicked.id ? '#bulk-action-selector-bottom' : '#bulk-action-selector-top' ).val() || '';

			// Only "Stage: X" (mark_*) for one of our stages emails customers; "(no email)" variants do not.
			if ( 0 !== action.indexOf( 'mark_' ) ) {
				return true;
			}
			key = 'wc-' + action.substring( 5 );
			if ( stageIndex( key ) < 0 ) {
				return true;
			}
			count = $( this ).find( 'input[name="id[]"]:checked, input[name="post[]"]:checked' ).length;
			if ( ! count ) {
				return true;
			}
			if ( ! window.confirm( format( 1 === count ? data.bulkOne : data.bulkMany, [ count ] ) ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				return false;
			}
			return true;
		} );
	} );
}( jQuery ) );
