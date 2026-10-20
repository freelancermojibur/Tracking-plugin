<?php
/**
 * Stage email: sos-on-ship.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-on-ship" stage.
 */
class Shilpi_OS_Email_Stage_On_Ship extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_on_ship';
		$this->stage = 'sos-on-ship';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} is on its way to Australia', 'shilpi-order-stages' ),
			'heading'    => __( 'Your order is at sea', 'shilpi-order-stages' ),
			'message'    => __( 'Exciting news, {customer_first_name}! Your order #{order_number} is now on board the ship and sailing to Australia.

Sea freight takes a little while, but we will let you know as soon as it reaches port. Current estimated delivery: {eta}.', 'shilpi-order-stages' ),
			'additional' => __( 'Thank you for your patience while your order makes its way across the ocean.', 'shilpi-order-stages' ),
		);
	}
}
