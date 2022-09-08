(function ($){
	Drupal.behaviors.gimOlMapContentElement = {
		attach: function (context, settings){


			// read default_value (selected value) from the hidden form element
			var selectedName = $("#colorBrewerInput").val();
			/*
			* Function creates color sheme from color array
			*
			* @colors           array     Array with colors
			*/
			function colorSquares(colors, name){

				var colorDiv = $("<div class='colorDiv' data-name='"+name+"' style=' width: 100px;height: 25px;cursor:pointer'></div>");

				$.each(colors, function(index,value){
					colorDiv.append($("<div style='width: 15px;height: 15px;margin: 0px;outline: 1px solid;display: inline-block;float: left;background: "+value+";' />"));
				})

				if (selectedName == name){
					colorDiv.append($("<div class='paletteSelection paletteBrewerDisplay' />"));
				} else {
					colorDiv.append($("<div class='paletteSelection' />"));
				}

				return colorDiv;
			}

			if($("#inverse_pallete_checkbox").prop('checked') == true){
				reverseColors();
			}
			renderColorPalete();

			// The function reverses colors in each row
			function reverseColors(){
				var reversed = {};
				$.each(colorbrewer, function (id, val){
					reversed[id] = {5:val[5].reverse()};
				});
				colorbrewer = reversed;
			}

			function renderColorPalete(){

				// empty container first
				$("#colorBrewerContainer").empty();
				// create palette for every colorbrewer item
				// colorbrewer defined in colorbrewer.min.js


				$.each(colorbrewer, function(index,value){
					$("#colorBrewerContainer").append(colorSquares(value[5], index));
				});

				var container = $('#colorBrewerContainer');
				var scrollTo = $("[data-name='"+selectedName+"']");

				// clicked color scheme save to drupal fapi element
				$(".colorDiv").click(function(){
					$(".paletteBrewerDisplay").removeClass("paletteBrewerDisplay");
					$(this).children(".paletteSelection").addClass("paletteBrewerDisplay");
					$("#colorBrewerInput").val($(this).data("name")).trigger('change');
					selectedName = $(this).data("name");
				});
				// click listener - when colorbrewer div is visible, set slider to position of default element
				var onceFlag = true;
				$("#colorBrewerContainer").click(function(){
					function process(){
						if(container.is(":visible") && onceFlag){
							container.scrollTop(scrollTo.offset().top - container.offset().top + container.scrollTop() - 100);
							onceFlag = false;
						}
					}
					setTimeout(process,300);
				});

			}

			// handle inverse color CB click
			$("#inverse_pallete_checkbox").click(function(){
				reverseColors();
				renderColorPalete();
			});



		}
	}
})(jQuery);
