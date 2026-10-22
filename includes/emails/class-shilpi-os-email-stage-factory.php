<?php
/**
 * Stage email: sos-factory.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-factory" stage.
 */
class Shilpi_OS_Email_Stage_Factory extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_factory';
		$this->stage = 'sos-factory';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} has finished production', 'shilpi-order-stages' ),
			'heading'    => __( 'Production complete', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nYour order #{order_number} has finished production and passed its checks at the factory. It is now being prepared for transport to our freight forwarder's warehouse, ready for shipping to you.", 'shilpi-order-stages' ),
			'additional' => __( 'We will be in touch again once it reaches the warehouse.', 'shilpi-order-stages' ),
		);
	}
}
