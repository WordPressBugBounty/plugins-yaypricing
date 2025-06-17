<?php
$saved_amount = \yaydp_get_saved_amount();
$label        = $attributes['content'] ? $attributes['content'] : __( 'Saved Amount', 'yaypricing' );
if ( 0 !== $saved_amount ) :
	?>
	<div <?php echo esc_attr( get_block_wrapper_attributes() ); ?>>
		<div className="yaydp-block-saved-amount-wrapper">
			<div className="yaydp-block-saved-amount-item wc-block-components-totals-item">
				<span className="yaydp-block-saved-amount-item__label wc-block-components-totals-item__label">
					<?php echo esc_html( $label ); ?>
				</span>
				<span className="yaydp-block-saved-amount-item__item wc-block-components-totals-item__value" id="saved-amount-value"><?php echo esc_html( \wc_price( $saved_amount ) ); ?></span>
			</div>
		</div>
	</div>
<?php endif; ?>
