<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCCS_Admin_Conditions_Hooks {

    public function __construct( WCCS_Loader $loader ) {
        $this->loader = $loader;
    }

    public function enable_hooks() {
        $this->loader->add_action( 'wccs_condition_added', $this, 'condition_added' );
        $this->loader->add_action( 'wccs_condition_deleted', $this, 'condition_deleted' );
        $this->loader->add_action( 'wccs_condition_updated', $this, 'condition_updated' );
        $this->loader->add_action( 'wccs_conditions_ordering_updated', $this, 'conditions_ordering_updated' );
        $this->loader->add_action( 'wccs_condition_duplicated', $this, 'condition_duplicated' );
    }

    public function condition_added( $condition ) {
        if ( ! $condition || ! $condition->id ) {
            return;
        }

        if ( 'pricing' === $condition->type ) {
            WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
        }
    }

    public function condition_deleted( $condition ) {
        if ( ! $condition || ! $condition->id ) {
            return;
        }

        if ( 'pricing' === $condition->type ) {
            WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
        }
    }

    public function condition_updated( $condition ) {
        if ( ! $condition || ! $condition->id ) {
            return;
        }

        if ( 'pricing' === $condition->type ) {
            WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
        }
    }

    public function condition_duplicated( $condition_id ) {
        if ( ! $condition_id ) {
            return;
        }

        $condition = WCCS()->conditions->get_condition( $condition_id );
        if ( 'pricing' === $condition->type ) {
            WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
        }
    }

    public function conditions_ordering_updated( $type ) {
        if ( 'pricing' === $type ) {
            WCCS()->WCCS_Clear_Cache->clear_pricing_caches();
        }
    }

}
