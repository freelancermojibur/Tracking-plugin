<?php
/**
 * Plugin Name:          Shilpi Order Stages
 * Plugin URI:           https://mojibur.com
 * Description:          Track every order from supplier payment to delivery with custom WooCommerce order stages, automatic customer emails and a tracking timeline.
 * Version:              1.0.0
 * Author:               Shilpi
 * Author URI:           https://mojibur.com
 * Text Domain:          shilpi-order-stages
 * Domain Path:          /languages
 * Requires at least:    6.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 8.0
 * WC tested up to:      10.3
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

define( 'SHILPI_OS_VERSION', '1.0.0' );
define( 'SHILPI_OS_FILE', __FILE__ );
define( 'SHILPI_OS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHILPI_OS_URL', plugin_dir_url( __FILE__ ) );

require_once SHILPI_OS_PATH . 'includes/class-shilpi-os-plugin.php';
spl_autoload_register( array( 'Shilpi_OS_Plugin', 'autoload' ) );

/**
 * Declare compatibility with WooCommerce features (HPOS).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SHILPI_OS_FILE, true );
		}
	}
);

/**
 * Start the plugin once all plugins are loaded.
 */
function shilpi_os_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'shilpi_os_missing_wc_notice' );
		return;
	}
	Shilpi_OS_Plugin::instance();
}
add_action( 'plugins_loaded', 'shilpi_os_init', 20 );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function shilpi_os_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Shilpi Order Stages requires WooCommerce to be installed and active.', 'shilpi-order-stages' ) . '</p></div>';
}

register_activation_hook( __FILE__, array( 'Shilpi_OS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Shilpi_OS_Plugin', 'deactivate' ) );
