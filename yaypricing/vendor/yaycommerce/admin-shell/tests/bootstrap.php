<?php
/**
 * PHPUnit bootstrap — mock all WordPress functions so tests run without WordPress.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/fixtures/plugin-menu-adapter-doubles.php';

// ──────────────────────────────────────────────────────────────
// WordPress constants
// ──────────────────────────────────────────────────────────────
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wp/' );
}
if ( ! defined( 'DOING_CRON' ) ) {
    define( 'DOING_CRON', false );
}

// ──────────────────────────────────────────────────────────────
// In-memory wp_options store
// ──────────────────────────────────────────────────────────────
$GLOBALS['_wp_options'] = [];

function get_option( $key, $default = false ) {
    return $GLOBALS['_wp_options'][ $key ] ?? $default;
}
function update_option( $key, $value, $autoload = null ) {
    $GLOBALS['_wp_options'][ $key ] = $value;
    return true;
}
function delete_option( $key ) {
    unset( $GLOBALS['_wp_options'][ $key ] );
    return true;
}

// ──────────────────────────────────────────────────────────────
// In-memory WP admin menu globals
// ──────────────────────────────────────────────────────────────
$GLOBALS['_wp_menu_removed'] = [];
$GLOBALS['admin_page_hooks'] = [];
$GLOBALS['submenu']          = [];

function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) {
    $GLOBALS['admin_page_hooks'][ $menu_slug ] = $menu_title;
    return $menu_slug;
}
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
    // Mirror WP: append the new item to the parent's submenu array.
    $GLOBALS['submenu'][ $parent ][] = [ $menu_title, $capability, $menu_slug, $page_title ];
    // Test-only: capture the render callback so tests can assert what a page renders.
    $GLOBALS['_wp_submenu_callbacks'][ $menu_slug ] = $callback;
    return $menu_slug;
}
function remove_menu_page( $menu_slug ) {
    $GLOBALS['_wp_menu_removed'][] = $menu_slug;
    unset( $GLOBALS['admin_page_hooks'][ $menu_slug ] );
    return true;
}
function remove_submenu_page( $menu_slug, $submenu_slug ) {
    // Mirror WP: unset the matching entry by key (leaves a sparse array).
    if ( ! empty( $GLOBALS['submenu'][ $menu_slug ] ) && is_array( $GLOBALS['submenu'][ $menu_slug ] ) ) {
        foreach ( $GLOBALS['submenu'][ $menu_slug ] as $i => $item ) {
            if ( ( $item[2] ?? null ) === $submenu_slug ) {
                unset( $GLOBALS['submenu'][ $menu_slug ][ $i ] );
                return $item;
            }
        }
    }
    return false;
}

// ──────────────────────────────────────────────────────────────
// WordPress hooks (no-op stubs)
// ──────────────────────────────────────────────────────────────
$GLOBALS['_wp_filters'] = [];
$GLOBALS['_wp_actions'] = [];

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
    $GLOBALS['_wp_actions'][ $hook ][] = [ 'callback' => $callback, 'priority' => $priority ];
}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
    $GLOBALS['_wp_filters'][ $hook ][] = [ 'callback' => $callback, 'priority' => $priority ];
}
function do_action( $hook, ...$args ) {
    // no-op in tests
}

/**
 * apply_filters — runs registered filter callbacks in priority order.
 * Needed by LegacyBridge and LicenseRegistry tests.
 */
function apply_filters( $hook, $value, ...$args ) {
    if ( empty( $GLOBALS['_wp_filters'][ $hook ] ) ) {
        return $value;
    }
    usort( $GLOBALS['_wp_filters'][ $hook ], function( $a, $b ) {
        return $a['priority'] <=> $b['priority'];
    } );
    foreach ( $GLOBALS['_wp_filters'][ $hook ] as $filter ) {
        $value = call_user_func( $filter['callback'], $value, ...$args );
    }
    return $value;
}

// ──────────────────────────────────────────────────────────────
// WordPress HTTP / misc stubs
// ──────────────────────────────────────────────────────────────
function wp_remote_get( $url, $args = [] ) {
    return [ 'body' => '{}', 'response' => [ 'code' => 200 ] ];
}
function wp_remote_post( $url, $args = [] ) {
    return [ 'body' => '{}', 'response' => [ 'code' => 200 ] ];
}
function wp_remote_retrieve_body( $response ) {
    return $response['body'] ?? '{}';
}
function is_wp_error( $thing ) {
    return false;
}
function home_url( $path = '' ) {
    return 'https://example.com' . $path;
}
function admin_url( $path = '' ) {
    return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
}
function network_admin_url( $path = '' ) {
    return 'https://example.com/wp-admin/network/' . ltrim( $path, '/' );
}
function rest_url( $path = '' ) {
    return 'https://example.com/wp-json/' . ltrim( $path, '/' );
}
function plugin_dir_url( $file ) {
    return 'https://example.com/wp-content/plugins/test/';
}
function plugin_dir_path( $file ) {
    return '/tmp/wp/wp-content/plugins/test/';
}
function wp_create_nonce( $action ) {
    return 'test_nonce_' . md5( $action );
}
function wp_verify_nonce( $nonce, $action ) {
    return true;
}
function wp_enqueue_script() {}
function wp_enqueue_style() {}
function wp_register_script() {}
function wp_localize_script() {}
function current_user_can( $cap ) {
    return true;
}
function is_admin() {
    return true;
}
// Multisite Network Admin context — toggle via $GLOBALS['_is_network_admin'].
$GLOBALS['_is_network_admin'] = false;
function is_network_admin() {
    return $GLOBALS['_is_network_admin'] ?? false;
}
function wp_schedule_event() {}
function wp_next_scheduled() {
    return false;
}
function get_current_screen() {
    return (object) [ 'id' => '' ];
}
function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $url ) {
    return $url;
}
function esc_url_raw( $url ) {
    return $url;
}
function esc_html__( $text, $domain = 'yaycommerce' ) {
    return $text;
}
function __( $text, $domain = 'yaycommerce' ) {
    return $text;
}
function esc_html_e( $text, $domain = 'yaycommerce' ) {
    echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr_e( $text, $domain = 'yaycommerce' ) {
    echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function wp_kses( $data, $allowed_html ) {
    return $data;
}
function wp_kses_post( $data ) {
    return $data;
}
// printf() is a builtin — do not redeclare it
function get_plugins() {
    return [];
}
function is_plugin_active( $plugin ) {
    return false;
}
function activate_plugin( $plugin ) {
    return null;
}
function plugins_api( $action, $args ) {
    return false;
}
function install_plugin_install_status( $api ) {
    return [ 'status' => 'not_installed', 'file' => '' ];
}
function get_site_transient( $transient ) {
    return false;
}
function set_site_transient( $transient, $value ) {}
function get_plugin_data( $file, $markup = true, $translate = true ) {
    return [ 'Name' => 'Test', 'Version' => '1.0' ];
}
function wp_send_json_success( $data ) {}
function wp_send_json_error( $data ) {}

// WP_REST_Server constant
if ( ! class_exists( 'WP_REST_Server' ) ) {
    class WP_REST_Server {
        const CREATABLE = 'POST';
    }
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        protected array $params = [];
        protected array $headers = [];

        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }
        public function set_param( string $key, $value ): void {
            $this->params[ $key ] = $value;
        }
        public function get_header( string $key ) {
            return $this->headers[ $key ] ?? null;
        }
        public function set_header( string $key, $value ): void {
            $this->headers[ $key ] = $value;
        }
    }
}
if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        public array $data;
        public int $status;
        public function __construct( array $data = [], int $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }
        public function get_status(): int {
            return $this->status;
        }
    }
}
function register_rest_route( $namespace, $route, $args ) {}

/**
 * Helper to reset wp_options store between tests.
 */
function _test_reset_options(): void {
    $GLOBALS['_wp_options'] = [];
}
function _test_reset_filters(): void {
    $GLOBALS['_wp_filters'] = [];
    $GLOBALS['_wp_actions'] = [];
}
function _test_reset_menus(): void {
    $GLOBALS['_wp_menu_removed'] = [];
    $GLOBALS['admin_page_hooks'] = [];
    $GLOBALS['submenu']          = [];
    $GLOBALS['_wp_submenu_callbacks'] = [];
    $GLOBALS['_is_network_admin'] = false;
    unset( $GLOBALS['yaycommerce_admin_shell_submenu_positions'] );
}
