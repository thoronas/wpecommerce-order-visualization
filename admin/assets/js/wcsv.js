(function($) {
    'use strict';

    $(function() {
		// render_pie_chart( dataset.monthly, "#report" );
		render_pie_chart( dataset.monthly, "#report" );
		render_product_sales_graph( dataset.days, "#price-chart" );
		render_pie_chart( dataset.users, "#users" );
		render_pie_chart( dataset.unregistered, "#unregistered" );
		render_pie_chart( dataset.categories, "#category-sales");
    });

	function render_product_sales_graph( dataset, $selector ) {

		//Width and height
		var margin = {top: 20, right: 20, bottom: 30, left: 50},
	    width = 960 - margin.left - margin.right,
	    height = 500 - margin.top - margin.bottom;
		// need to parse date into format D3 can read.
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

			// explanation of datum() vs data() found here: http://stackoverflow.com/a/13728584
	    svg.append("path")
			.datum(dataset)
	        .attr("class", "line")
	        .attr("d", line);

	}

    function old_render_pie_chart( dataset, $selector ) {

        //Width and height
        var w = 400;
        var h = 500;

        // w should be divided by 2 to make proper pie chart
        var outerRadius = w / 2;
        // divide width to create donut, eg: w/3
        var innerRadius = 0;
        var arc = d3.svg.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius);

        var pie = d3.layout.pie()
            .value(function(d) {
				return d.sale_totals;
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
                console.log(d);
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
                var text = d.data.name + ' ($' + d.data.sale_totals + ')';
                return text;
            });
    }


	function render_pie_chart( data, $selector ) {

		var width = 400,
		    height = 450,
			radius = Math.min(width, height) / 2;
		// w should be divided by 2 to make proper pie chart
        var outerRadius = width;
		var svg = d3.select( $selector )
			.append("svg")
			.append("g")
			.attr("transform", "translate(" + outerRadius + "," + outerRadius / 2 + ")");

		svg.append("g")
			.attr("class", "slices");
		svg.append("g")
			.attr("class", "labels");
		svg.append("g")
			.attr("class", "lines");

		var pie = d3.layout.pie()
			.value(function(d) {
				return d.sale_totals;
			}).sort(d3.ascending);
		var arc = d3.svg.arc()
			// .outerRadius(radius * 0.8)
			// .innerRadius(radius * 0.4);
			.outerRadius(radius * 0.8)
			.innerRadius(0);
		var outerArc = d3.svg.arc()
			.innerRadius(radius * 0.85)
			.outerRadius(radius * 0.85);

		// svg.attr("transform", "translate(" + width / 2 + ",500)");

		var key = function(d){ return d.data.name; };

		var color = d3.scale.category20();

		/* ------- PIE SLICES -------*/
		var slice = svg.select(".slices").selectAll("path.slice")
			.data(pie(data), key);

		slice.enter()
			.insert("path")
			.style("fill", function(d) { return color(d.data.name); })
			.attr("class", "slice");

		slice
			.transition().duration(1000)
			.attrTween("d", function(d) {
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					return arc(interpolate(t));
				};
			});

		slice.exit()
			.remove();

		/* ------- TEXT LABELS -------*/

		var text = svg.select(".labels").selectAll("text")
			.data(pie(data), key);

		text.enter()
			.append("text")
			.attr("dy", ".35em")
			.text(function(d) {
				return d.data.name + " ($"+d.data.sale_totals+")";
			})
			.on("click", function(d){
				console.log(d);
			});

		function midAngle(d){
			return d.startAngle + (d.endAngle - d.startAngle)/2;
		}

		text.transition().duration(1000)
			.attrTween("transform", function(d) {
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					var pos = outerArc.centroid(d2);
					pos[0] = radius * (midAngle(d2) < Math.PI ? 1 : -1);
					return "translate("+ pos +")";
				};
			})
			.styleTween("text-anchor", function(d){
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					return midAngle(d2) < Math.PI ? "start":"end";
				};
			});

		text.exit()
			.remove();

		/* ------- SLICE TO TEXT POLYLINES -------*/

		var polyline = svg.select(".lines").selectAll("polyline")
			.data(pie(data), key);

		polyline.enter()
			.append("polyline");

		polyline.transition().duration(1000)
			.attrTween("points", function(d){
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					var pos = outerArc.centroid(d2);
					pos[0] = radius * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
					return [arc.centroid(d2), outerArc.centroid(d2), pos];
				};
			});

		polyline.exit()
			.remove();

	}

})(jQuery);
