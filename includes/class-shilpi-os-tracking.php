<?php
/**
 * Customer facing tracking: My Account, order received page and shortcode.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the stage timeline for customers.
 */
class Shilpi_OS_Tracking {

	const RATE_LIMIT  = 5;
	const RATE_WINDOW = 900;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_view_order', array( $this, 'view_order_timeline' ), 5 );
		add_action( 'woocommerce_thankyou', array( $this, 'thankyou_timeline' ), 5 );
		add_shortcode( 'shilpi_track_order', array( $this, 'shortcode' ) );
		// Alias kept for the original project specification.
		add_shortcode( 'sk_track_order', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register front end styles.
	 */
	public function register_assets() {
		wp_register_style( 'shilpi-os-frontend', SHILPI_OS_URL . 'assets/css/frontend.css', array(), SHILPI_OS_VERSION );
		if ( function_exists( 'is_account_page' ) && ( is_account_page() || is_order_received_page() ) ) {
			wp_enqueue_style( 'shilpi-os-frontend' );
		}
	}

	/**
	 * Whether the order should show a timeline.
	 *
	 * @param WC_Order|false $order Order.
	 * @return bool
	 */
	private function has_timeline( $order ) {
		return $order instanceof WC_Order && ( Shilpi_OS_Stages::is_stage( $order->get_status() ) || 'processing' === $order->get_status() );
	}

	/**
	 * Timeline HTML for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public function render_timeline( $order ) {
		wp_enqueue_style( 'shilpi-os-frontend' );
		return wc_get_template_html(
			'myaccount/shilpi-os-timeline.php',
			array(
				'order'    => $order,
				'steps'    => Shilpi_OS_Stages::timeline( $order ),
				'eta'      => Shilpi_OS_Meta_Box::eta_display( $order ),
				'tracking' => Shilpi_OS_Meta_Box::get( $order, 'tracking_number' ),
				'url'      => Shilpi_OS_Meta_Box::get( $order, 'tracking_url' ),
			),
			'',
			SHILPI_OS_PATH . 'templates/'
		);
	}

	/**
	 * My Account > View order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function view_order_timeline( $order_id ) {
		$order = wc_get_order( $order_id );
		// WooCommerce has already checked the customer owns this order.
		if ( $this->has_timeline( $order ) ) {
			echo $this->render_timeline( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in template.
		}
	}

	/**
	 * Order received (thank you) page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_timeline( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $this->has_timeline( $order ) ) {
			echo $this->render_timeline( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in template.
		}
	}

	/**
	 * Visitor IP used for rate limiting.
	 *
	 * @return string
	 */
	private function client_ip() {
		$ip = class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '';
		if ( ! $ip && isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return (string) $ip;
	}

	/**
	 * Find an order by its displayed order number.
	 *
	 * @param string $number Order number.
	 * @return WC_Order|null
	 */
	private function find_order( $number ) {
		$number = ltrim( trim( $number ), '#' );
		/**
		 * Look up an order by number (for sequential order number plugins).
		 *
		 * @param WC_Order|null $order  Order.
		 * @param string        $number Number entered by the customer.
		 */
		$order = apply_filters( 'shilpi_os_find_order_by_number', null, $number );
		if ( ! $order && ctype_digit( $number ) ) {
			$order = wc_get_order( absint( $number ) );
		}
		if ( $order instanceof WC_Order && 'shop_order' === $order->get_type() && (string) $order->get_order_number() === $number ) {
			return $order;
		}
		return null;
	}

	/**
	 * [shilpi_track_order] shortcode.
	 *
	 * @return string
	 */
	public function shortcode() {
		wp_enqueue_style( 'shilpi-os-frontend' );

		$error  = '';
		$result = '';
		$number = '';
		$email  = '';

		if ( isset( $_POST['shilpi_os_track_nonce'] ) ) {
			$number = isset( $_POST['shilpi_os_order'] ) ? sanitize_text_field( wp_unslash( $_POST['shilpi_os_order'] ) ) : '';
			$email  = isset( $_POST['shilpi_os_email'] ) ? sanitize_email( wp_unslash( $_POST['shilpi_os_email'] ) ) : '';
			$key    = 'shilpi_os_rl_' . md5( $this->client_ip() );
			$tries  = (int) get_transient( $key );

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shilpi_os_track_nonce'] ) ), 'shilpi_os_track' ) ) {
				$error = __( 'Your session has expired. Please refresh the page and try again.', 'shilpi-order-stages' );
			} elseif ( $tries >= self::RATE_LIMIT ) {
				$error = __( 'Too many attempts. Please wait 15 minutes and try again.', 'shilpi-order-stages' );
			} elseif ( '' === $number || ! is_email( $email ) ) {
				$error = __( 'Please enter your order number and the email address used at checkout.', 'shilpi-order-stages' );
			} else {
				set_transient( $key, $tries + 1, self::RATE_WINDOW );
				$order = $this->find_order( $number );
				if ( $order && hash_equals( strtolower( $order->get_billing_email() ), strtolower( $email ) ) && $this->has_timeline( $order ) ) {
					$result = $this->render_timeline( $order );
				} elseif ( $order && hash_equals( strtolower( $order->get_billing_email() ), strtolower( $email ) ) ) {
					$result = '<p class="shilpi-os-status">' . esc_html(
						/* translators: %s: order status */
						sprintf( __( 'Order status: %s', 'shilpi-order-stages' ), wc_get_order_status_name( $order->get_status() ) )
					) . '</p>';
				} else {
					// Same message whether the order does not exist or the email is wrong.
					$error = __( 'Sorry, we could not find an order matching those details. Please check and try again.', 'shilpi-order-stages' );
				}
			}
		}

		return wc_get_template_html(
			'myaccount/shilpi-os-track-form.php',
			array(
				'error'  => $error,
				'result' => $result,
				'number' => $number,
				'email'  => $email,
			),
			'',
			SHILPI_OS_PATH . 'templates/'
		);
	}
}
