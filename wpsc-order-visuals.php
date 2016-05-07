<?php
/**
* Plugin Name: WP eCommerce Order Visuals
* Description: Visualize WP eCommerce order data.
* Version: 0.5
* Author: Flynn O'Connor
* Author URI: https://github.com/thoronas
* Text Domain: wp-e-commerce, order data, data visualization
**/

/**
 * Get sales data from the last 30 days.
 * Used on page load to provide data to start with.
 */
function current_dataset(){
	$end_date = strtotime('now');
	$start_date = strtotime("-30 days");
	$current_data['monthly'] = wcsv_get_monthly_sales_data( $start_date, $end_date );
	$current_data['days'] = wcsv_get_days_with_orders( $start_date, $end_date );
	$current_data['users'] = wcsv_get_top_users( $start_date, $end_date );
	$current_data['categories'] = wcsv_get_sales_per_category( $start_date, $end_date );
	return $current_data;
}

/**
 * Add submenu page to Products Page.
 * @param  array $page_hooks    array of submenu items.
 * @param  string $products_page path of parent page.
 * @return array                returns submenu page array with our order info page.
 */
function wcscv_menu_extension( $page_hooks, $products_page ){
	// user capability taken from wpsc
	$manage_coupon_cap = apply_filters( 'wpsc_coupon_cap', 'administrator' );

	// @see _add_post_type_submenus()
	// @see wp-admin/menu.php
	$page_hooks[] = $orders_info_page = add_submenu_page(
		$products_page, // parent slug
		'Order Information', // page title
		'Order Information', // menu title
		$manage_coupon_cap, // capability
		'wcsv_order_information', // menu slug
		'wcsv_register_subpage' // function
	);

	return $page_hooks;
}
add_filter( 'wpsc_additional_pages', 'wcscv_menu_extension', 10, 2 );

function wcsv_register_subpage() {
	include_once plugin_dir_path( __FILE__ ) . '/admin/orders-display.php';
}

function wcsv_register_scripts(){
	$screen = get_current_screen();

	if ( 'wpsc-product_page_wcsv_order_information' == $screen->id ) {

		wp_enqueue_script( 'wcsv-d3', plugin_dir_url( __FILE__ ) . 'admin/assets/js/d3.min.js', '', '3.5.9', true );
		wp_enqueue_script( 'wcsv', plugin_dir_url( __FILE__ ) . 'admin/assets/js/wcsv.js', array( 'wcsv-d3', 'jquery' ), '0.1', true );
		wp_enqueue_style( 'wcsv-styles', plugin_dir_url( __FILE__ ) . 'admin/assets/css/styles.css' );

		wp_enqueue_style( 'jquery-ui-datepicker', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css' );
		wp_enqueue_script( 'wcsv-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js', array( 'jquery' ), '0.1', true );

		wp_localize_script( 'wcsv', 'ajax_info', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( 'wcsv', 'dataset', current_dataset() );
	}
}
add_action( 'admin_enqueue_scripts', 'wcsv_register_scripts' );
function wcsv_process_start_date( $raw_date ) {
	if( $raw_date ){
		return wcsv_return_timestamp( $raw_date );
	} else {
		return strtotime("-30 days");
	}
}
function wcsv_process_end_date( $raw_date ) {
	if( $raw_date ){
		return wcsv_return_timestamp( $raw_date );
	} else {
		return strtotime("now");
	}
}
/**
 * Monthly Sales function called via wp_ajax
 */
function wcsv_process_monthly_sales(){
	$start_date = wcsv_process_start_date( $_POST['start_date'] );
	$end_date = wcsv_process_end_date( $_POST['end_date'] );
	$data = $_POST['products'];
	$data_array = array();
	foreach( $data as $product ){
		$product_array = wcsv_get_days_with_orders( $start_date, $end_date, $product );
		$data_array = array_merge($data_array, $product_array );
	}
	wp_die( json_encode( array( 'data' => $data_array ) ) );
}
add_action( 'wp_ajax_wcsv_monthly_sales', 'wcsv_process_monthly_sales' );

/**
 * Unregistered user sales called via wp_ajax
 */
function wcsv_process_unregistered_sales(){
	$start_date = wcsv_process_start_date( $_POST['start_date'] );
	$end_date = wcsv_process_end_date( $_POST['end_date'] );
	$sales = wcsv_get_unregistered_visitor_sales( $start_date, $end_date );
	wp_die( json_encode( $sales ) );
}
add_action( 'wp_ajax_wcsv_nonregisted_sales', 'wcsv_process_unregistered_sales' );

/**
 * Registered user sales called via wp_ajax
 */
function wcsv_process_registered_sales(){
	$start_date = wcsv_process_start_date( $_POST['start_date'] );
	$end_date = wcsv_process_end_date( $_POST['end_date'] );
	$sales = wcsv_get_top_users( $start_date, $end_date );
	wp_die( json_encode( $sales ) );
}
add_action( 'wp_ajax_wcsv_registered_user_sales', 'wcsv_process_registered_sales' );

function wcsv_get_sales_data() {
	$start_date = wcsv_process_start_date( $_POST['start_date'] );
	$end_date = wcsv_process_end_date( $_POST['end_date'] );
	$graph_type = $_POST['current_graph'];
	switch ( $graph_type ) {
		case "sales-dates":
			$data = array("sales-dates");
			$data[] = wcsv_get_days_with_orders( $start_date, $end_date );
			break;
		case "category-sales":
			$data = array("category-sales");
			$data[] = wcsv_get_sales_per_category( $start_date, $end_date );
			break;
		case "top-products":
			$data = array("top-products");
			$data[] = wcsv_get_monthly_sales_data( $start_date, $end_date );
			break;
		case "user-sales":
			$data = array("user-sales");
			$data[] = wcsv_get_top_users( $start_date, $end_date );
			break;

	}
	wp_die( json_encode( $data ) );
}
add_action( 'wp_ajax_get_sales_data', 'wcsv_get_sales_data' );

function wcsv_return_timestamp( $raw_date ){
	$date = new DateTime( $raw_date );
	return $date->getTimestamp();
}

/**
 * Get the sum totals of the top 20 products in a given time period.
 * @param  int $start_year
 * @param  int $start_month
 * @param  int $end_year
 * @param  int $end_month
 * @return array              array of products and their sales totals
 */
function wcsv_get_monthly_sales_data( $start_time, $end_time ){
	global $wpdb;

	$products = $wpdb->get_results( "SELECT `cart`.`prodid`,
	 `cart`.`name` as `name`,
	 SUM(`cart`.`price` * `cart`.`quantity`) as `sale_totals`
	 FROM `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
	 INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		 ON `cart`.`purchaseid` = `logs`.`id`
	 WHERE `logs`.`processed` >= 2
		 AND `logs`.`date` >= " . $start_time . "
		 AND `logs`.`date` < " . $end_time . "
	 GROUP BY `cart`.`prodid`
	 ORDER BY SUM(`cart`.`price` * `cart`.`quantity`) DESC
	 LIMIT 20", ARRAY_A );

	return $products;
}
/**
 * Gathers all orders in given time frame.
 * Adds all orders together and returns multidimensional arrays
 * with days and total orders in money.
 *
 * @param  int $start_year
 * @param  int $start_month
 * @param  int $end_year
 * @param  int $end_month
 * @return array            returns multidimensional array of days with total orders per day.
 */
function wcsv_get_days_with_orders( $start_time, $end_time, $prodid = 0 ){

	$days_totals = array();
	$prodid = absint( $prodid );
	global $wpdb;

	$select_month_string = "SELECT DAYOFMONTH( FROM_UNIXTIME(`logs`.`date`) ) AS order_day,";
	$all_totals = " `logs`.`totalprice` AS totalprice";
	$product_totals = " SUM(`cart`.`price` * `cart`.`quantity`) AS totalprice";
	$from = " FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`";
	$products_join = " INNER JOIN `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
		ON `cart`.`purchaseid` = `logs`.`id`";
	$where_string = " WHERE `logs`.`processed` >= 2
	AND `logs`.`date` >= ".$start_time."
	AND `logs`.`date` < ".$end_time."";
	$product_grouping = " AND `cart`.`prodid` = " . $prodid . "
	GROUP BY `cart`.`purchaseid`";

	$compiled_string = $select_month_string;

	if ( empty( $prodid ) ) {
		$compiled_string .= $all_totals . $from . $where_string;
	} else {
		$compiled_string .= $product_totals . $from . $products_join . $where_string . $product_grouping;
	}

	$dayswithorders = $wpdb->get_results( $compiled_string , ARRAY_A);

	/**
	 * Loop through every day in the time period specified.
	 * If there are orders query the total sum of those orders.
	 */
	for ( $i = $start_time; $i < $end_time; $i = $i + 86400 ) {
		$date = new DateTime();
		$date->setTimestamp( $i );

		$day_number = $date->format( 'd' );

		$order_total =  0;
		// loop through all orders and add any purchases to current day.
		foreach ( $dayswithorders as $day ) {
			if ( $day_number == $day['order_day'] ){
				$order_total += $day['totalprice'];
			}
		}
		// this structure is for D3. Expects the JSON in this format.
		$days_totals[] = array(
			'day'   => $date->format( 'Y' ).'-'.$date->format( 'm' ).'-'.$day_number,
			'total' => $order_total,
			'product' => ( empty( $prodid ) ? 'total' : get_the_title($prodid)  )
		);
	}
	return $days_totals;
}
/**
 * Gets the totals sales per user. Adds all sales of non users into same array.
 * @param  int $start_year
 * @param  int $start_month
 * @param  int $end_year
 * @param  int $end_month
 * @return array              Returns array of user ids, total sales, names and emails.
 */
function wcsv_get_top_users( $start_time, $end_time ){

	global $wpdb;
	$users = $wpdb->get_results( "SELECT `logs`.`user_ID`,
		SUM(`logs`.`totalprice`) as `sale_totals`,
		`users`.`display_name` as `name`,
		`users`.`user_email`
		FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		INNER JOIN `" . $wpdb->users . "` AS `users`
			ON `users`.`ID` = `logs`.`user_ID`
		WHERE `logs`.`processed` >= 2
			AND `logs`.`date` >= ".$start_time."
			AND `logs`.`date` < ".$end_time."
		GROUP BY `logs`.`user_ID`
		LIMIT 20", ARRAY_A );

	 $non_registered = $wpdb->get_results( "SELECT
		SUM(`logs`.`totalprice`) as `sale_totals`
		FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		WHERE `logs`.`processed` >= 2
			AND `logs`.`user_ID` = 0
			AND `logs`.`date` >= ".$start_time."
			AND `logs`.`date` < ".$end_time."
		GROUP BY `logs`.`user_ID`
		LIMIT 20", ARRAY_A );

	if( $non_registered ) {
		// add data for consistency so D3 knows how to display it.
		$non_registered[0]['name'] = 'unregistered';
		$non_registered[0]['user_ID'] = '0';
		$non_registered[0]['action'] = '1';
		$totals = array_merge( $users, $non_registered );
		return $totals;
	} else {
		return $users;
	}
}
/**
 * Gets the sales total based on visitor email.
 * This current version uses the basic checkout form and expect ths email id to be 9.
 * @param  int $start_year
 * @param  int $start_month
 * @param  int $end_year
 * @param  int $end_month
 * @return array              Returns array of total sales and emails.
 * @todo append WPSC_TABLE_CHECKOUT_FORMS to query and get email based on email form field id.
 */
function wcsv_get_unregistered_visitor_sales( $start_time, $end_time ){

	global $wpdb;

	// uses this to manually change the email form ID. It's ugly I know, will be imrpoved.
	$form_id = 9;

	$non_registered = $wpdb->get_results( "SELECT
		`forms`.value as name,
		SUM(`logs`.`totalprice`) as `sale_totals`
		FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		INNER JOIN `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` AS `forms`
			ON `forms`.`log_id` = `logs`.`id`
		WHERE `logs`.`processed` >= 2
			AND `logs`.`user_ID` = 0
			AND `forms`.`form_id` = ".$form_id."
			AND `logs`.`date` >= ".$start_time."
			AND `logs`.`date` < ".$end_time."
		GROUP BY `forms`.`value`
	", ARRAY_A );
	return $non_registered;
}
/**
 * Gets the sales total per Category.
 * @param  int $start_year
 * @param  int $start_month
 * @param  int $end_year
 * @param  int $end_month
 * @return array              Returns array of total sales and Category names.
 * @todo improve the array to return tax term id to allow for drill down of best selling products in a category.
 */

function wcsv_get_sales_per_category( $start_time, $end_time ){

	global $wpdb;
	$categories = $wpdb->get_results( "SELECT
		SUM(`cart`.`price` * `cart`.`quantity`) as `sale_totals`,
		`terms`.`name` as `name`
		FROM `" . $wpdb->terms . "` as `terms`
		INNER JOIN `" . $wpdb->term_taxonomy . "` as `term_tax`
			ON `terms`.`term_id` = `term_tax`.`term_id`
		INNER JOIN `" . $wpdb->term_relationships . "` as `term_rel`
			ON `term_tax`.`term_taxonomy_id` = `term_rel`.`term_taxonomy_id`
		INNER JOIN `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
			ON `cart`.`prodid` = `term_rel`.object_id
		INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
			ON `cart`.`purchaseid` = `logs`.`id`

		WHERE `term_tax`.`taxonomy` = 'wpsc_product_category'
			AND `logs`.`processed` >= 2
			AND `logs`.`id` = `cart`.`purchaseid`
			AND `cart`.`prodid` = `term_rel`.`object_id`
			AND `logs`.`date` >= ".$start_time."
			AND `logs`.`date` < ".$end_time."
		GROUP BY `terms`.`term_id`
		ORDER BY SUM(`cart`.`price` * `cart`.`quantity`) DESC
	LIMIT 20", ARRAY_A );

	return $categories;
}
