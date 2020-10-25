<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Admin_Select_Data_Provider {

	public function get_products( array $args = array() ) {
		$args = wp_parse_args( $args, array( 'limit' => -1 ) );
		if ( ! empty( $args['post_title'] ) ) {
			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( wc_clean( wp_unslash( $args['post_title'] ) ), '', false, true );
			if ( ! empty( $ids ) ) {
				$args['include'] = $ids;
				if ( isset( $args['post_id'] ) ) {
					unset( $args['post_id'] );
				}
			}
			unset( $args['post_title'] );
		}

		if ( empty( $args['include'] ) && empty( $args['post_id']  ) ) {
			return array();
		}

		$products = WCCS()->products->get_products( $args );
		if ( empty( $products ) ) {
			return array();
		}

		return $this->prepare_product_select( $products );
	}

	public function get_variations( array $args = array() ) {
		$args = wp_parse_args( $args, array( 'type' => 'variation', 'limit' => -1 ) );
		if ( ! empty( $args['post_title'] ) ) {
			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( wc_clean( wp_unslash( $args['post_title'] ) ), '', true, true );
			if ( ! empty( $ids ) ) {
				$args['include'] = $ids;
				if ( isset( $args['post_id'] ) ) {
					unset( $args['post_id'] );
				}
			}
			unset( $args['post_title'] );
		}

		if ( empty( $args['include'] ) && empty( $args['post_id']  ) ) {
			return array();
		}

		$products = WCCS()->products->get_products( $args );
		if ( empty( $products ) ) {
			return array();
		}

		return $this->prepare_product_select( $products, true );
	}

	protected function prepare_product_select( array $products, $variation = false ) {
		$products_select = array();
		foreach ( $products as $product ) {
			if ( $product->get_sku() ) {
				$identifier = $product->get_sku();
			} else {
				$identifier = '#' . $product->get_id();
			}

			if ( $variation ) {
				$formatted_variation_list = wc_get_formatted_variation( $product, true );
				$text = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() ) . ' ' . $formatted_variation_list;
			} else {
				$text = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() );
			}

			$products_select[] = (object) array(
				'id'   => $product->get_id(),
				'text' => $text,
			);
		}

		return $products_select;
	}

}
