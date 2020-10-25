<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Helpers {

	/**
	 * Checking a version against WooCommerce version.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $version
	 * @param  string $operator
	 *
	 * @return boolean
	 */
	public function wc_version_check( $version = '3.0', $operator = '>=' ) {
		return version_compare( WC_VERSION, $version, $operator );
	}

	/**
	 * Wrapper for wc_get_logger function.
	 *
	 * @since  1.1.0
	 *
	 * @return WC_Logger
	 */
	public function wc_get_logger() {
		return $this->wc_version_check() ? wc_get_logger() : new WC_Logger();
	}

	/**
	 * Format a price range for display.
	 * Wrapper method for WooCommerce wc_format_price_range function.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $from Price from.
	 * @param  string $to   Price to.
	 *
	 * @return string
	 */
	public function wc_format_price_range( $from, $to ) {
		if ( $this->wc_version_check() ) {
			return wc_format_price_range( $from, $to );
		}

		/* translators: 1: price from 2: price to */
		$price = sprintf( _x( '%1$s &ndash; %2$s', 'Price range: from-to', 'woocommerce' ), is_numeric( $from ) ? wc_price( $from ) : $from, is_numeric( $to ) ? wc_price( $to ) : $to );
		return apply_filters( 'woocommerce_format_price_range', $price, $from, $to );
	}

	/**
	 * Checking is product in given include and exclude products.
	 *
	 * @since  1.1.0
	 *
	 * @param  array   $include
	 * @param  array   $exclude
	 * @param  integer $product_id
	 * @param  integer $variation_id
	 *
	 * @return boolean
	 */
	public function is_product_in_items( $include, $exclude, $product_id, $variation_id = 0 ) {
		if ( empty( $include ) && empty( $exclude ) ) {
			return false;
		}

		if ( isset( $include['all_products'] ) || isset( $include['all_categories'] ) ) {
			if ( ! empty( $exclude ) ) {
				if ( isset( $exclude[ $product_id ] ) || ( 0 < (int) $variation_id && isset( $exclude[ $variation_id ] ) ) ) {
					return false;
				}
			}
			return true;
		} elseif ( isset( $include[ $product_id ] ) || ( 0 < (int) $variation_id && isset( $include[ $variation_id ] ) ) ) {
			if ( ! empty( $exclude ) ) {
				if ( isset( $exclude[ $product_id ] ) || ( 0 < (int) $variation_id && isset( $exclude[ $variation_id ] ) ) ) {
					return false;
				}
			}
			return true;
		} elseif ( empty( $include ) && ! empty( $exclude ) ) {
			if ( ! isset( $exclude[ $product_id ] ) && ( 0 >= (int) $variation_id || ! isset( $exclude[ $variation_id ] ) ) ) {
				return true;
			}
			return false;
		}

		return false;
	}

	/**
	 * Getting term hierarchy name.
	 *
	 * @since  2.0.0
	 *
	 * @param  int|WP_Term|object $term_id
	 * @param  string             $taxonomy
	 * @param  string             $separator
	 * @param  boolean            $nicename
	 * @param  array              $visited
	 *
	 * @return string
	 */
	public function get_term_hierarchy_name( $term_id, $taxonomy, $separator = '/', $nicename = false, $visited = array() ) {
		$chain = '';
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) ) {
			return '';
		}

		$name = $term->name;
		if ( $nicename ) {
			$name = $term->slug;
		}

		if ( $term->parent && ( $term->parent != $term->term_id ) && ! in_array( $term->parent, $visited ) ) {
			$visited[] = $term->parent;
			$chain     .= $this->get_term_hierarchy_name( $term->parent, $taxonomy, $separator, $nicename, $visited );
		}

		$chain .= $name . $separator;

		return $chain;
	}

	/**
	 * Get rounding precision for internal WC calculations.
	 * Will increase the precision of wc_get_price_decimals by 2 decimals, unless WC_ROUNDING_PRECISION is set to a higher number.
	 *
	 * @since  2.2.2
	 *
	 * @return int
	 */
	public function wc_get_rounding_precision() {
		if ( function_exists( 'wc_get_rounding_precision' ) ) {
			return wc_get_rounding_precision();
		}

		$precision = wc_get_price_decimals() + 2;
		if ( absint( WC_ROUNDING_PRECISION ) > $precision ) {
			$precision = absint( WC_ROUNDING_PRECISION );
		}
		return $precision;
	}

	/**
	 * Add precision to a number and return a number.
	 *
	 * @since  2.2.2
	 *
	 * @param  float $value Number to add precision to.
	 * @param  bool  $round If should round after adding precision.
	 *
	 * @return int|float
	 */
	public function wc_add_number_precision( $value, $round = true ) {
		if ( function_exists( 'wc_add_number_precision' ) ) {
			return wc_add_number_precision( $value, $round );
		}

		$cent_precision = pow( 10, wc_get_price_decimals() );
		$value          = $value * $cent_precision;
		return $round ? round( $value, $this->wc_get_rounding_precision() - wc_get_price_decimals() ) : $value;
	}

	/**
	 * Remove precision from a number and return a float.
	 *
	 * @since  2.2.2
	 *
	 * @param  float $value Number to add precision to.
	 * @return float
	 */
	public function wc_remove_number_precision( $value ) {
		if ( function_exists( 'wc_remove_number_precision' ) ) {
			return wc_remove_number_precision( $value );
		}

		$cent_precision = pow( 10, wc_get_price_decimals() );
		return $value / $cent_precision;
	}

	/**
	 * Add precision to an array of number and return an array of int.
	 *
	 * @since  2.2.2
	 *
	 * @param  array $value Number to add precision to.
	 * @param  bool  $round Should we round after adding precision?.
	 *
	 * @return int|array
	 */
	public function wc_add_number_precision_deep( $value, $round = true ) {
		if ( function_exists( 'wc_add_number_precision_deep' ) ) {
			return wc_add_number_precision_deep( $value, $round );
		}

		if ( ! is_array( $value ) ) {
			return $this->wc_add_number_precision( $value, $round );
		}

		foreach ( $value as $key => $sub_value ) {
			$value[ $key ] = $this->wc_add_number_precision_deep( $sub_value, $round );
		}

		return $value;
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backwards compatibility.
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public function wc_is_rest_api_request() {
		if ( is_callable( array( WC(), 'is_rest_api_request' ) ) ) {
			return WC()->is_rest_api_request();
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
	}

}
