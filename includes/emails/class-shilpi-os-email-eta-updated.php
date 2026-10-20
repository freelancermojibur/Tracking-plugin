<?php
/**
 * Email: delivery estimate updated.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sent when the admin changes the ETA without changing the stage and asks to notify the customer.
 */
class Shilpi_OS_Email_Eta_Updated extends Shilpi_OS_Email_Stage {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'shilpi_os_eta_updated';
		$this->placeholders = array(
			'{old_eta}' => '',
			'{new_eta}' => '',
		);
		parent::__construct();
		$this->title       = __( 'Order stage: Delivery estimate updated', 'shilpi-order-stages' );
		$this->description = __( 'Sent to the customer when the ETA changes without a stage change and "Email the customer about the new ETA" is ticked.', 'shilpi-order-stages' );
	}

	/**
	 * Default copy.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'subject'    => __( 'New delivery estimate for your order #{order_number}', 'shilpi-order-stages' ),
			'heading'    => __( 'Your delivery estimate has changed', 'shilpi-order-stages' ),
			'message'    => __( "Hi {customer_first_name},\n\nWe have an update on your order #{order_number}. The estimated delivery date has changed from {old_eta} to {new_eta}.\n\nSea freight and customs timings can shift a little, and we will keep you posted if anything changes again.", 'shilpi-order-stages' ),
			'additional' => __( 'If the new date does not suit you, simply reply to this email and we will do our best to help.', 'shilpi-order-stages' ),
		);
	}

	/**
	 * Send the email for an ETA change.
	 *
	 * @param WC_Order $order   Order.
	 * @param string   $old_eta Previous ETA (Y-m-d) or empty.
	 * @return bool
	 */
	public function trigger_eta( $order, $old_eta ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$this->stage                     = $order->get_status();
		$new                             = Shilpi_OS_Meta_Box::eta_display( $order );
		$this->placeholders['{old_eta}'] = $old_eta ? Shilpi_OS_Meta_Box::format_date( $old_eta ) : __( 'to be confirmed', 'shilpi-order-stages' );
		$this->placeholders['{new_eta}'] = $new ? $new : __( 'to be confirmed', 'shilpi-order-stages' );
		return $this->trigger( $order );
	}
}
