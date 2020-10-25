<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Updates {

	public static function clear_pricing_caches() {
		WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
	}

	public static function update_110_conditions() {
		global $wpdb;

		$condition_meta = WCCS()->condition_meta;

		// Adding apply_mode for pricing and cart_discount conditions.
		$results = $wpdb->get_col( "SELECT `id` FROM {$wpdb->prefix}wccs_conditions WHERE `type` IN ('pricing','cart-discount') AND `id` NOT IN ( SELECT DISTINCT `wccs_condition_id` FROM {$wpdb->prefix}wccs_condition_meta WHERE `meta_key` = 'apply_mode' )" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $condition_id ) {
				$condition_meta->update_meta( $condition_id, 'apply_mode', 'all' );
			}
		}

		// Adding exclude_items for pricing conditions.
		$results = $wpdb->get_col( "SELECT `id` FROM {$wpdb->prefix}wccs_conditions WHERE `type` = 'pricing' AND `id` NOT IN ( SELECT DISTINCT `wccs_condition_id` FROM {$wpdb->prefix}wccs_condition_meta WHERE `meta_key` = 'exclude_items' )" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $condition_id ) {
				$condition_meta->update_meta( $condition_id, 'exclude_items', array() );
			}
		}

		// Adding private_note for cart_discount conditions.
		$results = $wpdb->get_col( "SELECT `id` FROM {$wpdb->prefix}wccs_conditions WHERE `type` = 'cart-discount' AND `id` NOT IN ( SELECT DISTINCT `wccs_condition_id` FROM {$wpdb->prefix}wccs_condition_meta WHERE `meta_key` = 'private_note' )" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $condition_id ) {
				$condition_meta->update_meta( $condition_id, 'private_note', '' );
			}
		}

		// Adding date_times_match_mode to conditions.
		$results = $wpdb->get_col( "SELECT `id` FROM {$wpdb->prefix}wccs_conditions WHERE `type` IN ('pricing','cart-discount','products-list') AND `id` NOT IN ( SELECT DISTINCT `wccs_condition_id` FROM {$wpdb->prefix}wccs_condition_meta WHERE `meta_key` = 'date_times_match_mode' )" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $condition_id ) {
				$condition_meta->update_meta( $condition_id, 'date_times_match_mode', 'one' );
			}
		}

		// Adding conditions_match_mode to conditions.
		$results = $wpdb->get_col( "SELECT `id` FROM {$wpdb->prefix}wccs_conditions WHERE `type` IN ('pricing','cart-discount','products-list') AND `id` NOT IN ( SELECT DISTINCT `wccs_condition_id` FROM {$wpdb->prefix}wccs_condition_meta WHERE `meta_key` = 'conditions_match_mode' )" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $condition_id ) {
				$condition_meta->update_meta( $condition_id, 'conditions_match_mode', 'all' );
			}
		}

		// Updating pricing products to adding quantity.
		$results = $wpdb->get_results( "SELECT `conditions`.id, `conditions_meta`.meta_value FROM {$wpdb->prefix}wccs_conditions AS `conditions` JOIN {$wpdb->prefix}wccs_condition_meta AS `conditions_meta` ON `conditions`.id = `conditions_meta`.wccs_condition_id WHERE `conditions`.type = 'pricing' AND `conditions_meta`.meta_key = 'items' AND `conditions_meta`.meta_value != ''" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( ! empty( $result->meta_value ) ) {
					$update = false;
					$items = maybe_unserialize( $result->meta_value );
					foreach ( $items as &$item ) {
						if ( ! isset( $item['quantity'] ) ) {
							$update = true;
							$item['quantity'] = '';
						}
					}
					unset( $item );

					if ( $update ) {
						$condition_meta->update_meta( $result->id, 'items', $items );
					}
				}
			}
		}
	}

	public static function update_110_db_version() {
		WCCS_Activator::update_db_version( '1.1.0' );
	}

	public static function update_301() {
		WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
	}

	public static function update_460() {
		WCCS()->WCCS_Clear_Cache->clear_pricing_caches();

		if ( wp_using_ext_object_cache() ) {
            return;
		}
		
		global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_wccs-product-' ) . '%',
                $wpdb->esc_like( '_transient_timeout_wccs-product-' ) . '%'
            )
        );
	}

}
