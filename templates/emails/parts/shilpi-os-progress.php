<?php
/**
 * Email progress bar. Table based with inline styles for Gmail and Outlook.
 *
 * Override by copying to yourtheme/woocommerce/emails/parts/shilpi-os-progress.php.
 *
 * @package Shilpi_Order_Stages
 * @var WC_Order $order
 * @var string   $stage
 */

defined( 'ABSPATH' ) || exit;

$shilpi_os_steps  = Shilpi_OS_Stages::timeline( $order, $stage );
$shilpi_os_accent = sanitize_hex_color( get_option( 'woocommerce_email_base_color', '#2271b1' ) );
$shilpi_os_accent = $shilpi_os_accent ? $shilpi_os_accent : '#2271b1';
$shilpi_os_width  = floor( 100 / max( 1, count( $shilpi_os_steps ) ) );
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="width:100%; margin:0 0 24px; border-collapse:collapse;">
	<tr>
		<?php
		foreach ( $shilpi_os_steps as $shilpi_os_i => $shilpi_os_step ) :
			if ( 'done' === $shilpi_os_step['state'] ) {
				$shilpi_os_bg    = '#00a32a';
				$shilpi_os_color = '#ffffff';
				$shilpi_os_mark  = '&#10003;';
			} elseif ( 'current' === $shilpi_os_step['state'] ) {
				$shilpi_os_bg    = $shilpi_os_accent;
				$shilpi_os_color = '#ffffff';
				$shilpi_os_mark  = (string) ( $shilpi_os_i + 1 );
			} else {
				$shilpi_os_bg    = '#e5e5e5';
				$shilpi_os_color = '#777777';
				$shilpi_os_mark  = (string) ( $shilpi_os_i + 1 );
			}
			?>
			<td align="center" valign="top" width="<?php echo esc_attr( $shilpi_os_width ); ?>%" style="width:<?php echo esc_attr( $shilpi_os_width ); ?>%; padding:0 1px; text-align:center; vertical-align:top;">
				<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto; border-collapse:collapse;">
					<tr>
						<td width="26" height="26" bgcolor="<?php echo esc_attr( $shilpi_os_bg ); ?>" align="center" valign="middle" style="width:26px; min-width:26px; max-width:26px; height:26px; padding:0; background-color:<?php echo esc_attr( $shilpi_os_bg ); ?>; color:<?php echo esc_attr( $shilpi_os_color ); ?>; border-radius:13px; font-family:Arial, Helvetica, sans-serif; font-size:12px; font-weight:bold; line-height:26px; text-align:center;">
							<?php echo wp_kses_post( $shilpi_os_mark ); ?>
						</td>
					</tr>
				</table>
				<div style="font-family:Arial, Helvetica, sans-serif; font-size:10px; line-height:13px; margin-top:4px; color:<?php echo 'current' === $shilpi_os_step['state'] ? esc_attr( $shilpi_os_accent ) : '#555555'; ?>; <?php echo 'current' === $shilpi_os_step['state'] ? 'font-weight:bold;' : ''; ?>">
					<?php echo esc_html( $shilpi_os_step['short'] ); ?>
				</div>
			</td>
		<?php endforeach; ?>
	</tr>
</table>
