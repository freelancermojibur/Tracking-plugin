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

echo "=================================================\n";
echo esc_html( wp_strip_all_tags( $email_heading ) ) . "\n";
echo "=================================================\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( $message ) ) ) . "\n\n";

echo esc_html__( 'Progress', 'shilpi-order-stages' ) . "\n";
foreach ( Shilpi_OS_Stages::timeline( $order, $stage ) as $shilpi_os_i => $shilpi_os_step ) {
	if ( 'done' === $shilpi_os_step['state'] ) {
		$shilpi_os_mark = '[x]';
	} elseif ( 'current' === $shilpi_os_step['state'] ) {
		$shilpi_os_mark = '[>]';
	} else {
		$shilpi_os_mark = '[ ]';
	}
	echo esc_html( $shilpi_os_mark . ' ' . ( $shilpi_os_i + 1 ) . '. ' . $shilpi_os_step['label'] ) . "\n";
}
echo "\n";

echo esc_html__( 'Current stage:', 'shilpi-order-stages' ) . ' ' . esc_html( Shilpi_OS_Stages::label( $stage ) ) . "\n";
if ( 'completed' !== $stage ) {
	$shilpi_os_eta = Shilpi_OS_Meta_Box::eta_display( $order );
	echo esc_html__( 'Estimated delivery:', 'shilpi-order-stages' ) . ' ' . esc_html( $shilpi_os_eta ? $shilpi_os_eta : __( 'To be confirmed', 'shilpi-order-stages' ) ) . "\n";
}
$shilpi_os_tracking = Shilpi_OS_Meta_Box::get( $order, 'tracking_number' );
$shilpi_os_url      = Shilpi_OS_Meta_Box::get( $order, 'tracking_url' );
if ( $shilpi_os_tracking ) {
	echo esc_html__( 'Tracking number:', 'shilpi-order-stages' ) . ' ' . esc_html( $shilpi_os_tracking ) . "\n";
}
if ( $shilpi_os_url ) {
	echo esc_html__( 'Track your shipment:', 'shilpi-order-stages' ) . ' ' . esc_url_raw( $shilpi_os_url ) . "\n";
}
echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
