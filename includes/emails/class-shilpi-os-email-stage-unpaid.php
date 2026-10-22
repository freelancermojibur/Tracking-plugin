<?php
/**
 * Stage email: sos-unpaid.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-unpaid" stage.
 */
class Shilpi_OS_Email_Stage_Unpaid extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_unpaid';
		$this->stage = 'sos-unpaid';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'We have received your order #{order_number}', 'shilpi-order-stages' ),
			'heading'    => __( 'Thanks for your order, {customer_first_name}', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nThank you for choosing {site_title}. We have received your order #{order_number} for {product_names} and we are now arranging production with our manufacturing partner.\n\nYour order is made to order and shipped by sea to your destination, so the full journey takes a number of weeks. We will email you at every step along the way, so you always know exactly where it is.", 'shilpi-order-stages' ),
			'additional' => __( 'If you have any questions, simply reply to this email and we will be happy to help.', 'shilpi-order-stages' ),
		);
	}
}
