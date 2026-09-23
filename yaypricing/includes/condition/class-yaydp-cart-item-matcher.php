<?php
/**
 * Pure functions over a list of cart items, shared by condition types.
 *
 * Two flavours of "which items match this product/category list" exist and
 * they are not interchangeable:
 *
 *   by_*()         top-level `cart_item*` conditions — `not_contain` yields the
 *                  non-matching items only when NO listed id is in the cart,
 *                  and categories match through their ancestors.
 *   narrow_by_*()  combined-condition children — `not_contain` always yields
 *                  the non-matching items, categories match directly.
 *
 * Both are the historical behaviours; keep them apart.
 *
 * @package YayPricing\Condition
 * @since 3.5.8
 */

namespace YAYDP\Condition;

defined( 'ABSPATH' ) || exit;

final class YAYDP_Cart_Item_Matcher {

	/**
	 * Ids stored in a list condition, run through the translation filter.
	 *
	 * @param array  $condition   Stored condition.
	 * @param string $object_type product | product_cat | product_tag.
	 * @return array
	 */
	public static function translated_ids( array $condition, $object_type ) {
		$ids = \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition );
		return apply_filters( 'yaydp_translated_list_object_id', $ids, $object_type );
	}

	/**
	 * Product id plus parent id (variations) of a cart item's product.
	 *
	 * @param object $cart_item Cart item.
	 * @return int[]
	 */
	public static function product_ids( $cart_item ) {
		$product = $cart_item->get_product();
		$ids     = array( $product->get_id() );
		if ( ! empty( $product->get_parent_id() ) ) {
			$ids[] = $product->get_parent_id();
		}
		return $ids;
	}

	/**
	 * Total quantity of the given items (cart-item objects or arrays with a
	 * `quantity` key; anything else counts as zero).
	 *
	 * @param array $items Items.
	 * @return float|int
	 */
	public static function total_quantity( array $items ) {
		$quantity = 0;
		foreach ( $items as $item ) {
			if ( is_array( $item ) && ! empty( $item['quantity'] ) ) {
				$quantity += $item['quantity'];
			} elseif ( $item instanceof \YAYDP\Core\YAYDP_Cart_Item ) {
				$quantity += $item->get_quantity();
			}
		}
		return $quantity;
	}

	/**
	 * Quantity-weighted subtotal of the given cart items at their effective
	 * (or, for non-YayPricing items, plain) price.
	 *
	 * @param array $items Cart items.
	 * @return float
	 */
	public static function subtotal( array $items ) {
		$subtotal = 0;
		foreach ( $items as $item ) {
			$price     = $item instanceof \YAYDP\Core\YAYDP_Cart_Item ? $item->get_effective_price() : $item->get_price();
			$subtotal += $item->get_quantity() * $price;
		}
		return $subtotal;
	}

	/**
	 * Split items into matching / not matching by an id lookup, and collect
	 * every id seen in the cart.
	 *
	 * @param array    $items    Cart items.
	 * @param array    $ids      Ids to match.
	 * @param callable $ids_of   Item → ids it carries.
	 * @return array { matching, not_matching, in_cart }
	 */
	private static function partition( array $items, array $ids, callable $ids_of ) {
		$result = array(
			'matching'     => array(),
			'not_matching' => array(),
			'in_cart'      => array(),
		);
		foreach ( $items as $item ) {
			$item_ids = $ids_of( $item );
			$key      = array_intersect( $item_ids, $ids ) ? 'matching' : 'not_matching';

			$result[ $key ][]  = $item;
			$result['in_cart'] = array_merge( $result['in_cart'], $item_ids );
		}
		$result['in_cart'] = array_unique( $result['in_cart'] );
		return $result;
	}

	/**
	 * Apply the comparator to a partition.
	 *
	 * @param array $partition          From partition().
	 * @param array $ids                Ids compared against the cart.
	 * @param int   $all_count          Expected intersection size for contain_all.
	 * @param array $condition          Stored condition.
	 * @param bool  $exclusive_negative not_contain requires no listed id in the cart.
	 * @return array Matching items for the comparator, or empty.
	 */
	private static function select( array $partition, array $ids, $all_count, array $condition, $exclusive_negative ) {
		$intersect = array_intersect( $partition['in_cart'], $ids );
		switch ( $condition['comparation'] ) {
			case YAYDP_Comparators::CONTAIN:
				return ! empty( $intersect ) ? $partition['matching'] : array();
			case YAYDP_Comparators::CONTAIN_ALL:
				return count( $intersect ) === $all_count ? $partition['matching'] : array();
			case YAYDP_Comparators::CONTAIN_ONLY:
				return count( $intersect ) === count( $partition['in_cart'] ) ? $partition['matching'] : array();
			default:
				return ( ! $exclusive_negative || empty( $intersect ) ) ? $partition['not_matching'] : array();
		}
	}

	/**
	 * Items whose product (or parent) is in the condition's product list.
	 *
	 * @param array $items     Cart items.
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function by_product( array $items, array $condition ) {
		$ids = self::translated_ids( $condition, 'product' );
		return self::select(
			self::partition( $items, $ids, array( __CLASS__, 'product_ids' ) ),
			$ids,
			count( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ),
			$condition,
			true
		);
	}

	/**
	 * Combined-child variant of by_product().
	 *
	 * @param array $items     Cart items.
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function narrow_by_product( array $items, array $condition ) {
		$ids = self::translated_ids( $condition, 'product' );
		return self::select(
			self::partition( $items, $ids, array( __CLASS__, 'product_ids' ) ),
			$ids,
			count( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) ),
			$condition,
			false
		);
	}

	/**
	 * Items in one of the condition's categories or any of their ancestors.
	 *
	 * @param array $items     Cart items.
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function by_category( array $items, array $condition ) {
		$ids = self::translated_ids( $condition, 'product_cat' );
		foreach ( $ids as $cat_id ) {
			$ids = array_merge( $ids, get_ancestors( $cat_id, 'product_cat' ) );
		}
		$ids = array_unique( $ids );
		return self::select( self::partition( $items, $ids, array( __CLASS__, 'category_ids' ) ), $ids, count( $ids ), $condition, true );
	}

	/**
	 * Combined-child variant of by_category() (no ancestor expansion).
	 *
	 * @param array $items     Cart items.
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function narrow_by_category( array $items, array $condition ) {
		$ids       = self::translated_ids( $condition, 'product_cat' );
		$all_count = count( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) );
		return self::select( self::partition( $items, $ids, array( __CLASS__, 'category_ids' ) ), $ids, $all_count, $condition, false );
	}

	/**
	 * Combined-child tag matcher.
	 *
	 * @param array $items     Cart items.
	 * @param array $condition Stored condition.
	 * @return array
	 */
	public static function narrow_by_tag( array $items, array $condition ) {
		$ids       = self::translated_ids( $condition, 'product_tag' );
		$all_count = count( \YAYDP\Helper\YAYDP_Helper::map_filter_value( $condition ) );
		return self::select( self::partition( $items, $ids, array( __CLASS__, 'tag_ids' ) ), $ids, $all_count, $condition, false );
	}

	/**
	 * Category ids (with ancestors and parent product) of a cart item.
	 *
	 * @param object $cart_item Cart item.
	 * @return int[]
	 */
	public static function category_ids( $cart_item ) {
		return \YAYDP\Helper\YAYDP_Product_Helper::get_product_cats( $cart_item->get_product() );
	}

	/**
	 * Tag ids (with parent product) of a cart item.
	 *
	 * @param object $cart_item Cart item.
	 * @return int[]
	 */
	public static function tag_ids( $cart_item ) {
		return \YAYDP\Helper\YAYDP_Product_Helper::get_product_tags( $cart_item->get_product() );
	}

	/**
	 * Attribute taxonomy keys present on the items' products (a variation
	 * also carries its parent's visible, non-variation attributes).
	 *
	 * @param array $items Cart items.
	 * @return string[]
	 */
	public static function attribute_taxonomies( array $items ) {
		$keys = array();
		foreach ( $items as $item ) {
			$product    = $item->get_product();
			$attributes = $product->get_attributes();
			$parent     = \yaydp_is_variation_product( $product ) ? \wc_get_product( $product->get_parent_id() ) : null;
			if ( $parent ) {
				foreach ( $parent->get_attributes() as $attribute ) {
					if ( $attribute instanceof \WC_Product_Attribute && $attribute['visible'] && ! $attribute['variation'] ) {
						$attributes[ $attribute['name'] ] = $attribute;
					}
				}
			}
			$keys = array_merge( $keys, array_keys( $attributes ) );
		}
		return array_unique( $keys );
	}
}
