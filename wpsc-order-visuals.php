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
		wp_enqueue_script( 'wcsv', plugin_dir_url( __FILE__ ) . 'admin/assets/js/wcsv.js', array( 'wcsv-d3' ), '0.1', true );
		wp_localize_script( 'wcsv', 'dataset', test_dataset() );
	}
}
add_action( 'admin_enqueue_scripts', 'wcsv_register_scripts' );
function test_dataset(){
	$test_data = array();
	$test_data['monthly'] = wcsv_get_monthly_sales_data( '2015', '11', '2015', '12' );
	$test_data['days'] = wcsv_get_days_with_orders('2015', '11', '2015', '12' );
	$test_data['users'] = wcsv_get_top_users('2015', '11', '2016', '01' );
	$test_data['unregistered'] = wcsv_get_unregistered_visitor_sales('2015', '11', '2016', '01');
	return $test_data;
}
function wcsv_get_monthly_sales_data( $start_year, $start_month, $end_year, $end_month ){
	global $wpdb;

	$start_time = mktime( 0, 0, 0, $start_month, 1, $start_year );
	$end_time = mktime( 0, 0, 0, $end_month, 1, $end_year );

	$products = $wpdb->get_results( "SELECT `cart`.`prodid`,
	 `cart`.`name`
	 FROM `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
	 INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
	 ON `cart`.`purchaseid` = `logs`.`id`
	 WHERE `logs`.`processed` >= 2
	 AND `logs`.`date` >= " . $start_time . "
	 AND `logs`.`date` < " . $end_time . "
	 GROUP BY `cart`.`prodid`
	 ORDER BY SUM(`cart`.`price` * `cart`.`quantity`) DESC
	 LIMIT 20", ARRAY_A );

	 $prod_data = array( );
	 foreach ( (array)$products as $product ) { //run through products and get each product income amounts and name
		$sale_totals = array( );
			$prodsql = "SELECT
			SUM(`cart`.`price` * `cart`.`quantity`) AS sum
			FROM `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
			INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
				ON `cart`.`purchaseid` = `logs`.`id`
			WHERE `logs`.`processed` >= 2
				AND `logs`.`date` >= " . $start_time . "
				AND `logs`.`date` < " . $end_time . "
				AND `cart`.`prodid` = " . $product['prodid'] . "
			GROUP BY `cart`.`prodid`"; //get the amount of income that current product has generaterd over current time range
			$sale_totals[] = $wpdb->get_var( $prodsql ); //push amount to array
		$prod_data[] = array(
			'sale_totals' => $sale_totals,
			'name' => $product['name'] ); //result: array of 2: $prod_data[0] = array(income)
		$sums = array( ); //reset array    //$prod_data[1] = product name
	}
	return $prod_data;
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
function wcsv_get_days_with_orders( $start_year, $start_month, $end_year, $end_month, $prodid = 0 ){
	$start_time = mktime( 0, 0, 0, $start_month, 1, $start_year );
	$end_time = mktime( 0, 0, 0, $end_month, 1, $end_year );
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
		// convert unix time stamp to day of month.
		$day_number = date( 'd', $i );
		$order_total =  0;
		// loop through all orders and add any purchases to current day.
		foreach ( $dayswithorders as $day ) {
			if ( $day_number == $day['order_day'] ){
				$order_total += $day['totalprice'];
			}
		}
		$days_totals[] = array(
			'day'   => $start_year.'-'.$start_month.'-'.$day_number,
			'total' => $order_total,
			'product' => ( empty( $prodid ) ? 'total' : $prodid  )
		);
	}
	return $days_totals;
}
/**
 * Gets the totals sales per user. Adds all sales of non users into same array.
 * @param  [type] $start_year  [description]
 * @param  [type] $start_month [description]
 * @param  [type] $end_year    [description]
 * @param  [type] $end_month   [description]
 * @return array              Returns array of user ids, total sales, names and emails.
 */
function wcsv_get_top_users( $start_year, $start_month, $end_year, $end_month ){
	$start_time = mktime( 0, 0, 0, $start_month, 1, $start_year );
	$end_time = mktime( 0, 0, 0, $end_month, 1, $end_year );
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

	// add data for consistency so D3 knows how to display it.
	$non_registered[0]['name'] = 'unregistered';
	$non_registered[0]['user_ID'] = '0';
	$totals = array_merge( $users, $non_registered );
	return $totals;
}
function wcsv_get_unregistered_visitor_sales( $start_year, $start_month, $end_year, $end_month ){
	$start_time = mktime( 0, 0, 0, $start_month, 1, $start_year );
	$end_time = mktime( 0, 0, 0, $end_month, 1, $end_year );
	global $wpdb;

	$non_registered = $wpdb->get_results( "SELECT
		`forms`.value as name,
		SUM(`logs`.`totalprice`) as `sale_totals`
		FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		INNER JOIN `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` AS `forms`
			ON `forms`.`log_id` = `logs`.`id`
		WHERE `logs`.`processed` >= 2
		AND `logs`.`user_ID` = 0
		AND `forms`.`form_id` = 9
		AND `logs`.`date` >= ".$start_time."
		AND `logs`.`date` < ".$end_time."
		GROUP BY `forms`.`value`
	", ARRAY_A );
	return $non_registered;
}
