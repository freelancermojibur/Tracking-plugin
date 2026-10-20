<?php
/**
 * Email progress bar: a row of numbered dots, then "Step X of 9: Stage" and the next step.
 * Table based with inline styles only, so it works in Gmail and Outlook at any width.
 *
 * Override by copying to yourtheme/woocommerce/emails/parts/shilpi-os-progress.php.
 *
 * @package Shilpi_Order_Stages
 * @var WC_Order $order
 * @var string   $stage
 */

defined( 'ABSPATH' ) || exit;

$shilpi_os_steps  = Shilpi_OS_Stages::timeline( $order, $stage );
$shilpi_os_total  = count( $shilpi_os_steps );
$shilpi_os_accent = Shilpi_OS_Settings::email_accent();
$shilpi_os_pos    = Shilpi_OS_Stages::index( $stage );
$shilpi_os_final  = $shilpi_os_pos === $shilpi_os_total - 1;
$shilpi_os_font   = 'font-family:Arial, Helvetica, sans-serif;';
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px; border-collapse:collapse;">
	<tr>
		<?php
		foreach ( $shilpi_os_steps as $shilpi_os_i => $shilpi_os_step ) :
			if ( ! empty( $shilpi_os_step['skipped'] ) ) {
				// Jumped over: shown as done but muted, with no date.
				$shilpi_os_bg    = '#b8e0c2';
				$shilpi_os_color = '#ffffff';
				$shilpi_os_mark  = '&#10003;';
			} elseif ( 'done' === $shilpi_os_step['state'] || $shilpi_os_final ) {
				// Delivered: every step, including the last, is a green check.
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
			<td width="22" height="22" bgcolor="<?php echo esc_attr( $shilpi_os_bg ); ?>" align="center" valign="middle" title="<?php echo esc_attr( $shilpi_os_step['short'] . ( ! empty( $shilpi_os_step['skipped'] ) ? ' (' . __( 'skipped', 'shilpi-order-stages' ) . ')' : '' ) ); ?>" style="width:22px; min-width:22px; max-width:22px; height:22px; padding:0; background-color:<?php echo esc_attr( $shilpi_os_bg ); ?>; color:<?php echo esc_attr( $shilpi_os_color ); ?>; border-radius:11px; <?php echo esc_attr( $shilpi_os_font ); ?> font-size:11px; font-weight:bold; line-height:22px; text-align:center;">
				<?php echo wp_kses_post( $shilpi_os_mark ); ?>
			</td>
			<?php if ( $shilpi_os_i < $shilpi_os_total - 1 ) : ?>
				<td width="6" style="width:6px; min-width:6px; padding:0; font-size:1px; line-height:1px;">&nbsp;</td>
			<?php endif; ?>
		<?php endforeach; ?>
	</tr>
</table>
<?php if ( $shilpi_os_final ) : ?>
	<p style="margin:0 0 24px; <?php echo esc_attr( $shilpi_os_font ); ?> font-size:15px; font-weight:bold; color:#00a32a;">
		<?php echo esc_html( $shilpi_os_steps[ $shilpi_os_pos ]['short'] ); ?>
	</p>
<?php elseif ( $shilpi_os_pos >= 0 ) : ?>
	<p style="margin:0 0 4px; <?php echo esc_attr( $shilpi_os_font ); ?> font-size:15px; font-weight:bold;">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: step number, 2: total steps, 3: stage name */
				__( 'Step %1$d of %2$d: %3$s', 'shilpi-order-stages' ),
				$shilpi_os_pos + 1,
				$shilpi_os_total,
				$shilpi_os_steps[ $shilpi_os_pos ]['short']
			)
		);
		?>
	</p>
	<?php if ( isset( $shilpi_os_steps[ $shilpi_os_pos + 1 ] ) ) : ?>
		<p style="margin:0 0 24px; <?php echo esc_attr( $shilpi_os_font ); ?> font-size:13px; color:#777777;">
			<?php
			/* translators: %s: next stage name */
			echo esc_html( sprintf( __( 'Next: %s', 'shilpi-order-stages' ), $shilpi_os_steps[ $shilpi_os_pos + 1 ]['short'] ) );
			?>
		</p>
	<?php else : ?>
		<div style="height:20px; line-height:20px; font-size:1px;">&nbsp;</div>
	<?php endif; ?>
<?php endif; ?>
