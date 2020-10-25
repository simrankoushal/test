<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCCS_Public_Shipping_Hooks extends WCCS_Public_Controller {

    protected $loader;

    public function __construct( WCCS_Loader $loader ) {
        $this->loader = $loader;
        add_filter( 'woocommerce_shipping_methods', array( &$this, 'shipping_methods' ) );
        if ( 'yes' === WCCS()->settings->get_setting( 'hide_on_free_shipping', 'no' ) ) {
            add_filter( 'woocommerce_package_rates', array( &$this, 'hide_on_free_shipping' ) );
        }
    }

    /**
     * Hook method to edit WooCommerce shipping methods.
     * Add the plugin shipping method class to the WooCommerce shipping methods.
     * 
     * @since  4.0.0
     * 
     * @param  array $shipping_methods
     * 
     * @return array
     */
    public function shipping_methods( $shipping_methods ) {
        $shipping_methods['dynamic_shipping'] = 'WCCS_Shipping_Method';
        return $shipping_methods;
    }

    /**
     * When a free shipping method is available hide other shipping methods.
     * 
     * @since  4.0.0
     * 
     * @param  array $rates
     * 
     * @return array
     */
    public function hide_on_free_shipping( $rates ) {
        if ( empty( $rates ) || ! in_array( 0, wp_list_pluck( $rates, 'cost' ) ) ) {
            return $rates;
        }

        foreach ( $rates as $key => $value ) {
            if ( 0 != $value->cost ) {
                unset( $rates[ $key ] );
            }
        }

        return $rates;
    }

}
