<?php
/**
 * Fallback notice
 *
 * Display fallback notice when another YayPricing version is active
 *
 * @package YayPricing\Notices
 */

defined( 'ABSPATH' ) || exit;

add_action( 'network_admin_notices', 'yaydp_duplicate_fallback_notices' );
add_action( 'admin_notices', 'yaydp_duplicate_fallback_notices' );

if ( ! function_exists( 'yaydp_duplicate_fallback_notices' ) ) {
	/**
	 * Create notice when activate failed.
	 */
	function yaydp_duplicate_fallback_notices() {
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
				<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'It looks like you have another YayPricing version installed, please delete it before activating this new version. All current settings and data are still preserved.', 'yaypricing' ); ?>
					<a href="https://docs.yaycommerce.com/yaypricing/getting-started/how-to-update-yaypricing"><?php esc_html_e( 'Read more details.', 'yaypricing' ); ?></a>
					</strong>
				</p>
				</div>
			<?php
		}
	}
}
