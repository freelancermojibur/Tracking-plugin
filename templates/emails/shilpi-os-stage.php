<?php
/**
 * Order stage email (HTML).
 *
 * Override by copying to yourtheme/woocommerce/emails/shilpi-os-stage.php.
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

do_action( 'woocommerce_email_header', $email_heading, $email );

echo wp_kses_post( wpautop( wptexturize( $message ) ) );

wc_get_template(
	'emails/parts/shilpi-os-progress.php',
	array(
		'order' => $order,
		'stage' => $stage,
	),
	'',
	SHILPI_OS_PATH . 'templates/'
);

wc_get_template(
	'emails/parts/shilpi-os-shipment.php',
	array(
		'order' => $order,
		'stage' => $stage,
	),
	'',
	SHILPI_OS_PATH . 'templates/'
);

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
