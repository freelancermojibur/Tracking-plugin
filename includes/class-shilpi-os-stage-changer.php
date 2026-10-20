<?php
/**
 * Central handler for every stage change.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Records stage dates, decides whether to email the customer and writes the history note.
 *
 * Every status change goes through woocommerce_order_status_changed, so the order
 * edit dropdown, bulk actions, the next stage button, REST API and auto-move all behave the same.
 */
class Shilpi_OS_Stage_Changer {

	const META_DATES = '_shilpi_os_stage_dates';
	const META_SENT  = '_shilpi_os_emails_sent';

	/**
	 * When true, no stage emails are sent during this request.
	 *
	 * @var bool
	 */
	private static $suppress = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 10, 4 );
	}

	/**
	 * Turn stage emails off (or back on) for the rest of this request.
	 *
	 * @param bool $suppress Suppress.
	 */
	public static function suppress_emails( $suppress = true ) {
		self::$suppress = (bool) $suppress;
	}

	/**
	 * Stage dates for an order: stage key => Unix timestamp.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function get_dates( $order ) {
		$dates = $order->get_meta( self::META_DATES );
		return is_array( $dates ) ? $dates : array();
	}

	/**
	 * Stage emails already sent: stage key => Unix timestamp.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function get_sent( $order ) {
		$sent = $order->get_meta( self::META_SENT );
		return is_array( $sent ) ? $sent : array();
	}

	/**
	 * Handle a status change.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Old status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order.
	 */
	public function on_status_changed( $order_id, $from, $to, $order = null ) {
		if ( ! Shilpi_OS_Stages::is_stage( $from ) && ! Shilpi_OS_Stages::is_stage( $to ) ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || 'shop_order' !== $order->get_type() ) {
			return;
		}

		$old_index = Shilpi_OS_Stages::index( $from );
		$new_index = Shilpi_OS_Stages::index( $to );

		if ( $new_index >= 0 ) {
			$dates = self::get_dates( $order );
			if ( empty( $dates[ $to ] ) ) {
				$dates[ $to ] = time();
			}
			// After a backward move, later stages are no longer reached.
			foreach ( array_keys( Shilpi_OS_Stages::get_stages() ) as $i => $key ) {
				if ( $i > $new_index ) {
					unset( $dates[ $key ] );
				}
			}
			$order->update_meta_data( self::META_DATES, $dates );
		}

		$emailed = false;
		if ( $new_index < 0 ) {
			$email_note = __( 'No (order left stage tracking)', 'shilpi-order-stages' );
		} elseif ( $new_index < $old_index ) {
			$email_note = __( 'No (stage moved backwards)', 'shilpi-order-stages' );
		} elseif ( self::$suppress ) {
			$email_note = __( 'No (email option unchecked)', 'shilpi-order-stages' );
		} elseif ( isset( self::get_sent( $order )[ $to ] ) ) {
			$email_note = __( 'No (already sent for this stage)', 'shilpi-order-stages' );
		} else {
			/**
			 * Send the stage email. Return true when an email was sent.
			 *
			 * @param bool     $sent  Sent.
			 * @param WC_Order $order Order.
			 * @param string   $stage Stage key.
			 */
			$emailed = (bool) apply_filters( 'shilpi_os_send_stage_email', false, $order, $to );
			if ( $emailed ) {
				$sent        = self::get_sent( $order );
				$sent[ $to ] = time();
				$order->update_meta_data( self::META_SENT, $sent );
				$email_note = __( 'Yes', 'shilpi-order-stages' );
			} else {
				$email_note = __( 'No (email disabled or handled by WooCommerce)', 'shilpi-order-stages' );
			}
		}

		$order->save_meta_data();

		$order->add_order_note(
			sprintf(
				/* translators: 1: date and time, 2: old stage, 3: new stage, 4: emailed yes/no */
				__( 'Stage changed on %1$s from "%2$s" to "%3$s". Customer emailed: %4$s.', 'shilpi-order-stages' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				Shilpi_OS_Stages::label( $from ),
				Shilpi_OS_Stages::label( $to ),
				$email_note
			)
		);

		/**
		 * Fires after a stage change has been processed.
		 *
		 * @param WC_Order $order   Order.
		 * @param string   $from    Old status.
		 * @param string   $to      New status.
		 * @param bool     $emailed Whether the customer was emailed.
		 */
		do_action( 'shilpi_os_stage_changed', $order, $from, $to, $emailed );
	}
}
