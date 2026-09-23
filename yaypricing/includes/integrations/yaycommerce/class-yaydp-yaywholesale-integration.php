<?php
/**
 * Integration with yay-wholesale-b2b-pro.
 *
 * Two responsibilities:
 *
 * 1. Pricing Table — swaps the base price for the wholesale-adjusted price when a wholesale role
 *    is active for the current user.
 * 2. Price HTML — lets yay-wholesale-b2b-pro keep ownership of its "Retail: / Wholesale price:"
 *    layout while YayPricing supplies the discount math for both lines.
 *
 * YayPricing owns discount math, yay-wholesale-b2b-pro owns layout. Neither reimplements the other.
 *
 * @package YayPricing\Integrations
 */

namespace YAYDP\Integrations\YayCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_YayWholesale_Integration {

	use \YAYDP\Traits\YAYDP_Singleton;

	/**
	 * Guards against re-entering the wholesale pass while it is already running.
	 *
	 * @var bool
	 */
	private $in_wholesale_pass = false;

	/**
	 * Results of the wholesale pass, keyed by product id.
	 *
	 * Each entry holds the `original` and `discounted` price in store base currency plus the
	 * `role` they were computed for. Populated during the wholesale pass and read back when
	 * rendering the strike-through half, which has to convert both values the same way.
	 *
	 * @var array
	 */
	private $wholesale_pass = array();

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'yaydp_pricing_table_base_price', array( $this, 'apply_wholesale_base_price' ), 10, 2 );
		add_filter( 'yaydp_price_html_priority', array( $this, 'price_html_priority' ) );
		add_filter( 'ywhs_display_wholesale_price_additional_processed', array( $this, 'apply_yaypricing_to_wholesale' ), 10, 3 );
		add_filter( 'ywhs_wholesale_price_html', array( $this, 'wholesale_price_html' ), 10, 3 );
		add_filter( 'ywhs_use_labeled_price_layout', array( $this, 'use_labeled_price_layout' ), 10, 3 );
	}

	/**
	 * Ask yay-wholesale-b2b-pro for the labeled layout once YayPricing has rewritten the price html.
	 *
	 * Its variable and grouped paths otherwise wrap the retail html in `wc_format_sale_price()`,
	 * which nests it inside a `<del>`. That is correct for a plain price but not for the
	 * `<del>/<ins>` pair YayPricing produces — the discounted price would render struck through too.
	 * The labeled layout is what the simple-product path already uses.
	 *
	 * @param bool             $use_labeled Whether to use the labeled layout.
	 * @param \WC_Product|null $product     Product being rendered.
	 * @param string           $price_html  Incoming retail price html.
	 *
	 * @return bool
	 */
	public function use_labeled_price_layout( $use_labeled, $product = null, $price_html = '' ) {
		// The marker span is emitted by YAYDP_Discounted_Price exactly when it replaced the html.
		if ( false !== strpos( (string) $price_html, 'yaydp-product-discounted-data' ) ) {
			return true;
		}
		// Grouped parents keep their original html — YayPricing prices their children, not the
		// parent — so the retail side carries no marker even though the wholesale side is about to
		// become a <del>/<ins> pair. Without the labeled layout that pair nests inside the sale
		// wrapper's own <ins>.
		if ( $product instanceof \WC_Product && ! empty( $this->get_wholesale_pass_entries( $product ) ) ) {
			return true;
		}
		return $use_labeled;
	}

	/**
	 * Replace retail base with wholesale-adjusted base.
	 *
	 * Uses yay-wholesale-b2b-pro's raw `ProductPricingHelper::get_wholesale_price`
	 * (not the display helper) so the value stays in store base currency. The
	 * display helper would trigger yay-wholesale-b2b-pro's own YayCurrency
	 * compatibility and convert to the active currency, which YayPricing then
	 * converts again — causing double conversion after a currency switch. The
	 * `yaydp_pricing_table_base_price` filter must return store base currency;
	 * YayPricing's currency conversion runs once afterwards.
	 *
	 * @param float       $price   Retail base price in store base currency.
	 * @param \WC_Product $product Product being rendered in Pricing Table.
	 *
	 * @return float
	 */
	public function apply_wholesale_base_price( $price, $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return $price;
		}
		if ( ! class_exists( '\YayWholesaleB2B\Helpers\PricingHelpers\ProductPricingHelper' ) ) {
			return $price;
		}
		$role = \YayWholesaleB2B\Helpers\CustomerHelper::get_current_user_wholesale_role();
		if ( empty( $role ) ) {
			return $price;
		}
		return (float) \YayWholesaleB2B\Helpers\PricingHelpers\ProductPricingHelper::get_wholesale_price( $product, $role, 1 );
	}

	/**
	 * Render the discounted price before yay-wholesale-b2b-pro wraps it.
	 *
	 * YayPricing's price html callback discards the html it receives. At its default priority it
	 * therefore destroys yay-wholesale-b2b-pro's two-line layout, which is built at priority 100.
	 * Running first instead lets that layout wrap YayPricing's output as its "Retail" line.
	 *
	 * Gated on yay-wholesale-b2b-pro being active rather than on a wholesale role resolving: the
	 * current user is not reliably known this early, and the role makes no difference here — with
	 * no role, or in `retail-only` mode, yay-wholesale-b2b-pro returns the html untouched.
	 *
	 * @param int $priority Current filter priority.
	 *
	 * @return int
	 */
	public function price_html_priority( $priority ) {
		if ( ! class_exists( '\YayWholesaleB2B\Engine\Frontend\Pricing' ) ) {
			return $priority;
		}
		return 90;
	}

	/**
	 * Apply YayPricing rules to the wholesale price.
	 *
	 * The rule is recomputed against the wholesale price as base rather than reusing the retail
	 * discount rate — for a fixed-amount rule the two differ (retail 100 -> 90 is a rate of 0.9,
	 * but wholesale 90 -> 80 is not 90 * 0.9). Recomputing also matches what the cart charges:
	 * yay-wholesale-b2b-pro sets the wholesale price at `woocommerce_before_calculate_totals`
	 * priority 103 and YayPricing discounts from there at 110.
	 *
	 * Fires once per product for simple products and once per child for variable and grouped ones,
	 * since yay-wholesale-b2b-pro routes every price display through this helper.
	 *
	 * @param float       $wholesale_price Wholesale price in store base currency.
	 * @param \WC_Product $product         Product being priced.
	 * @param array       $role            Wholesale role config.
	 *
	 * @return float
	 */
	public function apply_yaypricing_to_wholesale( $wholesale_price, $product, $role ) {
		if ( $this->in_wholesale_pass ) {
			return $wholesale_price;
		}
		if ( ! $product instanceof \WC_Product ) {
			return $wholesale_price;
		}
		// yay-wholesale-b2b-pro also resolves a price for the variable/grouped parent itself but
		// only renders the range built from the children, so running the engine here would be
		// discarded work on every such product.
		if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
			return $wholesale_price;
		}
		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_price() ) {
			return $wholesale_price;
		}
		$wholesale_price = floatval( $wholesale_price );
		if ( $wholesale_price <= 0 ) {
			return $wholesale_price;
		}

		// Feed the engine the wholesale price as base, and keep the wholesale result out of the
		// cache slot already holding the retail result for this product — YAYDP_Product_Sale
		// memoizes on (pricing context, product id), which is otherwise identical across the two
		// passes, so the second pass would silently return the first pass's number.
		$base_callback = function () use ( $wholesale_price ) {
			return $wholesale_price;
		};
		$context_callback = function ( $key ) {
			return $key . '|ywhs';
		};

		$this->in_wholesale_pass = true;
		add_filter( 'yaydp_initial_product_price', $base_callback, 1000 );
		add_filter( 'yaydp_pricing_context_key', $context_callback, 1000 );

		try {
			$product_sale = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $product );
			$min_max      = $product_sale->get_min_max_discounted_price();
			$discounted   = is_null( $min_max ) ? $wholesale_price : floatval( $min_max['min'] );
		} finally {
			// A leaked base price filter turns every later retail price on the page into a
			// wholesale price, so tear down even if the engine throws.
			remove_filter( 'yaydp_initial_product_price', $base_callback, 1000 );
			remove_filter( 'yaydp_pricing_context_key', $context_callback, 1000 );
			$this->in_wholesale_pass = false;
		}

		if ( $discounted >= $wholesale_price ) {
			return $wholesale_price;
		}

		// Both values are still in store base currency here — yay-wholesale-b2b-pro's own
		// YayCurrency compatibility is hooked onto this same filter and converts whatever is
		// returned. The strike-through half has to be converted the same way, so keep both.
		$this->wholesale_pass[ $product->get_id() ] = array(
			'original'   => $wholesale_price,
			'discounted' => $discounted,
			'role'       => $role,
		);

		return $discounted;
	}

	/**
	 * Add the original wholesale price as a strike-through alongside the discounted one.
	 *
	 * Wraps yay-wholesale-b2b-pro's own html as the `<ins>` half rather than reformatting it, so the
	 * tax display and price suffix it already applied are preserved.
	 *
	 * @param string           $html    Wholesale price html built by yay-wholesale-b2b-pro.
	 * @param \WC_Product|null $product Product being rendered.
	 * @param array|null       $role    Wholesale role config.
	 *
	 * @return string
	 */
	public function wholesale_price_html( $html, $product = null, $role = null ) {
		if ( ! $product instanceof \WC_Product ) {
			return $html;
		}
		if ( ! \YAYDP\Settings\YAYDP_Product_Pricing_Settings::get_instance()->show_discounted_with_regular_price() ) {
			return $html;
		}

		$entries = $this->get_wholesale_pass_entries( $product );
		if ( empty( $entries ) ) {
			return $html;
		}

		$originals   = array();
		$discounteds = array();
		foreach ( $entries as $entry ) {
			$originals[]   = $this->convert_currency( $entry['original'], $entry['product'], $entry['role'] );
			$discounteds[] = $this->convert_currency( $entry['discounted'], $entry['product'], $entry['role'] );
		}

		// The active currency can collapse the two halves — YayCurrency fixed prices, for one,
		// replace the converted value outright rather than scaling it. Nothing to strike through.
		if ( min( $originals ) <= min( $discounteds ) && max( $originals ) <= max( $discounteds ) ) {
			return $html;
		}

		$min = min( $originals );
		$max = max( $originals );

		$original_html = ( $min !== $max )
			? \wc_format_price_range( \wc_price( $this->to_display_price( $product, $min ) ), \wc_price( $this->to_display_price( $product, $max ) ) )
			: \wc_price( $this->to_display_price( $product, $min ) );

		return '<del aria-hidden="true">' . $original_html . '</del> <ins>' . $html . '</ins>';
	}

	/**
	 * Convert a store base currency price the same way yay-wholesale-b2b-pro converts the
	 * discounted half.
	 *
	 * Delegates to yay-wholesale-b2b-pro's own YayCurrency compatibility rather than YayPricing's
	 * generic conversion: that class also resolves YayCurrency per-product fixed prices, which are
	 * not a rate applied to the base price. Reusing it is what keeps both halves of the
	 * strike-through consistent.
	 *
	 * @param float       $price   Price in store base currency.
	 * @param \WC_Product $product Product the price belongs to.
	 * @param array|null  $role    Wholesale role config.
	 *
	 * @return float
	 */
	private function convert_currency( $price, $product, $role ) {
		if ( ! class_exists( '\YayWholesaleB2B\Engine\Compatibles\YayCurrency' ) ) {
			return (float) $price;
		}
		return (float) \YayWholesaleB2B\Engine\Compatibles\YayCurrency::get_instance()->convert_currency_price( $price, $product, $role );
	}

	/**
	 * Collect the pre-discount wholesale prices recorded for a product.
	 *
	 * Variable and grouped products render one line for the whole parent, but the wholesale pass
	 * runs per child, so the parent's range is rebuilt from its children.
	 *
	 * @param \WC_Product $product Product being rendered.
	 *
	 * @return array List of prices in store base currency.
	 */
	private function get_wholesale_pass_entries( $product ) {
		$ids = ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) )
			? $product->get_children()
			: array( $product->get_id() );

		$entries = array();
		foreach ( $ids as $id ) {
			if ( ! isset( $this->wholesale_pass[ $id ] ) ) {
				continue;
			}
			$entry = $this->wholesale_pass[ $id ];
			// Currency conversion resolves fixed prices per product, so each child carries its own.
			$entry['product'] = ( $id === $product->get_id() ) ? $product : \wc_get_product( $id );
			if ( ! $entry['product'] instanceof \WC_Product ) {
				continue;
			}
			$entries[] = $entry;
		}
		return $entries;
	}

	/**
	 * Convert a store base currency price for display.
	 *
	 * Mirrors the tax handling yay-wholesale-b2b-pro applies to the discounted half so both halves
	 * of the strike-through are treated identically.
	 *
	 * @param \WC_Product $product Product being rendered.
	 * @param float       $price   Price in store base currency.
	 *
	 * @return float
	 */
	private function to_display_price( $product, $price ) {
		if ( 'incl' === get_option( 'woocommerce_tax_display_shop', 'excl' ) ) {
			return \wc_get_price_including_tax( $product, array( 'price' => $price ) );
		}
		return \wc_get_price_excluding_tax( $product, array( 'price' => $price ) );
	}
}
