<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCCS_Compatibility_WC_Subscriptions {

    protected $loader;

    public function __construct( WCCS_Loader $loader ) {
        $this->loader = $loader;
    }

    public function init() {
        $this->loader->add_filter( 'wccs_should_apply_pricing', $this, 'should_apply_pricing' );
    }

    public function should_apply_pricing( $apply_pricing ) {
        if ( ! is_callable( array( 'WC_Subscriptions_Cart', 'get_calculation_type' ) ) ) {
            return $apply_pricing;
        }

        if ( $apply_pricing && 'recurring_total' === WC_Subscriptions_Cart::get_calculation_type() ) {
            return false;
        }

        return $apply_pricing;
    }

}
