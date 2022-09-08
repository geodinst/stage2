define([], function() {
	// "use strict";
	var CC = {};

	/**
	 * Determine the coefficient of determination (r^2) of a fit from the observations
	 * and predictions.
	 *
	 * @param {Array<Array<number>>} data - Pairs of observed x-y values
	 * @param {Array<Array<number>>} results - Pairs of observed predicted x-y values
	 *
	 * @return {number} - The r^2 value, or NaN if one cannot be calculated.
	 */
	function determinationCoefficient(data, results) {
		var predictions = [];
		var observations = [];

		data.forEach(function(d, i) {
			if (d[1] !== null) {
				observations.push(d);
				predictions.push(results[i]);
			}
		});

		var sum = observations.reduce(function(a, observation) {
			return a + observation[1];
		}, 0);
		var mean = sum / observations.length;

		var ssyy = observations.reduce(function(a, observation) {
			var difference = observation[1] - mean;
			return a + difference * difference;
		}, 0);

		var sse = observations.reduce(function(accum, observation, index) {
			var prediction = predictions[index];
			var residual = observation[1] - prediction[1];
			return accum + residual * residual;
		}, 0);

		return 1 - sse / ssyy;
	};
	/**
	 * Round a number to a precision, specificed in number of decimal places
	 *
	 * @param {number} number - The number to round
	 * @param {number} precision - The number of decimal places to round to:
	 *                             > 0 means decimals, < 0 means powers of 10
	 *
	 *
	 * @return {numbr} - The number, rounded
	 */
	function round(number, precision) {
		var factor = Math.pow(10, precision);
		return Math.round(number * factor) / factor;
	};

	CC.lrg = function(data, options) {

		var sum = [0, 0, 0, 0, 0];
		var len = 0;

		for (var n = 0; n < data.length; n++) {
			if (data[n][1] !== null) {
				len++;
				sum[0] += data[n][0];
				sum[1] += data[n][1];
				sum[2] += data[n][0] * data[n][0];
				sum[3] += data[n][0] * data[n][1];
				sum[4] += data[n][1] * data[n][1];
			}
		}

		var run = len * sum[2] - sum[0] * sum[0];
		var rise = len * sum[3] - sum[0] * sum[1];
		var gradient = run === 0 ? 0 : round(rise / run, options.precision);
		var intercept = round(sum[1] / len - gradient * sum[0] / len, options.precision);

		var predict = function predict(x) {
			return [round(x, options.precision), round(gradient * x + intercept, options.precision)];
		};

		var points = data.map(function(point) {
			return predict(point[0]);
		});

		return {
			points: points,
			predict: predict,
			equation: [gradient, intercept],
			r2: round(determinationCoefficient(data, points), options.precision),
			string: intercept === 0 ? 'y = ' + gradient + 'x' : 'y = ' + gradient + 'x + ' + intercept
		};
	};


	CC.drawChart = function(cache) {

		var ctx = $('#load_all_results_chart');

		var var0 = cache[Object.keys(cache)[0]].vidAllDataFiltered;
		var var1 = cache[Object.keys(cache)[1]].vidAllDataFiltered;


		var line_dot = [];
		var lrg = [];
		var maxX = 0;
		var minX = 0;
		var res = $.map(var0.length > var1.length ? var0 : var1, function(v, i) {
			if (var0[i].value > maxX) {
				maxX = var0[i].value;
			}
			if (var0[i].value < minX) {
				minX = var0[i].value;
			}
			line_dot.push({
				x: var0[i].value,
				y: var1[i].value
			});
			lrg.push(
				[var0[i].value, var1[i].value]
			);
		});

		var regression = CC.lrg(lrg, {
			precision: 3
		});

		yValue = regression.equation[0] * maxX + regression.equation[1];
		minyValue = regression.equation[0] * minX + regression.equation[1];
		var equation = regression.string;
		var line_data = [{
				x: minX,
				y: minyValue
			},
			{
				x: maxX,
				y: yValue
			}
		];
		ctx.prop('height', 350);
		var chart = new Chart(ctx, {
			type: 'line',
			data: {
				datasets: [{
					type: 'line',
					label: 'Linear regression',
					data: line_data,
					fill: false,
					backgroundColor: "rgba(218,83,79, .7)",
					borderColor: "rgba(218,83,79, .7)",
					borderWidth: 0.5,
					pointRadius: 0
				}, {
					type: 'bubble',
					label: 'Real',
					data: line_dot,
					backgroundColor: "rgba(76,78,80, .7)",
					borderColor: "transparent"
				}]
			},
			options: {
				title: {
					display: true,
					text: 'Linear regression equation ( '+ equation+' )',
					fontStyle: 'bold',
					position: 'bottom',
				},
				tooltips: {
					enabled: false,
				},
				legend: {
					display: false
				},
				tooltips: {
					display: false
				},
				scales: {
					xAxes: [{
						gridLines: {
							display: false
						},
						type: 'linear',
						position: 'bottom',
						ticks: {
							autoSkip: false,
							// display: false
						},
						scaleLabel: {
							fontSize: 8,
							display: true,
							labelString: cache[Object.keys(cache)[0]].var_name + ', ' + cache[Object.keys(cache)[0]].var_su + ' (' + cache[Object.keys(cache)[0]].var_date + ')'
						}
					}],
					yAxes: [{
						display: true,
						gridLines: {
							display: false
						},
						barThickness: 20,
						ticks: {
							// display: false,
						},
						scaleLabel: {
							fontSize: 8,
							display: true,
							labelString: cache[Object.keys(cache)[1]].var_name + ', ' + cache[Object.keys(cache)[1]].var_su + ' (' + cache[Object.keys(cache)[1]].var_date + ')'
						}
					}]
				}
			}
		});
		$('#tab8').find('#download_all_delineation').click(function() {
			this.href = $('#tab8').find('#load_all_results_chart')[0].toDataURL();
			this.download = 'STAGE.png';
		});
	};

	return CC;
});
