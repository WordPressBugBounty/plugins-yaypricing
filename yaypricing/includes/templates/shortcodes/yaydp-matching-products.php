<?php
/**
 * Matching product template
 *
 * @package YayPricing\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="yaydp-matching-products">
	<?php
	foreach ( $products as $product ) :
		?>
		<li>
			<div>
				<?php
				if ( $has_link ) :
					$product_link = \get_permalink( $product->get_id() );
					?>
				<a href="<?php echo esc_url( $product_link ); ?>" target="_blank">
				<?php endif; ?>
					<?php
					if ( $has_image ) :
						$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' )[0] : \wc_placeholder_img_src();
						?>
					<img class="yaydp-matching-product-image" src="<?php echo esc_url( $product_image ); ?>" alt="product_image" />			
					<?php endif; ?>
					<span><?php esc_html_e( $product->get_name(), 'woocommerce' ); ?></span>
				<?php if ( $has_link ) : ?>
				</a>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
