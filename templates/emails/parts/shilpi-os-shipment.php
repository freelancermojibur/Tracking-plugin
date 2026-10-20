<?php
/**
 * Email shipment summary: estimated delivery and tracking (not shown in the Delivered email).
 *
 * Override by copying to yourtheme/woocommerce/emails/parts/shilpi-os-shipment.php.
 *
 * @package Shilpi_Order_Stages
 * @var WC_Order $order
 * @var string   $stage
 */

defined( 'ABSPATH' ) || exit;

$shilpi_os_eta      = Shilpi_OS_Meta_Box::eta_display( $order );
$shilpi_os_tracking = Shilpi_OS_Meta_Box::get( $order, 'tracking_number' );
$shilpi_os_url      = Shilpi_OS_Meta_Box::get( $order, 'tracking_url' );
$shilpi_os_cell     = 'text-align:left; padding:8px 12px; border:1px solid #e5e5e5; font-family:Arial, Helvetica, sans-serif; font-size:14px;';

// The "Step X of 9" line already names the stage. Delivered orders need neither ETA nor tracking.
if ( 'completed' === $stage ) {
	return;
}
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="width:100%; margin:0 0 24px; border-collapse:collapse;">
	<tr>
		<th scope="row" style="<?php echo esc_attr( $shilpi_os_cell ); ?> width:40%;"><?php esc_html_e( 'Estimated delivery', 'shilpi-order-stages' ); ?></th>
		<td style="<?php echo esc_attr( $shilpi_os_cell ); ?>"><?php echo esc_html( $shilpi_os_eta ? $shilpi_os_eta : __( 'To be confirmed', 'shilpi-order-stages' ) ); ?></td>
	</tr>
	<?php if ( $shilpi_os_tracking || $shilpi_os_url ) : ?>
	<tr>
		<th scope="row" style="<?php echo esc_attr( $shilpi_os_cell ); ?>"><?php esc_html_e( 'Tracking', 'shilpi-order-stages' ); ?></th>
		<td style="<?php echo esc_attr( $shilpi_os_cell ); ?>">
			<?php
			$shilpi_os_text = $shilpi_os_tracking ? $shilpi_os_tracking : __( 'Track your shipment', 'shilpi-order-stages' );
			if ( $shilpi_os_url ) {
				echo '<a href="' . esc_url( $shilpi_os_url ) . '">' . esc_html( $shilpi_os_text ) . '</a>';
			} else {
				echo esc_html( $shilpi_os_text );
			}
			?>
		</td>
	</tr>
	<?php endif; ?>
</table>
