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
<?php //print_r( wcsv_get_sales_per_category('2015', '11', '2016', '01') ); ?>
</pre>
<div class="wrap">
	<div class="sales-svg" id="price-chart">
		<h2>Sales by Month</h2>
	</div>
	<div class="sales-svg pie" id="category-sales">
		<h2>Sales By Category</h2>
	</div>
	<div class="sales-svg pie" id="report">
		<h2>Top Products</h2>
	</div>

	<div class="sales-svg pie" id="users">
		<h2>Sales by User</h2>
	</div>
	<div class="sales-svg pie" id="unregistered">
		<h2>Sales by unregistered visitor email</h2>
	</div>
</div>
