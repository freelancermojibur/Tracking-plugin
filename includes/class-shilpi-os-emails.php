<?php
/**
 * Registers the stage emails with WooCommerce.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email manager.
 */
class Shilpi_OS_Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register' ) );
		add_filter( 'shilpi_os_send_stage_email', array( $this, 'send' ), 10, 3 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'maybe_disable_core_completed' ), 20 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'maybe_disable_core_processing' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'saved_country_wording_notice' ) );
	}

	/**
	 * On WooCommerce > Settings > Emails, warn when a saved stage email still mentions Australia.
	 * Only saved settings are checked; the built-in defaults are destination neutral.
	 */
	public function saved_country_wording_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( 'wc-settings' !== $page || 'email' !== $tab || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$found = array();
		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( ! $email instanceof Shilpi_OS_Email_Stage ) {
				continue;
			}
			$saved = get_option( 'woocommerce_' . $email->id . '_settings', array() );
			if ( ! is_array( $saved ) ) {
				continue;
			}
			foreach ( array( 'subject', 'heading', 'message', 'additional_content' ) as $field ) {
				if ( isset( $saved[ $field ] ) && is_string( $saved[ $field ] ) && false !== stripos( $saved[ $field ], 'austral' ) ) {
					$found[ $email->id ] = $email->get_title();
					break;
				}
			}
		}
		if ( ! $found ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'These saved emails still mention Australia. Edit them or use Reset to default.', 'shilpi-order-stages' ) . '</p><ul style="list-style:disc; margin-left:20px;">';
		foreach ( $found as $id => $title ) {
			printf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=' . $id ) ),
				esc_html( $title )
			);
		}
		echo '</ul></div>';
	}

	/**
	 * Email class names keyed by stage.
	 *
	 * @return array
	 */
	public static function classes() {
		return array(
			'sos-unpaid'     => 'Shilpi_OS_Email_Stage_Unpaid',
			'sos-production' => 'Shilpi_OS_Email_Stage_Production',
			'sos-factory'    => 'Shilpi_OS_Email_Stage_Factory',
			'sos-warehouse'  => 'Shilpi_OS_Email_Stage_Warehouse',
			'sos-on-ship'    => 'Shilpi_OS_Email_Stage_On_Ship',
			'sos-at-port'    => 'Shilpi_OS_Email_Stage_At_Port',
			'sos-customs'    => 'Shilpi_OS_Email_Stage_Customs',
			'sos-delivery'   => 'Shilpi_OS_Email_Stage_Delivery',
			'completed'      => 'Shilpi_OS_Email_Stage_Delivered',
		);
	}

	/**
	 * Add our emails to WooCommerce.
	 *
	 * @param array $emails Emails.
	 * @return array
	 */
	public function register( $emails ) {
		foreach ( self::classes() as $stage => $class ) {
			if ( Shilpi_OS_Stages::is_stage( $stage ) ) {
				$emails[ $class ] = new $class();
			}
		}
		$emails['Shilpi_OS_Email_Eta_Updated'] = new Shilpi_OS_Email_Eta_Updated();
		return $emails;
	}

	/**
	 * Get the email object for a stage.
	 *
	 * @param string $stage Stage key.
	 * @return Shilpi_OS_Email_Stage|null
	 */
	public static function get_email( $stage ) {
		$classes = self::classes();
		if ( ! isset( $classes[ $stage ] ) ) {
			return null;
		}
		$emails = WC()->mailer()->get_emails();
		return isset( $emails[ $classes[ $stage ] ] ) ? $emails[ $classes[ $stage ] ] : null;
	}

	/**
	 * Get the "Delivery estimate updated" email object.
	 *
	 * @return Shilpi_OS_Email_Eta_Updated|null
	 */
	public static function get_eta_email() {
		$emails = WC()->mailer()->get_emails();
		return isset( $emails['Shilpi_OS_Email_Eta_Updated'] ) ? $emails['Shilpi_OS_Email_Eta_Updated'] : null;
	}

	/**
	 * Send the stage email (called by the stage changer).
	 *
	 * @param bool     $sent  Sent.
	 * @param WC_Order $order Order.
	 * @param string   $stage Stage key.
	 * @return bool
	 */
	public function send( $sent, $order, $stage ) {
		// Without the replace setting, WooCommerce's own Completed email is used for Delivered.
		if ( 'completed' === $stage && ! Shilpi_OS_Settings::enabled( 'replace_completed' ) ) {
			return $sent;
		}
		$email = self::get_email( $stage );
		return $email ? $email->trigger( $order ) : $sent;
	}

	/**
	 * Turn off the core Completed email when our Delivered email replaces it or emails are suppressed.
	 *
	 * @param bool $enabled Enabled.
	 * @return bool
	 */
	public function maybe_disable_core_completed( $enabled ) {
		// "Email the customer" unchecked or a "no email" bulk action also covers the core email.
		if ( Shilpi_OS_Stage_Changer::emails_suppressed() ) {
			return false;
		}
		if ( ! Shilpi_OS_Settings::enabled( 'replace_completed' ) ) {
			return $enabled;
		}
		$ours = self::get_email( 'completed' );
		return ( $ours && $ours->is_enabled() ) ? false : $enabled;
	}

	/**
	 * Turn off the core Processing order email for orders that will be auto-moved,
	 * because the stage 1 email is sent to the customer instead.
	 *
	 * @param bool          $enabled Enabled.
	 * @param WC_Order|null $order   Order.
	 * @return bool
	 */
	public function maybe_disable_core_processing( $enabled, $order = null ) {
		if ( ! $enabled || ! Shilpi_OS_Settings::enabled( 'replace_processing' ) || ! Shilpi_OS_Settings::enabled( 'auto_move' ) ) {
			return $enabled;
		}
		// Without Action Scheduler there is no guarantee the auto-move (and its email) will run.
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return $enabled;
		}
		// Only orders that are about to be auto-moved (not orders moved back to Processing later).
		if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || $order->get_meta( '_shilpi_os_auto_moved' ) ) {
			return $enabled;
		}
		$keys = array_keys( Shilpi_OS_Stages::get_stages() );
		$ours = self::get_email( $keys[0] );
		return ( $ours && $ours->is_enabled() ) ? false : $enabled;
	}
}
