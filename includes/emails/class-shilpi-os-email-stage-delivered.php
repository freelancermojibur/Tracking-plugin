<?php
/**
 * Stage email: completed.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "completed" stage.
 */
class Shilpi_OS_Email_Stage_Delivered extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_delivered';
		$this->stage = 'completed';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} has been delivered', 'shilpi-order-stages' ),
			'heading'    => __( 'Delivered! Enjoy your new purchase', 'shilpi-order-stages' ),
			'message'    => __( 'Hi {customer_first_name},

Your order #{order_number} has been delivered. Thank you for choosing {site_title}, we hope you love it.

Would you mind taking a minute to leave us a review? Your feedback helps other Australians choose with confidence and means a great deal to our team.', 'shilpi-order-stages' ),
			'additional' => __( 'If anything is not quite right, just reply to this email and we will sort it out.', 'shilpi-order-stages' ),
		);
	}
}
