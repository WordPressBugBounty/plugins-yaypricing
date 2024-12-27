<?php
/**
 * Handle Vite admin app.
 *
 * @package YayPricing\Class
 */

namespace YAYDP;

/**
 * Declare class
 */
class Vite {

	/**
	 * Get path of build files.
	 */
	public static function base_path() {
		return YAYDP_PLUGIN_URL . 'assets/dist/';
	}

	/**
	 * Enqueue build scripts.
	 *
	 * @param string $script Name of script file.
	 * @param string $port Current when run dev.
	 */
	public static function enqueue_vite( $script = 'main.tsx', $port = '3001' ) {
		self::enqueue_preload( $script, $port );
		self::css_tag( $script );
		self::register( $script, $port );
		add_filter(
			'script_loader_tag',
			function ( $tag, $handle, $src ) {
				if ( str_contains( $handle, 'module/yaydp/' ) ) {
					$str  = "type='module'";
					$str .= true ? ' crossorigin' : '';
					$tag  = '<script ' . $str . ' src="' . esc_url( $src ) . '" id="' . esc_attr( $handle ) . '-js"></script>';
				}
				return $tag;
			},
			10,
			3
		);

		add_filter(
			'script_loader_src',
			function( $src, $handle ) {
				if ( str_contains( $handle, 'module/yaydp/vite' ) && strpos( $src, '?ver=' ) ) {
					return remove_query_arg( 'ver', $src );
				}
				return $src;
			},
			10,
			2
		);
	}

	/**
	 * Enqueue script
	 *
	 * @param string $script Name of script file.
	 * @param string $port Current when run dev.
	 */
	public static function enqueue_preload( $script, $port ) {
		add_action(
			'admin_head',
			function() use ( $script, $port ) {
				self::js_preload_imports( $script, $port );
			}
		);
	}

	/**
	 * Register script
	 *
	 * @param string $entry Name of script file.
	 * @param string $port Current when run dev.
	 */
	public static function register( $entry, $port ) {
		$url = constant( 'YAYDP_DEVELOPMENT' )
		? "http://localhost:$port/src/$entry"
		: self::asset_url( $entry );

		if ( ! $url ) {
			return '';
		}

		if ( constant( 'YAYDP_DEVELOPMENT' ) ) {
			wp_enqueue_script( 'module/yaydp/vite', "http://localhost:$port/@vite/client", array(), YAYDP_VERSION, false );
		}
		wp_register_script( "module/yaydp/$entry", $url, false, YAYDP_DEVELOPMENT ? true : time(), true );
		wp_enqueue_script( "module/yaydp/$entry" );
	}

	/**
	 * Register script
	 *
	 * @param string $entry Name of script file.
	 * @param string $port Current when run dev.
	 */
	private static function js_preload_imports( $entry, $port ) {
		if ( constant( 'YAYDP_DEVELOPMENT' ) ) {
			echo '<script type="module">
			import RefreshRuntime from "http://localhost:' . esc_attr( $port ) . '/@react-refresh"
			RefreshRuntime.injectIntoGlobalHook(window)
			window.$RefreshReg$ = () => {}
			window.$RefreshSig$ = () => (type) => type
			window.__vite_plugin_react_preamble_installed__ = true
			</script>';
		} else {
			foreach ( self::imports_urls( $entry ) as $url ) {
				echo ( '<link rel="modulepreload" href="' . esc_url( $url ) . '">' );
			}
		}

	}

	/**
	 * Register script
	 *
	 * @param string $entry Name of css file.
	 */
	private static function css_tag( $entry ) {
		// not needed on dev, it's inject by Vite.
		if ( constant( 'YAYDP_DEVELOPMENT' ) ) {
			return '';
		}

		$tags = '';
		foreach ( self::css_urls( $entry ) as $key => $url ) {
			wp_register_style( "yaydp/$key", $url, array(), YAYDP_VERSION );
			wp_enqueue_style( "yaydp/$key", $url, array(), YAYDP_VERSION );
		}
		return $tags;
	}


	/**
	 * Get manifest file
	 */
	private static function get_manifest() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( YAYDP_PLUGIN_PATH . 'assets/dist/manifest.json' );

		return json_decode( $content, true );
	}

	/**
	 * Get asset url
	 *
	 * @param string $entry name of asset file.
	 */
	private static function asset_url( $entry ) {
		$manifest = self::get_manifest();

		return isset( $manifest[ $entry ] )
		? self::base_path() . $manifest[ $entry ]['file']
		: self::base_path() . $entry;
	}

	/**
	 * Get asset file url
	 */
	private static function get_public_url_base() {
		return constant( 'YAYDP_DEVELOPMENT' ) ? '/dist/' : self::base_path();
	}

	/**
	 * Import asset files from url
	 *
	 * @param string $entry Entry file.
	 */
	private static function imports_urls( $entry ) {
		$urls     = array();
		$manifest = self::get_manifest();

		if ( ! empty( $manifest[ $entry ]['imports'] ) ) {
			foreach ( $manifest[ $entry ]['imports'] as $imports ) {
				$urls[] = self::get_public_url_base() . $manifest[ $imports ]['file'];
			}
		}
		return $urls;
	}

	/**
	 * Get urls of css files
	 *
	 * @param string $entry Entry file.
	 */
	private static function css_urls( $entry ) {
		$urls     = array();
		$manifest = self::get_manifest();

		if ( ! empty( $manifest[ $entry ]['css'] ) ) {
			foreach ( $manifest[ $entry ]['css'] as $file ) {
				$urls[ "yaydp_entry_$file" ] = self::get_public_url_base() . $file;
			}
		}

		if ( ! empty( $manifest[ $entry ]['imports'] ) ) {
			foreach ( $manifest[ $entry ]['imports'] as $imports ) {
				if ( ! empty( $manifest[ $imports ]['css'] ) ) {
					foreach ( $manifest[ $imports ]['css'] as $css ) {
						$urls[ "yaydp_imports_$css" ] = self::get_public_url_base() . $css;
					}
				}
			}
		}
		return $urls;
	}
}
