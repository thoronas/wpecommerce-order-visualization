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
	$test_data['monthly'] =	wcsv_get_monthly_sales_data( '2015', '11', '2015', '12' );
	$test_data['days'] = wcsv_get_days_with_orders('2015', '11', '2015', '12' );
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
	 LIMIT 100", ARRAY_A );

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
			'product_name' => $product['name'] ); //result: array of 2: $prod_data[0] = array(income)
		$sums = array( ); //reset array    //$prod_data[1] = product name
	}
	return $prod_data;
}
function wcsv_get_days_with_orders( $start_year, $start_month, $end_year, $end_month ){
	$start_time = mktime( 0, 0, 0, $start_month, 1, $start_year );
	$end_time = mktime( 0, 0, 0, $end_month, 1, $end_year );
	global $wpdb;

	$dayswithorders = $wpdb->get_results( "SELECT
		DISTINCT DAYOFMONTH( FROM_UNIXTIME(`logs`.`date`) ) AS order_date
		FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
		WHERE `logs`.`processed` >= 2
		AND `logs`.`date` >= ".$start_time."
		AND `logs`.`date` < ".$end_time."", ARRAY_A);
		print_r($dayswithorders);
		$days = array();

		foreach ( $dayswithorders as $day ) {
			$days[] = $day['order_date'];
		}
		$days_totals = array();
		/**
		 * Loop through every day in the time period specified.
		 * If there are orders query the total sum of those orders.
		 */
		for ( $i = $start_time; $i < $end_time; $i = $i + 86400 ) {
			// convert unix time stamp to day of month.
			$day_number = date( 'd', $i );
			// if day of month has no order move to next day.
			if ( ! in_array( $day_number, $days ) ){
				$days_totals[] = array(
					'day'   => $start_year.'-'.$start_month.'-'.$day_number,
					'total' => 0
				);
				continue;
			}
			// -1 second for a second into the previous day.
			$current_day_start = mktime( 0, 0, -1, $start_month, $day_number, $start_year );
			// start of the next day
			$current_day_end = mktime( 24, 0, 0, $start_month, $day_number, $start_year );

			$order_total = $wpdb->get_results( "SELECT
				SUM(`logs`.`totalprice`) AS total
				FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
				WHERE `logs`.`processed` >= 2
				AND `logs`.`date` > ".$current_day_start."
				AND `logs`.`date` < ".$current_day_end."
				", ARRAY_A);
			$days_totals[] = array(
				'day'   => $start_year.'-'.$start_month.'-'.$day_number,
				'total' => $order_total[0]['total']
			);
		}
	return $days_totals;
}
