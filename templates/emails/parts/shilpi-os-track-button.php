<?php
/**
 * "Track your order" button shown under the progress bar in every stage email. Table based (bulletproof) for Gmail and Outlook.
 *
 * Override by copying to yourtheme/woocommerce/emails/parts/shilpi-os-track-button.php.
 *
 * @package Shilpi_Order_Stages
 * @var string $url Tracking page URL with ?order=.
 */

defined( 'ABSPATH' ) || exit;

$shilpi_os_accent = Shilpi_OS_Settings::email_accent();
$shilpi_os_text   = Shilpi_OS_Statuses::text_color( $shilpi_os_accent );
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; border-collapse:separate;">
	<tr>
		<td align="center" bgcolor="<?php echo esc_attr( $shilpi_os_accent ); ?>" style="background-color:<?php echo esc_attr( $shilpi_os_accent ); ?>; border-radius:6px; padding:0;">
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" style="display:inline-block; padding:14px 32px; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold; line-height:20px; color:<?php echo esc_attr( $shilpi_os_text ); ?>; text-decoration:none; border-radius:6px; border:1px solid <?php echo esc_attr( $shilpi_os_accent ); ?>;">
				<?php esc_html_e( 'Track your order', 'shilpi-order-stages' ); ?>
			</a>
		</td>
	</tr>
</table>
