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
			echo wp_kses_post( \yaydp_prepare_html( $offer_description->get_content() ) );
			?>
		</div>
		<?php
	endforeach;
	?>
</div>
