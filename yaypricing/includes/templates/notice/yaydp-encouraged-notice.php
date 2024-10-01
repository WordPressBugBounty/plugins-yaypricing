<?php
/**
 * The Template for displaying encouraged notice
 *
 * @package YayPricing\Templates
 *
 * @param $encouragements
 * @param $closable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $encouragements as $encouragement ) :?>
	<div class="yaydp-encouraged-notice-wrapper yaydp-notice yaydp-notice-info">
		<span class="yaydp-notice-icon"><svg viewBox="64 64 896 896" focusable="false" data-icon="info-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm32 664c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V456c0-4.4 3.6-8 8-8h48c4.4 0 8 3.6 8 8v272zm-32-344a48.01 48.01 0 010-96 48.01 48.01 0 010 96z"></path></svg></span>
		<?php echo wp_kses_post( \yaydp_prepare_html( $encouragement->get_content() ) ); ?>
		<?php if ( ! empty( $closable ) ) : ?>
		<span class="yaydp-encouraged-notice-close-icon">
			<svg viewBox="64 64 896 896" focusable="false" data-icon="close" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M563.8 512l262.5-312.9c4.4-5.2.7-13.1-6.1-13.1h-79.8c-4.7 0-9.2 2.1-12.3 5.7L511.6 449.8 295.1 191.7c-3-3.6-7.5-5.7-12.3-5.7H203c-6.8 0-10.5 7.9-6.1 13.1L459.4 512 196.9 824.9A7.95 7.95 0 00203 838h79.8c4.7 0 9.2-2.1 12.3-5.7l216.5-258.1 216.5 258.1c3 3.6 7.5 5.7 12.3 5.7h79.8c6.8 0 10.5-7.9 6.1-13.1L563.8 512z"></path></svg>
		</span>
		<?php endif; ?>
	</div>	
<?php endforeach;
?>
