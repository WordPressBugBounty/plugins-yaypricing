<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_notices',
	function() {
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong>
						<?php
						// translators:
						printf( esc_html__( 'YayPricing requires PHP 5.6 to work and does not support your current PHP version %1$s. Please contact your host and request a PHP upgrade to the latest one.', 'yaypricing' ), esc_html( phpversion() ) )
						?>
					</strong>
				</p>
			</div>
			<?php
		}
	}
);
