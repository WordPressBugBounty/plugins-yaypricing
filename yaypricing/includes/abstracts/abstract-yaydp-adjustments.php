<?php
/**
 * Managing adjustments
 *
 * @package YayPricing\Abstracts
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Adjustments {

	/**
	 * Contains adjustments
	 *
	 * @var array
	 */
	protected $adjustments = array();

	/**
	 * Collect adjustment
	 */
	abstract public function collect();

	/**
	 * Apply adjustments
	 */
	abstract public function apply();

	/**
	 * Initialize
	 */
	public function do_stuff() {
		$this->collect();
		$this->apply();
	}

	/**
	 * Retrives adjustments
	 */
	public function get_adjustments() {
		return $this->adjustments;
	}

	/**
	 * Add adjustment
	 */
	public function add_adjustment( $adjustment ) {
		$this->adjustments[] = $adjustment;
	}

	/**
	 * Convert list adjustments to contain total discount amount
	 */
	protected function get_list_adjustments_with_total_discount_amount() {
		$list = array();
		foreach ( $this->adjustments as $adjustment ) {
			$total_discount_amount_per_order = $adjustment->get_total_discount_amount_per_order();
			$list[]                          = array(
				'total_discount_amount_per_order' => $total_discount_amount_per_order,
				'adjustment'                      => $adjustment,
			);
		}
		return $list;
	}

	/**
	 * Assign list with total to normal list
	 *
	 * @param array $list List with total.
	 */
	protected function reassign_list_to_adjustments( $list ) {
		$this->adjustments = array_map(
			function( $item ) {
				return $item['adjustment'];
			},
			$list
		);
	}

	/**
	 * Sort list by asc total discount amount
	 */
	public function sort_by_asc_amount() {
		$list_with_total_discount_amount = $this->get_list_adjustments_with_total_discount_amount();
		usort(
			$list_with_total_discount_amount,
			function( $a, $b ) {
				return $a['total_discount_amount_per_order'] < $b['total_discount_amount_per_order'] ? -1 : 1;
			}
		);
		$this->reassign_list_to_adjustments( $list_with_total_discount_amount );
	}

	/**
	 * Sort list by desc total discount amount
	 */
	public function sort_by_desc_amount() {
		$list_with_total_discount_amount = $this->get_list_adjustments_with_total_discount_amount();
		usort(
			$list_with_total_discount_amount,
			function( $a, $b ) {
				return $a['total_discount_amount_per_order'] < $b['total_discount_amount_per_order'] ? 1 : -1;
			}
		);
		$this->reassign_list_to_adjustments( $list_with_total_discount_amount );
	}

}
