<?php
namespace YAYDP\Integrations\Eventtickets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * YayPricing Event Tickets Integration
 * 
 * Handles date synchronization between main products and extra BOGO products
 * for the Event Tickets with Ticket Scanner plugin
 */
class YAYDP_Event_Tickets_Integration {
	use \YAYDP\Traits\YAYDP_Singleton;

	protected function __construct() {
		if ( ! class_exists( 'SASO_EVENTTICKETS' ) ) {
			return;
		}

		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'yaydp_after_initial_cart_item', array( $this, 'sync_extra_product_date_from_main' ), 10, 2 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'handle_cart_item_removal' ), 10, 2 );
		add_action( 'woocommerce_cart_updated', array( $this, 'sync_extra_product_dates_on_cart_update' ), 20 );
		
		add_filter( 'yaydp_extra_cart_item_data', array( $this, 'add_main_product_reference_to_extra_item' ), 10, 3 );
		
		add_action( 'woocommerce_add_to_cart', array( $this, 'sync_extra_product_date_on_add_to_cart' ), 20, 6 );
		
		add_action( 'woocommerce_after_cart_item_name', array( $this, 'modify_extra_product_datepicker' ), 5, 2 );
		add_filter( 'woocommerce_cart_item_class', array( $this, 'add_extra_product_css_class' ), 10, 3 );
		add_action( 'wp_footer', array( $this, 'add_extra_product_datepicker_script' ) );
		
		add_action( 'wp_footer', array( $this, 'add_event_tickets_datepicker_modification' ) );

		add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'add_extra_product_css_class_to_checkout' ) );
	}

	/**
	 * Sync extra product date from main product when BOGO item is added
	 * 
	 * @param array $cart_item Cart item data
	 * @param object $yaydp_cart_item_instance YayPricing cart item instance
	 */
	public function sync_extra_product_date_from_main( $cart_item, $yaydp_cart_item_instance ) {
		if ( empty( $cart_item['is_extra'] ) ) {
			return;
		}

		$product_id = $this->get_product_id_from_cart_item( $cart_item, $yaydp_cart_item_instance );
		if ( ! $product_id ) {
			return;
		}

		if ( ! $this->is_ticket_product( $product_id ) ) {
			return;
		}

		$main_product_date = $this->get_main_product_date( $cart_item );
		
		if ( $main_product_date ) {
			$this->set_extra_product_date( $cart_item, $main_product_date );
		}
	}

	/**
	 * Handle cart item removal to clean up related extra products
	 * 
	 * @param string $cart_item_key Removed cart item key
	 * @param object $cart WooCommerce cart object
	 */
	public function handle_cart_item_removal( $cart_item_key, $cart ) {
		$locked_dates = WC()->session->get( 'yaydp_locked_dates', array() );
		if ( isset( $locked_dates[ $cart_item_key ] ) ) {
			unset( $locked_dates[ $cart_item_key ] );
			WC()->session->set( 'yaydp_locked_dates', $locked_dates );
		}
	}

	/**
	 * Sync extra product dates when cart is updated
	 */
	public function sync_extra_product_dates_on_cart_update() {
		if ( ! WC()->cart ) {
			return;
		}

		$cart_contents = WC()->cart->get_cart();
		$main_products_dates = array();

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item['is_extra'] ) && isset( $cart_item['product_id'] ) && $this->is_ticket_product( $cart_item['product_id'] ) ) {
				$date = $this->get_cart_item_date( $cart_item );
				if ( $date ) {
					$main_products_dates[ $cart_item['product_id'] ] = $date;
				}
			}
		}

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['is_extra'] ) && isset( $cart_item['product_id'] ) && $this->is_ticket_product( $cart_item['product_id'] ) ) {
				$main_product_id = $this->get_main_product_id_for_extra( $cart_item );
				
				if ( $main_product_id && isset( $main_products_dates[ $main_product_id ] ) ) {
					$this->set_cart_item_date( $cart_item_key, $main_products_dates[ $main_product_id ] );
					
					$this->lock_extra_product_date( $cart_item_key );
				}
			}
		}
	}

	/**
	 * Check if a product is a ticket product
	 * 
	 * @param int $product_id Product ID
	 * @return bool True if it's a ticket product
	 */
	private function is_ticket_product( $product_id ) {
		return get_post_meta( $product_id, 'saso_eventtickets_is_ticket', true ) === 'yes';
	}

	/**
	 * Get the main product date that triggered the BOGO for this extra product
	 * 
	 * @param array $extra_cart_item Extra product cart item
	 * @return string|null Date string or null
	 */
	private function get_main_product_date( $extra_cart_item ) {
		$main_product_id = $this->get_main_product_id_for_extra( $extra_cart_item );
		
		if ( ! $main_product_id ) {
			return null;
		}

		return $this->get_main_product_date_from_cart( $main_product_id );
	}

	/**
	 * Get the main product ID that triggered the BOGO for this extra product
	 * 
	 * @param array $extra_cart_item Extra product cart item
	 * @return int|null Main product ID or null
	 */
	private function get_main_product_id_for_extra( $extra_cart_item ) {
		if ( isset( $extra_cart_item['yaydp_main_product_id'] ) ) {
			return $extra_cart_item['yaydp_main_product_id'];
		}

		$product_id = isset( $extra_cart_item['product_id'] ) ? $extra_cart_item['product_id'] : null;
		
		if ( ! $product_id ) {
			return null;
		}
		
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['product_id'] ) && $cart_item['product_id'] == $product_id && empty( $cart_item['is_extra'] ) ) {
				return $product_id;
			}
		}

		return null;
	}

	/**
	 * Get the date from a cart item
	 * 
	 * @param array $cart_item Cart item data
	 * @return string|null Date string or null
	 */
	private function get_cart_item_date( $cart_item ) {
		if ( isset( $cart_item['_saso_eventtickets_request_daychooser'] ) && is_array( $cart_item['_saso_eventtickets_request_daychooser'] ) ) {
			$dates = $cart_item['_saso_eventtickets_request_daychooser'];
			if ( ! empty( $dates ) && isset( $dates[0] ) ) {
				return $dates[0];
			}
		}
		return null;
	}

	/**
	 * Set the date for an extra product
	 * 
	 * @param array $cart_item Cart item data
	 * @param string $date Date string
	 */
	private function set_extra_product_date( $cart_item, $date ) {
	}

	/**
	 * Set the date for a cart item
	 * 
	 * @param string $cart_item_key Cart item key
	 * @param string $date Date string
	 */
	private function set_cart_item_date( $cart_item_key, $date ) {
		if ( ! WC()->cart ) {
			return;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( ! $cart_item ) {
			return;
		}

		WC()->cart->cart_contents[ $cart_item_key ]['_saso_eventtickets_request_daychooser'] = array( $date );
		
		$session_data = WC()->session->get( '_saso_eventtickets_request_daychooser', array() );
		$session_data[ $cart_item_key ] = array( $date );
		WC()->session->set( '_saso_eventtickets_request_daychooser', $session_data );
	}

	/**
	 * Add main product reference to extra item data
	 * 
	 * @param array $cart_item_data Cart item data
	 * @param object $yaydp_item YayPricing item
	 * @param object $rule YayPricing rule
	 * @return array Modified cart item data
	 */
	public function add_main_product_reference_to_extra_item( $cart_item_data, $yaydp_item, $rule ) {
		$main_product_id = $this->find_main_product_for_rule( $rule );
		
		if ( $main_product_id ) {
			$cart_item_data['yaydp_main_product_id'] = $main_product_id;
		}
		
		return $cart_item_data;
	}

	/**
	 * Sync extra product date on add to cart
	 * 
	 * @param string $cart_item_key Cart item key
	 * @param int $product_id Product ID
	 * @param int $quantity Quantity
	 * @param int $variation_id Variation ID
	 * @param array $variation Variation data
	 * @param array $cart_item_data Cart item data
	 */
	public function sync_extra_product_date_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( empty( $cart_item_data['is_extra'] ) ) {
			return;
		}

		if ( ! $this->is_ticket_product( $product_id ) ) {
			return;
		}

		$main_product_id = isset( $cart_item_data['yaydp_main_product_id'] ) ? $cart_item_data['yaydp_main_product_id'] : null;
		
		if ( $main_product_id ) {
			$main_date = $this->get_main_product_date_from_cart( $main_product_id );
			if ( $main_date ) {
				$this->set_cart_item_date( $cart_item_key, $main_date );
			}
		}
	}

	/**
	 * Find the main product for a given rule
	 * 
	 * @param object $rule YayPricing rule
	 * @return int|null Main product ID or null
	 */
	private function find_main_product_for_rule( $rule ) {
		return null;
	}

	/**
	 * Get the main product date from cart
	 * 
	 * @param int $main_product_id Main product ID
	 * @return string|null Date string or null
	 */
	private function get_main_product_date_from_cart( $main_product_id ) {
		if ( ! WC()->cart ) {
			return null;
		}

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['product_id'] ) && $cart_item['product_id'] == $main_product_id && empty( $cart_item['is_extra'] ) ) {
				return $this->get_cart_item_date( $cart_item );
			}
		}

		return null;
	}

	/**
	 * Safely get product ID from cart item data
	 * 
	 * @param array $cart_item Cart item data
	 * @param object $yaydp_cart_item_instance YayPricing cart item instance (optional)
	 * @return int|null Product ID or null
	 */
	private function get_product_id_from_cart_item( $cart_item, $yaydp_cart_item_instance = null ) {
		if ( isset( $cart_item['product_id'] ) ) {
			return intval( $cart_item['product_id'] );
		}

		if ( $yaydp_cart_item_instance && method_exists( $yaydp_cart_item_instance, 'get_product' ) ) {
			$product = $yaydp_cart_item_instance->get_product();
			if ( $product && method_exists( $product, 'get_id' ) ) {
				return $product->get_id();
			}
		}

		if ( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ) {
			return intval( $cart_item['variation_id'] );
		}

		return null;
	}

	/**
	 * Lock the date picker for an extra product
	 * 
	 * @param string $cart_item_key Cart item key
	 */
	private function lock_extra_product_date( $cart_item_key ) {
		if ( ! WC()->cart ) {
			return;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( ! $cart_item ) {
			return;
		}

		WC()->cart->cart_contents[ $cart_item_key ]['yaydp_date_locked'] = true;
		
		WC()->session->set( 'yaydp_locked_dates', array_merge( 
			WC()->session->get( 'yaydp_locked_dates', array() ), 
			array( $cart_item_key => true ) 
		) );
	}

	/**
	 * Check if an extra product's date is locked
	 * 
	 * @param string $cart_item_key Cart item key
	 * @return bool True if date is locked
	 */
	private function is_extra_product_date_locked( $cart_item_key ) {
		if ( ! WC()->cart ) {
			return false;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( ! $cart_item ) {
			return false;
		}

		if ( empty( $cart_item['is_extra'] ) ) {
			return false;
		}

		if ( ! empty( $cart_item['yaydp_date_locked'] ) ) {
			return true;
		}

		$locked_dates = WC()->session->get( 'yaydp_locked_dates', array() );
		return isset( $locked_dates[ $cart_item_key ] ) && $locked_dates[ $cart_item_key ];
	}

	/**
	 * Modify the date picker display for extra products
	 * This runs before the Event Tickets plugin renders the date picker
	 * 
	 * @param array $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 */
	public function modify_extra_product_datepicker( $cart_item, $cart_item_key ) {
		if ( empty( $cart_item['is_extra'] ) ) {
			return;
		}

		$product_id = $this->get_product_id_from_cart_item( $cart_item );
		if ( ! $product_id || ! $this->is_ticket_product( $product_id ) ) {
			return;
		}

		WC()->cart->cart_contents[ $cart_item_key ]['yaydp_modify_datepicker'] = true;
		
		$main_product_date = $this->get_main_product_date( $cart_item );
		if ( $main_product_date ) {
			WC()->cart->cart_contents[ $cart_item_key ]['_saso_eventtickets_request_daychooser'] = array( $main_product_date );
			
			$session_data = WC()->session->get( '_saso_eventtickets_request_daychooser', array() );
			$session_data[ $cart_item_key ] = array( $main_product_date );
			WC()->session->set( '_saso_eventtickets_request_daychooser', $session_data );
		}
	}

	/**
	 * Add CSS class to extra products for identification
	 * 
	 * @param string $class CSS class string
	 * @param array $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 * @return string Modified CSS class string
	 */
	public function add_extra_product_css_class( $class, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['is_extra'] ) ) {
			$class .= ' yaydp-extra-product';
		}
		
		return $class;
	}

	/**
	 * Add CSS class to extra products in checkout review order section
	 */
	public function add_extra_product_css_class_to_checkout() {
		if ( ! WC()->cart ) {
			return;
		}

		$cart_contents = WC()->cart->get_cart();
		$extra_product_keys = array();
		
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['is_extra'] ) ) {
				$extra_product_keys[] = $cart_item_key;
			}
		}
		
		if ( ! empty( $extra_product_keys ) ) {
			?>
			<script type="text/javascript">
			window.yaydpExtraProducts = <?php echo json_encode( $extra_product_keys ); ?>;
			</script>
			<?php
		}
	}

	/**
	 * Add JavaScript to handle extra product date picker behavior
	 */
	public function add_extra_product_datepicker_script() {
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			function disableExtraProductDatePickers() {
				$('input[data-input-type="daychooser"]').each(function() {
					var $dateInput = $(this);
					var cartItemKey = $dateInput.attr('data-cart-item-id');
					
					if (!cartItemKey) return;
					
					var isExtraProduct = false;
					
					var $cartItem = $('.cart_item[data-cart-item-key="' + cartItemKey + '"]');
					if ($cartItem.length === 0) {
						$cartItem = $dateInput.closest('.cart_item');
					}
					
					if ($cartItem.length > 0) {
						isExtraProduct = $cartItem.hasClass('yaydp-extra-product');
					} else {
						var $checkoutReview = $dateInput.closest('.woocommerce-checkout-review-order');
						if ($checkoutReview.length > 0) {
							isExtraProduct = window.yaydpExtraProducts && window.yaydpExtraProducts.indexOf(cartItemKey) !== -1;
						}
					}
					
					if (isExtraProduct) {
						$dateInput.prop('disabled', true);
						$dateInput.prop('readonly', true);
						
						$dateInput.addClass('yaydp-date-locked');
						
						var $label = $dateInput.siblings('.yaydp-date-locked-label');
						if ($label.length === 0) {
							$dateInput.after('<small class="yaydp-date-locked-label" style="display: block; color: #666; font-style: italic; margin-top: 5px; text-align: left; font-size: 10px;">Date auto-set from main product</small>');
						}
						
						$dateInput.off('click focus');
						$dateInput.on('click focus', function(e) {
							e.preventDefault();
							e.stopPropagation();
							return false;
						});
					}
				});
			}
			
			
			function handleMainProductDateChange() {
				$('input[data-input-type="daychooser"]').off('change.yaydp').on('change.yaydp', function() {
					var $changedInput = $(this);
					var cartItemKey = $changedInput.attr('data-cart-item-id');
					
					if (!cartItemKey) return;
					
					var isMainProduct = true;
					
					var $cartItem = $('.cart_item[data-cart-item-key="' + cartItemKey + '"]');
					if ($cartItem.length === 0) {
						$cartItem = $changedInput.closest('.cart_item');
					}
					
					if ($cartItem.length > 0) {
						isMainProduct = !$cartItem.hasClass('yaydp-extra-product');
					} else {
						if (window.yaydpExtraProducts && window.yaydpExtraProducts.indexOf(cartItemKey) !== -1) {
							isMainProduct = false;
						}
					}
					
					if (isMainProduct) {
						var newDate = $changedInput.val();
						
						$('.cart_item.yaydp-extra-product').each(function() {
							var $extraItem = $(this);
							var $extraDateInput = $extraItem.find('input[data-input-type="daychooser"]');
							
							if ($extraDateInput.length > 0 && $extraDateInput.val() !== newDate) {
								$extraDateInput.val(newDate);
								$extraDateInput.trigger('change');
							}
						});
					}
				});
			}

			setTimeout(function() {
				disableExtraProductDatePickers();
				handleMainProductDateChange();
			}, 500);

			$(document.body).on('updated_cart_totals', function() {
				setTimeout(function() {
					disableExtraProductDatePickers();
					handleMainProductDateChange();
				}, 500);
			});

			$(document.body).on('updated_wc_div', function() {
				setTimeout(function() {
					disableExtraProductDatePickers();
					handleMainProductDateChange();
				}, 500);
			});
			
			$(window).on('load', function() {
				setTimeout(function() {
					disableExtraProductDatePickers();
					handleMainProductDateChange();
				}, 1000);
			});
			
			$(document.body).on('click', 'button[name="update_cart"], input[name="update_cart"]', function() {
				setTimeout(function() {
					syncCheckoutReviewOrderDates();
				}, 100);
			});
			
			function syncCheckoutReviewOrderDates() {
				if (window.yaydpExtraProducts && window.yaydpExtraProducts.length > 0) {
					var mainProductDate = null;
					$('.cart_item').each(function() {
						var $item = $(this);
						if (!$item.hasClass('yaydp-extra-product')) {
							var $mainDateInput = $item.find('input[data-input-type="daychooser"]');
							if ($mainDateInput.length > 0) {
								mainProductDate = $mainDateInput.val();
								return false;
							}
						}
					});
					
					if (mainProductDate) {
						window.yaydpExtraProducts.forEach(function(extraCartItemKey) {
							$('.woocommerce-checkout-review-order input[data-cart-item-id="' + extraCartItemKey + '"]').each(function() {
								var $extraDateInput = $(this);
								if ($extraDateInput.val() !== mainProductDate) {
									$extraDateInput.val(mainProductDate);
								}
							});
						});
					}
				}
			}
		});
		</script>
		
		<style>
		.yaydp-date-locked {
			background-color: #f5f5f5 !important;
			cursor: not-allowed !important;
			opacity: 0.7 !important;
			color: #999 !important;
		}
		
		.yaydp-date-locked-label {
			margin-top: 5px;
			font-size: 12px;
			display: block !important;
		}
		
		.yaydp-date-locked + .ui-datepicker {
			display: none !important;
		}
		</style>
		<?php
	}

	/**
	 * Add JavaScript to modify Event Tickets date picker behavior
	 */
	public function add_event_tickets_datepicker_modification() {
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			if (typeof window.SasoEventticketsValidator_WC_frontend !== 'undefined') {
				var originalAddHandler = window.SasoEventticketsValidator_WC_frontend._addHandlerToTheCodeFields;
				
				window.SasoEventticketsValidator_WC_frontend._addHandlerToTheCodeFields = function() {
					if (originalAddHandler) {
						originalAddHandler.call(this);
					}
					
					setTimeout(function() {
						$('input[data-input-type="daychooser"]').each(function() {
							var $input = $(this);
							var cartItemKey = $input.attr('data-cart-item-id');
							
							if (!cartItemKey) return;
							
							var isExtraProduct = false;
							
							var $cartItem = $('.cart_item[data-cart-item-key="' + cartItemKey + '"]');
							if ($cartItem.length === 0) {
								$cartItem = $input.closest('.cart_item');
							}
							
							if ($cartItem.length > 0) {
								isExtraProduct = $cartItem.hasClass('yaydp-extra-product');
							} else {
								var $checkoutReview = $input.closest('.woocommerce-checkout-review-order');
								if ($checkoutReview.length > 0) {
									isExtraProduct = window.yaydpExtraProducts && window.yaydpExtraProducts.indexOf(cartItemKey) !== -1;
								}
							}
							
							if (isExtraProduct) {
								$input.prop('disabled', true);
								$input.prop('readonly', true);
								$input.addClass('yaydp-date-locked');
								
								if ($input.siblings('.yaydp-date-locked-label').length === 0) {
									$input.after('<small class="yaydp-date-locked-label" style="display: block; color: #666; font-style: italic; margin-top: 5px; text-align: left; font-size: 10px;">Date auto-set from main product</small>');
								}
								
								$input.off('click focus');
								$input.on('click focus', function(e) {
									e.preventDefault();
									e.stopPropagation();
									return false;
								});
								
								$input.datepicker('destroy');
							}
						});
						
					}, 100);
				};
			}
			
			
		});
		</script>
		<?php
	}
}
