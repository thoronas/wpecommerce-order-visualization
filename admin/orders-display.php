<?php
?>
<style>

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

.x.axis path {
  display: none;
}
path.slice{
	stroke-width:2px;
}

polyline{
	opacity: .3;
	stroke: black;
	stroke-width: 2px;
	fill: none;
}
.sales-svg.pie svg {
	height: 400px;
	width: 100%;
}
</style>
<pre>
</pre>
<div class="wrap">
	<form id="date-range">
		<!-- <input type="date" id="date-range" name="date-range" value="" class="wpsc-datepicker datepicker" /> -->
<p>Starting Date: <input type="text" id="start-date" class="datepicker"></p>
<p>Ending Date: <input type="text" id="end-date" class="datepicker"></p>

	</form>

	<h2>Sales by Month</h2>
	<div class="test-selection">
	<?php
	$post_args = array(
		'post_type' => 'wpsc-product'
	);
	$products = new WP_Query($post_args);
	if( $products->have_posts() ){
	?>
	<label for="all-products">
	<input type="checkbox" class="product-filter" id="all-products" value="0" name="product[]" />
	<span>All Products</span>
	</label>
	<?php
		while( $products->have_posts() ): $products->the_post();
		?>
		<label for="<?php echo absint( get_the_id() ); ?>">
		<input type="checkbox" class="product-filter" id="<?php echo absint( get_the_id() ); ?>" value="<?php echo absint( get_the_id() ); ?>" name="product[]" />
		<span><?php the_title(); ?></span>
		</label>
		<?php
		endwhile; wp_reset_postdata();
	}
	?>
	<button id="update-product-data" class="button">Update Chart</button>
	</div>
	<div class="sales-svg" id="price-chart"></div>

	<h2>Sales By Category</h2>
	<div class="sales-svg pie" id="category-sales"></div>

	<h2>Top Products</h2>
	<div class="sales-svg pie" id="report"></div>

	<h2 class="user-sales">Sales by User</h2>
	<div class="sales-svg pie" id="users"></div>
</div>
