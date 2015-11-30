(function($) {
    'use strict';

    $(function() {
		render_popular_product_pie( dataset.monthly, "#report" );
		render_product_sales_graph( dataset.days, "#price-chart" );
    });

	function render_product_sales_graph( dataset, $selector ) {
		var chart = '';
		//Width and height
		var margin = {top: 20, right: 20, bottom: 30, left: 50},
	    width = 960 - margin.left - margin.right,
	    height = 500 - margin.top - margin.bottom;
		var parseDate = d3.time.format("%Y-%m-%d").parse;
		var x = d3.time.scale()
		    .range([0, width]);
		var y = d3.scale.linear()
		    .range([height, 0]);
		var line = d3.svg.line()
		    .x(function(d) { return x(d.day); })
		    .y(function(d) { return y(d.total); });
		var xAxis = d3.svg.axis()
		    .scale(x)
		    .orient("bottom");
		var yAxis = d3.svg.axis()
		    .scale(y)
		    .orient("left");
		var svg = d3.select($selector)
			.append("svg")
		    .attr("width", width + margin.left + margin.right)
		    .attr("height", height + margin.top + margin.bottom)
		 	.append("g")
		    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

		x.domain(d3.extent(dataset, function(d) { return d.day; }));
	    y.domain(d3.extent(dataset, function(d) { return d.total; }));

		// go through dataset and parse date.
		dataset.forEach(function(d) {
	      d.day = parseDate(d.day);
		  //convert total to number.
		  d.total = +d.total;

	    });

	    x.domain(d3.extent(dataset, function(d) { return d.day; }));
	    y.domain(d3.extent(dataset, function(d) { return d.total; }));



	    svg.append("g")
	        .attr("class", "x axis")
	        .attr("transform", "translate(0," + height + ")")
	        .call(xAxis);
	    svg.append("g")
	        .attr("class", "y axis")
	        .call(yAxis)
	      .append("text")
	        .attr("transform", "rotate(-90)")
	        .attr("y", 6)
	        .attr("dy", ".71em")
	        .style("text-anchor", "end")
	        .text("Total Sales ($)");

	    svg.append("path")
			// explanation of datum() vs data() found here: http://stackoverflow.com/a/13728584
			.datum(dataset)
	        .attr("class", "line")
	        .attr("d", line);

	}

    function render_popular_product_pie( dataset, $selector ) {

        //Width and height
        var w = 400;
        var h = 400;

        // w should be divided by 2 to make proper pie chart
        var outerRadius = w / 2;
        // divide width to create donut, eg: w/3
        var innerRadius = 0;
        var arc = d3.svg.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius);

        var pie = d3.layout.pie()
            .value(function(d) {
                return d.sale_totals[0];
            });

        //Easy colors accessible via a 10-step ordinal scale
        var color = d3.scale.category10();
        //Create SVG element
        var svg = d3.select($selector)
            .append("svg")
            .attr("width", w)
            .attr("height", h);

        //Set up groups
        var arcs = svg.selectAll("g.arc")
            .data(pie(dataset))
            .enter()
            .append("g")
            .attr("class", "arc")
            .attr("transform", "translate(" + outerRadius + "," + outerRadius + ")")
            .on('click', function(d) {
                // do something when you click on section.
            });

        //Draw arc paths
        arcs.append("path")
            .attr("fill", function(d, i) {
                return color(i);
            })
            .attr("d", arc);

        //Labels
        arcs.append("text")
            .attr("transform", function(d) {
                return "translate(" + arc.centroid(d) + ")";
            })
            .attr("text-anchor", "middle")
            .text(function(d) {
                var text = d.data.product_name + ' ($' + d.data.sale_totals[0] + ')';
                return text;
            });
    }
})(jQuery);
