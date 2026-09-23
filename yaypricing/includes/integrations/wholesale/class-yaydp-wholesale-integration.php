<?php
/**
 * Wholesale and YayPricing compatibility.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\Wholesale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAYDP_Wholesale_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! class_exists( 'WooCommerceWholeSalePricesPremium' ) ) {
            return;
        }

        add_filter( 'yaydp_can_current_user_see_discount_rule', array( $this, 'current_user_role_checker' ), 1, 2 );
	}

    public function current_user_role_checker( $checker ) {
        $current_user = wp_get_current_user();
        $current_user_roles = $current_user->roles;
        $running_rules = \yaydp_get_running_product_pricing_rules();

        if( ! $current_user_roles ) {
            return $checker;
        }
        
        if ( empty( $running_rules ) ) {
            return $checker;
        }
        
        foreach( $running_rules as $rule ) {
            $conditions = $rule->get_conditions();
            
            foreach( $conditions as $condition ) {
                if( $condition['type'] !== 'customer_role' ) {
                    continue;
                }
                
                $condition_values = array_map(
                    function ( $item ) {
                        return $item['value'];
                    },
                    $condition['value']
                );
                
                $intersection_roles = array_intersect( $condition_values, $current_user_roles );
                
                $comparation = isset( $condition['comparation'] ) ? $condition['comparation'] : 'in_list';
                
                if ( 'in_list' === $comparation ) {
                    if ( ! empty( $intersection_roles ) ) {
                        return true;
                    }
                } else {
                    if ( empty( $intersection_roles ) ) {
                        return true;
                    }
                }

                return false;
            }
        }
        
        return $checker;
    }
}