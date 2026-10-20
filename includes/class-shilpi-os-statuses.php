<?php
/**
 * Custom order statuses, list badges, filter, bulk actions and auto-move.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers stages 1 to 8 as WooCommerce order statuses.
 */
class Shilpi_OS_Statuses {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_to_order_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'add_custom_keys' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_to_reports' ) );
		add_filter( 'woocommerce_analytics_actionable_statuses', array( $this, 'add_custom_keys' ) );
		add_filter( 'woocommerce_order_is_download_permitted', array( $this, 'download_permitted' ), 10, 2 );

		// Order list: badge colours, stage filter, bulk actions (legacy and HPOS screens).
		add_action( 'admin_head', array( $this, 'badge_css' ) );
		add_action( 'restrict_manage_posts', array( $this, 'legacy_stage_filter' ) );
		add_filter( 'request', array( $this, 'legacy_filter_request' ) );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'hpos_stage_filter' ) );
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( $this, 'hpos_filter_query' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions' ), 20 );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_actions' ), 20 );

		// Auto-move new Processing orders to "Supplier unpaid".
		add_action( 'woocommerce_order_status_changed', array( $this, 'auto_move' ), 20, 4 );
	}

	/**
	 * Register post statuses (used by both storage engines).
	 */
	public function register_statuses() {
		foreach ( Shilpi_OS_Stages::get_custom_stages() as $key => $stage ) {
			register_post_status(
				'wc-' . $key,
				array(
					'label'                     => $stage['label'],
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( '%s order', '%s orders', 'shilpi-order-stages' ),
				)
			);
			// Keep the stage name in the count label.
			global $wp_post_statuses;
			if ( isset( $wp_post_statuses[ 'wc-' . $key ] ) ) {
				$wp_post_statuses[ 'wc-' . $key ]->label_count = array(
					0          => $stage['label'] . ' <span class="count">(%s)</span>',
					1          => $stage['label'] . ' <span class="count">(%s)</span>',
					'singular' => $stage['label'] . ' <span class="count">(%s)</span>',
					'plural'   => $stage['label'] . ' <span class="count">(%s)</span>',
					'context'  => null,
					'domain'   => null,
				);
			}
		}
	}

	/**
	 * Insert our statuses right after "Processing" and rename Completed in context.
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public function add_to_order_statuses( $statuses ) {
		$custom = array();
		foreach ( Shilpi_OS_Stages::get_custom_stages() as $key => $stage ) {
			$custom[ 'wc-' . $key ] = $stage['label'];
		}
		$out = array();
		foreach ( $statuses as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$out += $custom;
			}
		}
		return $out + $custom;
	}

	/**
	 * Add custom stage keys (without "wc-") to a status list.
	 *
	 * @param array $statuses Statuses.
	 * @return array
	 */
	public function add_custom_keys( $statuses ) {
		return array_values( array_unique( array_merge( (array) $statuses, array_keys( Shilpi_OS_Stages::get_custom_stages() ) ) ) );
	}

	/**
	 * Add custom stages to legacy reports.
	 *
	 * @param array|false $statuses Statuses.
	 * @return array|false
	 */
	public function add_to_reports( $statuses ) {
		if ( ! is_array( $statuses ) || ! in_array( 'processing', $statuses, true ) ) {
			return $statuses;
		}
		return $this->add_custom_keys( $statuses );
	}

	/**
	 * Allow downloads for orders in a paid stage.
	 *
	 * @param bool     $permitted Permitted.
	 * @param WC_Order $order     Order.
	 * @return bool
	 */
	public function download_permitted( $permitted, $order ) {
		if ( $order instanceof WC_Order && array_key_exists( $order->get_status(), Shilpi_OS_Stages::get_custom_stages() ) ) {
			return true;
		}
		return $permitted;
	}

	/**
	 * Print badge colours for the order list and edit screens.
	 */
	public function badge_css() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, self::order_screen_ids(), true ) ) {
			return;
		}
		$css = '';
		foreach ( Shilpi_OS_Stages::get_custom_stages() as $key => $stage ) {
			$bg   = sanitize_hex_color( $stage['color'] ) ? sanitize_hex_color( $stage['color'] ) : '#e5e5e5';
			$css .= sprintf(
				'.order-status.status-%1$s{background:%2$s;color:%3$s;}',
				sanitize_html_class( $key ),
				$bg,
				self::text_color( $bg )
			);
		}
		echo '<style id="shilpi-os-badges">' . wp_strip_all_tags( $css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from sanitised colours and class names.
	}

	/**
	 * Screen ids of the order list and edit screens (legacy and HPOS).
	 *
	 * @return array
	 */
	public static function order_screen_ids() {
		return array( 'edit-shop_order', 'shop_order', 'woocommerce_page_wc-orders' );
	}

	/**
	 * Pick black or white text for a background colour.
	 *
	 * @param string $hex Hex colour.
	 * @return string
	 */
	public static function text_color( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return ( ( $r * 299 + $g * 587 + $b * 114 ) / 1000 ) > 150 ? '#1d2327' : '#ffffff';
	}

	/**
	 * Render the stage dropdown.
	 *
	 * @param string $current Selected stage.
	 */
	private function render_stage_select( $current ) {
		echo '<select name="shilpi_os_stage" id="shilpi-os-stage-filter">';
		echo '<option value="">' . esc_html__( 'All stages', 'shilpi-order-stages' ) . '</option>';
		foreach ( Shilpi_OS_Stages::get_stages() as $key => $stage ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $stage['label'] ) );
		}
		echo '</select>';
	}

	/**
	 * Get the requested stage filter, validated against the stage list.
	 *
	 * @return string
	 */
	private function requested_stage() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$stage = isset( $_GET['shilpi_os_stage'] ) ? sanitize_key( wp_unslash( $_GET['shilpi_os_stage'] ) ) : '';
		return Shilpi_OS_Stages::is_stage( $stage ) ? $stage : '';
	}

	/**
	 * Stage filter on the legacy (posts) order list.
	 *
	 * @param string $post_type Post type.
	 */
	public function legacy_stage_filter( $post_type ) {
		if ( 'shop_order' === $post_type ) {
			$this->render_stage_select( $this->requested_stage() );
		}
	}

	/**
	 * Apply the stage filter on the legacy order list.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function legacy_filter_request( $vars ) {
		global $typenow;
		if ( is_admin() && 'shop_order' === $typenow ) {
			$stage = $this->requested_stage();
			if ( $stage ) {
				$vars['post_status'] = 'wc-' . $stage;
			}
		}
		return $vars;
	}

	/**
	 * Stage filter on the HPOS order list.
	 *
	 * @param string $order_type Order type.
	 */
	public function hpos_stage_filter( $order_type = 'shop_order' ) {
		if ( 'shop_order' === $order_type ) {
			$this->render_stage_select( $this->requested_stage() );
		}
	}

	/**
	 * Apply the stage filter on the HPOS order list.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function hpos_filter_query( $args ) {
		$stage = $this->requested_stage();
		if ( $stage ) {
			$args['status'] = array( 'wc-' . $stage );
		}
		return $args;
	}

	/**
	 * Add "Change stage to X" bulk actions. WooCommerce core handles "mark_{status}".
	 *
	 * @param array $actions Actions.
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		foreach ( Shilpi_OS_Stages::get_stages() as $key => $stage ) {
			/* translators: %s: stage name */
			$actions[ 'mark_' . $key ] = sprintf( __( 'Change stage to %s', 'shilpi-order-stages' ), $stage['label'] );
		}
		return $actions;
	}

	/**
	 * Move a newly Processing order to "Supplier unpaid" (once per order).
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Old status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order.
	 */
	public function auto_move( $order_id, $from, $to, $order = null ) {
		if ( 'processing' !== $to || ! Shilpi_OS_Settings::enabled( 'auto_move' ) ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || 'shop_order' !== $order->get_type() || $order->get_meta( '_shilpi_os_auto_moved' ) ) {
			return;
		}
		$keys = array_keys( Shilpi_OS_Stages::get_stages() );
		$order->update_meta_data( '_shilpi_os_auto_moved', time() );
		$order->update_status( $keys[0], __( 'Automatically moved to the first stage.', 'shilpi-order-stages' ) );
	}
}
