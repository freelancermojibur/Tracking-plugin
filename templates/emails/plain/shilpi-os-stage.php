<?php
/**
 * Order stage email (plain text).
 *
 * Override by copying to yourtheme/woocommerce/emails/plain/shilpi-os-stage.php.
 *
 * @package Shilpi_Order_Stages
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $message
 * @var string   $additional_content
 * @var string   $stage
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email hooks.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text email: HTML escaping would show "&amp;" to the reader, so text is stripped and decoded instead.

/**
 * Convert a string to plain text for this email.
 *
 * @param string $text Text.
 * @return string
 */
$shilpi_os_txt = static function ( $text ) {
	return wp_specialchars_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES );
};

echo "=================================================\n";
echo $shilpi_os_txt( $email_heading ) . "\n";
echo "=================================================\n\n";

echo $shilpi_os_txt( $message ) . "\n\n";

if ( 'completed' === $stage && Shilpi_OS_Settings::review_url() ) {
	echo $shilpi_os_txt( __( 'Leave a review:', 'shilpi-order-stages' ) ) . ' ' . esc_url_raw( Shilpi_OS_Settings::review_url() ) . "\n\n";
}

echo $shilpi_os_txt( __( 'Progress', 'shilpi-order-stages' ) ) . "\n";
foreach ( Shilpi_OS_Stages::timeline( $order, $stage ) as $shilpi_os_i => $shilpi_os_step ) {
	if ( ! empty( $shilpi_os_step['skipped'] ) ) {
		$shilpi_os_mark = '[-]';
	} elseif ( 'done' === $shilpi_os_step['state'] || 'completed' === $stage ) {
		$shilpi_os_mark = '[x]';
	} elseif ( 'current' === $shilpi_os_step['state'] ) {
		$shilpi_os_mark = '[>]';
	} else {
		$shilpi_os_mark = '[ ]';
	}
	$shilpi_os_note = ! empty( $shilpi_os_step['skipped'] ) ? ' (' . __( 'skipped', 'shilpi-order-stages' ) . ')' : '';
	echo $shilpi_os_txt( $shilpi_os_mark . ' ' . ( $shilpi_os_i + 1 ) . '. ' . $shilpi_os_step['short'] . $shilpi_os_note ) . "\n";
}
echo "\n";

// Same summary as the HTML email: "Step X of 9: Stage" and the next step, or "Delivered".
$shilpi_os_steps = Shilpi_OS_Stages::timeline( $order, $stage );
$shilpi_os_pos   = Shilpi_OS_Stages::index( $stage );
if ( count( $shilpi_os_steps ) - 1 === $shilpi_os_pos ) {
	echo $shilpi_os_txt( $shilpi_os_steps[ $shilpi_os_pos ]['short'] ) . "\n";
} elseif ( $shilpi_os_pos >= 0 ) {
	/* translators: 1: step number, 2: total steps, 3: stage name */
	echo $shilpi_os_txt( sprintf( __( 'Step %1$d of %2$d: %3$s', 'shilpi-order-stages' ), $shilpi_os_pos + 1, count( $shilpi_os_steps ), $shilpi_os_steps[ $shilpi_os_pos ]['short'] ) ) . "\n";
	if ( isset( $shilpi_os_steps[ $shilpi_os_pos + 1 ] ) ) {
		/* translators: %s: next stage name */
		echo $shilpi_os_txt( sprintf( __( 'Next: %s', 'shilpi-order-stages' ), $shilpi_os_steps[ $shilpi_os_pos + 1 ]['short'] ) ) . "\n";
	}
	echo "\n";
}

$shilpi_os_track_url = Shilpi_OS_Settings::tracking_page_url( $order );
if ( $shilpi_os_track_url ) {
	echo $shilpi_os_txt( __( 'Track your order:', 'shilpi-order-stages' ) ) . ' ' . esc_url_raw( $shilpi_os_track_url ) . "\n\n";
}
if ( 'completed' !== $stage ) {
	$shilpi_os_eta = Shilpi_OS_Meta_Box::eta_display( $order );
	echo $shilpi_os_txt( __( 'Estimated delivery:', 'shilpi-order-stages' ) . ' ' . ( $shilpi_os_eta ? $shilpi_os_eta : __( 'To be confirmed', 'shilpi-order-stages' ) ) ) . "\n";
}
// Delivered emails do not repeat tracking details.
$shilpi_os_tracking = 'completed' === $stage ? '' : Shilpi_OS_Meta_Box::get( $order, 'tracking_number' );
$shilpi_os_url      = 'completed' === $stage ? '' : Shilpi_OS_Meta_Box::get( $order, 'tracking_url' );
if ( $shilpi_os_tracking ) {
	echo $shilpi_os_txt( __( 'Tracking number:', 'shilpi-order-stages' ) . ' ' . $shilpi_os_tracking ) . "\n";
}
if ( $shilpi_os_url ) {
	echo $shilpi_os_txt( __( 'Track your shipment:', 'shilpi-order-stages' ) ) . ' ' . esc_url_raw( $shilpi_os_url ) . "\n";
}
echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo "\n" . $shilpi_os_txt( $additional_content ) . "\n";
}

echo "\n----------------------------------------\n\n";
echo $shilpi_os_txt( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
