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
 * @var bool   $prefilled Order number came from ?order= in the URL.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="shilpi-os-track woocommerce">
	<form method="post" class="shilpi-os-track__form">
		<p><?php esc_html_e( 'Enter your order number and the email address you used at checkout to see where your order is.', 'shilpi-order-stages' ); ?></p>
		<p class="form-row form-row-first">
			<label for="shilpi-os-order"><?php esc_html_e( 'Order number', 'shilpi-order-stages' ); ?></label>
			<input type="text" class="input-text" id="shilpi-os-order" name="shilpi_os_order" value="<?php echo esc_attr( $number ); ?>" maxlength="20" required />
		</p>
		<p class="form-row form-row-last">
			<label for="shilpi-os-email"><?php esc_html_e( 'Billing email', 'shilpi-order-stages' ); ?></label>
			<input type="email" class="input-text" id="shilpi-os-email" name="shilpi_os_email" value="<?php echo esc_attr( $email ); ?>" autocomplete="email" required<?php echo ! empty( $prefilled ) ? ' autofocus' : ''; ?> />
		</p>
		<div class="clear"></div>
		<?php // Honeypot: hidden from people, often filled in by bots. ?>
		<p class="shilpi-os-hp" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;">
			<label for="shilpi-os-website"><?php esc_html_e( 'Leave this field empty', 'shilpi-order-stages' ); ?></label>
			<input type="text" id="shilpi-os-website" name="shilpi_os_website" value="" tabindex="-1" autocomplete="off" />
		</p>
		<input type="hidden" name="shilpi_os_track" value="1" />
		<p><button type="submit" class="button"><?php esc_html_e( 'Track order', 'shilpi-order-stages' ); ?></button></p>
	</form>
	<?php if ( $error ) : ?>
		<div class="woocommerce-error" role="alert"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>
	<?php echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the timeline template. ?>
</div>
