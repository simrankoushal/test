<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post, $product;

if ( ! $product || $product->is_on_sale() ) {
    return;
}

// Getting sale badge except of simple pricing rules.
$sale_badge = WCCS()->settings->get_setting( 'sale_badge', array() );
unset( $sale_badge['simple'] );
if ( empty( $sale_badge ) ) {
    return;
}

// If product has any pricing rule except simple rules show sale tag.
if ( WCCS()->WCCS_Product_Onsale_Cache->is_onsale( $product, $sale_badge ) ) {
    echo apply_filters( 'wccs_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product );
}
