<?php
/**
 * The Template for displaying all offer description for product
 *
 * @package YayPricing\Templates
 * @param $offer_descriptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="yaydp-offer-description">
	<?php
	foreach ( $offer_descriptions as $offer_description ) :
		?>
		<div class="yaydp-offer-description">
		<?php
			// Custom wp_kses configuration to allow iframe tags
			$allowed_html = wp_kses_allowed_html( 'post' );
			$allowed_html['iframe'] = array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'title'           => true,
				'class'           => true,
				'id'              => true,
				'style'           => true,
			);
			
			echo wp_kses( \yaydp_prepare_html( $offer_description->get_content() ), $allowed_html );
		?>
		</div>
		<?php
	endforeach;
	?>
</div>
