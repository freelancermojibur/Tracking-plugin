<?php
/**
 * Admin dashboard: WooCommerce > Order Stages.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Overview cards and ETA table.
 */
class Shilpi_OS_Dashboard {

	const UPCOMING_DAYS = 14;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_shilpi_os_send_alert', array( $this, 'send_alert_now' ) );
		add_action( 'admin_post_shilpi_os_move_processing', array( $this, 'move_processing' ) );
	}

	/**
	 * IDs of orders still in Processing (not yet started in the stage workflow).
	 *
	 * @return int[]
	 */
	public static function processing_order_ids() {
		return wc_get_orders(
			array(
				'type'   => 'shop_order',
				'status' => array( 'processing' ),
				'limit'  => -1,
				'return' => 'ids',
			)
		);
	}

	/**
	 * Register the submenu.
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Order Stages', 'shilpi-order-stages' ),
			__( 'Order Stages', 'shilpi-order-stages' ),
			'edit_shop_orders',
			'shilpi-os-dashboard',
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue admin CSS on the dashboard.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function assets( $hook ) {
		if ( 'woocommerce_page_shilpi-os-dashboard' === $hook ) {
			wp_enqueue_style( 'shilpi-os-admin', SHILPI_OS_URL . 'assets/css/admin.css', array(), SHILPI_OS_VERSION );
		}
	}

	/**
	 * All orders in stages 1 to 8.
	 *
	 * @return WC_Order[]
	 */
	public static function active_orders() {
		return wc_get_orders(
			array(
				'type'   => 'shop_order',
				'status' => array_keys( Shilpi_OS_Stages::get_custom_stages() ),
				'limit'  => -1,
			)
		);
	}

	/**
	 * Today's date (Y-m-d) in the site timezone.
	 *
	 * @return string
	 */
	public static function today() {
		return wp_date( 'Y-m-d' );
	}

	/**
	 * Active orders whose ETA has passed, oldest ETA first.
	 *
	 * @param WC_Order[]|null $orders Orders to check (defaults to all active orders).
	 * @return WC_Order[]
	 */
	public static function overdue_orders( $orders = null ) {
		$today = self::today();
		$out   = array();
		foreach ( null === $orders ? self::active_orders() : $orders as $order ) {
			$eta = Shilpi_OS_Meta_Box::get( $order, 'eta' );
			if ( $eta && $eta < $today ) {
				$out[] = $order;
			}
		}
		return self::sort_by_eta( $out );
	}

	/**
	 * Sort orders by ETA ascending.
	 *
	 * @param WC_Order[] $orders Orders.
	 * @return WC_Order[]
	 */
	private static function sort_by_eta( $orders ) {
		usort(
			$orders,
			function ( $a, $b ) {
				return strcmp( Shilpi_OS_Meta_Box::get( $a, 'eta' ), Shilpi_OS_Meta_Box::get( $b, 'eta' ) );
			}
		);
		return $orders;
	}

	/**
	 * Days between today and an ETA (negative when overdue).
	 *
	 * @param string $eta Y-m-d.
	 * @return int
	 */
	public static function days_until( $eta ) {
		$tz    = wp_timezone();
		$today = date_create_immutable( self::today(), $tz );
		$date  = date_create_immutable( $eta, $tz );
		if ( ! $today || ! $date ) {
			return 0;
		}
		return (int) $today->diff( $date )->format( '%r%a' );
	}

	/**
	 * URL of the order list filtered by a status.
	 *
	 * @param string $status Status without "wc-".
	 * @return string
	 */
	private function list_url( $status = '' ) {
		if ( Shilpi_OS_Plugin::hpos_enabled() ) {
			$args = array( 'page' => 'wc-orders' );
			if ( $status ) {
				$args['status'] = 'wc-' . $status;
			}
			return add_query_arg( $args, admin_url( 'admin.php' ) );
		}
		$args = array( 'post_type' => 'shop_order' );
		if ( $status ) {
			$args['post_status'] = 'wc-' . $status;
		}
		return add_query_arg( $args, admin_url( 'edit.php' ) );
	}

	/**
	 * Render the dashboard.
	 */
	public function render() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'shilpi-order-stages' ) );
		}

		$orders     = self::active_orders();
		$overdue    = self::overdue_orders( $orders );
		$today      = self::today();
		$limit      = wp_date( 'Y-m-d', time() + self::UPCOMING_DAYS * DAY_IN_SECONDS );
		$keys       = array_keys( Shilpi_OS_Stages::get_custom_stages() );
		$transit    = array_slice( $keys, 4, 3 );
		$to_pay     = 0;
		$in_transit = 0;
		$upcoming   = array();
		$processing = self::processing_order_ids();

		foreach ( $orders as $order ) {
			$status = $order->get_status();
			if ( $keys[0] === $status ) {
				++$to_pay;
			}
			if ( in_array( $status, $transit, true ) ) {
				++$in_transit;
			}
			$eta = Shilpi_OS_Meta_Box::get( $order, 'eta' );
			if ( $eta && $eta >= $today && $eta <= $limit ) {
				$upcoming[] = $order;
			}
		}
		$upcoming = self::sort_by_eta( $upcoming );

		$cards = array(
			array( __( 'Waiting to start (Processing)', 'shilpi-order-stages' ), count( $processing ), 'is-neutral', $this->list_url( 'processing' ) ),
			array( __( 'Active orders', 'shilpi-order-stages' ), count( $orders ), 'is-info', $this->list_url() ),
			array( __( 'To pay supplier', 'shilpi-order-stages' ), $to_pay, 'is-warning', $this->list_url( $keys[0] ) ),
			array( __( 'In transit / at port', 'shilpi-order-stages' ), $in_transit, 'is-ok', '#shilpi-os-upcoming' ),
			array( __( 'Overdue', 'shilpi-order-stages' ), count( $overdue ), 'is-danger', '#shilpi-os-overdue' ),
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Order Stages', 'shilpi-order-stages' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shilpi_os' ) ); ?>"><?php esc_html_e( 'Settings', 'shilpi-order-stages' ); ?></a>
			<hr class="wp-header-end" />
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
			if ( isset( $_GET['shilpi_os_alert'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
				$sent = absint( $_GET['shilpi_os_alert'] );
				echo '<div class="notice notice-' . ( $sent ? 'success' : 'info' ) . ' is-dismissible"><p>' . esc_html( $sent ? __( 'Overdue alert email sent.', 'shilpi-order-stages' ) : __( 'No alert sent: there are no overdue orders, or alerts are turned off.', 'shilpi-order-stages' ) ) . '</p></div>';
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
			if ( isset( $_GET['shilpi_os_moved'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
				$moved = absint( $_GET['shilpi_os_moved'] );
				/* translators: %d: number of orders */
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d order moved to the first stage.', '%d orders moved to the first stage.', $moved, 'shilpi-order-stages' ), $moved ) ) . '</p></div>';
			}
			if ( $processing ) :
				?>
				<div class="notice notice-warning is-dismissible shilpi-os-processing-notice">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: number of orders, 2: first stage name */
								_n( '%1$d order is still in Processing and has not started the stage workflow. Move it to "%2$s"?', '%1$d orders are still in Processing and have not started the stage workflow. Move them to "%2$s"?', count( $processing ), 'shilpi-order-stages' ),
								count( $processing ),
								Shilpi_OS_Stages::label( $keys[0] )
							)
						);
						?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="shilpi_os_move_processing" />
						<?php wp_nonce_field( 'shilpi_os_move_processing' ); ?>
						<p>
							<button type="submit" class="button button-primary" name="shilpi_os_mode" value="quiet">
								<?php
								/* translators: %s: first stage name */
								echo esc_html( sprintf( __( 'Move them to %s without email', 'shilpi-order-stages' ), Shilpi_OS_Stages::label( $keys[0] ) ) );
								?>
							</button>
							<button type="submit" class="button" name="shilpi_os_mode" value="email"><?php esc_html_e( 'Move and email customers', 'shilpi-order-stages' ); ?></button>
						</p>
					</form>
				</div>
				<?php
			endif;
			?>
			<div class="shilpi-os-cards">
				<?php foreach ( $cards as $card ) : ?>
					<a class="shilpi-os-card <?php echo esc_attr( $card[2] ); ?>" href="<?php echo esc_url( $card[3] ); ?>">
						<span class="shilpi-os-num"><?php echo esc_html( number_format_i18n( $card[1] ) ); ?></span>
						<?php echo esc_html( $card[0] ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<h2 id="shilpi-os-overdue"><?php esc_html_e( 'Overdue orders', 'shilpi-order-stages' ); ?></h2>
			<?php $this->table( $overdue, __( 'No overdue orders. Nice work!', 'shilpi-order-stages' ) ); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0 24px;">
				<input type="hidden" name="action" value="shilpi_os_send_alert" />
				<?php wp_nonce_field( 'shilpi_os_send_alert' ); ?>
				<?php submit_button( __( 'Email overdue list now', 'shilpi-order-stages' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2 id="shilpi-os-upcoming">
				<?php
				/* translators: %d: number of days */
				echo esc_html( sprintf( __( 'ETA in the next %d days', 'shilpi-order-stages' ), self::UPCOMING_DAYS ) );
				?>
			</h2>
			<?php $this->table( $upcoming, __( 'No orders due in this period.', 'shilpi-order-stages' ) ); ?>
		</div>
		<?php
	}

	/**
	 * Render an orders table.
	 *
	 * @param WC_Order[] $orders Orders.
	 * @param string     $empty_text Message when empty.
	 */
	private function table( $orders, $empty_text ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Order', 'shilpi-order-stages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Customer', 'shilpi-order-stages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Stage', 'shilpi-order-stages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'ETA', 'shilpi-order-stages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Days', 'shilpi-order-stages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Supplier', 'shilpi-order-stages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $orders ) : ?>
					<tr><td colspan="6"><?php echo esc_html( $empty_text ); ?></td></tr>
				<?php endif; ?>
				<?php
				foreach ( $orders as $order ) :
					$days = self::days_until( Shilpi_OS_Meta_Box::get( $order, 'eta' ) );
					?>
					<tr>
						<td><a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
						<td><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
						<td><mark class="order-status status-<?php echo esc_attr( $order->get_status() ); ?>"><span><?php echo esc_html( Shilpi_OS_Stages::label( $order->get_status() ) ); ?></span></mark></td>
						<td><?php echo esc_html( Shilpi_OS_Meta_Box::eta_display( $order ) ); ?></td>
						<td class="<?php echo $days < 0 ? 'shilpi-os-overdue' : ''; ?>">
							<?php
							if ( $days < 0 ) {
								/* translators: %d: days overdue */
								echo esc_html( sprintf( _n( '%d day overdue', '%d days overdue', abs( $days ), 'shilpi-order-stages' ), abs( $days ) ) );
							} elseif ( 0 === $days ) {
								esc_html_e( 'Today', 'shilpi-order-stages' );
							} else {
								/* translators: %d: days left */
								echo esc_html( sprintf( _n( 'in %d day', 'in %d days', $days, 'shilpi-order-stages' ), $days ) );
							}
							?>
						</td>
						<td><?php echo esc_html( Shilpi_OS_Meta_Box::get( $order, 'supplier' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * "Email overdue list now" button handler.
	 */
	public function send_alert_now() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shilpi-order-stages' ) );
		}
		check_admin_referer( 'shilpi_os_send_alert' );
		$sent = Shilpi_OS_Cron::send_overdue_alert();
		wp_safe_redirect( add_query_arg( 'shilpi_os_alert', $sent ? 1 : 0, admin_url( 'admin.php?page=shilpi-os-dashboard' ) ) );
		exit;
	}

	/**
	 * Move all Processing orders to the first stage (dashboard notice buttons).
	 */
	public function move_processing() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shilpi-order-stages' ) );
		}
		check_admin_referer( 'shilpi_os_move_processing' );

		$email = isset( $_POST['shilpi_os_mode'] ) && 'email' === sanitize_key( wp_unslash( $_POST['shilpi_os_mode'] ) );
		$keys  = array_keys( Shilpi_OS_Stages::get_stages() );
		$moved = 0;

		Shilpi_OS_Stage_Changer::suppress_emails( ! $email );
		foreach ( self::processing_order_ids() as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || 'processing' !== $order->get_status() ) {
				continue;
			}
			$order->update_meta_data( '_shilpi_os_auto_moved', time() );
			$order->update_status(
				$keys[0],
				$email
					? __( 'Moved from Processing to the first stage from the Order Stages dashboard (customer emailed).', 'shilpi-order-stages' )
					: __( 'Moved from Processing to the first stage from the Order Stages dashboard (no email).', 'shilpi-order-stages' ),
				true
			);
			++$moved;
		}
		Shilpi_OS_Stage_Changer::suppress_emails( false );

		wp_safe_redirect( add_query_arg( 'shilpi_os_moved', $moved, admin_url( 'admin.php?page=shilpi-os-dashboard' ) ) );
		exit;
	}
}
