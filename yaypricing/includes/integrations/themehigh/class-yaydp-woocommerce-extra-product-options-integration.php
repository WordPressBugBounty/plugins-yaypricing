<?php
/**
 * Handles the integration of YITH WooCommerce Brands plugin with our system
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\ThemeHigh;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_WooCommerce_Extra_Product_Options_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! class_exists( 'THWEPO' ) || ! class_exists( 'THWEPO_Price' ) ) {
			return;
		}
		add_action( 'yaydp_after_initial_cart_item', array( $this, 'initialize_extra_options' ), 100, 2 );
		add_filter( 'yaydp_initial_cart_item_price', array( $this, 'initialize_cart_item_price' ), 100, 2 );
	}

	public function initialize_extra_options( $cart_item, $yaydp_cart_item_instance ) {
		if ( ! empty( $cart_item['thwepo_options'] ) ) {
			$yaydp_cart_item_instance->regardless_extra_options = true;
		}
	}

	public function initialize_cart_item_price( $price, $cart_item ) {
		if ( empty( $cart_item['thwepo_options'] ) ) {
			return $price;
		}
		return $this->calculate_cart_item_extra_costs( $cart_item, $price );
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function calculate_cart_item_extra_costs( $cart_item, $original_price ) {

		$price        = $original_price;
		$request_data = $this->prepare_extra_price_request_data_cart( $cart_item );
		if ( $request_data ) {
			try {
				$result = $this->calculate_extra_price( $request_data );
			} catch ( \Exception $e ) {
			}
		}

		if ( ! empty( $result['extra_price'] ) ) {
			$price += $result['extra_price'];
		}

		return $price;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function prepare_extra_price_request_data_cart( $cart_item ) {
		$data          = false;
		$extra_options = $cart_item['thwepo_options'] ?? false;

		if ( is_array( $extra_options ) ) {
			$price_info_list = array();

			$product_id = $cart_item['product_id'] ?? false;
			$quantity   = $cart_item['quantity'] ?? false;
			$product    = $cart_item['data'] ?? '';

			foreach ( $extra_options as $name => $data ) {
				if ( isset( $data['price_field'] ) && $data['price_field'] ) {
					$price_info               = $this->prepare_extra_price_request_data_cart_single( $data );
					$price_info_list[ $name ] = $price_info;
				}
			}

			$original_price = floatval( $product->get_price( '' ) );
			if ( isset( $cart_item['thwepo-original_price'] ) && is_numeric( $cart_item['thwepo-original_price'] ) ) {
				$original_price = floatval( $cart_item['thwepo-original_price'] );
			}

			$data                  = array();
			$data['product_id']    = $product_id;
			$data['product_price'] = $original_price;
			$data['product']       = $product;
			$data['price_info']    = $price_info_list;
			$data['product_qty']   = $quantity;
		}
		return apply_filters( 'thwepo_cart_item_extra_price_request_data', $data, $cart_item );
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function prepare_extra_price_request_data_cart_single( $data ) {
		$price_info = false;

		if ( is_array( $data ) ) {
			$field_type = $data['field_type'] ?? '';

			$price_info          = array();
			$price_info['name']  = $data['name'] ?? '';
			$price_info['label'] = $data['label'] ?? '';
			$price_info['value'] = $data['value'] ?? '';

			if ( $this->is_price_field_type_option( $field_type ) ) {
				$of_price_info = $this->prepare_option_field_price_props( $data );

				$price_info['price']          = isset( $of_price_info['price'] ) ? $of_price_info['price'] : '';
				$price_info['price_type']     = isset( $of_price_info['price_type'] ) ? $of_price_info['price_type'] : '';
				$price_info['price_options']  = isset( $of_price_info['price_options'] ) ? $of_price_info['price_options'] : '';
				$price_info['price_unit']     = '';
				$price_info['price_min_unit'] = '';

				$price_info['product_group_quantity'] = isset( $of_price_info['product_group_quantity'] ) ? $of_price_info['product_group_quantity'] : '';
			} else {
				$price_info['price']          = isset( $data['price'] ) ? $data['price'] : '';
				$price_info['price_type']     = isset( $data['price_type'] ) ? $data['price_type'] : '';
				$price_info['price_unit']     = isset( $data['price_unit'] ) ? $data['price_unit'] : '';
				$price_info['price_min_unit'] = isset( $data['price_min_unit'] ) ? $data['price_min_unit'] : '';
			}

			//$price_info['multiple']    = isset($data['multiple']) ? $data['multiple'] : '';
			$price_info['multiple']       = $this->is_price_field_type_multi_option( $field_type, $data );
			$price_info['quantity']       = isset( $data['quantity'] ) ? $data['quantity'] : '';
			$price_info['is_flat_fee']    = isset( $data['price_flat_fee'] ) && 'yes' === $data['price_flat_fee'] ? true : false;
			$price_info['custom_formula'] = isset( $data['custom_formula'] ) ? $data['custom_formula'] : '';
		}

		return $price_info;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function is_price_field_type_option( $type ) {
		if ( $type && in_array( $type, array( 'select', 'multiselect', 'radio', 'checkboxgroup', 'colorpalette', 'imagegroup', 'productgroup' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function is_price_field_type_multi_option( $type, $data ) {
		if ( $type && in_array( $type, array( 'multiselect', 'checkboxgroup' ) ) ) {
			return true;
		} elseif ( $data && $type && in_array( $type, array( 'colorpalette', 'imagegroup', 'productgroup' ) ) ) {
			$value = isset( $data['value'] ) ? $data['value'] : '';
			if ( is_array( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function prepare_option_field_price_props( $args ) {
		$price_props     = array();
		$price           = '';
		$price_type      = '';
		$selected_values = '';
		$productgrp_qty  = '';

		$type           = isset( $args['field_type'] ) ? $args['field_type'] : '';
		$name           = isset( $args['name'] ) ? $args['name'] : '';
		$value          = isset( $args['value'] ) ? $args['value'] : false;
		$options        = isset( $args['options'] ) ? $args['options'] : false;
		$productgrp_qts = isset( $args['product_group_quantity'] ) ? $args['product_group_quantity'] : false;
		if ( ! is_array( $options ) || empty( $options ) ) {
			return $price_props;
		}

		$is_multiselect = false;
		if ( ( $type === 'colorpalette' || $type === 'imagegroup' || $type === 'productgroup' ) && is_array( $value ) ) {
			$is_multiselect = true;
		}
		if ( $type === 'select' || $type === 'radio' || ( ! $is_multiselect && ( $type === 'colorpalette' || $type === 'imagegroup' || $type === 'productgroup' ) ) ) {
			$selected_option = isset( $options[ $value ] ) ? $options[ $value ] : false;

			if ( is_array( $selected_option ) ) {
				$price      = isset( $selected_option['price'] ) ? $selected_option['price'] : false;
				$price_type = isset( $selected_option['price_type'] ) ? $selected_option['price_type'] : false;
				$price_type = $price_type ? $price_type : 'normal';

				if ( $price_type === 'product_price' ) {
					$product = wc_get_product( $value );
					$price   = $product->get_price();
				}
				$productgrp_qty = isset( $productgrp_qts[ $selected_option['key'] ] ) ? $productgrp_qts[ $selected_option['key'] ] : '';
			}
		} elseif ( $type === 'multiselect' || $type === 'checkboxgroup' || $is_multiselect ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $ovalue ) {
					$selected_option = isset( $options[ $ovalue ] ) ? $options[ $ovalue ] : false;

					if ( is_array( $selected_option ) ) {
						$oprice           = isset( $selected_option['price'] ) ? $selected_option['price'] : false;
						$oprice_type      = isset( $selected_option['price_type'] ) ? $selected_option['price_type'] : false;
						$ogrp_product_qty = '';

						if ( $oprice_type === 'product_price' ) {
							$product = wc_get_product( $ovalue );
							if ( $product ) {
								$oprice = $product->get_price();
							}
						}

						$ogrp_product_qty = isset( $productgrp_qts[ $selected_option['key'] ] ) ? $productgrp_qts[ $selected_option['key'] ] : '';

						if ( is_numeric( $oprice ) ) {
							$oprice_type = $oprice_type ? $oprice_type : 'normal';

							if ( ! empty( $price ) || $price === '0' ) {
								$price .= ',';
							}

							if ( ! empty( $price_type ) ) {
								$price_type .= ',';
							}

							if ( ! empty( $selected_values ) ) {
								$selected_values .= ',';
							}

							if ( ! empty( $productgrp_qty ) ) {
								$productgrp_qty .= ',';
							}

							if ( $type === 'productgroup' ) {
								$selected_values .= $ovalue;
								$productgrp_qty  .= $ogrp_product_qty;
							}

							$price      .= $oprice;
							$price_type .= $oprice_type;
						}
					}
				}
			}
		}

		if ( ! empty( $price ) && ! empty( $price_type ) ) {
			$price_props['price']      = $price;
			$price_props['price_type'] = $price_type;
		}

		if ( $type === 'productgroup' && ! empty( $selected_values ) ) {
			$price_props['price_options'] = $selected_values;

		}
		if ( $type === 'productgroup' && $productgrp_qty ) {
			$price_props['product_group_quantity'] = $productgrp_qty;
		}
		return $price_props;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function calculate_extra_price( $args, $context = 'product' ) {
		if ( ! $this->is_valid_request( $args ) ) {
			return array( 'resp_code' => 'E002' );
		}

		$product         = false;
		$product_id      = $args['product_id'];
		$price_info_list = isset( $args['price_info'] ) ? $args['price_info'] : false;
		$product_qty     = isset( $args['product_qty'] ) ? $args['product_qty'] : 1;

		if ( isset( $args['product'] ) && $args['product'] instanceof \WC_Product ) {
			$product = $args['product'];
		} else {
			//$product = $product_id ? wc_get_product( $product_id ) : false;
			$product = $this->get_product( $args );
		}

		if ( ! is_array( $price_info_list ) || empty( $price_info_list ) ) {
			return array(
				'resp_code' => 'E001',
				'product'   => $product,
			);
		}

		$excl_base_price = apply_filters( 'thwepo_extra_cost_exclude_base_price', false, $product_id );
		$product_price   = $product->get_price( 'original' );

		$product_info          = array();
		$product_info['id']    = $product_id;
		$product_info['price'] = $product_price;
		$product_info['qty']   = $product_qty;

		$extra_price             = 0;
		$final_price             = 0;
		$flat_fees               = array();
		$make_product_price_zero = false;

		foreach ( $price_info_list as $fname => $price_info ) {
			$price_type  = isset( $price_info['price_type'] ) ? $price_info['price_type'] : '';
			$is_flat_fee = isset( $price_info['is_flat_fee'] ) ? $price_info['is_flat_fee'] : false;

			$excl_base_price = $this->is_exclude_base_price( $excl_base_price, $product_id, $fname, $price_type );
			$fprice          = $this->calculate_extra_price_single( $price_info, $product_info );

			if ( ! $is_flat_fee ) {
				$extra_price += $fprice;
			}
		}

		$flat_fees = is_array( $flat_fees ) && ! empty( $flat_fees ) ? $flat_fees : false;

		// Make product price zero for Field price as Flat fee which is Dynmic-Exclude Base Price.
		if ( $make_product_price_zero ) {
			$product_price = 0;
		}

		$price_data                    = array();
		$price_data['resp_code']       = 'E000';
		$price_data['product']         = $product;
		$price_data['product_price']   = $product_price;
		$price_data['extra_price']     = $extra_price;
		$price_data['excl_base_price'] = $excl_base_price;
		$price_data['flat_fees']       = $flat_fees;
		return $price_data;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function is_valid_request( $request ) {
		if ( ! is_array( $request ) ) {
			return false;
		}

		$product_id = isset( $request['product_id'] ) ? $request['product_id'] : false;
		if ( ! $product_id ) {
			return false;
		}

		$is_variable_product = isset( $request['is_variable_product'] ) ? $request['is_variable_product'] : false;
		$variation_id        = isset( $request['variation_id'] ) ? $request['variation_id'] : false;

		if ( $is_variable_product && ! $variation_id ) {
			return false;
		}

		return true;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function get_product( $args ) {
		$product = false;

		$product_id   = isset( $args['product_id'] ) ? $args['product_id'] : false;
		$variation_id = isset( $args['variation_id'] ) ? $args['variation_id'] : false;

		if ( $variation_id ) {
			$product = new \WC_Product_Variation( $variation_id );
		} elseif ( $product_id ) {
			$pf      = new \WC_Product_Factory();
			$product = $pf->get_product( $product_id );
		}

		return $product;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function is_exclude_base_price( $exclude, $product_id, $field_name, $price_type ) {
		$exclude = $price_type === 'dynamic-excl-base-price' ? true : $exclude;
		$exclude = apply_filters( 'thwepo_extra_cost_exclude_base_price_single', $exclude, $product_id, $field_name );
		return $exclude;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function calculate_extra_price_single( $price_info, $product_info ) {
		$fprice = 0;
		if ( is_array( $price_info ) ) {
			$multiple    = isset( $price_info['multiple'] ) ? $price_info['multiple'] : 0;
			$price_type  = isset( $price_info['price_type'] ) ? $price_info['price_type'] : '';
			$price       = isset( $price_info['price'] ) ? $price_info['price'] : 0;
			$prodgrp_qty = isset( $price_info['product_group_quantity'] ) ? $price_info['product_group_quantity'] : '';

			if ( $multiple == 1 ) {
				$price_arr        = explode( ',', $price );
				$price_type_arr   = explode( ',', $price_type );
				$prodgrp_qty_arry = explode( ',', $prodgrp_qty );

				foreach ( $price_arr as $index => $oprice ) {
					$oprice_type  = isset( $price_type_arr[ $index ] ) ? $price_type_arr[ $index ] : 'normal';
					$oprodgrp_qty = isset( $prodgrp_qty_arry[ $index ] ) ? $prodgrp_qty_arry[ $index ] : 1;
					$fprice      += $this->calculate_extra_cost( $price_info, $product_info, $oprice_type, $oprice, $index, $oprodgrp_qty );
				}
			} else {

				$fprice = $this->calculate_extra_cost( $price_info, $product_info, $price_type, $price, false, $prodgrp_qty );
			}
		}

		return apply_filters( 'thwepo_product_field_extra_price_single', $fprice, $price_info, $product_info );
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function calculate_extra_cost( $price_info, $product_info, $price_type, $price, $index = false, $prod_grp_qty = false ) {

		$fprice        = 0;
		$name          = isset( $price_info['name'] ) ? $price_info['name'] : '';
		$value         = isset( $price_info['value'] ) ? $price_info['value'] : false;
		$product_price = is_array( $product_info ) && isset( $product_info['price'] ) ? $product_info['price'] : false;

		$price = apply_filters( 'thwepo_product_field_price', $price, $price_type, $name, $price_info, $index );

		if ( $price_type === 'percentage' ) {
			if ( is_numeric( $price ) && is_numeric( $product_price ) ) {
				$fprice = ( $price / 100 ) * $product_price;
			}
		} elseif ( $price_type === 'option_percentage' ) {

			$price_options     = isset( $price_info['price_options'] ) ? $price_info['price_options'] : false;
			$price_options_arr = $price_options ? explode( ',', $price_options ) : false;

			if ( is_array( $price_options_arr ) ) {
				$product_id = isset( $price_options_arr[ $index ] ) ? $price_options_arr[ $index ] : '';
			} else {
				$product_id = $value;
			}

			if ( $product_id ) {
				$product = \wc_get_product( $product_id );

				if ( $product ) {
					$option_product_price = $product->get_price();

					if ( is_numeric( $option_product_price ) && is_numeric( $option_product_price ) ) {
						$fprice = ( $price / 100 ) * $option_product_price;
					}
				}
			}
		} elseif ( $price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count' ) {
			$price_unit = isset( $price_info['price_unit'] ) ? $price_info['price_unit'] : false;
			$quantity   = isset( $price_info['quantity'] ) ? $price_info['quantity'] : false;

			if ( $price_type === 'char-count' && ! empty( $value ) ) {
				$quantity = strlen( $value );
			}

			$quantity = apply_filters( 'thwepo_extra_cost_quantity_' . $name, $quantity, $value ); //Deprecated
			$quantity = apply_filters( 'thwepo_extra_cost_quantity', $quantity, $name, $value );
			$value    = $quantity && is_numeric( $quantity ) ? $quantity : $value;

			if ( is_numeric( $price ) && is_numeric( $value ) && is_numeric( $price_unit ) && $price_unit > 0 ) {
				$price_min_unit = isset( $price_info['price_min_unit'] ) && is_numeric( $price_info['price_min_unit'] ) ? $price_info['price_min_unit'] : 0;
				$value          = $value && ( $value > $price_min_unit ) ? $value - $price_min_unit : 0;

				// $price = apply_filters('thwepo_extra_cost_unit_price_'.$name, $price, $product_price, $price_type);
				// $price = apply_filters('thwepo_extra_cost_unit_price', $price, $name, $product_price, $price_type);
				$price              = apply_filters( 'thwepo_extra_cost_dynamic_unit_price', $price, $name, $product_price, $price_type );
				$is_unit_type_range = apply_filters( 'thwepo_extra_cost_unit_price_type_range_' . $name, false );

				$total_units = $value / $price_unit;
				$total_units = $is_unit_type_range ? ceil( $total_units ) : $total_units;

				$fprice = $price * $total_units;
				//$fprice = $price*($value/$price_unit);

				if ( $price_type === 'dynamic-excl-base-price' && is_numeric( $product_price ) && $value >= $price_unit ) {
					//$fprice = $fprice - $product_price;
				}
			}
		} elseif ( $price_type === 'custom' ) {
			if ( $value && is_numeric( $value ) ) {
				$fprice = $value;
			}
		} elseif ( $price_type === 'custom-formula' ) {
			$custom_formula = $price;
			$product_qty    = is_array( $product_info ) && isset( $product_info['qty'] ) ? $product_info['qty'] : 1;
			$calculate      = false;

			if ( strpos( $custom_formula, '{value}' ) !== false ) {
				$custom_formula = str_replace( '{value}', $value, $custom_formula );
				$calculate      = true;
			}

			if ( strpos( $custom_formula, '{quantity}' ) !== false ) {
				$custom_formula = str_replace( '{quantity}', $product_qty, $custom_formula );
				$calculate      = true;
			}

			if ( strpos( $custom_formula, '{product_price}' ) !== false ) {
				$custom_formula = str_replace( '{product_price}', $product_price, $custom_formula );
				$calculate      = true;
			}

			if ( strpos( $custom_formula, '{length}' ) !== false ) {
				$char_count     = strlen( $value );
				$custom_formula = str_replace( '{length}', $char_count, $custom_formula );
				$calculate      = true;
			}

			if ( strpos( $custom_formula, '{thwepo_' ) !== false ) {
				$custom_fields = isset( $price_info['custom_formula'] ) ? $price_info['custom_formula'] : array();

				if ( ! empty( $custom_fields ) ) {
					$price_field = ! empty( $custom_fields['price_field'] ) ? $custom_fields['price_field'] : array();

					foreach ( $price_field as $field_name => $field_price ) {
						$placeholder = '{thwepo_' . $field_name . '_price}';
						if ( strpos( $custom_formula, $placeholder ) !== false ) {
							$custom_formula = str_replace( $placeholder, $field_price, $custom_formula );
							$calculate      = true;
						}
					}

					$value_field = ! empty( $custom_fields['value_field'] ) ? $custom_fields['value_field'] : array();

					foreach ( $value_field as $field_name => $field_value ) {
						$placeholder = '{thwepo_' . $field_name . '_value}';
						if ( strpos( $custom_formula, $placeholder ) !== false ) {
							$option_val = 0;
							if ( is_array( $field_value ) ) {
								foreach ( $field_value as $key => $option ) {
									$option_val = is_numeric( $option ) ? $option_val + $option : $option_val;
								}
								$field_value = $option_val;
							}

							$custom_formula = str_replace( $placeholder, $field_value, $custom_formula );
							$calculate      = true;
						}
					}
				}
			}

			$custom_formula = apply_filters( 'thwepo_formated_custom_formula', $custom_formula, $product_info );

			$regex     = '/^[0-9.\+\*\-\/\(\)\s]*$/';
			$valid_eqn = preg_match( $regex, $custom_formula );

			if ( $calculate && $valid_eqn ) {
				if ( phpversion() >= 7 ) {
					try {
						$price = eval( "return $custom_formula;" );
					} catch ( \ParseError $e ) {
						$price = 0;
					}
				} else {
					if ( $this->is_valid_eval_statement( $custom_formula ) ) {
						$price = eval( "return $custom_formula;" );
					} else {
						$price = 0;
					}
				}
			}

			$fprice = $price;

		} else {
			if ( is_numeric( $price ) ) {
				$fprice = $price;
			}
		}
		if ( $prod_grp_qty ) {
			$fprice *= $prod_grp_qty;
		}

		if ( $name ) {
			$fprice = apply_filters( 'thwepo_product_field_extra_cost_' . $name, $fprice, $product_price, $price_info, $price_type ); //Deprecated
			$fprice = apply_filters( 'thwepo_product_field_extra_cost', $fprice, $name, $price_info, $product_info, $price_type );
		}

		return is_numeric( $fprice ) ? $fprice : 0;
	}

	/**
	 * @override thwepo function
	 *
	 * Because their function is private
	 */
	public function is_valid_eval_statement( $code ) {
		return @eval( $code . '; return true;' );
	}
}
