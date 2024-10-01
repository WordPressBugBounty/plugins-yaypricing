<?php
/**
 * The Template for displaying cart coupon tooltips
 *
 * Only cart coupon is created by YayPricing will display.
 * Render tooltips shown on cart coupon description.
 *
 * @package YayPricing\Templates
 *
 * @param $tooltips
 */

defined( 'ABSPATH' ) || exit;

?>
<span class="yaydp-tooltip-icon">
	<div class="yaydp-tooltip-content">
		<?php foreach ( $tooltips as $tooltip ) : ?>
				<div><?php echo wp_kses_post( $tooltip->get_content() ); ?></div>
			<?php endforeach; ?>
	</div>
</span>
