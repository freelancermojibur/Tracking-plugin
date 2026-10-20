<?php
/**
 * Main plugin class.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps all plugin components.
 */
final class Shilpi_OS_Plugin {

	/**
	 * Single instance.
	 *
	 * @var Shilpi_OS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance.
	 *
	 * @return Shilpi_OS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		new Shilpi_OS_Statuses();
		new Shilpi_OS_Stage_Changer();
		new Shilpi_OS_Emails();
		new Shilpi_OS_Tracking();
		if ( is_admin() ) {
			new Shilpi_OS_Meta_Box();
		}
	}

	/**
	 * Whether HPOS (custom order tables) is the active order storage.
	 *
	 * @return bool
	 */
	public static function hpos_enabled() {
		return class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Autoload Shilpi_OS_* classes from includes/ and includes/emails/.
	 *
	 * @param string $class_name Class name.
	 */
	public static function autoload( $class_name ) {
		if ( 0 !== strpos( $class_name, 'Shilpi_OS_' ) ) {
			return;
		}
		$file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
		foreach ( array( 'includes/', 'includes/emails/' ) as $dir ) {
			if ( is_readable( SHILPI_OS_PATH . $dir . $file ) ) {
				require_once SHILPI_OS_PATH . $dir . $file;
				return;
			}
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'shilpi-order-stages', false, dirname( plugin_basename( SHILPI_OS_FILE ) ) . '/languages' );
	}

	/**
	 * Activation callback.
	 */
	public static function activate() {
		add_option( 'shilpi_os_settings', array() );
	}

	/**
	 * Deactivation callback.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'shilpi_os_daily_overdue_check' );
	}
}
