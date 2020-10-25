<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Cart_Discount {

	protected $discounts;

	protected $cart;

	protected $apply_method;

	protected $date_time_validator;

	protected $condition_validator;

	public $rules_filter;

	const DISCOUNT_SUFFIX = 'wccs_cart_discount_';

	public function __construct( array $discounts, WCCS_Cart $cart = null, $apply_method = null ) {
		$wccs = WCCS();

		$this->discounts           = $discounts;
		$this->cart                = null === $cart ? $wccs->cart : $cart;
		$this->apply_method        = null === $apply_method ? $wccs->settings->get_setting( 'cart_discount_apply_method', 'first' ) : $apply_method;
		$this->date_time_validator = $wccs->WCCS_Date_Time_Validator;
		$this->condition_validator = $wccs->WCCS_Condition_Validator;
		$this->rules_filter        = new WCCS_Rules_Filter();
	}

	public function get_discounts() {
		return $this->discounts;
	}

	public function get_valid_discounts() {
		if ( empty( $this->discounts ) ) {
			return array();
		}

		$valid_discounts = array();

		foreach ( $this->discounts as $discount ) {
			if ( ! $this->date_time_validator->is_valid_date_times( $discount->date_time, ( ! empty( $discount->date_times_match_mode ) ? $discount->date_times_match_mode : 'one' ) ) ) {
				continue;
			}

			if ( ! $this->condition_validator->is_valid_conditions( $discount->conditions, ( ! empty( $discount->conditions_match_mode ) ? $discount->conditions_match_mode : 'all' ) ) ) {
				continue;
			}

			$valid_discounts[] = $discount;
		}

		if ( ! empty( $valid_discounts ) ) {
			usort( $valid_discounts, array( WCCS()->WCCS_Sorting, 'sort_by_ordering_asc' ) );
			$valid_discounts = $this->rules_filter->by_apply_mode( $valid_discounts );
		}

		return $valid_discounts;
	}

	public function get_possible_discounts() {
		$valids = $this->get_valid_discounts();
        if ( empty( $valids ) ) {
            return array();
		}

		$prices_include_tax = wc_prices_include_tax();
		$cart_subtotal      = $prices_include_tax ? $this->cart->subtotal : $this->cart->subtotal_ex_tax;
		if ( 0 >= (float) $cart_subtotal ) {
			return array();
		}

		$possibles = array();
        foreach ( $valids as $discount ) {
            if ( empty( $discount->discount_amount ) ) {
                continue;
			}

			$discount = clone $discount;

			$discount->code = WCCS()->WCCS_Helpers->wc_version_check() ? wc_format_coupon_code( $discount->name ) : apply_filters( 'woocommerce_coupon_code', $discount->name );
			if ( ! strlen( trim( $discount->code ) ) ) {
				continue;
			}
			$discount->code = self::DISCOUNT_SUFFIX . $discount->code;

            $discount_amount = (float) $discount->discount_amount;
            if ( 'percentage' === $discount->discount_type ) {
				$discount_amount = $discount_amount / 100 * $cart_subtotal;
            }

            if ( 0 >= $discount_amount ) {
                continue;
            }

            $discount->discount_amount    = $discount_amount;
            $possibles[ $discount->code ] = $discount;
        }

        if ( ! empty( $possibles ) ) {
            $first = array_shift( $possibles );
			return array( $first->code => $first );
        }

        return $possibles;
	}

	public function get_combine_coupon_code() {
		$coupon_code = WCCS()->settings->get_setting( 'coupon_label', 'discount' );
		if ( strlen( trim( $coupon_code ) ) ) {
			$coupon_code = WCCS()->WCCS_Helpers->wc_version_check() ? wc_format_coupon_code( $coupon_code ) : apply_filters( 'woocommerce_coupon_code', $coupon_code );
		}
		$coupon_code = strlen( $coupon_code ) ? $coupon_code : 'discount';

		$coupon_code = self::DISCOUNT_SUFFIX . $coupon_code;

		return apply_filters( 'wccs_cart_discount_combine_coupon_code', $coupon_code );
	}

	/**
	 * Checking is the given coupon belong to the plugin.
	 *
	 * @since  2.3.0
	 *
	 * @param  string $coupon_code
	 *
	 * @return boolean
	 */
	public function is_cart_discount_coupon( $coupon_code ) {
		if ( $coupon_code === $this->get_combine_coupon_code() ) {
			return apply_filters( 'wccs_is_cart_discount_coupon', true, $coupon_code );
		} elseif ( 0 === strpos( $coupon_code, self::DISCOUNT_SUFFIX ) ) {
			return apply_filters( 'wccs_is_cart_discount_coupon', true, $coupon_code );
		}

		return apply_filters( 'wccs_is_cart_discount_coupon', false, $coupon_code );
	}

	public function get_discount_amount() {
		$valid_discounts = $this->get_valid_discounts();

		if ( empty( $valid_discounts ) ) {
			return 0;
		}

		$cart_subtotal = wc_prices_include_tax() ? $this->cart->subtotal : $this->cart->subtotal_ex_tax;

		$amounts = array();

		foreach ( $valid_discounts as $discount ) {
			if ( empty( $discount->discount_amount ) ) {
				continue;
			}

			$discount_amount = (float) $discount->discount_amount;

			if ( $discount_amount > 0 ) {
				if ( 'percentage' == $discount->discount_type ) {
					$discount_amount = $discount_amount / 100 * $cart_subtotal;
					if ( $discount_amount > 0 ) {
						$amounts[] = $discount_amount;
					}
				} else {
					$amounts[] = $discount_amount;
				}

				break;
			}
		}

		$discount_amount = 0;
		if ( ! empty( $amounts ) ) {
			$discount_amount = $amounts[0];
		}

		return $discount_amount;
	}

}
