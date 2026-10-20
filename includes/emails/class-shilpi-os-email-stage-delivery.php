<?php
/**
 * Stage email: sos-delivery.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-delivery" stage.
 */
class Shilpi_OS_Email_Stage_Delivery extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_delivery';
		$this->stage = 'sos-delivery';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} is out for delivery', 'shilpi-order-stages' ),
			'heading'    => __( 'Your order is almost there', 'shilpi-order-stages' ),
			'message'    => __( 'Great news, {customer_first_name}! Your order #{order_number} is out for delivery to:

{shipping_address}

Please remember this is a large, heavy item. Someone will need to be available at the delivery address to receive it, and a clear path for unloading makes the driver\'s job much easier. If no one can be there, please reply to this email as soon as possible so we can make other arrangements.', 'shilpi-order-stages' ),
			'additional' => __( 'Thank you for shopping with {site_title}.', 'shilpi-order-stages' ),
		);
	}
}
