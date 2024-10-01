<div class="error">
	<p>
	<?php
	/* Translators: %s: search WooCommerce plugin link. */
	printf( 'YayPricing ' . esc_html__( 'is enabled but not effective. It requires %1$sWooCommerce%2$s in order to work.', 'yaypricing' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">', '</a>' );
	?>
	</p>
</div>
