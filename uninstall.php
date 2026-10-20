<?php
/**
 * Uninstall: delete plugin options only. Order data (meta, notes) is kept.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'shilpi_os_settings' );
wp_clear_scheduled_hook( 'shilpi_os_daily_overdue_check' );

// Stage email settings saved by WooCommerce.
foreach ( array( 'unpaid', 'production', 'factory', 'warehouse', 'on_ship', 'at_port', 'customs', 'delivery', 'delivered' ) as $shilpi_os_email ) {
	delete_option( 'woocommerce_shilpi_os_' . $shilpi_os_email . '_settings' );
}
