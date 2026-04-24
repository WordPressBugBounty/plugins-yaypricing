<?php
/**
 * Managing all things about the sale flash displaying.
 *
 * @since 2.4
 *
 * @package YayPricing\SaleDisplay
 */

namespace YAYDP\Core\Sale_Display;

use DOMDocument;

/**
 * Declare class
 */
class YAYDP_Sale_Flash {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Track products that have already displayed sale tags to prevent duplicates
	 *
	 * @var array
	 */
	protected static $displayed_products = array();

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'add_sale_tag' ), 100 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_sale_tag' ), 5 );
		add_filter( 'woocommerce_product_get_image', array( $this, 'product_image_html' ), 100, 5 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'single_product_image_thumbnail_html' ), 100, 2 );

		add_filter( 'woocommerce_sale_flash', array( $this, 'remove_sale_flash' ), 100, 3 );
		add_action( 'yaydp_custom_sale_tag', array( $this, 'add_sale_tag' ) );

		add_action( 'woocommerce_before_shop_loop', array( $this, 'reset_displayed_products' ), 1 );
		add_action( 'woocommerce_after_shop_loop', array( $this, 'reset_displayed_products' ), 999 );

		// Block-based single product template: woocommerce/product-image block.
		add_filter( 'render_block_woocommerce/product-image', array( $this, 'inject_sale_badge_in_product_image_block' ), 10, 2 );
	}

	/**
	 * Reset displayed products tracking
	 */
	public function reset_displayed_products() {
		self::$displayed_products = array();
	}

	/**
	 * Add sale tag
	 */
	public function add_sale_tag( $product = null ) {
		$can_current_user_see_sale_flash = apply_filters( 'yaydp_can_current_user_see_discount_rule', true );
		if( ! $can_current_user_see_sale_flash ) {
			return;
		}

		if ( $this->is_mini_cart_context() ) {
			return;
		}

		// In block context, inject_sale_badge_in_product_image_block handles rendering.
		if ( $this->is_wc_blocks_page() ) {
			return;
		}

		if ( ! $this->is_valid_sale_badge_context() ) {
			return;
		}
		
		if ( empty( $product ) ) {
			global $product;
		}
		if ( empty( $product ) ) {
			return;
		}
		
		$product_id = $product->get_id();
		
		if ( isset( self::$displayed_products[ $product_id ] ) ) {
			return;
		}
		
		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		if ( ! $sale_tag->can_display() ) {
			return;
		}
		$html = $sale_tag->get_content();
		if ( ! empty( $html ) ) {
			self::$displayed_products[ $product_id ] = true;
			echo wp_kses_post( $html );
		}
	}

	/**
	 * Callback for woocommerce_product_get_image hook to add sale tag to product image
	 *
	 * @param string $image HTML.
	 * @param \WC_Product $product Product object.
	 * @param string $size Image size.
	 * @param array $attr Attributes.
	 * @param bool $placeholder Whether to use placeholder.
	 *
	 * @return string Modified HTML.
	 */
	public function product_image_html( $image, $product, $size, $attr, $placeholder ) {
		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return $image;
		}
		
		if ( \is_product() ) {
			return $image;
		}
		
		if ( \is_cart() || \is_checkout() ) {
			return $image;
		}
		
		if ( $this->is_mini_cart_context() ) {
			return $image;
		}

		// In block context, inject_sale_badge_in_product_image_block handles rendering.
		if ( $this->is_wc_blocks_page() ) {
			return $image;
		}

		if ( ! $this->is_product_loop_context() ) {
			return $image;
		}
		
		$product_id = $product->get_id();
		
		if ( isset( self::$displayed_products[ $product_id ] ) ) {
			return $image;
		}
		
		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		if ( ! $sale_tag->can_display() ) {
			return $image;
		}
		
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $image;
		}
		
		self::$displayed_products[ $product_id ] = true;
		
		libxml_use_internal_errors( true );
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->encoding = 'UTF-8';
		
		$wrapped_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $image . '</body></html>';
		$dom->loadHTML( $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		
		$fragment = $dom->createDocumentFragment();
		$fragment->appendXML( $sale_tag_content );
		
		if ( ! $fragment->hasChildNodes() ) {
			return $image;
		}
		
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( $body ) {
			$body->appendChild( $fragment );
		} else {
			if ( $dom->firstChild ) {
				$dom->firstChild->appendChild( $fragment );
			} else {
				$dom->appendChild( $fragment );
			}
		}
		
		$body_content = '';
		if ( $body ) {
			foreach ( $body->childNodes as $child ) {
				$body_content .= $dom->saveHTML( $child );
			}
		} else {
			$body_content = $dom->saveHTML( $dom->documentElement );
		}
		
		return $body_content;
	}

	/**
	 * Callback for woocommerce_single_product_image_thumbnail_html hook
	 *
	 * @param string $html HTML.
	 * @param int    $attachment_id Image id.
	 */
	public function single_product_image_thumbnail_html( $html, $attachment_id ) {
		$can_current_user_see_sale_flash = apply_filters( 'yaydp_can_current_user_see_discount_rule', true );
		if( ! $can_current_user_see_sale_flash ) {
			return $html;
		}
		
		if ( ! \is_product() ) {
			return $html;
		}
		
		global $product;
		if ( empty( $product ) ) {
			return $html;
		}
		$current_product = $product;
		if ( \yaydp_is_variable_product( $product ) ) {
			$variation = \YAYDP\Helper\YAYDP_Variable_Product_Helper::get_variation_with_attachment_id( $product, $attachment_id );
			if ( ! empty( $variation ) ) {
				$current_product = $variation;
			}
		}
		
		$tracking_key = $current_product->get_id() . '_' . $attachment_id;
		if ( isset( self::$displayed_products[ $tracking_key ] ) ) {
			return $html;
		}
		
		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $current_product );
		if ( ! $sale_tag->can_display() ) {
			return $html;
		}
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $html;
		}
		
		self::$displayed_products[ $tracking_key ] = true;
		
		libxml_use_internal_errors(true);
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->encoding = 'UTF-8';
		
		$wrapped_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
		$dom->loadHTML($wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
		$xpath = new \DOMXPath($dom);
		$gallery_images = $xpath->query("//*[contains(@class, 'woocommerce-product-gallery__image')]");
		
		if ( $gallery_images->length > 0 ) {
			$gallery_image = $gallery_images->item(0);
			$fragment = $dom->createDocumentFragment();
			$fragment->appendXML( $sale_tag_content );
			
			if ( $fragment->hasChildNodes() ) {
				$gallery_image->appendChild( $fragment );
			}
		} else {
			$body = $dom->getElementsByTagName('body')->item(0);
			if ( $body ) {
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXML( $sale_tag_content );
				
				if ( $fragment->hasChildNodes() ) {
					$body->appendChild( $fragment );
				}
			}
		}
		
		$body_content = '';
		$body = $dom->getElementsByTagName('body')->item(0);
		if ( $body ) {
			foreach ( $body->childNodes as $child ) {
				$body_content .= $dom->saveHTML( $child );
			}
		} else {
			$body_content = $dom->saveHTML( $dom->documentElement );
		}
		
		return $body_content;
	}

	/**
	 * Check if we're in a mini cart context
	 *
	 * @return bool
	 */
	protected function is_mini_cart_context() {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['function'] ) && 'apply_filters' === $trace['function'] ) {
				if ( isset( $trace['args'][0] ) && 'woocommerce_cart_item_thumbnail' === $trace['args'][0] ) {
					return true;
				}
			}
		}
		
		if ( did_action( 'woocommerce_before_mini_cart' ) || did_action( 'woocommerce_mini_cart_contents' ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if we're in a valid context to display sale badge (single product or product loop)
	 *
	 * @return bool
	 */
	protected function is_valid_sale_badge_context() {
		if ( \is_product() ) {
			return true;
		}
		
		if ( $this->is_product_loop_context() ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if we're in a product loop context (shop, category, tag, or other product archive)
	 *
	 * @return bool
	 */
	protected function is_product_loop_context() {
		if ( \is_shop() ) {
			return true;
		}
		
		if ( \is_product_category() ) {
			return true;
		}
		
		if ( \is_product_tag() ) {
			return true;
		}
		
		if ( \is_product_taxonomy() ) {
			return true;
		}
		
		if ( \did_action( 'woocommerce_before_shop_loop' ) || \did_action( 'woocommerce_after_shop_loop' ) ) {
			return true;
		}
		
		if ( $this->is_woocommerce_block_context() ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Detect if the current page is rendered using WooCommerce Blocks.
	 * When true, block mode rendering via inject_sale_badge_in_product_image_block
	 * is responsible for the sale badge — classic hooks should bail out.
	 *
	 * @return bool
	 */
	protected function is_wc_blocks_page() {
		// Active block render in the call stack (product grid, product-collection, etc.)
		if ( $this->is_woocommerce_block_context() ) {
			return true;
		}

		// Block/FSE theme: WooCommerce uses block templates for shop and single-product pages.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we're in a WooCommerce block context (like Featured Products, Product Grid, etc.)
	 *
	 * @return bool
	 */
	protected function is_woocommerce_block_context() {
		$backtrace = \debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['class'] ) ) {
				$class = $trace['class'];
				if ( 
					\strpos( $class, 'WooCommerce\Blocks' ) !== false ||
					\strpos( $class, 'woocommerce\blocks' ) !== false ||
					\strpos( $class, 'Block' ) !== false
				) {
					if ( 
						\strpos( $class, 'Cart' ) !== false ||
						\strpos( $class, 'Checkout' ) !== false ||
						\strpos( $class, 'MiniCart' ) !== false ||
						\strpos( $class, 'Mini-Cart' ) !== false
					) {
						return false;
					}
					return true;
				}
			}
			
			if ( isset( $trace['function'] ) ) {
				$function = $trace['function'];
				if ( 
					\strpos( $function, 'render_block' ) !== false ||
					\strpos( $function, 'block_core' ) !== false ||
					\strpos( $function, 'woocommerce_blocks' ) !== false
				) {
					return true;
				}
			}
		}
		
		if ( \did_action( 'woocommerce_blocks_product_grid_item_html' ) ) {
			return true;
		}
		
		if ( 
			( \defined( 'REST_REQUEST' ) && \REST_REQUEST ) ||
			( \function_exists( 'wp_is_json_request' ) && \wp_is_json_request() )
		) {
			if ( ! \is_cart() && ! \is_checkout() ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Inject sale badge into the woocommerce/product-image block output (block-based single product template).
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data including attrs.
	 *
	 * @return string Modified HTML.
	 */
	public function inject_sale_badge_in_product_image_block( $block_content, $block ) {
		$can_current_user_see_sale_flash = apply_filters( 'yaydp_can_current_user_see_discount_rule', true );
		if ( ! $can_current_user_see_sale_flash ) {
			return $block_content;
		}

		// Resolve product: prefer explicit productId attr, then global $product / $post.
		$product_id = isset( $block['attrs']['productId'] ) ? absint( $block['attrs']['productId'] ) : 0;
		if ( ! $product_id ) {
			global $product;
			if ( ! empty( $product ) && is_a( $product, 'WC_Product' ) ) {
				$product_id = $product->get_id();
			} else {
				global $post;
				if ( ! empty( $post ) ) {
					$product_id = $post->ID;
				}
			}
		}

		if ( ! $product_id ) {
			return $block_content;
		}

		$wc_product = wc_get_product( $product_id );
		if ( ! $wc_product ) {
			return $block_content;
		}

		$tracking_key = 'block_image_' . $product_id;
		if ( isset( self::$displayed_products[ $tracking_key ] ) ) {
			return $block_content;
		}

		$sale_tag = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $wc_product );
		if ( ! $sale_tag->can_display() ) {
			return $block_content;
		}
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $block_content;
		}

		self::$displayed_products[ $tracking_key ] = true;

		libxml_use_internal_errors( true );
		$dom           = new DOMDocument( '1.0', 'UTF-8' );
		$dom->encoding = 'UTF-8';

		$wrapped_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $block_content . '</body></html>';
		$dom->loadHTML( $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$xpath         = new \DOMXPath( $dom );
		$gallery_images = $xpath->query( "//*[contains(@class, 'woocommerce-product-gallery__image')]" );
		$fragment      = $dom->createDocumentFragment();
		$fragment->appendXML( $sale_tag_content );

		if ( ! $fragment->hasChildNodes() ) {
			return $block_content;
		}

		if ( $gallery_images->length > 0 ) {
			$gallery_images->item( 0 )->appendChild( $fragment );
		} else {
			$body = $dom->getElementsByTagName( 'body' )->item( 0 );
			if ( $body ) {
				$body->appendChild( $fragment );
			}
		}

		$body_content = '';
		$body         = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( $body ) {
			foreach ( $body->childNodes as $child ) {
				$body_content .= $dom->saveHTML( $child );
			}
		}

		return $body_content ?: $block_content;
	}

	/**
	 * Callback for woocommerce_sale_flash hook
	 *
	 * @param string      $wc_sale_flash_html HTML.
	 * @param
	 * @param \WC_Product $product Given product.
	 */
	public function remove_sale_flash( $wc_sale_flash_html, $post, $product ) {
		if ( empty( $product ) ) {
			return $wc_sale_flash_html;
		}
		$sale_tag         = new \YAYDP\Core\Sale_Display\YAYDP_Sale_Tag( $product );
		$sale_tag_content = $sale_tag->get_content();
		if ( empty( $sale_tag_content ) ) {
			return $wc_sale_flash_html;
		}
		return '';
	}
}
