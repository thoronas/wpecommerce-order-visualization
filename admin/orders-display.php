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

<div class="wrap">

	<form id="date-range">
		<label for="start-date" >Starting Date: <input type="text" id="start-date" name="start-date" class="datepicker"></label>
		<label for="end-date">Ending Date: <input type="text" id="end-date" class="datepicker"></label>
		<button id="get-sales-data" class="button">Get Sales Data</button>
	</form>

	<div class="wpscv-tabs">

		<a class="wpscv-tab active" data-graph="#sales-dates"><strong>Sales Over Time</strong></a>
		<a class="wpscv-tab" data-graph="#category-sales"><strong>Sales By Category</strong></a>
		<a class="wpscv-tab" data-graph="#top-products"><strong>Top Products</strong></a>
		<a class="wpscv-tab" data-graph="#user-sales"><strong>Sales By User</strong></a>

	</div>

	<div class="wpscv-graphs">
		<div id="sales-dates" class="graph active">
			<h2>Sales Over Time</h2>
			<div class="product-selection">
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
		</div>
		<div id="category-sales" class="graph">
			<h2>Sales By Category</h2>
			<div class="sales-svg pie" id="categories"></div>
		</div>
		<div id="top-products" class="graph">
			<h2>Top Products</h2>
			<div class="sales-svg pie" id="report"></div>
		</div>
		<div id="user-sales" class="graph">
			<h2 class="user-sales">Sales by User</h2>
			<div class="sales-svg pie" id="users"></div>
		</div>
	</div>
</div>
