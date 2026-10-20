<?php
/**
 * Customer order timeline.
 *
 * Override by copying to yourtheme/woocommerce/myaccount/shilpi-os-timeline.php.
 *
 * @package Shilpi_Order_Stages
 * @var WC_Order $order
 * @var array    $steps
 * @var string   $eta
 * @var string   $tracking
 * @var string   $url
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="shilpi-os-tracking">
	<h2 class="shilpi-os-tracking__title"><?php esc_html_e( 'Order progress', 'shilpi-order-stages' ); ?></h2>
	<?php if ( 'completed' !== $order->get_status() ) : ?>
		<p class="shilpi-os-tracking__meta">
			<strong><?php esc_html_e( 'Estimated delivery:', 'shilpi-order-stages' ); ?></strong>
			<?php echo esc_html( $eta ? $eta : __( 'To be confirmed', 'shilpi-order-stages' ) ); ?>
		</p>
	<?php endif; ?>
	<?php if ( $tracking || $url ) : ?>
		<p class="shilpi-os-tracking__meta">
			<strong><?php esc_html_e( 'Tracking:', 'shilpi-order-stages' ); ?></strong>
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $tracking ? $tracking : __( 'Track your shipment', 'shilpi-order-stages' ) ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $tracking ); ?>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<ol class="shilpi-os-timeline">
		<?php foreach ( $steps as $shilpi_os_step ) : ?>
			<li class="shilpi-os-timeline__step is-<?php echo esc_attr( $shilpi_os_step['state'] ); ?>"<?php echo 'current' === $shilpi_os_step['state'] ? ' aria-current="step"' : ''; ?>>
				<span class="shilpi-os-timeline__dot" aria-hidden="true"></span>
				<span class="shilpi-os-timeline__label"><?php echo esc_html( $shilpi_os_step['short'] ); ?></span>
				<?php if ( $shilpi_os_step['date'] ) : ?>
					<span class="shilpi-os-timeline__date"><?php echo esc_html( wp_date( get_option( 'date_format' ), $shilpi_os_step['date'] ) ); ?></span>
				<?php elseif ( 'done' === $shilpi_os_step['state'] ) : ?>
					<span class="shilpi-os-timeline__date"><?php esc_html_e( 'Completed', 'shilpi-order-stages' ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
