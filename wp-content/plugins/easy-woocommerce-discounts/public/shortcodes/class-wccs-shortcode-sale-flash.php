<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Shortcode_Sale_Flash {

	public function output( $atts, $content = null ) {
                ob_start();
                $this->render_view( 'product-pricing.sale-flash', array( 'controller' => $this ) );
                return ob_get_clean();
	}

}
