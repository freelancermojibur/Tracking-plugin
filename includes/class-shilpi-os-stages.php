<?php
/**
 * Stage definitions and helpers.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for the order stages.
 */
class Shilpi_OS_Stages {

	/**
	 * Get all stages in order, keyed by order status (without the "wc-" prefix).
	 *
	 * Each stage: label (admin), short (customer facing name), color (badge background).
	 *
	 * @return array
	 */
	public static function get_stages() {
		$stages = array(
			'sos-unpaid'     => array(
				'label' => __( 'Supplier unpaid', 'shilpi-order-stages' ),
				'short' => __( 'Order confirmed', 'shilpi-order-stages' ),
				'color' => '#d63638',
			),
			'sos-production' => array(
				'label' => __( 'Paid, in production', 'shilpi-order-stages' ),
				'short' => __( 'In production', 'shilpi-order-stages' ),
				'color' => '#dba617',
			),
			'sos-factory'    => array(
				'label' => __( 'Factory ready', 'shilpi-order-stages' ),
				'short' => __( 'Factory ready', 'shilpi-order-stages' ),
				'color' => '#b26200',
			),
			'sos-warehouse'  => array(
				'label' => __( 'At forwarder warehouse', 'shilpi-order-stages' ),
				'short' => __( 'At warehouse', 'shilpi-order-stages' ),
				'color' => '#8c5fbf',
			),
			'sos-on-ship'    => array(
				'label' => __( 'On ship', 'shilpi-order-stages' ),
				'short' => __( 'On ship', 'shilpi-order-stages' ),
				'color' => '#2271b1',
			),
			'sos-at-port'    => array(
				'label' => __( 'Arrived at port', 'shilpi-order-stages' ),
				'short' => __( 'At port', 'shilpi-order-stages' ),
				'color' => '#135e96',
			),
			'sos-customs'    => array(
				'label' => __( 'Customs clearance', 'shilpi-order-stages' ),
				'short' => __( 'Customs', 'shilpi-order-stages' ),
				'color' => '#3c434a',
			),
			'sos-delivery'   => array(
				'label' => __( 'Out for delivery', 'shilpi-order-stages' ),
				'short' => __( 'Out for delivery', 'shilpi-order-stages' ),
				'color' => '#00a32a',
			),
			'completed'      => array(
				'label' => __( 'Delivered', 'shilpi-order-stages' ),
				'short' => __( 'Delivered', 'shilpi-order-stages' ),
				'color' => '#007017',
			),
		);

		/**
		 * Filter the stage list. Keys are order statuses without "wc-".
		 * Keys must stay 17 characters ("wc-" adds 3, max 20) or less and "completed" must remain last.
		 *
		 * @param array $stages Stages.
		 */
		$stages = apply_filters( 'shilpi_os_stages', $stages );

		// Labels and colours customised in the settings page.
		foreach ( $stages as $key => $stage ) {
			$label = Shilpi_OS_Settings::get( 'label_' . $key );
			$short = Shilpi_OS_Settings::get( 'short_' . $key );
			$color = Shilpi_OS_Settings::get( 'color_' . $key );
			if ( is_string( $short ) && '' !== trim( $short ) ) {
				$stages[ $key ]['short'] = $short;
			}
			if ( is_string( $label ) && '' !== trim( $label ) ) {
				$stages[ $key ]['label'] = $label;
			}
			if ( is_string( $color ) && sanitize_hex_color( $color ) ) {
				$stages[ $key ]['color'] = $color;
			}
		}
		return $stages;
	}

	/**
	 * Custom stages only (excludes core "completed").
	 *
	 * @return array
	 */
	public static function get_custom_stages() {
		$stages = self::get_stages();
		unset( $stages['completed'] );
		return $stages;
	}

	/**
	 * Whether a status (with or without "wc-") is one of our stages.
	 *
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_stage( $status ) {
		return array_key_exists( self::strip( $status ), self::get_stages() );
	}

	/**
	 * Zero-based position of a status in the stage list, or -1.
	 *
	 * @param string $status Status.
	 * @return int
	 */
	public static function index( $status ) {
		$pos = array_search( self::strip( $status ), array_keys( self::get_stages() ), true );
		return false === $pos ? -1 : (int) $pos;
	}

	/**
	 * Next stage key after the given status, or null.
	 *
	 * @param string $status Status.
	 * @return string|null
	 */
	public static function next( $status ) {
		$keys = array_keys( self::get_stages() );
		$i    = self::index( $status );
		if ( -1 === $i ) {
			return 'processing' === self::strip( $status ) ? $keys[0] : null;
		}
		return isset( $keys[ $i + 1 ] ) ? $keys[ $i + 1 ] : null;
	}

	/**
	 * Label for a stage key.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function label( $status ) {
		$stages = self::get_stages();
		$key    = self::strip( $status );
		return isset( $stages[ $key ] ) ? $stages[ $key ]['label'] : wc_get_order_status_name( $key );
	}

	/**
	 * Timeline steps for an order.
	 *
	 * Each step: key, label, short, state (done|current|upcoming), date (timestamp or null).
	 *
	 * @param WC_Order    $order Order.
	 * @param string|null $stage Stage to show as current (defaults to the order status).
	 * @return array
	 */
	public static function timeline( $order, $stage = null ) {
		$current = self::index( $stage ? $stage : $order->get_status() );
		$dates   = Shilpi_OS_Stage_Changer::get_dates( $order );
		$steps   = array();
		$i       = 0;
		foreach ( self::get_stages() as $key => $stage ) {
			if ( $i < $current ) {
				$state = 'done';
			} elseif ( $i === $current ) {
				$state = 'current';
			} else {
				$state = 'upcoming';
			}
			$steps[] = array(
				'key'   => $key,
				'label' => $stage['label'],
				'short' => $stage['short'],
				'state' => $state,
				'date'  => ( 'upcoming' !== $state && ! empty( $dates[ $key ] ) ) ? (int) $dates[ $key ] : null,
			);
			++$i;
		}
		return $steps;
	}

	/**
	 * Customer facing name for a stage.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function customer_label( $status ) {
		$stages = self::get_stages();
		$key    = self::strip( $status );
		return isset( $stages[ $key ] ) ? $stages[ $key ]['short'] : wc_get_order_status_name( $key );
	}

	/**
	 * Remove a leading "wc-".
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function strip( $status ) {
		return 0 === strpos( (string) $status, 'wc-' ) ? substr( $status, 3 ) : (string) $status;
	}
}
