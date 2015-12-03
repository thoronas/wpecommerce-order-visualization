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

.line {
  fill: none;
  stroke: steelblue;
  stroke-width: 1.5px;
}

</style>
<pre>
<?php print_r( wcsv_get_top_users('2015', '11', '2016', '01') ); ?>
</pre>
<div class="wrap">
	<div id="price-chart"></div>
	<div id="report"></div>
	<div id="users"></div>
</div>
