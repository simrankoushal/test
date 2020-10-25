<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The plugin compatibilities manager.
 *
 * @since 3.5.0
 */
class WCCS_Compatibilities {

    /**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.5.0
	 * @access   protected
	 * @var      WCCS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	private $loader;

	/**
	 * Service container of the plugin.
	 *
	 * @since 3.5.0
	 *
	 * @var   WCCS_Service_Manager
	 */
    private $services;

    public function __construct( WCCS_Loader $loader, WCCS_Service_Manager $services ) {
        $this->loader   = $loader;
        $this->services = $services;
    }

    public function init() {
		// Extra Product Options compatibility.
        if ( class_exists( 'TM_Extra_Product_Options' ) || class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-tm-epo.php';
			$compatibility = new WCCS_Compatibility_TM_EPO( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_TM_EPO', $compatibility );
		}

		// YITH WooCommerce Product Add-ons compatibility.
		if ( function_exists( 'YITH_WAPO' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-yith-wapo.php';
			$compatibility = new WCCS_Compatibility_Yith_WAPO( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_Yith_WAPO', $compatibility );
		}

		// WooCommerce Product Add-Ons compatibility.
		if ( class_exists( 'WC_Product_Addons' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-product-addons.php';
			$compatibility = new WCCS_Compatibility_Product_Addons( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_Product_Addons', $compatibility );
		}

		// WooCommerce Subscriptions compatibility.
		if ( class_exists( 'WC_Subscriptions' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-wc-subscriptions.php';
			$compatibility = new WCCS_Compatibility_WC_Subscriptions( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_WC_Subscriptions', $compatibility );
		}

		// Booster for WooCommerce compatibility.
		if ( function_exists( 'WCJ' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-booster-wc.php';
			$compatibility = new WCCS_Compatibility_Booster_WC( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_Booster_WC', $compatibility );
		}

		// WooCommerce Currency Switcher(WOOCS) compatibility.
		if ( class_exists( 'WOOCS_STARTER' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-woocs.php';
			$compatibility = new WCCS_Compatibility_WOOCS( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_WOOCS', $compatibility );
		}

		// WooCommerce Bookings compatibility.
		if ( class_exists( 'WC_Bookings' ) ) {
			require_once dirname( __FILE__ ) . '/compatibility/class-wccs-compatibility-wc-bookings.php';
			$compatibility = new WCCS_Compatibility_WC_Bookings( $this->loader );
			$compatibility->init();
			$this->services->set( 'WCCS_Compatibility_WC_Bookings', $compatibility );
		}
    }

}
