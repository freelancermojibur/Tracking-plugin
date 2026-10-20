<?php
/**
 * Shipment & Supplier meta box.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta box on the order edit screen (legacy and HPOS).
 */
class Shilpi_OS_Meta_Box {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add' ), 30 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ), 10, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'move_next' ), 60, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'maybe_email_eta' ), 70, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'woocommerce_order_action_shilpi_os_resend_stage', array( $this, 'resend_stage_email' ) );
	}

	/**
	 * Order status when the save request started.
	 *
	 * @var string|null
	 */
	private $status_before = null;

	/**
	 * Previous ETA when the ETA changed in this save, otherwise null.
	 *
	 * @var string|null
	 */
	private $old_eta = null;

	/**
	 * Field definitions: key => label, type.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'supplier'         => array( __( 'Supplier name', 'shilpi-order-stages' ), 'text' ),
			'supplier_invoice' => array( __( 'Supplier invoice / PI #', 'shilpi-order-stages' ), 'text' ),
			/* translators: %s: currency code */
			'cost'             => array( sprintf( __( 'Total cost %s (supplier + freight + duty)', 'shilpi-order-stages' ), get_woocommerce_currency() ), 'price' ),
			'forwarder'        => array( __( 'Freight forwarder', 'shilpi-order-stages' ), 'text' ),
			'container'        => array( __( 'Container / BL / vessel', 'shilpi-order-stages' ), 'text' ),
			'tracking_number'  => array( __( 'Tracking number', 'shilpi-order-stages' ), 'text' ),
			'tracking_url'     => array( __( 'Tracking URL (optional)', 'shilpi-order-stages' ), 'url' ),
			'eta'              => array( __( 'Current ETA', 'shilpi-order-stages' ), 'date' ),
			'notes'            => array( __( 'Internal notes (never shown to customer)', 'shilpi-order-stages' ), 'textarea' ),
		);
	}

	/**
	 * Read a shipment field from an order.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $key   Field key.
	 * @return string
	 */
	public static function get( $order, $key ) {
		return (string) $order->get_meta( '_shilpi_os_' . $key );
	}

	/**
	 * ETA formatted with the site date format, or empty string.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function eta_display( $order ) {
		return self::format_date( self::get( $order, 'eta' ) );
	}

	/**
	 * Register the meta box on both order screens.
	 */
	public function add() {
		$screen = Shilpi_OS_Plugin::hpos_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'shilpi-os-stage', __( 'Order stage', 'shilpi-order-stages' ), array( $this, 'render_stage' ), $screen, 'side', 'high' );
		add_meta_box( 'shilpi-os-shipment', __( 'Shipment & Supplier', 'shilpi-order-stages' ), array( $this, 'render' ), $screen, 'normal', 'high' );
	}

	/**
	 * Get the order from a meta box callback argument.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 * @return WC_Order|false
	 */
	private function order_from( $post_or_order ) {
		return $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	}

	/**
	 * Render the side "Order stage" box: stage, mini stepper, next stage button, email checkbox.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 */
	public function render_stage( $post_or_order ) {
		$order = $this->order_from( $post_or_order );
		if ( ! $order ) {
			return;
		}
		$status = $order->get_status();
		$next   = Shilpi_OS_Stages::next( $status );
		$dates  = Shilpi_OS_Stage_Changer::get_dates( $order );

		echo '<div class="shilpi-os-stage-box">';
		echo '<input type="hidden" name="shilpi_os_status_before" value="' . esc_attr( $status ) . '" />';
		echo '<p class="shilpi-os-current"><strong>' . esc_html( Shilpi_OS_Stages::label( $status ) ) . '</strong>';
		if ( ! empty( $dates[ $status ] ) ) {
			/* translators: %s: date */
			echo '<br /><span class="description">' . esc_html( sprintf( __( 'since %s', 'shilpi-order-stages' ), wp_date( get_option( 'date_format' ), $dates[ $status ] ) ) ) . '</span>';
		}
		echo '</p>';

		echo '<ol class="shilpi-os-stepper">';
		foreach ( Shilpi_OS_Stages::timeline( $order ) as $i => $step ) {
			$title = ( $i + 1 ) . '. ' . $step['label'];
			if ( $step['skipped'] ) {
				$title .= ' (' . __( 'skipped', 'shilpi-order-stages' ) . ')';
			} elseif ( $step['date'] ) {
				$title .= ' (' . wp_date( get_option( 'date_format' ), $step['date'] ) . ')';
			}
			printf(
				'<li class="is-%1$s" title="%2$s"><span class="screen-reader-text">%2$s</span></li>',
				esc_attr( $step['state'] . ( $step['skipped'] ? ' is-skipped' : '' ) ),
				esc_attr( $title )
			);
		}
		echo '</ol>';

		if ( $next ) {
			printf(
				'<p><button type="submit" class="button button-primary shilpi-os-next" name="shilpi_os_next_stage" value="1">%s</button></p>',
				/* translators: %s: next stage name */
				esc_html( sprintf( __( 'Move to next stage: %s', 'shilpi-order-stages' ), Shilpi_OS_Stages::label( $next ) ) )
			);
		}
		printf(
			'<p><label><input type="checkbox" name="shilpi_os_notify" value="1" checked="checked" /> %s</label></p>',
			esc_html__( 'Email the customer about this change', 'shilpi-order-stages' )
		);
		echo '</div>';
	}

	/**
	 * Enqueue admin assets on order screens.
	 */
	public function assets() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, Shilpi_OS_Statuses::order_screen_ids(), true ) ) {
			return;
		}
		wp_enqueue_style( 'shilpi-os-admin', SHILPI_OS_URL . 'assets/css/admin.css', array(), SHILPI_OS_VERSION );
		wp_enqueue_script( 'shilpi-os-admin', SHILPI_OS_URL . 'assets/js/admin.js', array( 'jquery' ), SHILPI_OS_VERSION, true );
		wp_localize_script(
			'shilpi-os-admin',
			'shilpiOsAdmin',
			array(
				'decimals'  => wc_get_price_decimals(),
				'decimal'   => wc_get_price_decimal_separator(),
				'thousands' => wc_get_price_thousand_separator(),
				'symbol'    => html_entity_decode( get_woocommerce_currency_symbol() ),
			)
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 */
	public function render( $post_or_order ) {
		$order = $this->order_from( $post_or_order );
		if ( ! $order ) {
			return;
		}
		wp_nonce_field( 'shilpi_os_save', 'shilpi_os_nonce' );

		$dates = Shilpi_OS_Stage_Changer::get_dates( $order );
		$sent  = Shilpi_OS_Stage_Changer::get_sent( $order );
		$cost  = self::get( $order, 'cost' );
		$total = (float) $order->get_total();

		echo '<div class="shilpi-os-box">';

		echo '<div class="shilpi-os-fields">';
		foreach ( self::fields() as $key => $field ) {
			$id    = 'shilpi_os_' . $key;
			$value = self::get( $order, $key );
			echo '<p class="form-field shilpi-os-field-' . esc_attr( $key ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $field[0] ) . '</label>';
			switch ( $field[1] ) {
				case 'textarea':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" rows="3">' . esc_textarea( $value ) . '</textarea>';
					break;
				case 'price':
					echo '<input type="text" class="wc_input_price" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( wc_format_localized_price( $value ) ) . '" />';
					break;
				default:
					echo '<input type="' . esc_attr( $field[1] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" />';
			}
			if ( 'eta' === $key && Shilpi_OS_Stages::index( $order->get_status() ) >= 0 && 'completed' !== $order->get_status() ) {
				printf(
					'<label class="shilpi-os-eta-notify"><input type="checkbox" name="shilpi_os_eta_notify" value="1" /> %s</label>',
					esc_html__( 'Email the customer about the new ETA', 'shilpi-order-stages' )
				);
			}
			echo '</p>';
		}
		echo '</div>';

		echo '<p class="shilpi-os-profit" data-total="' . esc_attr( $total ) . '"><strong>' . esc_html__( 'Profit:', 'shilpi-order-stages' ) . '</strong> <span>';
		if ( '' === $cost ) {
			echo esc_html__( 'Enter the total cost to calculate profit.', 'shilpi-order-stages' );
		} else {
			echo wp_kses_post( wc_price( $total - (float) $cost, array( 'currency' => $order->get_currency() ) ) );
		}
		echo '</span> <span class="description">' . esc_html__( '(order total minus total cost)', 'shilpi-order-stages' ) . '</span></p>';

		if ( $dates ) {
			echo '<h4>' . esc_html__( 'Stage history', 'shilpi-order-stages' ) . '</h4><ul class="shilpi-os-history">';
			foreach ( Shilpi_OS_Stages::get_stages() as $key => $stage ) {
				if ( empty( $dates[ $key ] ) ) {
					continue;
				}
				printf(
					'<li>%1$s: %2$s%3$s</li>',
					esc_html( $stage['label'] ),
					esc_html( wp_date( get_option( 'date_format' ), $dates[ $key ] ) ),
					isset( $sent[ $key ] ) ? ' <span class="description">(' . esc_html__( 'customer emailed', 'shilpi-order-stages' ) . ')</span>' : ''
				);
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	/**
	 * Whether the current request is a valid meta box save.
	 *
	 * @return bool
	 */
	private function verified() {
		return isset( $_POST['shilpi_os_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shilpi_os_nonce'] ) ), 'shilpi_os_save' )
			&& current_user_can( 'edit_shop_orders' );
	}

	/**
	 * Save fields. Runs before WooCommerce saves the order status (priority 40).
	 *
	 * @param int $order_id Order ID.
	 */
	public function save( $order_id ) {
		if ( ! $this->verified() ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce checked in verified().
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// The checkbox controls emails for any stage change made in this save.
		Shilpi_OS_Stage_Changer::suppress_emails( empty( $_POST['shilpi_os_notify'] ) );

		$this->status_before = $order->get_status();
		$eta_before          = self::get( $order, 'eta' );

		foreach ( self::fields() as $key => $field ) {
			$name = 'shilpi_os_' . $key;
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per type below.
			$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			switch ( $field[1] ) {
				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;
				case 'price':
					$value = '' === trim( (string) $raw ) ? '' : wc_format_decimal( sanitize_text_field( $raw ) );
					break;
				case 'url':
					$value = esc_url_raw( trim( (string) $raw ) );
					break;
				case 'date':
					$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $raw ) ? (string) $raw : '';
					break;
				default:
					$value = sanitize_text_field( $raw );
			}
			if ( '' === $value ) {
				$order->delete_meta_data( '_shilpi_os_' . $key );
			} else {
				$order->update_meta_data( '_shilpi_os_' . $key, $value );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$order->save();

		$eta_after     = self::get( $order, 'eta' );
		$this->old_eta = ( $eta_after !== $eta_before ) ? $eta_before : null;
	}

	/**
	 * Apply the "Move to next stage" button after WooCommerce has saved the order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function move_next( $order_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce checked in verified().
		if ( ! $this->verified() || empty( $_POST['shilpi_os_next_stage'] ) ) {
			return;
		}
		$before   = isset( $_POST['shilpi_os_status_before'] ) ? sanitize_key( wp_unslash( $_POST['shilpi_os_status_before'] ) ) : '';
		$dropdown = isset( $_POST['order_status'] ) ? Shilpi_OS_Stages::strip( sanitize_key( wp_unslash( $_POST['order_status'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// The Status dropdown was also changed: apply only that change, never a double jump.
		if ( $before && $dropdown && $dropdown !== $before ) {
			WC_Admin_Meta_Boxes::add_error( __( 'The "Move to next stage" button was ignored because the Status dropdown was also changed. The status you picked in the dropdown was saved.', 'shilpi-order-stages' ) );
			return;
		}

		$order = wc_get_order( $order_id );
		$next  = $order ? Shilpi_OS_Stages::next( $order->get_status() ) : null;
		if ( $next ) {
			$order->update_status( $next );
		}
	}

	/**
	 * After all saves: email the customer about a changed ETA when asked and the stage did not change.
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_email_eta( $order_id ) {
		if ( null === $this->old_eta || ! $this->verified() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$old_eta       = $this->old_eta;
		$this->old_eta = null;
		$stage_changed = $order->get_status() !== $this->status_before;
		$wanted        = ! empty( $_POST['shilpi_os_eta_notify'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verified().
		$emailed       = false;

		if ( $wanted && ! $stage_changed && Shilpi_OS_Stages::index( $order->get_status() ) >= 0 && 'completed' !== $order->get_status() ) {
			$email   = Shilpi_OS_Emails::get_eta_email();
			$emailed = $email ? $email->trigger_eta( $order, $old_eta ) : false;
		}

		$new_display = self::eta_display( $order );
		$order->add_order_note(
			sprintf(
				/* translators: 1: old ETA, 2: new ETA, 3: yes/no */
				__( 'ETA changed from %1$s to %2$s. Customer emailed: %3$s.', 'shilpi-order-stages' ),
				$old_eta ? self::format_date( $old_eta ) : __( 'not set', 'shilpi-order-stages' ),
				$new_display ? $new_display : __( 'not set', 'shilpi-order-stages' ),
				$emailed ? __( 'Yes', 'shilpi-order-stages' ) : __( 'No', 'shilpi-order-stages' )
			)
		);
	}

	/**
	 * Format a Y-m-d date with the site date format.
	 *
	 * @param string $ymd Date.
	 * @return string
	 */
	public static function format_date( $ymd ) {
		$date = $ymd ? date_create_immutable( $ymd, wp_timezone() ) : false;
		return $date ? wp_date( get_option( 'date_format' ), $date->getTimestamp() ) : '';
	}

	/**
	 * Add "Resend current stage email" to the Order actions dropdown.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	public function order_actions( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			global $theorder;
			$order = $theorder;
		}
		if ( $order instanceof WC_Order && Shilpi_OS_Stages::is_stage( $order->get_status() ) ) {
			$actions['shilpi_os_resend_stage'] = __( 'Resend current stage email to customer', 'shilpi-order-stages' );
		}
		return $actions;
	}

	/**
	 * Resend the current stage email, bypassing the already-sent guard.
	 *
	 * @param WC_Order $order Order.
	 */
	public function resend_stage_email( $order ) {
		if ( ! $order instanceof WC_Order || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$stage = $order->get_status();
		$email = Shilpi_OS_Emails::get_email( $stage );
		$sent  = $email ? $email->trigger( $order ) : false;
		if ( $sent ) {
			$log           = Shilpi_OS_Stage_Changer::get_sent( $order );
			$log[ $stage ] = time();
			$order->update_meta_data( Shilpi_OS_Stage_Changer::META_SENT, $log );
			$order->save_meta_data();
		}
		$order->add_order_note(
			sprintf(
				/* translators: 1: stage name, 2: result */
				__( 'Stage email for "%1$s" resent manually: %2$s.', 'shilpi-order-stages' ),
				Shilpi_OS_Stages::label( $stage ),
				$sent ? __( 'sent', 'shilpi-order-stages' ) : __( 'not sent (email disabled or failed)', 'shilpi-order-stages' )
			),
			0,
			true
		);
	}
}
