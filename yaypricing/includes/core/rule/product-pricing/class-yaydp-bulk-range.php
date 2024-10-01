<?php
/**
 * Handling a single bulk pricing range
 *
 * @package YayPricing\Rule\ProductPricing
 *
 * @since 2.4
 */

namespace YAYDP\Core\Rule\Product_Pricing;

/**
 * Declare class
 */
class YAYDP_Bulk_Range {

	/**
	 * From quantity
	 *
	 * @var float
	 */
	protected $min_quantity = 0;

	/**
	 * To quantity
	 *
	 * @var float
	 */
	protected $max_quantity = 0;

	/**
	 * Pricing type
	 *
	 * @var string
	 */
	protected $pricing_type = 'fixed_discount';

	/**
	 * Pricing value
	 *
	 * @var float
	 */
	protected $pricing_value = 0;

	/**
	 * Maximum value
	 *
	 * @var float
	 */
	protected $maximum_adjustment_amount = PHP_INT_MAX;

	/**
	 * Constructor
	 *
	 * @param array $data Passed in data.
	 */
	public function __construct( $data ) {
		$this->min_quantity              = $data['from_quantity'];
		$this->max_quantity              = $data['to_quantity'];
		$this->pricing_type              = $data['pricing']['type'];
		$this->pricing_value             = $data['pricing']['value'];
		$this->maximum_adjustment_amount = $data['pricing']['maximum_value'];
	}

	/**
	 * Check this range match with given quantity
	 *
	 * @param float $quantity Quantity.
	 */
	public function is_matching_current_quantity( $quantity ) {
		$max_quantity = empty( $this->max_quantity ) ? PHP_INT_MAX : $this->max_quantity;
		return $this->min_quantity <= $quantity && $quantity <= $max_quantity;
	}

	/**
	 * Retrieves pricing type
	 */
	public function get_pricing_type() {
		return $this->pricing_type;
	}

	/**
	 * Retrieves pricing value
	 */
	public function get_pricing_value() {
		return $this->pricing_value;
	}

	/**
	 * Retrieves maximum value
	 */
	public function get_maximum_adjustment_amount() {
		return is_null( $this->maximum_adjustment_amount ) ? PHP_INT_MAX : $this->maximum_adjustment_amount;
	}

	/**
	 * Retrieves min quantity
	 */
	public function get_min_quantity() {
		return $this->min_quantity;
	}

	/**
	 * Retrieves max quantity
	 */
	public function get_max_quantity() {
		return $this->max_quantity;
	}
}
