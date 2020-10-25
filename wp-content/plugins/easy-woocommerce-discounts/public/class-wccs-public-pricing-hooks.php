<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Public_Pricing_Hooks extends WCCS_Public_Controller {

	protected $loader;

	protected $pricing;

	protected $discounted_products;

	public $applied_pricings = false;

	public function __construct( WCCS_Loader $loader ) {
		$this->loader = $loader;

		$loader->add_action( 'woocommerce_cart_loaded_from_session', $this, 'cart_loaded_from_session' );
		$loader->add_action( 'woocommerce_cart_loaded_from_session', $this, 'enable_price_and_badge_hooks' );
		$loader->add_action( 'woocommerce_before_calculate_totals', $this, 'remove_pricings' );
		$loader->add_action( 'woocommerce_after_calculate_totals', $this, 'apply_pricings' );
		$loader->add_action( 'woocommerce_add_to_cart', $this, 'reset_applied_pricings' );
		$loader->add_action( 'woocommerce_cart_item_removed', $this, 'reset_applied_pricings' );
		$loader->add_action( 'woocommerce_checkout_update_order_review', $this, 'reset_applied_pricings' );
		$loader->add_action( 'woocommerce_after_cart_item_quantity_update', $this, 'reset_applied_pricings' );
		$loader->add_filter( 'woocommerce_cart_item_price', $this, 'cart_item_price', 10, 3 );

		if ( (int) WCCS()->settings->get_setting( 'display_quantity_table', 1 ) ) {
			$this->quantity_table_hooks();
		}
	}

	public function cart_loaded_from_session( $cart ) {
		if ( ! WCCS()->is_request( 'frontend' ) ) {
			return;
		}
		
		$cart_contents = $cart->get_cart();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			// It is a cart item product so do not override its price.
			$cart->cart_contents[ $cart_item_key ]['data']->wccs_is_cart_item = true;
		}

		if ( ! $cart->is_empty() ) {
			$this->reset_applied_pricings();
			/**
			 * Used for subtotal conditions in pricing rules.
			 * Do not use wtih update-order-review action that caused issue for some shipping plugins.
			 */
			if ( ! is_cart() && ! is_checkout() ) {
				$this->calculate_cart_totals();
			}
		}
	}

	public function enable_price_and_badge_hooks() {
		if ( ! WCCS()->is_request( 'frontend' ) ) {
			return;
		}

		$sale_badge = WCCS()->settings->get_setting( 'sale_badge', array() );
		if ( ! empty( $sale_badge['bulk'] ) ) {
			$this->sale_badge_hooks();
		}
	}

	public function apply_pricings() {
		if ( ! $this->should_apply_pricing() ) {
			return;
		}

		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}

		$this->pricing    = isset( $this->pricing ) ? $this->pricing : WCCS()->pricing;
		$calculate_totals = false;

		$cart_contents = $cart->get_cart();

		if ( ! empty( $cart_contents ) ) {
			$pricing_cache = new WCCS_Cart_Pricing_Cache();
		}

		do_action( 'wccs_public_pricing_hooks_before_apply_pricings', $this );

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			// It is a cart item product so do not override its price.
			$cart->cart_contents[ $cart_item_key ]['data']->wccs_is_cart_item = true;

			$product = $cart_item['data'];
			if ( isset( $cart_item['_wccs_main_price'] ) ) {
				$product->set_price( $cart_item['_wccs_main_price'] );
				if ( isset( $cart_item['_wccs_main_sale_price'] ) ) {
					$product->set_sale_price( $cart_item['_wccs_main_sale_price'] );
				}
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_main_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_main_sale_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_main_display_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_before_discounted_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_discounted_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_prices'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_prices_main'] );

				if ( ! has_action( 'woocommerce_before_calculate_totals', array( &$this, 'remove_pricings' ) ) ) {
					$calculate_totals = true;
				}
			}

			if ( ! apply_filters( 'wccs_apply_pricing_on_cart_item', true, $cart_item ) ) {
				continue;
			}

			$pricing_item = new WCCS_Public_Cart_Item_Pricing( $cart_item_key, $cart_item, $this->pricing, '', null, $pricing_cache );
			$item_discounted_price = $pricing_item->get_price();
			if ( false === $item_discounted_price || $item_discounted_price < 0 ) {
				continue;
			}

			$item_discounted_price = apply_filters( 'wccs_cart_item_discounted_price', $item_discounted_price, $cart_item );

			$calculate_totals = true;

			$cart->cart_contents[ $cart_item_key ]['_wccs_main_price']              = apply_filters( 'wccs_cart_item_main_price', WCCS()->product_helpers->wc_get_price( $pricing_item->product ), $cart_item );
			$cart->cart_contents[ $cart_item_key ]['_wccs_main_display_price']      = apply_filters( 'wccs_cart_item_main_display_price', WCCS()->product_helpers->wc_get_price_to_display( $pricing_item->product ), $cart_item );
			$cart->cart_contents[ $cart_item_key ]['_wccs_before_discounted_price'] = apply_filters( 'wccs_cart_item_before_discounted_price', $cart->get_product_price( $product ), $cart_item );
			$cart->cart_contents[ $cart_item_key ]['_wccs_discounted_price']        = wc_format_decimal( $item_discounted_price );
			$cart->cart_contents[ $cart_item_key ]['_wccs_prices']                  = apply_filters( 'wccs_cart_item_prices', $pricing_item->get_prices(), $cart_item );
			// Do not apply any filter on _wccs_prices_main.
			$cart->cart_contents[ $cart_item_key ]['_wccs_prices_main']             = $pricing_item->get_prices();
			$product->set_price( $item_discounted_price );

			// Setting sale price.
			if ( $item_discounted_price < $pricing_item->get_base_price() ) {
				$cart->cart_contents[ $cart_item_key ]['_wccs_main_sale_price'] = apply_filters( 'wccs_cart_item_main_sale_price', WCCS()->product_helpers->wc_get_sale_price( $pricing_item->product ), $cart_item );
				$product->set_sale_price( $item_discounted_price );
			}
		}

		do_action( 'wccs_public_pricing_hooks_after_apply_pricings', $this );

		if ( $calculate_totals ) {
			remove_action( 'woocommerce_before_calculate_totals', array( &$this, 'remove_pricings' ) );
			remove_action( 'woocommerce_after_calculate_totals', array( &$this, 'apply_pricings' ) );
			$cart->calculate_totals();
			add_action( 'woocommerce_after_calculate_totals', array( &$this, 'apply_pricings' ) );
		}

		$this->applied_pricings = true;
	}

	/**
	 * Reset applied pricings.
	 *
	 * @since  2.8.0
	 *
	 * @return void
	 */
	public function reset_applied_pricings() {
		if ( $this->applied_pricings ) {
			$this->applied_pricings = false;
			$this->pricing->reset_cache();
		}
	}

	public function remove_pricings( $cart = null ) {
		$cart = $cart && is_a( $cart, 'WC_Cart' ) ? $cart : WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}

		$cart_contents = $cart->get_cart();
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['_wccs_main_price'] ) ) {
				$cart_item['data']->set_price( $cart_item['_wccs_main_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_main_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_main_display_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_before_discounted_price'] );
				unset( $cart->cart_contents[ $cart_item_key ]['_wccs_discounted_price'] );
			}

			// It is a cart item product so do not override its price.
			$cart->cart_contents[ $cart_item_key ]['data']->wccs_is_cart_item = true;
		}
	}

	protected function should_apply_pricing() {
		return apply_filters( 'wccs_should_apply_pricing', ! $this->applied_pricings );
	}

	public function cart_item_price( $price, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['_wccs_discounted_price'] ) || ! isset( $cart_item['_wccs_before_discounted_price'] ) || ! isset( $cart_item['_wccs_main_price'] ) ) {
			return $price;
		}

		$before_discounted_price = apply_filters( 'wccs_cart_item_price_before_discounted_price', $cart_item['_wccs_before_discounted_price'], $cart_item, $cart_item_key, $price );

		if ( (float) $cart_item['_wccs_main_price'] > (float) $cart_item['_wccs_discounted_price'] ) {
			$content = '<del>' . $before_discounted_price . '</del> <ins>' . $price . '</ins>';
			return apply_filters( 'wccs_cart_item_price', $content, $price, $cart_item, $cart_item_key );
		}

		return apply_filters( 'wccs_cart_item_price', $price, $price, $cart_item, $cart_item_key );
	}

	public function calculate_cart_totals() {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		} elseif ( (int) WCCS()->settings->get_setting( 'disable_calculate_cart_totals', 0 ) ) {
			return;
		} elseif ( ! apply_filters( 'wccs_calculate_cart_totals', true ) ) {
			return;
		}

		do_action( 'wccs_before_calculate_cart_totals' );
		WC()->cart->calculate_totals();
		do_action( 'wccs_after_calculate_cart_totals' );
	}

	protected function quantity_table_hooks() {
		$position = WCCS()->settings->get_setting( 'quantity_table_position', 'before_add_to_cart_button' );

		switch ( $position ) {
			case 'before_add_to_cart_button' :
			case 'after_add_to_cart_button' :
				$add_to_cart_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
				if ( 'before_add_to_cart_button' === $position ) {
					$add_to_cart_priority ?
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', $add_to_cart_priority - 1 ) :
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', 29 );
				} elseif ( 'after_add_to_cart_button' === $position ) {
					$add_to_cart_priority ?
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', $add_to_cart_priority + 1 ) :
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', 31 );
				}
				break;

			case 'before_add_to_cart_form':
				$this->loader->add_action( 'woocommerce_before_add_to_cart_form', $this, 'display_bulk_pricing_table' );
				break;

			case 'after_add_to_cart_form':
				$this->loader->add_action( 'woocommerce_after_add_to_cart_form', $this, 'display_bulk_pricing_table' );
				break;

			case 'before_excerpt' :
			case 'after_excerpt' :
				$excerpt_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt' );
				if ( 'before_excerpt' === $position ) {
					$excerpt_priority ?
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', $excerpt_priority - 1 ) :
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', 19 );
				} elseif ( 'after_excerpt' === $position ) {
					$excerpt_priority ?
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', $excerpt_priority + 1 ) :
						$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', 21 );
				}
				break;

			case 'after_product_meta' :
				$meta_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta' );
				$meta_priority ?
					$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', $meta_priority + 1 ) :
					$this->loader->add_action( 'woocommerce_single_product_summary', $this, 'display_bulk_pricing_table', 41 );
				break;

			case 'in_modal' :
				break;

			default :
				break;
		}
	}

	public function display_bulk_pricing_table() {
		global $product;

		$product_pricing = $this->get_product_pricing( $product );
		$product_pricing->bulk_pricing_table();
	}

	protected function sale_badge_hooks() {
		$loop_position = WCCS()->settings->get_setting( 'loop_sale_badge_position', 'before_shop_loop_item_thumbnail' );
		switch ( $loop_position ) {
			case 'before_shop_loop_item_thumbnail':
				$priority = has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' );
				if ( $priority ) {
					add_action( 'woocommerce_before_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_before_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 9 );
				}
				break;

			case 'after_shop_loop_item_thumbnail':
				$priority = has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' );
				if ( $priority ) {
					add_action( 'woocommerce_before_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_before_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 11 );
				}
				break;

			case 'before_shop_loop_item_title':
				$priority = has_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' );
				if ( $priority ) {
					add_action( 'woocommerce_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 9 );
				}
				break;

			case 'after_shop_loop_item_title':
				$priority = has_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' );
				if ( $priority ) {
					add_action( 'woocommerce_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 10 );
				}
				break;

			case 'before_shop_loop_item_rating':
				$priority = has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating' );
				if ( $priority ) {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 4 );
				}
				break;

			case 'after_shop_loop_item_rating':
				$priority = has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating' );
				if ( $priority ) {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 6 );
				}
				break;

			case 'before_shop_loop_item_price':
				$priority = has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );
				if ( $priority ) {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 9 );
				}
				break;

			case 'after_shop_loop_item_price':
				$priority = has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );
				if ( $priority ) {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_after_shop_loop_item_title', array( &$this, 'display_sale_badge' ), 11 );
				}
				break;
		}

		$single_position = WCCS()->settings->get_setting( 'single_sale_badge_position', 'before_single_item_images' );
		switch ( $single_position ) {
			case 'before_single_item_images':
				$priority = has_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images' );
				if ( $priority ) {
					add_action( 'woocommerce_before_single_product_summary', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_before_single_product_summary', array( &$this, 'display_sale_badge' ), 19 );
				}
				break;

			case 'after_single_item_images':
				$priority = has_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images' );
				if ( $priority ) {
					add_action( 'woocommerce_before_single_product_summary', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_before_single_product_summary', array( &$this, 'display_sale_badge' ), 21 );
				}
				break;

			case 'before_single_item_title':
				$priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title' );
				if ( $priority ) {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), 4 );
				}
				break;

			case 'after_single_item_title':
				$priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title' );
				if ( $priority ) {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), 6 );
				}
				break;

			case 'before_single_item_price':
				$priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' );
				if ( $priority ) {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), $priority - 1 );
				} else {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), 9 );
				}
				break;

			case 'after_single_item_price':
				$priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' );
				if ( $priority ) {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), $priority + 1 );
				} else {
					add_action( 'woocommerce_single_product_summary', array( &$this, 'display_sale_badge' ), 11 );
				}
				break;
		}
	}

	public function display_sale_badge() {
		$this->render_view( 'product-pricing.sale-flash', array( 'controller' => $this ) );
	}

	protected function get_product_pricing( $product, $pricing = null ) {
		if ( null === $pricing ) {
			$pricing = $this->pricing = isset( $this->pricing ) ? $this->pricing : WCCS()->pricing;
		}
		
		if ( ! isset( $this->product_pricing ) ) {
			$this->product_pricing = new WCCS_Public_Product_Pricing( $product, $pricing );
		} elseif ( is_numeric( $product ) && $product != $this->product_pricing->product->get_id() ) {
			$this->product_pricing = new WCCS_Public_Product_Pricing( $product, $pricing );
		} elseif ( $product !== $this->product_pricing->product ) {
			$this->product_pricing = new WCCS_Public_Product_Pricing( $product, $pricing );
		}

		return $this->product_pricing;
	}

}
