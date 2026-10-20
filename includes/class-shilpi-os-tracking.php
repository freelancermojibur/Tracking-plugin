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

		// Customer facing views where order status names use the customer name.
		add_action( 'woocommerce_account_content', array( 'Shilpi_OS_Stages', 'begin_customer_view' ), 1 );
		add_action( 'woocommerce_account_content', array( 'Shilpi_OS_Stages', 'end_customer_view' ), 999 );
		add_action( 'woocommerce_before_thankyou', array( 'Shilpi_OS_Stages', 'begin_customer_view' ), 1 );
		add_action( 'woocommerce_thankyou', array( 'Shilpi_OS_Stages', 'end_customer_view' ), 999 );
		add_action( 'woocommerce_email_header', array( $this, 'email_header' ), 1, 2 );
		add_action( 'woocommerce_email_footer', array( $this, 'email_footer' ), 999 );
	}

	/**
	 * Emails currently rendered as customer views.
	 *
	 * @var int
	 */
	private $email_views = 0;

	/**
	 * Customer emails are customer facing views.
	 *
	 * @param string        $heading Heading.
	 * @param WC_Email|null $email   Email.
	 */
	public function email_header( $heading, $email = null ) {
		if ( $email instanceof WC_Email && $email->is_customer_email() ) {
			Shilpi_OS_Stages::begin_customer_view();
			++$this->email_views;
		}
	}

	/**
	 * End the customer email view.
	 */
	public function email_footer() {
		if ( $this->email_views > 0 ) {
			--$this->email_views;
			Shilpi_OS_Stages::end_customer_view();
		}
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
	 * Whether the order should show a timeline: it is in a custom stage now, or it is
	 * Delivered and actually went through the stages (has stage dates for stages 1 to 8).
	 *
	 * @param WC_Order|false $order Order.
	 * @return bool
	 */
	private function has_timeline( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$status = $order->get_status();
		if ( array_key_exists( $status, Shilpi_OS_Stages::get_custom_stages() ) ) {
			return true;
		}
		// Delivered orders: only when at least one of stages 1 to 8 was actually reached.
		$dates = array_intersect_key( Shilpi_OS_Stage_Changer::get_dates( $order ), Shilpi_OS_Stages::get_custom_stages() );
		return Shilpi_OS_Stages::is_stage( $status ) && ! empty( $dates );
	}

	/**
	 * Timeline HTML for an order.
	 *
	 * @param WC_Order $order           Order.
	 * @param bool     $with_track_link Show the "Track this order any time" link (not on the tracking page itself).
	 * @return string
	 */
	public function render_timeline( $order, $with_track_link = true ) {
		wp_enqueue_style( 'shilpi-os-frontend' );
		return wc_get_template_html(
			'myaccount/shilpi-os-timeline.php',
			array(
				'track_url' => $with_track_link ? Shilpi_OS_Settings::tracking_page_url( $order ) : '',
				'order'     => $order,
				'steps'     => Shilpi_OS_Stages::timeline( $order ),
				'eta'       => Shilpi_OS_Meta_Box::eta_display( $order ),
				'tracking'  => Shilpi_OS_Meta_Box::get( $order, 'tracking_number' ),
				'url'       => Shilpi_OS_Meta_Box::get( $order, 'tracking_url' ),
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
		Shilpi_OS_Stages::begin_customer_view();
		$html = $this->shortcode_html();
		Shilpi_OS_Stages::end_customer_view();
		return $html;
	}

	/**
	 * Build the shortcode output.
	 *
	 * @return string
	 */
	private function shortcode_html() {
		$error  = '';
		$result = '';
		$email  = '';

		// Prefill from ?order= (links in emails, thank you page and My Account). Never contains the email.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only prefill.
		$number    = isset( $_GET['order'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['order'] ) ), 0, 20 ) : '';
		$prefilled = '' !== $number;

		/*
		 * No nonce check on purpose: the page may be served from a full-page cache, where a nonce
		 * would be stale. Protection is the IP rate limit, the honeypot and the requirement that
		 * the order number and billing email match. The form reveals nothing else.
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['shilpi_os_track'] ) ) {
			$number    = isset( $_POST['shilpi_os_order'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['shilpi_os_order'] ) ), 0, 20 ) : '';
			$email     = isset( $_POST['shilpi_os_email'] ) ? sanitize_email( wp_unslash( $_POST['shilpi_os_email'] ) ) : '';
			$honeypot  = isset( $_POST['shilpi_os_website'] ) ? sanitize_text_field( wp_unslash( $_POST['shilpi_os_website'] ) ) : '';
			$prefilled = false;
			$key       = 'shilpi_os_rl_' . md5( $this->client_ip() );
			$tries     = (int) get_transient( $key );
			$not_found = __( 'Sorry, we could not find an order matching those details. Please check and try again.', 'shilpi-order-stages' );

			if ( $tries >= self::RATE_LIMIT ) {
				$error = __( 'Too many attempts. Please wait 15 minutes and try again.', 'shilpi-order-stages' );
			} elseif ( '' !== $honeypot ) {
				// Bots fill the hidden field. Count the attempt and answer like a normal miss.
				set_transient( $key, $tries + 1, self::RATE_WINDOW );
				$error = $not_found;
			} elseif ( '' === $number || ! is_email( $email ) ) {
				$error = __( 'Please enter your order number and the email address used at checkout.', 'shilpi-order-stages' );
			} else {
				set_transient( $key, $tries + 1, self::RATE_WINDOW );
				$order   = $this->find_order( $number );
				$matches = $order && hash_equals( strtolower( $order->get_billing_email() ), strtolower( $email ) );
				if ( $matches && $this->has_timeline( $order ) ) {
					$result = $this->render_timeline( $order, false );
				} elseif ( $matches ) {
					$result = '<p class="shilpi-os-status">' . esc_html(
						/* translators: %s: order status */
						sprintf( __( 'Order status: %s', 'shilpi-order-stages' ), wc_get_order_status_name( $order->get_status() ) )
					) . '</p>';
				} else {
					// Same message whether the order does not exist or the email is wrong.
					$error = $not_found;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return wc_get_template_html(
			'myaccount/shilpi-os-track-form.php',
			array(
				'error'     => $error,
				'result'    => $result,
				'number'    => $number,
				'email'     => $email,
				'prefilled' => $prefilled,
			),
			'',
			SHILPI_OS_PATH . 'templates/'
		);
	}
}
