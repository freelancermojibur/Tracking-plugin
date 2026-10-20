<?php
/**
 * Plugin settings: WooCommerce > Settings > Order Stages.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and reads plugin settings (option: shilpi_os_settings) and renders the settings tab.
 */
class Shilpi_OS_Settings {

	const OPTION = 'shilpi_os_settings';
	const TAB    = 'shilpi_os';

	/**
	 * True while reading built-in stage names, so saved overrides are ignored.
	 *
	 * @var bool
	 */
	private static $building = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 50 );
		add_action( 'woocommerce_settings_' . self::TAB, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . self::TAB, array( $this, 'save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SHILPI_OS_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'auto_move'          => 'yes',
			'replace_completed'  => 'yes',
			'replace_processing' => 'yes',
			'overdue_enabled'    => 'yes',
			'overdue_email'      => get_option( 'admin_email' ),
		);
	}

	/**
	 * Get a setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		if ( self::$building ) {
			return null;
		}
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

	/**
	 * Add the settings tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs[ self::TAB ] = __( 'Order Stages', 'shilpi-order-stages' );
		return $tabs;
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB ) ) . '">' . esc_html__( 'Settings', 'shilpi-order-stages' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=shilpi-os-dashboard' ) ) . '">' . esc_html__( 'Dashboard', 'shilpi-order-stages' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Field id for a setting key (stored inside the single option array).
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function id( $key ) {
		return self::OPTION . '[' . $key . ']';
	}

	/**
	 * Settings fields.
	 *
	 * @return array
	 */
	public function fields() {
		$defaults = self::defaults();

		// Built-in labels and colours (without saved overrides) are used as placeholders.
		self::$building = true;
		$stages         = Shilpi_OS_Stages::get_stages();
		self::$building = false;

		$fields = array(
			array(
				'title' => __( 'Workflow', 'shilpi-order-stages' ),
				'type'  => 'title',
				'id'    => 'shilpi_os_workflow',
			),
			array(
				'title'   => __( 'New orders', 'shilpi-order-stages' ),
				'desc'    => sprintf(
					/* translators: %s: first stage name */
					__( 'Automatically move new Processing orders to "%s"', 'shilpi-order-stages' ),
					reset( $stages )['label']
				),
				'id'      => $this->id( 'auto_move' ),
				'type'    => 'checkbox',
				'default' => $defaults['auto_move'],
			),
			array(
				'title'    => __( 'Processing email', 'shilpi-order-stages' ),
				'desc'     => __( 'Replace the WooCommerce Processing order email with the stage 1 email', 'shilpi-order-stages' ),
				'desc_tip' => __( 'Only applies when new orders are moved automatically. The admin New order email is not affected.', 'shilpi-order-stages' ),
				'id'       => $this->id( 'replace_processing' ),
				'type'     => 'checkbox',
				'default'  => $defaults['replace_processing'],
			),
			array(
				'title'   => __( 'Completed email', 'shilpi-order-stages' ),
				'desc'    => __( 'Replace the WooCommerce "Completed order" email with the Delivered stage email', 'shilpi-order-stages' ),
				'id'      => $this->id( 'replace_completed' ),
				'type'    => 'checkbox',
				'default' => $defaults['replace_completed'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'shilpi_os_workflow',
			),
			array(
				'title' => __( 'Overdue alerts', 'shilpi-order-stages' ),
				'type'  => 'title',
				'desc'  => __( 'Once a day, email a list of orders whose ETA has passed and that are not delivered yet.', 'shilpi-order-stages' ),
				'id'    => 'shilpi_os_overdue',
			),
			array(
				'title'   => __( 'Enable alerts', 'shilpi-order-stages' ),
				'desc'    => __( 'Send the daily overdue alert', 'shilpi-order-stages' ),
				'id'      => $this->id( 'overdue_enabled' ),
				'type'    => 'checkbox',
				'default' => $defaults['overdue_enabled'],
			),
			array(
				'title'   => __( 'Alert email address', 'shilpi-order-stages' ),
				'id'      => $this->id( 'overdue_email' ),
				'type'    => 'email',
				'default' => $defaults['overdue_email'],
				'css'     => 'min-width:300px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'shilpi_os_overdue',
			),
			array(
				'title' => __( 'Stages', 'shilpi-order-stages' ),
				'type'  => 'title',
				'desc'  => __( 'Rename stages and choose badge colours. The admin name is used in the order list; the customer name is shown in emails, My Account and the tracking page. Leave blank to use the default.', 'shilpi-order-stages' ),
				'id'    => 'shilpi_os_stages',
			),
		);

		$n = 0;
		foreach ( $stages as $key => $stage ) {
			++$n;
			$fields[] = array(
				/* translators: %d: stage number */
				'title'       => sprintf( __( 'Stage %d admin name', 'shilpi-order-stages' ), $n ),
				'id'          => $this->id( 'label_' . $key ),
				'type'        => 'text',
				'placeholder' => $stage['label'],
				'default'     => '',
			);
			$fields[] = array(
				/* translators: %d: stage number */
				'title'       => sprintf( __( 'Stage %d customer name', 'shilpi-order-stages' ), $n ),
				'id'          => $this->id( 'short_' . $key ),
				'type'        => 'text',
				'placeholder' => $stage['short'],
				'default'     => '',
			);
			if ( 'completed' !== $key ) {
				$fields[] = array(
					/* translators: %d: stage number */
					'title'   => sprintf( __( 'Stage %d badge colour', 'shilpi-order-stages' ), $n ),
					'id'      => $this->id( 'color_' . $key ),
					'type'    => 'color',
					'default' => $stage['color'],
					'css'     => 'width:6em;',
				);
			}
		}
		$fields[] = array(
			'type' => 'sectionend',
			'id'   => 'shilpi_os_stages',
		);

		return apply_filters( 'shilpi_os_settings_fields', $fields );
	}

	/**
	 * Output the settings tab.
	 */
	public function output() {
		WC_Admin_Settings::output_fields( $this->fields() );
	}

	/**
	 * Save the settings tab. WooCommerce verifies the nonce and capability.
	 */
	public function save() {
		WC_Admin_Settings::save_fields( $this->fields() );
	}
}
