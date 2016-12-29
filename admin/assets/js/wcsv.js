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
				type		: 'POST',
				url			: ajax_info.ajax_url,
				data		: data,
				dataType	: "json",
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

		// console.log(dataset);

		// demo data
		// d3 functions
		// render_pie_chart( dataset.monthly, "#report" );
		// render_pie_chart( dataset.users, "#users" );
		// render_pie_chart( dataset.categories, "#categories");
		render_product_sales_graph_v2( dataset.days, $('#price-chart') );


		$('#users').on( 'click', '#get_registered_users', function(){
			var start_date = $('#start-date').val();
			var end_date = $('#end-date').val();
			var data = {
				'action'		: 'wcsv_registered_user_sales',
				'start_date'    : start_date,
				'end_date'      : end_date,
			};
			$.ajax({
				type		: 'POST',
				url			: ajax_info.ajax_url,
				data		: data,
				dataType	: "json",
				success: function( response ) {
					//render_pie_chart( response, "#users" );
					console.log(response);

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
					type		: 'POST',
					url			: ajax_info.ajax_url,
					data		: data,
					dataType	: "json",
					success: function( response ) {
						render_product_sales_graph_v2( response.data, $('#price-chart') );
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
		// var ctx = $('#price-chart');
		$selector.html('').siblings('.chartjs-hidden-iframe').remove();
		var data = {
			labels : dataset[0].day_labels,
			datasets: []
		};

		// pass the details for each set of sales data as a dataset
		// TODO: add custom colors for different lines.
		dataset.forEach( function(el){
			data.datasets.push({
	            label: el.product_label,
	            data: el.days_income,
	            fill: false,
	            lineTension: 0.1,
	            backgroundColor: "rgba(75,192,192,0.4)",
	            borderColor: "rgba(75,192,192,1)",
	            borderCapStyle: 'butt',
	            borderDash: [],
	            borderDashOffset: 0.0,
	            borderJoinStyle: 'miter',
	            pointBorderColor: "rgba(75,192,192,1)",
	            pointBackgroundColor: "#fff",
	            pointBorderWidth: 1,
	            pointHoverRadius: 5,
	            pointHoverBackgroundColor: "rgba(75,192,192,1)",
	            pointHoverBorderColor: "rgba(220,220,220,1)",
	            pointHoverBorderWidth: 2,
	            pointRadius: 1,
	            pointHitRadius: 10,
			});
		} );
		// create the line chart
		var myLineChart = new Chart($selector, {
		    type: 'line',
		    data: data
		});
	}

	function get_unregistered_users(selector){
		var start_date = $('#start-date').val();
		var end_date = $('#end-date').val();
		var data = {
			'action'		: 'wcsv_nonregisted_sales',
			'start_date'    : start_date,
			'end_date'      : end_date,
		};
		$.ajax({
			type	: 'POST',
			url		: ajax_info.ajax_url,
			data	: data,
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
