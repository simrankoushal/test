<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Easy WooCommerce Discounts compatibility with WooCommerce Currency Switcher(WOOCS).
 *
 * @since 4.3.0
 */
class WCCS_Compatibility_WOOCS {

    protected $woocs;
    
    protected $loader;

    protected $disabled_price_hooks;

    public function __construct( WCCS_Loader $loader ) {
        $this->loader = $loader;
        $this->woocs  = $GLOBALS['WOOCS'] ? $GLOBALS['WOOCS'] : null;
    }

    public function init() {
        if ( ! $this->woocs ) {
            return;
        }

        // If multiple currency allowed in WOOCS.
        if ( $this->woocs->is_multiple_allowed ) {
            $this->loader->add_action( 'wccs_public_product_pricing_before_get_base_price', $this, 'disable_price_hook' );
            $this->loader->add_action( 'wccs_public_product_pricing_after_get_base_price', $this, 'enable_price_hook' );
            $this->loader->add_action( 'wccs_public_cart_item_pricing_before_get_price', $this, 'disable_price_hook' );
            $this->loader->add_action( 'wccs_public_cart_item_pricing_after_get_price', $this, 'enable_price_hook' );
            $this->loader->add_action( 'wccs_public_pricing_hooks_before_apply_pricings', $this, 'disable_price_hook' );
            $this->loader->add_action( 'wccs_public_pricing_hooks_after_apply_pricings', $this, 'enable_price_hook' );
            $this->loader->add_action( 'wccs_public_product_pricing_before_get_discounted_price', $this, 'disable_price_hook' );
            $this->loader->add_action( 'wccs_public_product_pricing_after_get_discounted_price', $this, 'enable_price_hook' );
            $this->loader->add_action( 'wccs_public_product_pricing_before_get_price_html', $this, 'before_get_price_html' );
            $this->loader->add_action( 'wccs_public_product_pricing_after_get_price_html', $this, 'after_get_price_html' );
            $this->loader->add_filter( 'wccs_cart_discount_coupon_amount', $this, 'get_coupon_amount', 10, 2 );
            $this->loader->add_filter( 'wccs_cart_item_price_before_discounted_price', $this, 'cart_item_price_before_discounted_price', 100, 2 );
            $this->loader->add_filter( 'wccs_cart_item_price_prices_price', $this, 'cart_item_price_prices_price', 100, 2 );
            $this->loader->add_filter( 'wccs_public_product_pricing_get_discounted_price_product', $this, 'change_price', 100, 2 );
            $this->loader->add_filter( 'wccs_public_product_pricing_get_discounted_price_variation', $this, 'change_price', 100, 2 );
        }
    }

    public function disable_price_hook() {
        if ( isset( $_REQUEST['woocs_block_price_hook'] ) ) {
            return;
        }

        $_REQUEST['woocs_block_price_hook'] = 1;
        $this->disabled_price_hooks         = current_filter();
    }

    public function enable_price_hook() {
        if ( ! $this->disabled_price_hooks ) {
            return;
        } elseif ( $this->disabled_price_hooks !== str_replace( 'after', 'before', current_filter() ) ) {
            return;
        }

        unset( $_REQUEST['woocs_block_price_hook'] );
    }

    public function before_get_price_html( $product_pricing ) {
        if ( 'variable' !== $product_pricing->product_type ) {
            return;
        }
        if ( ! is_callable( array( $this->woocs, 'woocommerce_get_variation_prices_hash' ) ) ) {
            return;
        }  
        remove_filter( 'woocommerce_get_variation_prices_hash', array( $this->woocs, 'woocommerce_get_variation_prices_hash' ), 9999 );
    }

    public function after_get_price_html( $product_pricing ) {
        if ( 'variable' !== $product_pricing->product_type ) {
            return;
        }
        if ( ! is_callable( array( $this->woocs, 'woocommerce_get_variation_prices_hash' ) ) ) {
            return;
        } 
        add_filter( 'woocommerce_get_variation_prices_hash', array( $this->woocs, 'woocommerce_get_variation_prices_hash' ), 9999, 3 );
    }

    public function get_coupon_amount( $amount, $discount ) {
        if ( $this->woocs->current_currency == $this->woocs->default_currency ) {
            return $amount;
        } elseif ( 'price' !== $discount->discount_type && 'price_discount_per_item' !== $discount->discount_type ) {
            return $amount;
        }

        return $this->exchange_price( $amount );
    }

    public function cart_item_price_before_discounted_price( $before_discounted_price, $cart_item ) {
        if ( ! isset( $cart_item['_wccs_main_display_price'] ) || ! $this->woocs->is_multiple_allowed ) {
            return $before_discounted_price;
        }
        return wc_price( $this->exchange_price( $cart_item['_wccs_main_display_price'] ) );
    }

    public function cart_item_price_prices_price( $formated_price, $price ) {
        return wc_price( $this->exchange_price( $price ) );
    }

    public function change_price( $price, $product ) {
        return $this->exchange_price( $price );
    }

    public function exchange_price( $price ) {
        return $this->woocs->woocs_exchange_value( $price );
    }

}
