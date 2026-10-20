<?php
/**
 * Plugin settings.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and reads plugin settings (option: shilpi_os_settings).
 */
class Shilpi_OS_Settings {

	const OPTION = 'shilpi_os_settings';

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'auto_move'         => 'yes',
			'replace_completed' => 'yes',
			'overdue_enabled'   => 'yes',
			'overdue_email'     => get_option( 'admin_email' ),
		);
	}

	/**
	 * Get a setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$saved    = get_option( self::OPTION, array() );
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Whether a yes/no setting is on.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function enabled( $key ) {
		return 'yes' === self::get( $key );
	}
}
