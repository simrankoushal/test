<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Public_Cart_Item_Pricing {

	protected $pricing;

	protected $apply_method;

	protected $cart;

	protected $discounts;

	/**
	 * An array containing discount_id and it's related prices with quantity of each price.
	 *
	 * @var array
	 */
	protected $discount_prices = array();

	/**
	 * An array of prices that applied to this item associated with their applied quantites.
	 *
	 * @var array
	 */
	public $prices = array();

	public $item;

	public $product_id;

	public $variation_id;

	public $product;

	public function __construct( $cart_item_id, $cart_item, WCCS_Pricing $pricing, $apply_method = '', $cart = null, WCCS_Cart_Pricing_Cache $pricing_cache = null ) {
		$this->item          = $cart_item;
		$this->pricing       = $pricing;
		$this->apply_method  = ! empty( $apply_method ) ? $apply_method : WCCS()->settings->get_setting( 'product_pricing_discount_apply_method', 'first' );
		$this->product_id    = $cart_item['product_id'];
		$this->variation_id  = $cart_item['variation_id'];
		$this->product       = ! empty( $this->variation_id ) ? wc_get_product( $this->variation_id ) : wc_get_product( $this->product_id );
		$this->cart          = null !== $cart ? $cart : WCCS()->cart;
		$this->discounts     = new WCCS_Cart_Item_Pricing_Discounts( $cart_item_id, $this->item, $this->pricing, $this->cart, $pricing_cache );
	}

	/**
	 * Getting price.
	 *
	 * @since  1.0.0
	 *
	 * @return float
	 */
	public function get_price() {
		if ( $this->pricing->is_in_exclude_rules( $this->product_id, $this->variation_id, ( ! empty( $this->item['variation'] ) ? $this->item['variation'] : array() ) ) ) {
			return false;
		}

		do_action( 'wccs_public_cart_item_pricing_before_get_price', $this );

		$base_price     = $this->get_base_price();
		$adjusted_price = $this->apply_discounts( $base_price );

		if ( $base_price != $adjusted_price ) {
			// Round adjusted price or no.
			if ( 'yes' === WCCS()->settings->get_setting( 'round_product_adjustment', 'no' ) ) {
				$adjusted_price = round( $adjusted_price, wc_get_price_decimals() );
			}

			do_action( 'wccs_public_cart_item_pricing_after_get_price', $this );

			return $adjusted_price;
		}

		do_action( 'wccs_public_cart_item_pricing_after_get_price', $this );

		return false;
	}

	public function get_base_price() {
		do_action( 'wccs_public_cart_item_pricing_before_get_base_price', $this );

		$base_price = (float) WCCS()->product_helpers->wc_get_price( $this->product );
		if ( WCCS()->product_helpers->is_on_sale( $this->product, 'edit' ) ) {
			if ( 'regular_price' === WCCS()->settings->get_setting( 'on_sale_products_price', 'regular_price' ) ) {
				$base_price = (float) WCCS()->product_helpers->wc_get_regular_price( $this->product );
			}
		}

		do_action( 'wccs_public_cart_item_pricing_after_get_base_price', $this );

		return apply_filters( 'wccs_public_cart_item_pricing_' . __FUNCTION__, $base_price, $this->item, $this->product, $this );
	}

	/**
	 * Getting prices applied to this item.
	 *
	 * @since  2.7.0
	 *
	 * @return array
	 */
	public function get_prices() {
		return $this->prices;
	}

	/**
	 * Setting applied prices to the item.
	 *
	 * @since  2.7.0
	 *
	 * @param  array $applied_discounts // An array of applied discounts to the item.
	 *
	 * @return void
	 */
	protected function set_applied_prices( array $applied_discounts ) {
		$this->prices = array();

		if ( empty( $this->discount_prices ) || empty( $applied_discounts ) ) {
			return;
		}

		foreach ( $this->discount_prices as $discount_id => $prices ) {
			if ( in_array( $discount_id, $applied_discounts ) ) {
				foreach ( $prices as $price => $quantity ) {
					$price = (string) WCCS()->product_helpers->wc_get_price_to_display( $this->product, array( 'price' => $price ) );
					if ( isset( $this->prices[ $price ] ) ) {
						$this->prices[ $price ] += $quantity;
					} else {
						$this->prices[ $price ] = $quantity;
					}
				}
			}
		}
	}

	protected function apply_discounts( $base_price ) {
		$this->prices = array();
		$discounts    = $this->discounts->get_discounts();
		if ( empty( $discounts ) ) {
			return $base_price;
		}

		// Get discount limit.
		$discount_limit = '';

		$discount_amounts = array();
		foreach ( $discounts as $discount_id => $discount ) {
			if ( '' !== $discount_limit && 0 >= $discount_limit ) {
				break;
			}

			$discount_amount = $this->calculate_discount_amount( $discount, $base_price, $discount_id, $discount_limit );
			if ( false !== $discount_amount ) {
				if ( '' !== $discount_limit ) {
					$discount_limit -= $discount_amount;
				}

				$discount_amounts[] = array(
					'id'     => $discount_id,
					'amount' => $discount_amount,
				);
			}
		}

		if ( empty( $discount_amounts ) ) {
			return $base_price;
		}

		$applied_discounts   = array();
		$discount_amount     = $discount_amounts[0]['amount'];
		$applied_discounts[] = $discount_amounts[0]['id'];

		if ( $base_price - $discount_amount >= 0 ) {
			$this->set_applied_prices( $applied_discounts );
			return $base_price - $discount_amount;
		}

		return $base_price;
	}

	protected function calculate_discount_amount( $discount, $product_display_price, $discount_id, $discount_limit ) {
		$this->discount_prices[ $discount_id ] = array();

		if ( 'percentage_discount' === $discount['discount_type'] ) {
			if ( $discount['discount'] / 100 * $product_display_price > 0 ) {
				// Limit discount amount if limit exists.
				$discount_amount = $discount['discount'] / 100 * $product_display_price;
				if ( '' !== $discount_limit && (float) $discount_amount > (float) $discount_limit ) {
					$discount_amount = (float) $discount_limit;
				}

				// Set discount prices.
				if ( 0 <= $product_display_price - $discount_amount ) {
					$this->discount_prices[ $discount_id ][ strval( $product_display_price - $discount_amount ) ] = 1;
				}

				return $discount_amount;
			}
		}

		return false;
	}

}
