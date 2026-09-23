<?php
/**
 * Product filter type: products_subscription_status — whether the customer
 * holds an active / on-hold / pending subscription to the product.
 *
 * @package YayPricing\Integrations
 * @since 3.5.8
 */

namespace YAYDP\Integrations\WooCommerce;

use YAYDP\Condition\YAYDP_Comparators;
use YAYDP\Product_Filter\YAYDP_Product_Filter_Context;

defined( 'ABSPATH' ) || exit;

class YAYDP_Subscription_Status_Product_Filter extends \YAYDP\Abstracts\YAYDP_Product_Filter_Type {

	public function slug() {
		return 'products_subscription_status';
	}

	public function label() {
		return __( 'Subscription status', 'yaypricing' );
	}

	public function comparators() {
		return YAYDP_Comparators::pick(
			array(
				'has_subscription' => __( 'Has subscription', 'yaypricing' ),
				'no_subscription'  => __( 'No subscription', 'yaypricing' ),
			)
		);
	}

	public function default_value() {
		return array();
	}

	public function check( $product, array $filter, YAYDP_Product_Filter_Context $ctx ) {
		$user_id = $ctx->user_id();
		if ( empty( $user_id ) ) {
			return false;
		}
		$wants_subscription = 'has_subscription' === $filter['comparation'];
		if ( \yaydp_is_variable_product( $product ) || \yaydp_is_grouped_product( $product ) ) {
			foreach ( array_merge( array( $product->get_id() ), $product->get_children() ) as $product_id ) {
				if ( $this->has_subscription( $user_id, $product_id ) === $wants_subscription ) {
					return true;
				}
			}
			return false;
		}
		return $this->has_subscription( $user_id, $product->get_id() ) === $wants_subscription;
	}

	/**
	 * Whether the user holds a live subscription to the product.
	 *
	 * @param int $user_id    User id.
	 * @param int $product_id Product id.
	 * @return bool
	 */
	private function has_subscription( $user_id, $product_id ) {
		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {
			if ( \wcs_user_has_subscription( $user_id, $product_id, $status ) ) {
				return true;
			}
		}
		return false;
	}

	public function matching_products( array $values, $comparation ) {
		$user_id = \get_current_user_id();
		if ( empty( $user_id ) ) {
			return array();
		}
		$subscriptions = \wcs_get_subscriptions(
			array(
				'customer_id'         => $user_id,
				'subscription_status' => array( 'active', 'on-hold', 'pending' ),
			)
		);
		if ( empty( $subscriptions ) ) {
			return array();
		}
		$products = array();
		foreach ( $subscriptions as $subscription ) {
			foreach ( $subscription->get_items() as $item ) {
				$products[] = $item->get_product();
			}
		}
		if ( 'has_subscription' === $comparation ) {
			return $products;
		}
		$ids = array_map(
			function ( $product ) {
				return $product->get_id();
			},
			$products
		);
		return \YAYDP\Helper\YAYDP_Matching_Products_Helper::get_product_by_ids( $ids, 'not_in_list' );
	}
}
