<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCS_Admin_Assets {

	protected $loader;

	protected $scripts = array();

	protected $styles = array();

	protected $localize_scripts = array();

	protected $menu;

	public function __construct( WCCS_Loader $loader, WCCS_Admin_Menu $menu ) {
		$this->loader = $loader;
		$this->menu   = $menu;
	}

	public function init_hooks() {
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'load_scripts' );
	}

	protected function get_asset_url( $path ) {
		return apply_filters( 'wccs_get_admin_asset_url', plugins_url( $path, WCCS_PLUGIN_FILE ), $path );
	}

	protected function register_script( $handle, $path, $deps = array( 'jquery' ), $version = WCCS_VERSION, $in_footer = true ) {
		$this->scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	protected function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = WCCS_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, $this->scripts ) && $path ) {
			$this->register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	protected function register_style( $handle, $path, $deps = array(), $version = WCCS_VERSION, $media = 'all', $has_rtl = false ) {
		$this->styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	protected function enqueue_style( $handle, $path = '', $deps = array(), $version = WCCS_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, $this->styles ) && $path ) {
			$this->register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	public function load_scripts() {
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$menus     = $this->menu->get_menus();

		if ( in_array( $screen_id, array_values( $menus ) ) ) {
			$this->enqueue_style( 'wccs-admin', $this->get_asset_url( 'admin/css/wccs-admin' . $suffix . '.css' ) );
		}

		$this->enqueue_style( 'wp-color-picker' );

		if ( isset( $menus['wc_conditions'] ) && $menus['wc_conditions'] === $screen_id ) {
			$this->enqueue_style( 'select2', $this->get_asset_url( 'admin/css/select2/select2' . $suffix . '.css' ), array(), '4.0.3' );
			$this->enqueue_style( 'wc-conditions', $this->get_asset_url( 'admin/css/conditions' . $suffix . '.css' ) );
			$this->enqueue_style( 'wccs-font-awesome', $this->get_asset_url( 'admin/css/font-awesome' . $suffix . '.css' ), array(), '4.6.3' );
			$this->register_script( 'select2', $this->get_asset_url( 'admin/js/select2/select2' . $suffix . '.js' ), array( 'jquery' ), '4.0.3' );
			$this->enqueue_script( 'wc-conditions', $this->get_asset_url( 'admin/js/conditions' . $suffix . '.js' ), array( 'select2', 'wp-color-picker' ), WCCS_VERSION, true );
		} else if ( isset( $menus['settings'] ) && $menus['settings'] === $screen_id ) {
			$this->enqueue_script( 'wccs-admin', $this->get_asset_url( 'admin/js/wccs-admin' . $suffix . '.js' ), array( 'wp-color-picker' ), WCCS_VERSION, true );
		}

		$this->localize_scripts();
	}

	protected function localize_script( $handle ) {
		if ( ! in_array( $handle, $this->localize_scripts ) && wp_script_is( $handle ) && ( $data = $this->get_script_data( $handle ) ) ) {
			$name                        = 'wc-conditions' === $handle ? 'wcConditions' : str_replace( '-', '_', $handle ) . '_params';
			$this->localize_scripts[]    = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	protected function get_script_data( $handle ) {
		switch ( $handle ) {
			case 'wc-conditions' :
				$wccs        = WCCS();
				$wc_products = $wccs->products;
				$select_data = new WCCS_Admin_Select_Data_Provider();

				return array(
					'strings' => array(
						'products'  => __( 'Products', 'easy-woocommerce-discounts' ),
						'variations' => __( 'Variations', 'easy-woocommerce-discounts' ),
						'discounts' => __( 'Discounts', 'easy-woocommerce-discounts' ),
						'all_products' => __( 'All products', 'easy-woocommerce-discounts' ),
						'products_in_list' => __( 'Products in list', 'easy-woocommerce-discounts' ),
						'products_not_in_list' => __( 'Products not in list', 'easy-woocommerce-discounts' ),
						'product_variations_in_list' => __( 'Product variations in list', 'easy-woocommerce-discounts' ),
						'product_variations_not_in_list' => __( 'Product variations not in list', 'easy-woocommerce-discounts' ),
						'one_of_products_in_list' => __( 'One of products in list', 'easy-woocommerce-discounts' ),
						'all_of_products_in_list' => __( 'All of products in list', 'easy-woocommerce-discounts' ),
						'products_have_tags' => __( 'Products have tags', 'easy-woocommerce-discounts' ),
						'products_added' => __( 'Products added', 'easy-woocommerce-discounts' ),
						'similar_products_to_customer_bought_products' => __( 'Similar products to customer bought products', 'easy-woocommerce-discounts' ),
						'similar_products_to_customer_cart_products' => __( 'Similar products to customer cart products', 'easy-woocommerce-discounts' ),
						'all_categories' => __( 'All categories', 'easy-woocommerce-discounts' ),
						'categories_in_list' => __( 'Categories in list', 'easy-woocommerce-discounts' ),
						'one_of_categories_in_list' => __( 'One of categories in list', 'easy-woocommerce-discounts' ),
						'all_of_categories_in_list' => __( 'All of categories in list', 'easy-woocommerce-discounts' ),
						'categories_not_in_list' => __( 'Categories not in list', 'easy-woocommerce-discounts' ),
						'customers' => __( 'Customers', 'easy-woocommerce-discounts' ),
						'customer' => __( 'Customer', 'easy-woocommerce-discounts' ),
						'all_customers' => __( 'All customers', 'easy-woocommerce-discounts' ),
						'customers_in_list' => __( 'Customers in list', 'easy-woocommerce-discounts' ),
						'customers_not_in_list' => __( 'Customers not in list', 'easy-woocommerce-discounts' ),
						'cart_products' => __( 'Cart products', 'easy-woocommerce-discounts' ),
						'roles' => __( 'Roles', 'easy-woocommerce-discounts' ),
						'all_roles' => __( 'All roles', 'easy-woocommerce-discounts' ),
						'roles_in_list' => __( 'Roles in list', 'easy-woocommerce-discounts' ),
						'roles_not_in_list' => __( 'Roles not in list', 'easy-woocommerce-discounts' ),
						'all_roles_buyed' => __( 'All roles buyed', 'easy-woocommerce-discounts' ),
						'all_roles_not_buyed' => __( 'All roles not buyed', 'easy-woocommerce-discounts' ),
						'add' => __( 'Add', 'easy-woocommerce-discounts' ),
						'add_new' => __( 'Add New', 'easy-woocommerce-discounts' ),
						'value' => __( 'Value', 'easy-woocommerce-discounts' ),
						'items' => __( 'Items', 'easy-woocommerce-discounts' ),
						'discounted_products' => __( 'Discounted Products', 'easy-woocommerce-discounts' ),
						'products_group' => __( 'Products Group', 'easy-woocommerce-discounts' ),
						'item' => __( 'Item', 'easy-woocommerce-discounts' ),
						'action' => __( 'Action', 'easy-woocommerce-discounts' ),
						'actions' => __( 'Actions', 'easy-woocommerce-discounts' ),
						'show' => __( 'Show', 'easy-woocommerce-discounts' ),
						'dont_show' => __( "Don't show", 'easy-woocommerce-discounts' ),
						'name' => __( 'Name', 'easy-woocommerce-discounts' ),
						'add_new_condition' => __( 'Add New Condition', 'easy-woocommerce-discounts' ),
						'conditions' => __( 'Conditions', 'easy-woocommerce-discounts' ),
						'categories' => __( 'Categories', 'easy-woocommerce-discounts' ),
						'tags' => __( 'Tags', 'easy-woocommerce-discounts' ),
						'to' => __( 'To', 'easy-woocommerce-discounts' ),
						'buyed' => __( 'Buyed', 'easy-woocommerce-discounts' ),
						'not_buyed' => __( 'Not Buyed', 'easy-woocommerce-discounts' ),
						'buyed_products' => __( 'Buyed products', 'easy-woocommerce-discounts' ),
						'not_buyed_products' => __( 'Not Buyed products', 'easy-woocommerce-discounts' ),
						'buyed_categories' => __( 'Buyed categories', 'easy-woocommerce-discounts' ),
						'not_buyed_categories' => __( 'Not Buyed categories', 'easy-woocommerce-discounts' ),
						'save' => __( 'Save', 'easy-woocommerce-discounts' ),
						'cancel' => __( 'Cancel', 'easy-woocommerce-discounts' ),
						'edit' => __( 'Edit', 'easy-woocommerce-discounts' ),
						'delete' => __( 'Delete', 'easy-woocommerce-discounts' ),
						'period' => __( 'Period', 'easy-woocommerce-discounts' ),
						'today' => __( 'Today', 'easy-woocommerce-discounts' ),
						'this_week' => __( 'This week', 'easy-woocommerce-discounts' ),
						'this_month' => __( 'This month', 'easy-woocommerce-discounts' ),
						'last_month' => __( 'Last month', 'easy-woocommerce-discounts' ),
						'this_year' => __( 'This year', 'easy-woocommerce-discounts' ),
						'limit' => __( 'Limit', 'easy-woocommerce-discounts' ),
						'top_seller_products' => __( 'Top seller products', 'easy-woocommerce-discounts' ),
						'top_earner_products' => __( 'Top earner products', 'easy-woocommerce-discounts' ),
						'top_free_products' => __( 'Top free products', 'easy-woocommerce-discounts' ),
						'featured_products' => __( 'Featured products', 'easy-woocommerce-discounts' ),
						'onsale_products' => __( 'On-sale products', 'easy-woocommerce-discounts' ),
						'top_rated_products' => __( 'Top rated products', 'easy-woocommerce-discounts' ),
						'recently_viewed_products' => __( 'Recently viewed products', 'easy-woocommerce-discounts' ),
						'start_date' => __( 'Start date', 'easy-woocommerce-discounts' ),
						'end_date' => __( 'End date', 'easy-woocommerce-discounts' ),
						'discount_type' => __( 'Discount type', 'easy-woocommerce-discounts' ),
						'percentage_discount' => __( 'Percentage discount', 'easy-woocommerce-discounts' ),
						'price_discount' => __( 'Price discount', 'easy-woocommerce-discounts' ),
						'discount_amount' => __( 'Discount Amount', 'easy-woocommerce-discounts' ),
						'number_of_cart_items' => __( 'Number of cart items', 'easy-woocommerce-discounts' ),
						'subtotal_including_tax' => __( 'Subtotal including tax', 'easy-woocommerce-discounts' ),
						'subtotal_excluding_tax' => __( 'Subtotal excluding tax', 'easy-woocommerce-discounts' ),
						'quantity_of_cart_items' => __( 'Quantity of cart items', 'easy-woocommerce-discounts' ),
						'cart_categories' => __( 'Cart categories', 'easy-woocommerce-discounts' ),
						'condition' => __( 'Condition', 'easy-woocommerce-discounts' ),
						'pricing' => __( 'Pricing', 'easy-woocommerce-discounts' ),
						'simple' => __( 'Simple', 'easy-woocommerce-discounts' ),
						'bulk' => __( 'Bulk', 'easy-woocommerce-discounts' ),
						'tiered' => __( 'Tiered', 'easy-woocommerce-discounts' ),
						'purchase_x_receive_y' => __( 'Purchase x Receive y', 'easy-woocommerce-discounts' ),
						'purchase_x_receive_y_same' => __( 'Purchase x Receive y - same products', 'easy-woocommerce-discounts' ),
						'add_new_discounted_item' => __( 'Add New Discounted Item', 'easy-woocommerce-discounts' ),
						'mode' => __( 'Mode', 'easy-woocommerce-discounts' ),
						'quantity_min' => __( 'Quantity minimum', 'easy-woocommerce-discounts' ),
						'quantity_max' => __( 'Quantity maximum', 'easy-woocommerce-discounts' ),
						'discount' => __( 'Discount', 'easy-woocommerce-discounts' ),
						'fixed_price' => __( 'Fixed price', 'easy-woocommerce-discounts' ),
						'quantities' => __( 'Quantities', 'easy-woocommerce-discounts' ),
						'quantity_based_on' => __( 'Quantity Based On', 'easy-woocommerce-discounts' ),
						'single_product' => __( 'Single product', 'easy-woocommerce-discounts' ),
						'single_product_variation' => __( 'Single product variation', 'easy-woocommerce-discounts' ),
						'cart_line_item' => __( 'Cart line item', 'easy-woocommerce-discounts' ),
						'quantity_by_category' => __( 'Sum of categories quantities', 'easy-woocommerce-discounts' ),
						'quantity_by_all_products' => __( 'Sum of all products quantities', 'easy-woocommerce-discounts' ),
						'purchase' => __( 'Purchase', 'easy-woocommerce-discounts' ),
						'receive' => __( 'Receive', 'easy-woocommerce-discounts' ),
						'purchase_rule' => __( 'Purchase Rule', 'easy-woocommerce-discounts' ),
						'add_new_purchased_item' => __( 'Add New Purchased Item', 'easy-woocommerce-discounts' ),
						'purchased_item' => __( 'Purchased item', 'easy-woocommerce-discounts' ),
						'purchased_items' => __( 'Purchased Items', 'easy-woocommerce-discounts' ),
						'type' => __( 'Type', 'easy-woocommerce-discounts' ),
						'start' => __( 'Start', 'easy-woocommerce-discounts' ),
						'end' => __( 'End', 'easy-woocommerce-discounts' ),
						'after' => __( 'After', 'easy-woocommerce-discounts' ),
						'before' => __( 'Before', 'easy-woocommerce-discounts' ),
						'date' => __( 'Date', 'easy-woocommerce-discounts' ),
						'date_time' => __( 'Date Time', 'easy-woocommerce-discounts' ),
						'specific_date' => __( 'Specific Date', 'easy-woocommerce-discounts' ),
						'time' => __( 'Time', 'easy-woocommerce-discounts' ),
						'days' => __( 'Days', 'easy-woocommerce-discounts' ),
						'add_new_date_time' => __( 'Add New Date Time', 'easy-woocommerce-discounts' ),
						'empty_datetimes' => __( 'There is not any date time.', 'easy-woocommerce-discounts' ),
						'empty_purchased_items' => __( 'There is not any purchased item.' , 'easy-woocommerce-discounts' ),
						'empty_items' => __( 'There is not any item.', 'easy-woocommerce-discounts' ),
						'empty_conditions' => __( 'There is not any condition.', 'easy-woocommerce-discounts' ),
						'info' => sprintf( __( 'Info%s', 'easy-woocommerce-discounts' ), '!' ),
						'warning' => sprintf( __( 'Warning%s', 'easy-woocommerce-discounts' ), '!' ),
						'status' => __( 'Status', 'easy-woocommerce-discounts' ),
						'enabled' => __( 'Enabled', 'easy-woocommerce-discounts' ),
						'disabled' => __( 'Disabled', 'easy-woocommerce-discounts' ),
						'discounted_products' => __( 'Discounted products', 'easy-woocommerce-discounts' ),
						'purchased_products_message' => __( 'Purchased Products Message', 'easy-woocommerce-discounts' ),
						'receive_products_message' => __( 'Receive Products Message', 'easy-woocommerce-discounts' ),
						'purchased_products_message_desc' => __( 'Message that will show in products page that are in discount purchased items.', 'easy-woocommerce-discounts' ),
						'receive_products_message_desc' => __( 'Message that will show in products page that will receive this discount.', 'easy-woocommerce-discounts' ),
						'repeat' => __( 'Repeat', 'easy-woocommerce-discounts' ),
						'save_changes_i18n' => __( 'Please save changes.', 'easy-woocommerce-discounts' ),
						'saved_successfully_i18n' => __( 'Saved successfully.', 'easy-woocommerce-discounts' ),
						'success_i18n' => sprintf( __( 'Success%s', 'easy-woocommerce-discounts' ), '!' ),
						'at_least_one_of' => __( 'At least one of', 'easy-woocommerce-discounts' ),
						'all_of' => __( 'All of', 'easy-woocommerce-discounts' ),
						'none_of' => __( 'None of', 'easy-woocommerce-discounts' ),
						'only' => __( 'Only', 'easy-woocommerce-discounts' ),
						'have' => __( 'Have', 'easy-woocommerce-discounts' ),
						'money_spent' => __( 'Money spent', 'easy-woocommerce-discounts' ),
						'time_type' => __( 'Time Type', 'easy-woocommerce-discounts' ),
						'current' => __( 'Current', 'easy-woocommerce-discounts' ),
						'all_time' => __( 'All time', 'easy-woocommerce-discounts' ),
						'day' => __( 'Day', 'easy-woocommerce-discounts' ),
						'week' => __( 'Week', 'easy-woocommerce-discounts' ),
						'month' => __( 'Month', 'easy-woocommerce-discounts' ),
						'year' => __( 'Year', 'easy-woocommerce-discounts' ),
						'days_ago' => __( 'Days ago', 'easy-woocommerce-discounts' ),
						'weeks_ago' => __( 'Weeks ago', 'easy-woocommerce-discounts' ),
						'months_ago' => __( 'Months ago', 'easy-woocommerce-discounts' ),
						'years_ago' => __( 'Years ago', 'easy-woocommerce-discounts' ),
						'after_days_ago' => __( 'After days ago', 'easy-woocommerce-discounts' ),
						'after_weeks_ago' => __( 'After weeks ago', 'easy-woocommerce-discounts' ),
						'after_months_ago' => __( 'After months ago', 'easy-woocommerce-discounts' ),
						'after_years_ago' => __( 'After years ago', 'easy-woocommerce-discounts' ),
						'cart' => __( 'Cart', 'easy-woocommerce-discounts' ),
						'bought' => __( 'Bought', 'easy-woocommerce-discounts' ),
						'select_type' => __( 'Select type', 'easy-woocommerce-discounts' ),
						'selected' => __( 'Selected', 'easy-woocommerce-discounts' ),
						'not_selected' => __( 'Not selected', 'easy-woocommerce-discounts' ),
						'products_in_cart' => __( 'Products in cart', 'easy-woocommerce-discounts' ),
						'product_variations_in_cart' => __( 'Product variations in cart', 'easy-woocommerce-discounts' ),
						'featured_products_in_cart' => __( 'Featured products in cart', 'easy-woocommerce-discounts' ),
						'onsale_products_in_cart' => __( 'Onsale products in cart', 'easy-woocommerce-discounts' ),
						'product_categories_in_cart' => __( 'Product categories in cart', 'easy-woocommerce-discounts' ),
						'product_tags_in_cart' => __( 'Product tags in cart', 'easy-woocommerce-discounts' ),
						'bought_products' => __( 'Bought products', 'easy-woocommerce-discounts' ),
						'bought_product_variations' => __( 'Bought variations', 'easy-woocommerce-discounts' ),
						'bought_product_categories' => __( 'Bought categories', 'easy-woocommerce-discounts' ),
						'bought_product_tags' => __( 'Bought product tags', 'easy-woocommerce-discounts' ),
						'bought_featured_products' => __( 'Bought featured products', 'easy-woocommerce-discounts' ),
						'bought_onsale_products' => __( 'Bought onsale products', 'easy-woocommerce-discounts' ),
						'is_logged_in' => __( 'Is logged in', 'easy-woocommerce-discounts' ),
						'number_of_orders' => __( 'Number of orders', 'easy-woocommerce-discounts' ),
						'less_than' => __( 'Less than', 'easy-woocommerce-discounts' ),
						'less_equal_to' => __( 'Less equal to', 'easy-woocommerce-discounts' ),
						'greater_than' => __( 'Greater than', 'easy-woocommerce-discounts' ),
						'greater_equal_to' => __( 'Greater equal to', 'easy-woocommerce-discounts' ),
						'equal_to' => __( 'Equal to', 'easy-woocommerce-discounts' ),
						'not_equal_to' => __( 'Not equal to', 'easy-woocommerce-discounts' ),
						'is' => __( 'Is', 'easy-woocommerce-discounts' ),
						'are' => __( 'Are', 'easy-woocommerce-discounts' ),
						'last_order_amount' => __( 'Last order amount', 'easy-woocommerce-discounts' ),
						'add_new_include_products' => __( 'Add new include products', 'easy-woocommerce-discounts' ),
						'add_new_exclude_products' => __( 'Add new exclude products', 'easy-woocommerce-discounts' ),
						'include_products' => __( 'Include products', 'easy-woocommerce-discounts' ),
						'exclude_products' => __( 'Exclude products', 'easy-woocommerce-discounts' ),
						'empty_products' => __( 'There is not any product', 'easy-woocommerce-discounts' ),
						'products_of' => __( 'Products of', 'easy-woocommerce-discounts' ),
						'empty_list' => __( 'There is not any item.', 'easy-woocommerce-discounts' ),
						'required_field' => __( 'Field is required.', 'easy-woocommerce-discounts' ),
						'field_right_value' => __( 'Please enter right value.', 'easy-woocommerce-discounts' ),
						'save_errors' => __( 'There are some errors in the form please fix them and save it again.', 'easy-woocommerce-discounts' ),
						'discount_amount_desc' => sprintf( __( 'Discount amount should be greater than %d.', 'easy-woocommerce-discounts' ), 0 ),
						'add_new_include_products_desc' => __( 'Add new products that will be included in products list result.', 'easy-woocommerce-discounts' ),
						'add_new_exclude_products_desc' => __( 'Add new products that will be excluded from results of products list.', 'easy-woocommerce-discounts' ),
						'add_new_date_time_desc' => __( 'Add new date time condition.', 'easy-woocommerce-discounts' ),
						'include_products_desc' => __( 'Products that will be included in products list result.', 'easy-woocommerce-discounts' ),
						'exclude_products_desc' => __( 'Products that will be excluded from results of products list.', 'easy-woocommerce-discounts' ),
						'products_view_date_time_desc' => __( 'At least one of below date time conditions should be approved to show products list result.', 'easy-woocommerce-discounts' ),
						'products_view_conditions_desc' => __( 'All of below conditions should be approved to show products list result.', 'easy-woocommerce-discounts' ),
						'discount_view_date_time_desc' => __( 'At least one of below date time conditions should be approved to apply discount to the cart.', 'easy-woocommerce-discounts' ),
						'discount_view_conditions_desc' => __( 'All of below conditions should be approved to apply discount to the cart.', 'easy-woocommerce-discounts' ),
						'add_new_purchased_item_desc' => __( 'Add new products that customer should add them to the cart to receive this discount on listed products in discounted items.', 'easy-woocommerce-discounts' ),
						'add_new_discounted_item_desc' => __( 'Add new products that this discount will apply to them.', 'easy-woocommerce-discounts' ),
						'purchased_items_desc' => __( 'List of products that customer should add them to the cart to receive this discount on listed products in discounted items.', 'easy-woocommerce-discounts' ),
						'discounted_items_desc' => __( 'List of products that will receive this discount.', 'easy-woocommerce-discounts' ),
						'exclude_products_desc' => __( 'List of products that will exclude from all rules.', 'easy-woocommerce-discounts' ),
						'pricing_view_date_time_desc' => __( 'At least one of below date time conditions should be approved to apply this discount to listed products in discounted items.', 'easy-woocommerce-discounts' ),
						'pricing_view_conditions_desc' => __( 'All of below conditions should be approved to apply this discount to listed products in discounted items.', 'easy-woocommerce-discounts' ),
						'pricing_view_quantities_desc' => sprintf (__( 'Adjusts discounted products price based on quantities added to the cart.%1$s Set quantities ranges and each range discount amount.%1$s Leave Quantity maximum empty to setting no limit for range.', 'easy-woocommerce-discounts' ), '<br>' ),
						'pricing_mode' => sprintf( __( 'Simple : Just a simple discount that will apply to listed discounted products.%1$s Bulk : Adjusts discounted products price based on quantities added to the cart - All units get the highest discount.%1$s Tiered : Adjusts discounted products price based on quantities added to the cart - Subsequent units get increasing discount.%1$s Products group : Adjusts specified products group price.%1$s Purchase X Receive Y : Apply discount to specified number of discounted items when customer adds specified number of listed purchased items to the cart.%1$s Purchase X Receive Y - same products : Same as Purchase X Receive Y but purchased and discounted products are same. Use this mode when purchase and receive products are same. %1$s Example 1 for Purchase X Receive Y : purchase two number of the product to get one free.%1$s Example 2 for Purchase X Receive Y : purchase product X to get %2$s discount on product Y.%1$s Exclude products from all rules : excludes selected products from all of pricing rules.%1$s', 'easy-woocommerce-discounts' ), '<br>', '50%' ),
						'quantity_based_on_desc' => sprintf( __( 'Single product : Quantity is calculated separately for each product.%1$s Single product variation : Quantity is calculated separately for each product variation, for simple products quantity will calculated based on product.%1$s Cart line item : Quantity is calculated separately for each product line in the cart.%1$s Sum of categories quantities : Quantity is calculated separately for each category in the cart.%1$s Sum of all products quantities : Quantity is calculated based on sum of all quantities in the cart.', 'easy-woocommerce-discounts' ), '<br>' ),
						'pricing_view_repeat_desc' => __( 'Repeat means that this discount will be applied more than once when possible.', 'easy-woocommerce-discounts' ),
						'products_group_repeat_desc' => __( 'Apply this rule again if more than one matching group added to the cart.', 'easy-woocommerce-discounts' ),
						'pricing_view_purchase_table_desc' => __( 'Set purchase and receive quantity and discount amount.', 'easy-woocommerce-discounts' ),
						'min' => __( 'Min', 'easy-woocommerce-discounts' ),
						'max' => __( 'Max - No limit', 'easy-woocommerce-discounts' ),
						'apply_mode' => __( 'Apply Mode', 'easy-woocommerce-discounts' ),
						'apply_all_applicable_rules' => __( 'Apply with other applicable rules', 'easy-woocommerce-discounts' ),
						'apply_rule_individually' => __( 'Apply this rule and disregard other rules', 'easy-woocommerce-discounts' ),
						'applicable_rule_not_exists' => __( 'Apply if other rules are not applicable', 'easy-woocommerce-discounts' ),
						'non_exclusive' => __( 'Non Exclusive', 'easy-woocommerce-discounts' ),
						'exclusive' => __( 'Exclusive', 'easy-woocommerce-discounts' ),
						'id' => __( 'ID', 'easy-woocommerce-discounts' ),
						'price' => __( 'Price', 'easy-woocommerce-discounts' ),
						'products_group' => __( 'Products group', 'easy-woocommerce-discounts' ),
						'adjustment' => __( 'Adjustment', 'easy-woocommerce-discounts' ),
						'adjustment_type' => __( 'Adjustment Type', 'easy-woocommerce-discounts' ),
						'fixed_discount_per_item' => __( 'Fixed discount per item', 'easy-woocommerce-discounts' ),
						'fixed_discount_per_group' => __( 'Fixed discount per group', 'easy-woocommerce-discounts' ),
						'fixed_price_per_item' => __( 'Fixed price per item', 'easy-woocommerce-discounts' ),
						'fixed_price_per_group' => __( 'Fixed price per group', 'easy-woocommerce-discounts' ),
						'quantity' => __( 'Quantity', 'easy-woocommerce-discounts' ),
						'matching_mode' => __( 'Matching Mode', 'easy-woocommerce-discounts' ),
						'at_least_one_date_time_should_match' => __( 'At least one date time should match', 'easy-woocommerce-discounts' ),
						'all_date_times_should_match' => __( 'All date times should match', 'easy-woocommerce-discounts' ),
						'at_least_one_condition_should_match' => __( 'At least one condition should match', 'easy-woocommerce-discounts' ),
						'all_conditions_should_match' => __( 'All conditions should match', 'easy-woocommerce-discounts' ),
						'shortcode' => __( 'Shortcode', 'easy-woocommerce-discounts' ),
						'exclude_products_from_all_rules' => __( 'Exclude products from all rules', 'easy-woocommerce-discounts' ),
						'excluded_products' => __( 'Excluded Products', 'easy-woocommerce-discounts' ),
						'excluded_products_desc' => __( 'Exclude products from this rule.', 'easy-woocommerce-discounts' ),
						'private_note' => __( 'Private Note', 'easy-woocommerce-discounts' ),
						'yes' => __( 'Yes', 'easy-woocommerce-discounts' ),
						'no' => __( 'No', 'easy-woocommerce-discounts' ),
						'delete_warning_message' => __( 'Are you sure to delete?', 'easy-woocommerce-discounts' ),
						'user_capability' => __( 'User capability', 'easy-woocommerce-discounts' ),
						'user_meta' => __( 'User meta', 'easy-woocommerce-discounts' ),
						'average_money_spent_per_order' => __( 'Average money spent per order', 'easy-woocommerce-discounts' ),
						'last_order_date' => __( 'Last order date', 'easy-woocommerce-discounts' ),
						'number_of_products_reviews' => __( 'Number of products reviews', 'easy-woocommerce-discounts' ),
						'cart_total_weight' => __( 'Cart total weight', 'easy-woocommerce-discounts' ),
						'coupons_applied' => __( 'Coupons applied', 'easy-woocommerce-discounts' ),
						'product_attributes_in_cart' => __( 'Product attributes in cart', 'easy-woocommerce-discounts' ),
						'cart_items_quantity' => __( 'Cart items quantity', 'easy-woocommerce-discounts' ),
						'quantity_of_products' => __( 'Quantity of products', 'easy-woocommerce-discounts' ),
						'quantity_of_variations' => __( 'Quantity of variations', 'easy-woocommerce-discounts' ),
						'quantity_of_categories' => __( 'Quantity of categories', 'easy-woocommerce-discounts' ),
						'quantity_of_attributes' => __( 'Quantity of attributes', 'easy-woocommerce-discounts' ),
						'quantity_of_tags' => __( 'Quantity of tags', 'easy-woocommerce-discounts' ),
						'purchase_history' => __( 'Purchase history', 'easy-woocommerce-discounts' ),
						'bought_product_attributes' => __( 'Bought product attributes', 'easy-woocommerce-discounts' ),
						'purchase_history_quantity' => __( 'Purchase history quantity', 'easy-woocommerce-discounts' ),
						'quantity_of_bought_products' => __( 'Quantity of bought products', 'easy-woocommerce-discounts' ),
						'quantity_of_bought_variations' => __( 'Quantity of bought variations', 'easy-woocommerce-discounts' ),
						'quantity_of_bought_categories' => __( 'Quantity of bought categories', 'easy-woocommerce-discounts' ),
						'quantity_of_bought_attributes' => __( 'Quantity of bought attributes', 'easy-woocommerce-discounts' ),
						'quantity_of_bought_tags' => __( 'Quantity of bought tags', 'easy-woocommerce-discounts' ),
						'purchase_history_amount' => __( 'Purchase history amount', 'easy-woocommerce-discounts' ),
						'amount_of_bought_products' => __( 'Amount of bought products', 'easy-woocommerce-discounts' ),
						'amount_of_bought_variations' => __( 'Amount of bought variations', 'easy-woocommerce-discounts' ),
						'amount_of_bought_categories' => __( 'Amount of bought categories', 'easy-woocommerce-discounts' ),
						'amount_of_bought_attributes' => __( 'Amount of bought attributes', 'easy-woocommerce-discounts' ),
						'amount_of_bought_tags' => __( 'Amount of bought tags', 'easy-woocommerce-discounts' ),
						'customer_value' => __( 'Customer value', 'easy-woocommerce-discounts' ),
						'cart_items' => __( 'Cart items', 'easy-woocommerce-discounts' ),
						'meta_field_key' => __( 'Meta field key', 'easy-woocommerce-discounts' ),
						'empty' => __( 'Empty', 'easy-woocommerce-discounts' ),
						'is_not_empty' => __( 'Is not empty', 'easy-woocommerce-discounts' ),
						'contains' => __( 'Contains', 'easy-woocommerce-discounts' ),
						'does_not_contain' => __( 'Does not contain', 'easy-woocommerce-discounts' ),
						'begins_with' => __( 'Begins with', 'easy-woocommerce-discounts' ),
						'ends_with' => __( 'Ends with', 'easy-woocommerce-discounts' ),
						'is_checked' => __( 'Is checked', 'easy-woocommerce-discounts' ),
						'is_not_checked' => __( 'Is not checked', 'easy-woocommerce-discounts' ),
						'at_least_one_of_any' => __( 'At least one of any', 'easy-woocommerce-discounts' ),
						'at_least_one_of_selected' => __( 'At least one of selected', 'easy-woocommerce-discounts' ),
						'all_of_selected' => __( 'All of selected', 'easy-woocommerce-discounts' ),
						'only_selected' => __( 'Only selected', 'easy-woocommerce-discounts' ),
						'none_of_selected' => __( 'None of selected', 'easy-woocommerce-discounts' ),
						'none_at_all' => __( 'None at all', 'easy-woocommerce-discounts' ),
						'product_attributes' => __( 'Product attributes', 'easy-woocommerce-discounts' ),
						'product_regular_price' => __( 'Product regular price', 'easy-woocommerce-discounts' ),
						'product_display_price' => __( 'Product display price', 'easy-woocommerce-discounts' ),
						'product_is_on_sale' => __( 'Product is on sale', 'easy-woocommerce-discounts' ),
						'product_stock_quantity' => __( 'Product stock quantity', 'easy-woocommerce-discounts' ),
						'product_meta_field' => __( 'Product meta field', 'easy-woocommerce-discounts' ),
						'product_properties' => __( 'Product Properties', 'easy-woocommerce-discounts' ),
						'fee' => __( 'Fee', 'easy-woocommerce-discounts' ),
						'percentage_fee' => __( 'Percentage fee', 'easy-woocommerce-discounts' ),
						'price_fee' => __( 'Price fee', 'easy-woocommerce-discounts' ),
						'duplicate' => __( 'Duplicate', 'easy-woocommerce-discounts' ),
						'checkout_fees' => __( 'Checkout Fees', 'easy-woocommerce-discounts' ),
						'checkout_fee' => __( 'Checkout Fee', 'easy-woocommerce-discounts' ),
						'fee_type' => __( 'Fee Type', 'easy-woocommerce-discounts' ),
						'fee_amount' => __('Fee Amount', 'easy-woocommerce-discounts'),
						'fee_amount_desc' => sprintf( __( 'Fee amount should be greater or equal to %d.', 'easy-woocommerce-discounts' ), 0 ),
						'fee_per_item' => __( 'Fee Per Item', 'easy-woocommerce-discounts' ),
						'price_fee_per_item' => __( 'Price fee per item', 'easy-woocommerce-discounts' ),
						'percentage_fee_per_item' => __( 'Percentage fee per item', 'easy-woocommerce-discounts' ),
						'discount_per_item' => __( 'Discount Per Item', 'easy-woocommerce-discounts' ),
						'price_discount_per_item' => __( 'Price discount per item', 'easy-woocommerce-discounts' ),
						'percentage_discount_per_item' => __( 'Percentage discount per item', 'easy-woocommerce-discounts' ),
						'shipping_address' => __( 'Shipping Address', 'easy-woocommerce-discounts' ),
						'shipping_country' => __( 'Shipping country', 'easy-woocommerce-discounts' ),
						'shipping_state' => __( 'Shipping state', 'easy-woocommerce-discounts' ),
						'shipping_city' => __( 'Shipping city', 'easy-woocommerce-discounts' ),
						'shipping_postcode' => __( 'Shipping postcode', 'easy-woocommerce-discounts' ),
						'shipping_zone' => __( 'Shipping zone', 'easy-woocommerce-discounts' ),
						'checkout' => __( 'Checkout', 'easy-woocommerce-discounts' ),
						'payment_method' => __( 'Payment method', 'easy-woocommerce-discounts' ),
						'shipping_method' => __( 'Shipping method', 'easy-woocommerce-discounts' ),
						'post_code_eg' => __( 'e.g. 96969, 969**, [96960 - 96970], SIQQ 1ZZ, TKCA 1ZZ', 'easy-woocommerce-discounts' ),
						'match' => __( 'Match', 'easy-woocommerce-discounts' ),
						'not_match' => __( 'Not match', 'easy-woocommerce-discounts' ),
						'applies_to_all_items' => __( 'Applies to all items.', 'easy-woocommerce-discounts' ),
						'cart_items_subtotal' => __( 'Cart items subtotal', 'easy-woocommerce-discounts' ),
						'subtotal_of_products_include_tax' => __( 'Subtotal of products include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_products_exclude_tax' => __( 'Subtotal of products exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_variations_include_tax' => __( 'Subtotal of variations include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_variations_exclude_tax' => __( 'Subtotal of variations exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_categories_include_tax' => __( 'Subtotal of categories include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_categories_exclude_tax' => __( 'Subtotal of categories exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_attributes_include_tax' => __( 'Subtotal of attributes include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_attributes_exclude_tax' => __( 'Subtotal of attributes exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_tags_include_tax' => __( 'Subtotal of tags include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_tags_exclude_tax' => __( 'Subtotal of tags exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_regular_products_include_tax' => __( 'Subtotal of regular products include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_regular_products_exclude_tax' => __( 'Subtotal of regular products exclude tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_onsale_products_include_tax' => __( 'Subtotal of onsale products include tax', 'easy-woocommerce-discounts' ),
						'subtotal_of_onsale_products_exclude_tax' => __( 'Subtotal of onsale products exclude tax', 'easy-woocommerce-discounts' ),
						'message_type' => __( 'Message Type', 'easy-woocommerce-discounts' ),
						'text_message' => __( 'Text Message', 'easy-woocommerce-discounts' ),
						'message_background_color' => __( 'Message Background Color', 'easy-woocommerce-discounts' ),
						'message_background_color_desc' => __( 'Background color of the message box, leave it empty to use default background color.', 'easy-woocommerce-discounts' ),
						'message_color' => __( 'Message Color', 'easy-woocommerce-discounts' ),
						'message_color_desc' => __( 'Color of the message in the message box, leave it empty to use default color.', 'easy-woocommerce-discounts' ),
						'display_quantity' => __( 'Display Quantity', 'easy-woocommerce-discounts' ),
						'display_quantity_desc' => __( 'Display quantity column in the quantity table.', 'easy-woocommerce-discounts' ),
						'display_price' => __( 'Display Price', 'easy-woocommerce-discounts' ),
						'display_price_desc' => __( 'Display price column in the quantity table.', 'easy-woocommerce-discounts' ),
						'display_discount' => __( 'Display Discount', 'easy-woocommerce-discounts' ),
						'display_discount_desc' => __( 'Display discount column in the quantity table.', 'easy-woocommerce-discounts' ),
						'pro' => __( 'Pro Version', 'easy-woocommerce-discounts' ),
						'feature_available_in_pro' => sprintf( __( 'This feature is available only in the pro version, %1$s or %2$s.', 'easy-woocommerce-discounts' ), '<a href="https://www.asanaplugins.com/product/advanced-woocommerce-dynamic-pricing-discounts/?utm_source=easy-woocommerce-discounts-free&utm_campaign=easy-woocommerce-discounts&utm_medium=link" target="_blank">' . __( 'Go Pro', 'easy-woocommerce-discounts' ) . '</a>', '<a href="http://pricing-and-discounts.asanaplugins.com/" target="_blank">' . __( 'Try It Now', 'easy-woocommerce-discounts' ) . '</a>' ),
						'go_pro' => __( 'Go Pro', 'easy-woocommerce-discounts' ),
						'pro_off' => __( '50% Off', 'easy-woocommerce-discounts' ),
						'some_of_pro_features' => __( 'Some of the pro version features', 'easy-woocommerce-discounts' ),
						'pro_simple_pricing' => __( 'Simple Pricing', 'easy-woocommerce-discounts' ),
						'pro_simple_pricing_desc' => __( 'Decrease or set the fixed price for products by using conditions.', 'easy-woocommerce-discounts' ),
						'pro_simple_pricing_subtotal' => sprintf( __( 'Apply %1$s discount on Cap when subtotal is greater than %2$s', 'easy-woocommerce-discounts' ), '10%', '£50' ),
						'pro_bulk_pricing' => __( 'Bulk Pricing', 'easy-woocommerce-discounts' ),
						'pro_bulk_pricing_desc' => __( 'Decrease product price by bought quantities. All units get highest discount.', 'easy-woocommerce-discounts' ),
						'pro_bulk_pricing_variation_desc' => __( 'Bulk pricing for variable and variation products.', 'easy-woocommerce-discounts' ),
						'pro_tiered_pricing' => __( 'Tiered Pricing', 'easy-woocommerce-discounts' ),
						'pro_tiered_pricing_desc' => __( 'Decrease product price by bought quantities. Subsequent units get increasing discount.', 'easy-woocommerce-discounts' ),
						'pro_products_group' => __( 'Products Group', 'easy-woocommerce-discounts' ),
						'pro_products_group_desc' => __( 'Adjusts specified products group price.', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing' => __( 'Buy A Get B Discounted', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing_desc' => __( 'Buy X quantity from product A to get Y quantity of product B with discount.', 'easy-woocommerce-discounts' ),
						'pro_exclude_products' => __( 'Exclude Products', 'easy-woocommerce-discounts' ),
						'pro_exclude_products_desc' => __( 'Excludes specified products from all of pricing rules.', 'easy-woocommerce-discounts' ),
						'pro_live_price' => __( 'Live Price', 'easy-woocommerce-discounts' ),
						'pro_live_price_desc' => __( 'Live price helps your customers to see product discounted price in product page as Your Price.', 'easy-woocommerce-discounts' ),
						'pro_live_price_for_bulk_pricing' => __( 'Live Price for Bulk Pricing rule', 'easy-woocommerce-discounts' ),
						'pro_live_price_for_purchase_pricing' => __( 'Live price for Purchase 3 quantities to get 1 with 50% discount rule', 'easy-woocommerce-discounts' ),
						'pro_auto_add_free_products' => __( 'Automatically add free products to cart for BOGO rules', 'easy-woocommerce-discounts' ),
						'pro_auto_add_free_same_products' => __( 'When buy and get products are same', 'easy-woocommerce-discounts' ),
						'pro_auto_add_free_different_products' => __( 'When buy and get products are different', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing_desc_same_free' => __( 'Buy 3 quantities of a product and get 1 for free.', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing_desc_same_discounted' => __( 'Buy 2 quantities of a product and get 1 with a specified discount.', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing_desc_another_free' => __( 'Buy 2 quantity of product A and get 1 quantity of product B for free.', 'easy-woocommerce-discounts' ),
						'pro_purchase_pricing_desc_another_discounted' => __( 'Buy 2 quantity of product A and get 1 quantity of product B with a specific discount.', 'easy-woocommerce-discounts' ),
						'pro_conditional_cart_discounts' => __( 'Conditional Cart Discounts', 'easy-woocommerce-discounts' ),
						'pro_conditional_cart_discounts_desc' => __( 'The system automatically adds a dynamic discount to user cart based on conditions and rules.', 'easy-woocommerce-discounts' ),
						'pro_conditional_cart_discounts_desc_payment_method' => sprintf( __( '%1$s discount on Paypal payment gateway.', 'easy-woocommerce-discounts' ), '10%' ),
						'pro_conditional_cart_discounts_desc_cart_subtotal' => sprintf( __( 'Get %1$s discount when subtotal is greater than 100£.', 'easy-woocommerce-discounts' ), '10%' ),
						'pro_conditional_checkout_fee' => __( 'Conditional Checkout Fee', 'easy-woocommerce-discounts' ),
						'pro_conditional_checkout_fee_desc' => __( 'By checkout fees, you can charge extra amount your customer. For example, you can charge 2$ per each heavy products, or you can charge extra amounts for international orders.', 'easy-woocommerce-discounts' ),
						'pro_conditional_checkout_fee_desc_weight' => sprintf( __( 'Charge %1$s fee when weight is greater than a specific weight.', 'easy-woocommerce-discounts' ), '£20.00' ),
						'pro_buy' => sprintf( __( 'Buy for %1$s', 'easy-woocommerce-discounts' ), '30$' ),
						'pro_buy_desc' => sprintf( __( 'Buy now with %1$s discount for limited time only from %2$s.', 'easy-woocommerce-discounts' ), '<span class="wccs-red">50%</span>', '<a href="https://www.asanaplugins.com/product/advanced-woocommerce-dynamic-pricing-discounts/?utm_source=easy-woocommerce-discounts-free&utm_campaign=easy-woocommerce-discounts&utm_medium=link" target="_blank">Our Website</a>' ),
						'watch_gif_video' => __( 'Watch GIF Video', 'easy-woocommerce-discounts' ),
						'shipping_methods' => __( 'Shipping Methods', 'easy-woocommerce-discounts' ),
						'shipping_method' => __( 'Shipping Method', 'easy-woocommerce-discounts' ),
						'cost' => __( 'Cost', 'easy-woocommerce-discounts' ),
						'shipping_cost_desc' => __( 'A base shipping cost.', 'easy-woocommerce-discounts' ),
						'cost_per_quantity' => __( 'Cost Per Quantity', 'easy-woocommerce-discounts' ),
						'cost_per_quantity_desc' => sprintf( __( 'Cost for each item quantity in the cart.%1$s Use %2$s a for percentage base amount. e.g: %3$s', 'easy-woocommerce-discounts' ), '<br>', '%', '20%' ),
						'cost_per_weight' => __( 'Cost Per Weight', 'easy-woocommerce-discounts' ),
						'cost_per_weight_desc' => sprintf( __( 'Cost for each item weight in the cart.%1$s Use %2$s for a percentage base amount. e.g: %3$s', 'easy-woocommerce-discounts' ), '<br>', '%', '20%' ),
						'tax_status' => __( 'Tax Status', 'easy-woocommerce-discounts' ),
						'taxable' => __( 'Taxable', 'easy-woocommerce-discounts' ),
						'none' => __( 'None', 'easy-woocommerce-discounts' ),
						'shipping_fee_desc' => sprintf( __( 'An additional fee.%1$s Use %2$s for a percentage base amount. e.g: %3$s', 'easy-woocommerce-discounts' ), '<br>', '%', '20%' ),
						'min_fee' => __( 'Minimum Fee', 'easy-woocommerce-discounts' ),
						'shipping_min_fee_desc' => __( 'A minimum fee amount. Useful when using percentage fee.', 'easy-woocommerce-discounts' ),
						'max_fee' => __( 'Maximum Fee', 'easy-woocommerce-discounts' ),
						'shipping_max_fee_desc' => __( 'A maximum fee amount. Useful when using percentage fee.', 'easy-woocommerce-discounts' ),
						'percentage' => __( 'Percentage', 'easy-woocommerce-discounts' ),
						'max_width_of_cart_items' => __( 'Maximum width of cart items', 'easy-woocommerce-discounts' ),
						'max_height_of_cart_items' => __( 'Maximum height of cart items', 'easy-woocommerce-discounts' ),
						'max_length_of_cart_items' => __( 'Maximum length of cart items', 'easy-woocommerce-discounts' ),
						'min_stock_of_cart_items' => __( 'Minimum stock quantity of cart items', 'easy-woocommerce-discounts' ),
						'shipping_classes_in_cart' => __( 'Shipping classes in cart', 'easy-woocommerce-discounts' ),
						'shipping_package' => __( 'Shipping package', 'easy-woocommerce-discounts' ),
						'package_total_weight' => __( 'Package total weight', 'easy-woocommerce-discounts' ),
						'number_of_package_items' => __( 'Number of package items', 'easy-woocommerce-discounts' ),
						'quantity_of_package_items' => __( 'Quantity of package items', 'easy-woocommerce-discounts' ),
						'shipping_package_items' => __( 'Shipping package items', 'easy-woocommerce-discounts' ),
						'products_in_package' => __( 'Products in package', 'easy-woocommerce-discounts' ),
						'product_variations_in_package' => __( 'Product variations in package', 'easy-woocommerce-discounts' ),
						'product_categories_in_package' => __( 'Product categories in package', 'easy-woocommerce-discounts' ),
						'product_attributes_in_package' => __( 'Product attributes in package', 'easy-woocommerce-discounts' ),
						'product_tags_in_package' => __( 'Product tags in package', 'easy-woocommerce-discounts' ),
						'shipping_classes_in_package' => __( 'Shipping classes in package', 'easy-woocommerce-discounts' ),
						'pro_dynamic_shipping_methods' => __( 'Dynamic Shipping Methods', 'easy-woocommerce-discounts' ),
						'pro_dynamic_shipping_methods_desc' => __( 'Create advanced and dynamic shipping methods by powerful conditions available.', 'easy-woocommerce-discounts' ),
						'pro_dynamic_shipping_methods_desc_weight' => __( 'WooCommerce weight-based shipping – Add 10$ shipping method when weight is greater or equal to 10, the product weight is 5', 'easy-woocommerce-discounts' ),
						'pro_dynamic_shipping_methods_desc_free_shipping_cart_total' => __( 'WooCommerce free shipping based on cart total – free shipping is available when cart total is greater or equal to 50$', 'easy-woocommerce-discounts' ),
						'paginate' => __( 'Paginate', 'easy-woocommerce-discounts' ),
						'paginate_products_desc' => __( 'If set to yes it will paginate products otherwise it will show all of the products without pagination.', 'easy-woocommerce-discounts' ),
					),
					'nonce' => wp_create_nonce( 'wccs_conditions_nonce' ),
					'categories' => $wc_products->get_categories(),
					'productsList' => $wccs->WCCS_Conditions_Provider->get_products_lists(),
					'discountList' => $wccs->WCCS_Conditions_Provider->get_cart_discounts(),
					'pricingList' => $wccs->WCCS_Conditions_Provider->get_pricings(),
					'shippingList' => $wccs->WCCS_Conditions_Provider->get_shippings(),
				);
			break;
		}

		return false;
	}

	protected function localize_scripts() {
		foreach ( $this->scripts as $handle ) {
			$this->localize_script( $handle );
		}
	}

}
