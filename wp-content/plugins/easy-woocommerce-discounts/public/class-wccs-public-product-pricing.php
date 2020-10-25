<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Public_Product_Pricing extends WCCS_Public_Controller {

	protected $pricing;

	protected $apply_method;

	public $product;

	public $product_type;

	public $product_id;

	public $parent_id;

	public function __construct( $product, WCCS_Pricing $pricing, $apply_method = '' ) {
		if ( is_numeric( $product ) ) {
			$this->product = wc_get_product( $product );
		} else {
			$this->product = $product;
		}

		$wccs = WCCS();

		$this->product_type = $this->product->get_type();
		$this->product_id   = $this->product->get_id();
		$this->parent_id    = 'variation' === $this->product_type ? $wccs->product_helpers->get_parent_id( $this->product ) : $this->product_id;
		$this->pricing      = $pricing;
		$this->apply_method = ! empty( $apply_method ) ? $apply_method : $wccs->settings->get_setting( 'product_pricing_discount_apply_method', 'first' );
	}

	/**
	 * Getting price.
	 *
	 * @since  1.0.0
	 *
	 * @return float
	 */
	public function get_price() {
		return false;
	}

	/**
	 * Get discount value html.
	 *
	 * @since  2.8.0
	 *
	 * @param  float  $discount
	 * @param  string $discount_type
	 *
	 * @return string
	 */
	public function get_discount_value_html( $discount, $discount_type ) {
		$discount = (float) $discount;
		if ( $discount < 0 || empty( $discount_type ) ) {
			return apply_filters( 'wccs_product_pricing_discount_value_html', '' );
		}

		if ( 'percentage_discount' === $discount_type ) {
			return apply_filters( 'wccs_product_pricing_discount_value_html', $discount . '%' );
		}

		return apply_filters( 'wccs_product_pricing_discount_value_html', '' );
	}

	public function bulk_pricing_table() {
		$bulks = $this->get_bulk_pricings();

		if ( ! empty( $bulks ) ) {
            $settings       = WCCS()->settings;
            $view           = $settings->get_setting( 'quantity_table_layout', 'bulk-pricing-table-vertical' );
			$exclude_rules  = $this->pricing->get_exclude_rules();
			$table_title    = __( 'Discount per Quantity', 'easy-woocommerce-discounts' );
			$price_label    = __( 'Price', 'easy-woocommerce-discounts' ); 
			$discount_label = __( 'Discount', 'easy-woocommerce-discounts' );
			$quantity_label = __( 'Quantity', 'easy-woocommerce-discounts' );
			if ( (int) $settings->get_setting( 'localization_enabled', 1 ) ) {
				$table_title    = $settings->get_setting( 'quantity_table_title', $table_title );
				$price_label    = $settings->get_setting( 'price_label', $price_label ); 
				$discount_label = $settings->get_setting( 'discount_label', $discount_label );
				$quantity_label = $settings->get_setting( 'quantity_label', $quantity_label );
			}

            $cache_args = array(
                'product_id'     => $this->product_id,
				'parent_id'      => $this->parent_id,
				'price_html'     => WCCS()->product_helpers->wc_get_price_html( $this->product ),
                'rules'          => $bulks,
                'exclude_rules'  => $exclude_rules,
                'view'           => $view,
                'table_title'    => $table_title,
                'quantity_label' => $quantity_label,
                'price_label'    => $price_label,
                'discount_label' => $discount_label,
                'variation'      => 'variation' === $this->product_type ? $this->product_id : '',
            );
            $cache = WCCS()->WCCS_Product_Quantity_Table_Cache->get_quantity_table( $cache_args );
            if ( false !== $cache ) {
                if ( ! empty( $cache ) ) {
                    echo apply_filters( 'wccs_product_pricing_bulk_pricing_table', $cache, $this );
                }
            } else {
				if ( $this->is_in_exclude_rules() ) {
					WCCS()->WCCS_Product_Quantity_Table_Cache->set_quantity_table( $cache_args, '' );
					return;
				}

				$table = '';
				foreach ( $bulks as $discount ) {
					ob_start();
					$this->render_view(
						"product-pricing.$view",
						array(
							'controller'     => $this,
							'discount'       => $discount,
							'table_title'    => $settings->get_setting( 'quantity_table_title', 'Discount per Quantity' ),
							'quantity_label' => $settings->get_setting( 'quantity_label', 'Quantity' ),
							'price_label'    => $settings->get_setting( 'price_label', 'Price' ),
							'discount_label' => $settings->get_setting( 'discount_label', 'Discount' ),
							'variation'      => 'variation' === $this->product_type ? $this->product_id : '',
						)
					);
					$table .= ob_get_clean();
				}

				WCCS()->WCCS_Product_Quantity_Table_Cache->set_quantity_table( $cache_args, $table );

				echo apply_filters( 'wccs_product_pricing_bulk_pricing_table', $table, $this );
            }
		}

		if ( 'variable' === $this->product_type ) {
			add_filter( 'woocommerce_show_variation_price', '__return_false', 100 );
			$variations = $this->product->get_available_variations();
			remove_filter( 'woocommerce_show_variation_price', '__return_false', 100 );
			if ( ! empty( $variations ) ) {
				foreach ( $variations as $variation ) {
					$variation_pricing = new WCCS_Public_Product_Pricing( $variation['variation_id'], $this->pricing, $this->apply_method );
					$variation_pricing->bulk_pricing_table();
				}
			}
		}
	}

	public function get_bulk_pricings() {
		if ( isset( $this->bulk_pricings ) ) {
			return $this->bulk_pricings;
		}

		$bulks = $this->pricing->get_bulk_pricings();
		if ( empty( $bulks ) ) {
			$this->bulk_pricings = array();
			return array();
		}

		$pricings = array();
		foreach ( $bulks as $pricing_id => $pricing ) {
			if ( ! WCCS()->WCCS_Product_Validator->is_valid_product( $pricing['items'], $this->parent_id, ( 'variation' === $this->product_type ? $this->product_id : 0 ), array() ) ) {
				continue;
			}

			if ( ! empty( $pricing['exclude_items'] ) && WCCS()->WCCS_Product_Validator->is_valid_product( $pricing['exclude_items'], $this->parent_id, ( 'variation' === $this->product_type ? $this->product_id : 0 ), array() ) ) {
				continue;
			}

			$pricings[ $pricing_id ] = $pricing;
		}

		if ( ! empty( $pricings ) ) {
			usort( $pricings, array( WCCS()->WCCS_Sorting, 'sort_by_order_asc' ) );
			$pricings = $this->pricing->rules_filter->by_apply_mode( $pricings );
		}

		$this->bulk_pricings = $pricings;
		return $pricings;
	}

	protected function is_in_exclude_rules() {
		if ( isset( $this->is_in_excludes ) ) {
			return $this->is_in_excludes;
		}

		if ( $this->pricing->is_in_exclude_rules( $this->parent_id, ( 'variation' === $this->product_type ? $this->product_id : 0 ), array() ) ) {
			$this->is_in_excludes = true;
			return true;
		}

		$this->is_in_excludes = false;
		return false;
	}

}
