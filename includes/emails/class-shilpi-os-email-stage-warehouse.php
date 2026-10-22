<?php
/**
 * Stage email: sos-warehouse.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-warehouse" stage.
 */
class Shilpi_OS_Email_Stage_Warehouse extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_warehouse';
		$this->stage = 'sos-warehouse';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} is at the shipping warehouse', 'shilpi-order-stages' ),
			'heading'    => __( 'Ready to ship', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nYour order #{order_number} has arrived safely at our freight forwarder's warehouse. It is being packed and booked onto the next available sailing to your destination.", 'shilpi-order-stages' ),
			'additional' => __( 'We will let you know as soon as it is on board.', 'shilpi-order-stages' ),
		);
	}
}
