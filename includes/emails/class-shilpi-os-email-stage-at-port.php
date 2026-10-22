<?php
/**
 * Stage email: sos-at-port.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-at-port" stage.
 */
class Shilpi_OS_Email_Stage_At_Port extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_at_port';
		$this->stage = 'sos-at-port';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} has arrived at the destination port', 'shilpi-order-stages' ),
			'heading'    => __( 'Arrived at the destination port', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nYour order #{order_number} has arrived at the destination port. Next it will go through customs clearance, which is a standard step for all imported goods.", 'shilpi-order-stages' ),
			'additional' => __( 'We are getting closer. We will update you again once customs clearance begins.', 'shilpi-order-stages' ),
		);
	}
}
