<?php
/**
 * Order tracking form used by the [shilpi_track_order] shortcode.
 *
 * Override by copying to yourtheme/woocommerce/myaccount/shilpi-os-track-form.php.
 *
 * @package Shilpi_Order_Stages
 * @var string $error
 * @var string $result Timeline HTML (already escaped).
 * @var string $number
 * @var string $email
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="shilpi-os-track woocommerce">
	<form method="post" class="shilpi-os-track__form">
		<p><?php esc_html_e( 'Enter your order number and the email address you used at checkout to see where your order is.', 'shilpi-order-stages' ); ?></p>
		<p class="form-row form-row-first">
			<label for="shilpi-os-order"><?php esc_html_e( 'Order number', 'shilpi-order-stages' ); ?></label>
			<input type="text" class="input-text" id="shilpi-os-order" name="shilpi_os_order" value="<?php echo esc_attr( $number ); ?>" required />
		</p>
		<p class="form-row form-row-last">
			<label for="shilpi-os-email"><?php esc_html_e( 'Billing email', 'shilpi-order-stages' ); ?></label>
			<input type="email" class="input-text" id="shilpi-os-email" name="shilpi_os_email" value="<?php echo esc_attr( $email ); ?>" autocomplete="email" required />
		</p>
		<div class="clear"></div>
		<?php wp_nonce_field( 'shilpi_os_track', 'shilpi_os_track_nonce' ); ?>
		<p><button type="submit" class="button"><?php esc_html_e( 'Track order', 'shilpi-order-stages' ); ?></button></p>
	</form>
	<?php if ( $error ) : ?>
		<div class="woocommerce-error" role="alert"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>
	<?php echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the timeline template. ?>
</div>
