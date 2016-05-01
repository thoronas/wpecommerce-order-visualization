(function($) {
    'use strict';

    $(function() {
		// jQuery UI datepicker
		$( ".datepicker" ).datepicker();

		$('#get-sales-data').click( function(e){
			e.preventDefault();
			var $that = $(this);
			var start_date = $('#start-date').val();
			var end_date = $('#end-date').val();
			var current_graph = $('.wpscv-tab.active').data('graph');
			current_graph = current_graph.substr(1);

			var data = {
				'action'        : 'get_sales_data',
				'start_date'    : start_date,
				'end_date'      : end_date,
				'current_graph' : current_graph,
			};
			$.ajax({
				type: 'POST',
				url: ajax_info.ajax_url,
				data: data,
				dataType: "json",
				success: function( response ) {
					var $graph = response[0];
					var $sales_data = response[1];
					switch ( $graph ) {
						case "sales-dates":
							render_product_sales_graph_v2( $sales_data, "#price-chart" );
							break;
						case "category-sales":
							render_pie_chart( $sales_data, "#categories");
							break;
						case "top-products":
							render_pie_chart( $sales_data, "#report" );
							break;
						case "user-sales":
							render_pie_chart( $sales_data, "#users" );
							break;
					}
				}
			}).fail(function (response) {
				if ( window.console && window.console.log ) {
					console.log( response );
				}
			});
		});

		// Tab functionality
		$('.wpscv-tab').on('click', function(e){
			e.preventDefault();

			var that = $(this);
			var graph = that.data('graph');
			that.addClass('active').siblings().removeClass('active');
			$(graph).addClass('active').siblings().removeClass('active');
		});

		// demo data
		render_pie_chart( dataset.monthly, "#report" );
		render_pie_chart( dataset.users, "#users" );
		render_pie_chart( dataset.categories, "#categories");
		render_product_sales_graph_v2( dataset.days, "#price-chart" );


		$('#users').on( 'click', '#get_registered_users', function(){
			var data = {
				'action': 'wcsv_registered_user_sales',
			};
			$.ajax({
				type: 'POST',
				url: ajax_info.ajax_url,
				data: data,
				dataType: "json",
				success: function( response ) {
					render_pie_chart( response, "#users" );
				}
			}).fail(function (response) {
				if ( window.console && window.console.log ) {
					console.log( response );
				}
			});
		});

		$('#update-product-data').on( 'click', function(){
			var selected_products = [];
			var start_date = $('#start-date').val();
			var end_date = $('#end-date').val();
			var data = {
				'action'     : 'wcsv_monthly_sales',
				'products'   : '',
				'start_date' : start_date,
				'end_date'   : end_date
			};
			$('.product-selection .product-filter').each(function(){
				var $that = $(this);
				if ( $that.is(':checked') ){
					selected_products.push( $that.val() );
				}
			});
			// only run ajax if there are checkboxes set.
			if(selected_products.length !== 0 ){
				data.products = selected_products;
				$.ajax({
					type: 'POST',
					url: ajax_info.ajax_url,
					data: data,
					dataType: "json",
					success: function( response ) {
						render_product_sales_graph_v2( response.data, "#price-chart" );
					}
				}).fail(function (response) {
					if ( window.console && window.console.log ) {
						console.log( response );
					}
				});
			}
		});
    });

	function render_pie_chart( data, $selector ) {
		d3.select($selector).html("");

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
				if( d.data.action == 1 ) {
					// do a thing!
					get_unregistered_users($selector);
				}
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

	function render_product_sales_graph_v2( dataset, $selector ) {
		// clear old svgs.
		d3.select($selector).html("");
		//Width and height
		var margin = {top: 20, right: 150, bottom: 30, left: 50},
		width = 900 - margin.left - margin.right,
		height = 400 - margin.top - margin.bottom;
		// need to parse date into format D3 can read.
		var parseDate = d3.time.format("%Y-%m-%d").parse;
		// set the ranges. Uses SVG dimensions.
		var x = d3.time.scale()
			.range([0, width]);
		var y = d3.scale.linear()
			.range([height, 0]);
		// Draw the line based on data.
		var line = d3.svg.line()
			.x(function(d) { return x(d.day); })
			.y(function(d) { return y(d.total); });
		//
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
	    var color = d3.scale.category10();   // set the colour scale

		// go through dataset and parse date.
		dataset.forEach(function(d) {
		  d.day = parseDate(d.day);
		  //convert total to number.
		  d.total = +d.total;

		});

		// Nest the entries by symbol
	    var dataNest = d3.nest()
	        .key(function(d) {return d.product;})
	        .entries(dataset);
	    var legendSpace = height/dataNest.length; // spacing for legend

		x.domain(d3.extent(dataset, function(d) { return d.day; }));
		y.domain(d3.extent(dataset, function(d) { return d.total; }));
		dataNest.forEach( function(d,i){
			svg.append("path")
				.attr("class", "line"+d.key)
				.style("stroke", function() {
					// Add the colours dynamically
                	return color(d.key);
				})
				.style("fill", "none")
				.attr("d", line(d.values));

			// add legend color box.
			svg.append("rect")
				.attr("x", width + 10) // spacing
				.attr("y", (legendSpace/2)+i*legendSpace - 7)
				.attr("width", 5)
				.attr("height", 5)
				.style("fill", function() { // dynamic colours
	                return color(d.key);
				});

			// Add the Legend
	        svg.append("text")
	            .attr("x", width + 17) // spacing
	            .attr("y", (legendSpace/2)+i*legendSpace)
	            .attr("class", "legend")    // style the legend
	            .text(d.key);
		});

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
	}

	function get_unregistered_users(selector){
		console.log(selector);
		var data = {
			'action': 'wcsv_nonregisted_sales',
		};
		$.ajax({
			type: 'POST',
			url: ajax_info.ajax_url,
			data: data,
			dataType: "json",
			success: function( response ) {
				render_pie_chart( response, selector );
				jQuery(selector).prepend('<button id="get_registered_users" class="button">View Registered User Sales</button>');
			}
		}).fail(function (response) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		});
	}
})(jQuery);
