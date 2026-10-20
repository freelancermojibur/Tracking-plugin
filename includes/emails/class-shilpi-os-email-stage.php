<?php
/**
 * Base class for the stage emails.
 *
 * @package Shilpi_Order_Stages
 */

defined( 'ABSPATH' ) || exit;

/**
 * A customer email sent when an order reaches a stage.
 */
abstract class Shilpi_OS_Email_Stage extends WC_Email {

	/**
	 * Stage key (order status without "wc-").
	 *
	 * @var string
	 */
	public $stage = '';

	/**
	 * Default copy: subject, heading, message, additional.
	 *
	 * @return array
	 */
	abstract protected function defaults();

	/**
	 * Constructor. Child classes set $id and $stage before calling this.
	 */
	public function __construct() {
		$position             = Shilpi_OS_Stages::index( $this->stage ) + 1;
		$this->customer_email = true;
		/* translators: 1: stage number, 2: stage name */
		$this->title = sprintf( __( 'Order stage %1$d: %2$s', 'shilpi-order-stages' ), $position, Shilpi_OS_Stages::label( $this->stage ) );
		/* translators: %s: stage name */
		$this->description    = sprintf( __( 'Sent to the customer when their order moves forward to "%s".', 'shilpi-order-stages' ), Shilpi_OS_Stages::label( $this->stage ) );
		$this->template_base  = SHILPI_OS_PATH . 'templates/';
		$this->template_html  = 'emails/shilpi-os-stage.php';
		$this->template_plain = 'emails/plain/shilpi-os-stage.php';
		$this->placeholders   = array(
			'{customer_first_name}' => '',
			'{order_number}'        => '',
			'{order_date}'          => '',
			'{product_names}'       => '',
			'{eta}'                 => '',
			'{tracking_number}'     => '',
			'{tracking_url}'        => '',
			'{shipping_address}'    => '',
		);

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return $this->defaults()['subject'];
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return $this->defaults()['heading'];
	}

	/**
	 * Default main message.
	 *
	 * @return string
	 */
	public function get_default_message() {
		return $this->defaults()['message'];
	}

	/**
	 * Default additional content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return $this->defaults()['additional'];
	}

	/**
	 * Main message with placeholders replaced.
	 *
	 * @return string
	 */
	public function get_message() {
		return $this->format_string( $this->get_option( 'message', $this->get_default_message() ) );
	}

	/**
	 * Settings fields: core fields plus a "Message" field.
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$placeholder_text = sprintf(
			/* translators: %s: list of placeholders */
			__( 'Available placeholders: %s', 'shilpi-order-stages' ),
			'<code>' . implode( '</code>, <code>', array_keys( array_merge( $this->placeholders, array( '{site_title}' => '' ) ) ) ) . '</code>'
		);
		$fields = array();
		foreach ( $this->form_fields as $key => $field ) {
			if ( in_array( $key, array( 'subject', 'heading', 'additional_content' ), true ) ) {
				$field['description'] = $placeholder_text;
			}
			$fields[ $key ] = $field;
			if ( 'heading' === $key ) {
				$fields['message'] = array(
					'title'       => __( 'Message', 'shilpi-order-stages' ),
					'description' => __( 'Main text of the email, shown above the progress bar.', 'shilpi-order-stages' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 150px;',
					'placeholder' => $this->get_default_message(),
					'type'        => 'textarea',
					'default'     => $this->get_default_message(),
					'desc_tip'    => false,
				);
			}
		}
		$this->form_fields = $fields;
	}

	/**
	 * Send the email for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return bool Whether the email was sent.
	 */
	public function trigger( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$this->setup_locale();

		$this->object    = $order;
		$this->recipient = $order->get_billing_email();

		$names = array();
		foreach ( $order->get_items() as $item ) {
			$names[] = $item->get_name();
		}
		$address = $order->get_formatted_shipping_address();
		if ( ! $address ) {
			$address = $order->get_formatted_billing_address();
		}
		$eta = Shilpi_OS_Meta_Box::eta_display( $order );

		$this->placeholders['{customer_first_name}'] = $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'there', 'shilpi-order-stages' );
		$this->placeholders['{order_number}']        = $order->get_order_number();
		$this->placeholders['{order_date}']          = wc_format_datetime( $order->get_date_created() );
		$this->placeholders['{product_names}']       = implode( ', ', $names );
		$this->placeholders['{eta}']                 = $eta ? $eta : __( 'to be confirmed', 'shilpi-order-stages' );
		$this->placeholders['{tracking_number}']     = Shilpi_OS_Meta_Box::get( $order, 'tracking_number' );
		$this->placeholders['{tracking_url}']        = Shilpi_OS_Meta_Box::get( $order, 'tracking_url' );
		$this->placeholders['{shipping_address}']    = wp_strip_all_tags( preg_replace( '#<br\s*/?>#i', ', ', (string) $address ) );

		$sent = false;
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
		return (bool) $sent;
	}

	/**
	 * Template arguments.
	 *
	 * @param bool $plain_text Plain text.
	 * @return array
	 */
	protected function template_args( $plain_text ) {
		return array(
			'order'              => $this->object,
			'email_heading'      => $this->get_heading(),
			'message'            => $this->get_message(),
			'additional_content' => $this->get_additional_content(),
			'stage'              => $this->stage,
			'sent_to_admin'      => false,
			'plain_text'         => $plain_text,
			'email'              => $this,
		);
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}
}
