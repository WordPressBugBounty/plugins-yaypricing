<?php
/**
 * Deprecated pass-through kept so existing wc_get_template() callers and theme
 * overrides keep resolving. The markup lives in templates/tooltip.php.
 *
 * @package YayPricing\Templates
 *
 * @deprecated 3.5.8 Use yaydp_render_tooltips(). Removed in 3.6.0.
 *
 * @param $tooltips
 */

defined( 'ABSPATH' ) || exit;

\wc_get_template( 'tooltip.php', array( 'tooltips' => $tooltips ), '', YAYDP_PLUGIN_PATH . 'includes/templates/' );
