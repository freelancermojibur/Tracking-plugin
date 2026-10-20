<?php
/**
 * Stage email: sos-production.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-production" stage.
 */
class Shilpi_OS_Email_Stage_Production extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_production';
		$this->stage = 'sos-production';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} is now in production', 'shilpi-order-stages' ),
			'heading'    => __( 'Your order is being made', 'shilpi-order-stages' ),
			'message'    => __( 'Good news, {customer_first_name}! Your order #{order_number} is now in production at the factory.

Our team is keeping a close eye on progress and we will let you know as soon as it is finished and ready to leave the factory.', 'shilpi-order-stages' ),
			'additional' => __( 'Thanks again for your patience. Good things take a little time.', 'shilpi-order-stages' ),
		);
	}
}
