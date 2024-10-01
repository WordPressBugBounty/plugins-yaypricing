<?php
/**
 * Handle cart item modifier
 *
 * @package YayPricing\Classes
 *
 * @since 2.4
 */

namespace YAYDP\Core\Single_Modifier;

/**
 * Declare class
 */
class YAYDP_Product_Pricing_Modifier extends \YAYDP\Abstracts\YAYDP_Modifier {

	/**
	 * Contains modifying quantity
	 *
	 * @var null|float
	 */
	protected $modify_quantity = null;

	/**
	 * Constains discount amount per unit
	 *
	 * @var null|float
	 */
	protected $discount_per_unit = null;

	/**
	 * Contain target item
	 *
	 * @var null|\YAYDP\Core\YAYDP_Cart_Item
	 */
	protected $item = null;

	/**
	 * Constructor
	 *
	 * @override
	 *
	 * @param array $data Pass in data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
		$this->modify_quantity   = isset( $data['modify_quantity'] ) ? $data['modify_quantity'] : 0;
		$this->discount_per_unit = isset( $data['discount_per_unit'] ) ? $data['discount_per_unit'] : 0;
		$this->item              = isset( $data['item'] ) ? $data['item'] : null;
	}

	/**
	 * Check whether modify extra item
	 */
	public function is_modify_extra_item() {
		$is_bogo_or_buy_x_get_y = \yaydp_is_buy_x_get_y( $this->rule ) || \yaydp_is_bogo( $this->rule );
		if ( $is_bogo_or_buy_x_get_y ) {
			return $this->rule->is_get_free_item();
		}
		return false;
	}

	/**
	 * Returns discount amount per unit
	 */
	public function get_discount_per_unit() {
		return empty( $this->discount_per_unit ) ? 0 : $this->discount_per_unit;
	}

	/**
	 * Returns modified item
	 */
	public function get_item() {
		return empty( $this->item ) ? null : $this->item;
	}

	/**
	 * Returns modify quantity
	 */
	public function get_modify_quantity() {
		return empty( $this->modify_quantity ) ? 0 : $this->modify_quantity;
	}
}
