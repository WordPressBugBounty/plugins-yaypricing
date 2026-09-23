<?php
/**
 * Abstract class for manage tooltips
 *
 * @package YayPricing\Tooltip
 *
 * @since 2.4
 */

namespace YAYDP\Abstracts;

/**
 * Declare class
 */
abstract class YAYDP_Tooltip {

	/**
	 * Tooltip settings of the rule: `enable` and `content`.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Rule the tooltip belongs to.
	 *
	 * @var \YAYDP\Abstracts\YAYDP_Rule|null
	 */
	protected $rule = null;

	/**
	 * For product pricing tooltips: the modifier the rule applied to the cart item.
	 * Null for cart discount / checkout fee tooltips, which describe the rule as a whole.
	 *
	 * @var \YAYDP\Core\Single_Modifier\YAYDP_Product_Pricing_Modifier|null
	 */
	protected $modifier = null;

	/**
	 * Constructor
	 *
	 * @param array                              $data     Tooltip settings.
	 * @param \YAYDP\Abstracts\YAYDP_Rule|null   $rule     Owning rule.
	 * @param \YAYDP\Core\Single_Modifier\YAYDP_Product_Pricing_Modifier|null $modifier Applied modifier, product pricing only.
	 */
	public function __construct( $data, $rule = null, $modifier = null ) {
		$this->data     = ! empty( $data ) ? $data : array();
		$this->rule     = $rule;
		$this->modifier = $modifier;
	}

	/**
	 * Retrieve tooltip data
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Retrieve the owning rule
	 */
	public function get_rule() {
		return $this->rule;
	}

	/**
	 * Retrieve the applied modifier (product pricing tooltips only, null otherwise)
	 */
	public function get_modifier() {
		return $this->modifier;
	}

	/**
	 * Check whether this tooltip is enabled
	 */
	public function is_enabled() {
		return ! empty( $this->data['enable'] );
	}

	/**
	 * Retrieve tooltip raw content
	 * This content has not replaced variables yet
	 */
	public function get_raw_content() {
		$content = empty( $this->data['content'] ) ? '' : $this->data['content'];
		return $this->translate_content( $content );
	}

	/**
	 * Translates the admin-entered tooltip content using WPML, Polylang or the plugin
	 * text domain (Loco Translate). Runs before variables such as [discount_value]
	 * are replaced, so the placeholders stay intact in the translated string.
	 *
	 * @param string $content Raw tooltip content.
	 */
	protected function translate_content( $content ) {
		$rule_id = empty( $this->rule ) ? '' : $this->rule->get_id();
		$name    = empty( $rule_id ) ? '' : "rule_{$rule_id}_tooltip_content";
		return \YAYDP\Helper\YAYDP_Helper::translate_user_string( $content, 'yaypricing', $name );
	}

	/**
	 * Abstract function for getting tooltip content ( replaced variables )
	 * It must be implemented by child class.
	 */
	abstract public function get_content();
}
