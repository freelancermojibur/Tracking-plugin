<?php
/**
 * Daily overdue alert.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emails the store owner a list of orders whose ETA has passed.
 */
class Shilpi_OS_Cron {

	const HOOK = 'shilpi_os_daily_overdue_check';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( self::HOOK, array( __CLASS__, 'send_overdue_alert' ) );
		add_action( 'admin_init', array( __CLASS__, 'schedule' ) );
	}

	/**
	 * Schedule the daily event at 08:00 site time, if not already scheduled.
	 */
	public static function schedule() {
		if ( wp_next_scheduled( self::HOOK ) ) {
			return;
		}
		$next = date_create_immutable( 'today 08:00', wp_timezone() );
		if ( $next && $next->getTimestamp() <= time() ) {
			$next = $next->modify( '+1 day' );
		}
		wp_schedule_event( $next ? $next->getTimestamp() : time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
	}

	/**
	 * Remove the scheduled event.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Send the overdue list to the configured address.
	 *
	 * @return bool Whether an email was sent.
	 */
	public static function send_overdue_alert() {
		if ( ! Shilpi_OS_Settings::enabled( 'overdue_enabled' ) ) {
			return false;
		}
		$to = sanitize_email( (string) Shilpi_OS_Settings::get( 'overdue_email' ) );
		if ( ! is_email( $to ) ) {
			$to = get_option( 'admin_email' );
		}
		$orders = Shilpi_OS_Dashboard::overdue_orders();
		if ( ! $orders ) {
			return false;
		}

		$rows = '';
		foreach ( $orders as $order ) {
			$days  = abs( Shilpi_OS_Dashboard::days_until( Shilpi_OS_Meta_Box::get( $order, 'eta' ) ) );
			$rows .= sprintf(
				'<tr><td style="padding:6px;border:1px solid #e5e5e5;"><a href="%1$s">#%2$s</a></td><td style="padding:6px;border:1px solid #e5e5e5;">%3$s</td><td style="padding:6px;border:1px solid #e5e5e5;">%4$s</td><td style="padding:6px;border:1px solid #e5e5e5;">%5$s</td><td style="padding:6px;border:1px solid #e5e5e5;">%6$s</td></tr>',
				esc_url( $order->get_edit_order_url() ),
				esc_html( $order->get_order_number() ),
				esc_html( $order->get_formatted_billing_full_name() ),
				esc_html( Shilpi_OS_Stages::label( $order->get_status() ) ),
				esc_html( Shilpi_OS_Meta_Box::eta_display( $order ) ),
				/* translators: %d: days overdue */
				esc_html( sprintf( _n( '%d day', '%d days', $days, 'shilpi-order-stages' ), $days ) )
			);
		}

		$intro = sprintf(
			/* translators: %d: number of orders */
			_n( '%d order has passed its ETA and has not been delivered yet:', '%d orders have passed their ETA and have not been delivered yet:', count( $orders ), 'shilpi-order-stages' ),
			count( $orders )
		);
		$body  = '<p>' . esc_html( $intro ) . '</p>';
		$body .= '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;"><thead><tr>';
		foreach ( array( __( 'Order', 'shilpi-order-stages' ), __( 'Customer', 'shilpi-order-stages' ), __( 'Stage', 'shilpi-order-stages' ), __( 'ETA', 'shilpi-order-stages' ), __( 'Overdue by', 'shilpi-order-stages' ) ) as $th ) {
			$body .= '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html( $th ) . '</th>';
		}
		$body .= '</tr></thead><tbody>' . $rows . '</tbody></table>';
		$body .= '<p><a href="' . esc_url( admin_url( 'admin.php?page=shilpi-os-dashboard' ) ) . '">' . esc_html__( 'Open the Order Stages dashboard', 'shilpi-order-stages' ) . '</a></p>';

		/* translators: 1: site name, 2: number of orders */
		$subject = sprintf( __( '[%1$s] %2$d overdue order(s)', 'shilpi-order-stages' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), count( $orders ) );
		$mailer  = WC()->mailer();
		$message = $mailer->wrap_message( __( 'Overdue orders', 'shilpi-order-stages' ), $body );

		return (bool) $mailer->send( $to, $subject, $message );
	}
}
