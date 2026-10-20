<?php
/**
 * Plugin Name:          Shilpi Order Stages
 * Plugin URI:           https://mojibur.com
 * Description:          Track every order from supplier payment to delivery with custom WooCommerce order stages, automatic customer emails and a tracking timeline.
 * Version:              1.1.3
 * Author:               Shilpi
 * Author URI:           https://mojibur.com
 * Text Domain:          shilpi-order-stages
 * Domain Path:          /languages
 * Requires at least:    6.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

// Another copy of this plugin is already loaded (for example an old folder with a different name).
// Stop here instead of causing a fatal error, and tell the admin which copy to remove.
if ( defined( 'SHILPI_OS_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>' . esc_html(
				sprintf(
					/* translators: 1: folder of the copy in use, 2: folder of the duplicate copy */
					__( 'Shilpi Order Stages is installed twice. The copy in "%1$s" is running; please deactivate and delete the duplicate in "%2$s".', 'shilpi-order-stages' ),
					basename( dirname( SHILPI_OS_FILE ) ),
					basename( __DIR__ )
				)
			) . '</p></div>';
		}
	);
	return;
}

define( 'SHILPI_OS_VERSION', '1.1.3' );
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
 *
 * Closures are used instead of named functions on purpose: PHP declares named
 * top-level functions before the duplicate copy guard above can run.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Shilpi Order Stages requires WooCommerce to be installed and active.', 'shilpi-order-stages' ) . '</p></div>';
				}
			);
			return;
		}
		Shilpi_OS_Plugin::instance();
	},
	20
);

register_activation_hook( __FILE__, array( 'Shilpi_OS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Shilpi_OS_Plugin', 'deactivate' ) );
