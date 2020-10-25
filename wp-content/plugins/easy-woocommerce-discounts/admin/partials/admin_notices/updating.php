<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p><strong><?php _e( 'WooCommerce Dynamic Pricing & Discounts data update', 'easy-woocommerce-discounts' ); ?></strong> &#8211; <?php _e( 'Your database is being updated in the background.', 'easy-woocommerce-discounts' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_asnp_wccs', 'true', admin_url( 'admin.php?page=wccs-settings' ) ) ); ?>"><?php _e( 'Taking a while? Click here to run it now.', 'easy-woocommerce-discounts' ); ?></a></p>
</div>
