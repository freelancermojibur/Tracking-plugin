<?php
/**
 * Stage email: sos-customs.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email sent when an order reaches the "sos-customs" stage.
 */
class Shilpi_OS_Email_Stage_Customs extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shilpi_os_customs';
		$this->stage = 'sos-customs';
		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'Your order #{order_number} is going through customs', 'shilpi-order-stages' ),
			'heading'    => __( 'Customs clearance', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nYour order #{order_number} is now going through customs and border checks. This usually takes a few business days. Once it is cleared, we will arrange delivery to your address.", 'shilpi-order-stages' ),
			'additional' => __( 'There is nothing you need to do right now. We will take care of the paperwork.', 'shilpi-order-stages' ),
		);
	}
}
