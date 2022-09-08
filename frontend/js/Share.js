define(['js/controller', 'js/embed.js', 'js/utils', 'locale'], function(controller, embed, U, locale) {
	var t = window.app.T;
	var selected_language = window.app.lang;

	var render_y_location; // zgornja koordinata za renderiranje elementov v legendi (odvisna od tega, kaj je prikazano na karti)
	var checkboxes = {
		'scale_cb': 'Scale',
		'level_cb': 'Level',
		'year_cb':  'Year',
		'legend_cb': 'Legend',
		'desctiption_cb': 'Description',
		'footer_cb': 'Footer',
		'sub_cb':'Spatial units borders'
	};

	return function() {

		this.back = function(){
			$('#tab8').find('#back_toinfo').click(function(){
				controller.openTab('tab2');
			});
		}

		this.map_download = function() {
			var var_values_id = controller.getvid();
			var downloadLinks={};
			downloadLinks['do_shp'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=0&format=SHAPE-ZIP';
			downloadLinks['do_shp_all'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=1&format=SHAPE-ZIP';
			downloadLinks['do_geopackage'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=0&format=gpkg';
			downloadLinks['do_geopackage_all'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=1&format=gpkg';
			downloadLinks['do_tsv'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=0&format=TSV';
			downloadLinks['do_tsv_all'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=1&format=TSV';
			downloadLinks['do_xlsx'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=0&format=xlsx';
			downloadLinks['do_xlsx_all'] = locale.s2c + 'export?var_values_id=' + var_values_id + '&all_variables=1&format=xlsx';

			var content_export_map_link = '<div id="static_content_popup" class = "side-popup">';
			content_export_map_link += '<ul><li>';
			content_export_map_link += '<a id="back_toinfo"><button type="button" class = "style_button style_buttonBack">' + t['Back'] + '</button></a>';
			content_export_map_link += '</li></ui>';
			content_export_map_link += '<a id="do_shp" class="stage-download" href="#"><button type="button" class = "style_button ">' + t['Download SHP'] + '</button></a>';
			content_export_map_link += '<a id="do_shp_all" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download SHP (all variables)'] + '</button></a>';
			content_export_map_link += '<a id="do_geopackage" class="stage-download" href="#"><button type="button" class = "style_button ">' + t['Download GeoPackage'] + '</button></a>';
			content_export_map_link += '<a id="do_geopackage_all" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download GeoPackage (all variables)'] + '</button></a>';
			content_export_map_link += '<a id="do_tsv" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download TSV'] + '</button></a>';
			content_export_map_link += '<a id="do_tsv_all" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download TSV (all variables)'] + '</button></a>';
			content_export_map_link += '<a id="do_xlsx" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download XLSX'] + '</button></a>';
			content_export_map_link += '<a id="do_xlsx_all" class="stage-download" href="#"><button type="button" class = "style_button">' + t['Download XLSX (all variables)'] + '</button></a>';
			content_export_map_link += '<a id="download_image"><button type="button" class = "style_button save_export_image share_chart_btn_class">' + t['Download as image'] + '</button></a>';
			content_export_map_link += '</div>';
			$tab8 = $('#tab8').find('#share_content_div').html(content_export_map_link);
			
			$tab8.find('.stage-download').click(function() {
				var id=$(this).attr('id');
				window.onbeforeunload = null;
				window.location = downloadLinks[id];
				setTimeout(function(){
					window.onbeforeunload = function() {
						return true;
					};
				}, 100);
			});

			$tab8.find('#download_image').click(function() {
				description_var = $('#selected-variable_description').text();
				var maxchars = 800;
				var seperator = ' ...';

				if (description_var.length > (maxchars - seperator.length)) {
					description_var = description_var.substr(0, maxchars - seperator.length) + seperator;
				}
				populate_export_image_popup(description_var);
				$('#tab9').find('#share_content_tab_title').html(t['Download as image']+'; '+$('#tab2 #selected-variable').first().text());
      	controller.openTab('tab9');
			});

		};

		this.populate_share_shp = function() {
			var content_export_map_link = '<div id="static_content_popup" class = "side-popup">';
			content_export_map_link += '<ul><li>';
			content_export_map_link += '<a id="back_toinfo"><button type="button" class = "style_button style_buttonBack">' + t['Back'] + '</button></a>';
			content_export_map_link += '</li>';
			content_export_map_link += '<li>';
			content_export_map_link += '<h3>' + t['Share link'] + '</h3>';
			content_export_map_link += '<input type="text" id = "map_link_ta">';
			content_export_map_link += '<button id = "copy_map_link" type="button" class = "style_button">' + t['Copy'] + ' <i class="icon ion-ios-copy-outline"></i> </button>';
			content_export_map_link += '</li><li>';
			content_export_map_link += '<h3>' + t['Embed map'] + '</h3>';
			content_export_map_link += '<input type="text" id = "map_link_embed">';
			content_export_map_link += '<button id = "copy_map_link_embed" type="button" class = "style_button">' + t['Copy'] + ' <i class="icon ion-ios-copy-outline"></i> </button>';
			content_export_map_link += '</li></ul>';
			content_export_map_link += '</div>';
			$tab8 = $('#tab8').find('#share_content_div').html(content_export_map_link);
				var deepLink = controller.getDeepLink();
				$('#tab8').find('#map_link_ta').val(deepLink);
				var $div = $('<div id="embed_map_div"><div/>');
				$tab8.append($div);
			embed.init($div, {
				deepLink: deepLink
			});
			$tab8.find('#map_link_embed').val($div.html());
			$div.hide();

			$tab8.find('#copy_map_link').click(function() {
				var $copy_text = $tab8.find('#map_link_ta').val();
				copyToClipboard($copy_text);
			});
			$tab8.find('#copy_map_link_embed').click(function() {
				var $copy_text = $tab8.find('#map_link_embed').val();
				copyToClipboard($copy_text);
			});
		}


		function copyToClipboard(text) {
			var textArea = document.createElement("textarea");
			textArea.value = text;
			document.body.appendChild(textArea);
			textArea.select();
			try {
				var successful = document.execCommand('copy');
			} catch (err) {
				console.log('Oops, unable to copy');
			}
			document.body.removeChild(textArea);
		}

		var rendering=false;

		 function populate_export_image_popup(description_var) {
			var content_export="";
			content_export += '<a id="back_to_tab_8"><button type="button" class = "style_button style_buttonBack">' + t['Back'] + '</button></a></div>';
			content_export += '<div id= "canvas_container" class = "canvas_container"></div>';;
			content_export += '<div class = "static_content_content">';
			content_export += '<h4 id = "map_title_label">' + t['Map title'] + '</h4>';
			title = controller.getPopupTitle() ? controller.getPopupTitle():'';

			content_export += '<textarea class = "ta_export" id="set_map_title" maxlength="64" rows="2" WRAP="HARD">'+title+'</textarea><br>';
			content_export += '<div id="options_container_pic_export" class="u-vmenu"><ul id = "first_li_option_pic_exp"><li><a>' + t['Options'] + '</a><ul id= "share_content_list_element"><li><div id="set_pic_prop_div" class="set_pic_prop"></div></li></ul></li></div>'
			content_export += '<a id="download"><button type="button" class = "style_button save_export_image share_chart_btn_class">' + t['Download'] + '</button></a>';

			var cb_export_container = '<div class = "cb_export_container">';
			cb_export_container += '<h4 id = "map_description_label">' + t['Map description'] + '</h4>';
			cb_export_container += '<textarea class = "ta_export" id="set_map_description" maxlength="800" rows="10" WRAP="HARD">' + description_var + '</textarea><br>';

			$.each(checkboxes, function(name, value) {
				cb_export_container += '<div class = "export-map-cb-div"><input type="checkbox" name = "map_export_options" class="switch_1" id="' + name + '" checked><label for=' + name + '> ' + t['' + value + ''] + '</label></div>';
			});
			cb_export_container += '</div">';

			$tab9 = $('#tab9').find('#share_content_div').html(content_export);
			$tab9 = $('#tab9').find('#set_pic_prop_div').html(cb_export_container);

			$options_container_pic_export = $('#tab9').find('#options_container_pic_export');

			if($('#tab9').find('#set_map_description').val() == ''){
				$tab9 = $('#tab9').find('#desctiption_cb').attr('checked', false);

			};
			$tab9 = $('#tab9').find('#back_to_tab_8').click(function(){
				controller.openTab('tab8');
			});

			$options_container_pic_export.vmenuModule({
				Speed: 200,
				autostart: false,
				autohide: false
			});

			var scale_length = 500;

			// get advanced settings export_img_settings
			U.get(window.app.s2c + 'client_get_advanced_settings', {
				setting: 'export_img_settings'
			}, function(data) {
				if ($.isEmptyObject(data)) {
					console.warn('Check service client_get_advanced_settings - export_img_settings');
					return;
				} else {
					var labels = data.labels[window.app.lang];

					//if locale language labels is undefined use english labels
					if (labels===undefined) {
						labels=data.labels.en;
					}

					//override labels with translations if language different than english
					if (window.app.lang!==undefined && window.app.lang.toLowerCase()!='en'){
						$.each(labels,function(label){
							if (t[label]!==undefined && $.trim(t[label])!=='') labels[label]=t[label];
						});
					}

					render_preview(labels, scale_length);
					$('#tab9').find('#set_map_title').on('change', function() {
						render_preview(labels, scale_length);
					});
					$('#tab9').find('#set_map_description').on('change', function() {
						render_preview(labels, scale_length);
					});

					$('#tab9').find('#download').click(function() {
						$(this).removeAttr( "href download");
						if ($('#tab9').find('#set_map_title').val() !=''){
							this.href = $('#previewcanvas')[0].toDataURL();
							this.download = 'STAGE';

							if (rendering===false) return true;
							var $self=$(this);
							var tid=setInterval(function(){
								if (rendering===false) {
									clearInterval(tid);
									$self.find('button').click();
								}
							},100);
						}
						else{
							alert(labels.no_title);
						}
						return false;
					});
					$('#tab9').find("input[name*='map_export_options']").change(function() {
						render_preview(labels, scale_length);
					});
				}
			});

		};



		/**
		 *@param map_title string title of the map
		 */
		function render_preview(labels, scale_length) {
			rendering=true;
			$('#canvas_container').empty();
			$('<canvas>').attr({
				id: 'previewcanvas'
			}).css({
				'max-width': 95 + '%',
			}).appendTo('#canvas_container');


			//*************** RENDER PROPERTIES ***************
			var canvas_width = 2970; // skupna šitina platna
			var canvas_height = 2100; // skupna višina platna
			var background = 'white'; // barva platna
			var external_lw = 4; // širina/teza zunanje obrobe
			var internal_lw = 2; // širina/teza notranje locilne crte
			var border_padding_horizontal = 0.932; // horizontalna uporabna velikost lista
			var border_padding_vertical = 0.904; // vertikalna uporabna velikost lista
			var inner_border_padding = 0.2525; // širina polja za naslov, legendo ...
			var inner_content_padding = 0.010; // odmik levo in desno od notranjega okvirja
			var top_logo_padding = 0.07; // odmik logotipa (SURS od zgornjega roba)
			var top_logo_height = locale.top_logo_height;//66 is the prefered value for surs logo;
			var top_logo_width = locale.top_logo_width;//360 is the prefered value for surs logo;
			var map_title_padding = 0.05; // razmik med logotipom in naslovom
			var map_title_lineHeight = 0.024; // velikost pisave naslova
			var map_title_font_height = 0.022; // velikost pisave
			var map_title_color = 'black';
			var map_title_style = 'Arial';
			var title_bottom_padding = 0.027;

			var map_scale_padding = 0.019; // razmik med logotipom in naslovom
			var map_scale_lw = 10; // debelina črte za skalo
			var map_scale_font_height = 0.01; // velikost pisave
			var scale_line_padding = 0.01; // razmik med črto za merilo in naslovom
			var map_scale_bottom_padding = 0.019;

			var map_level_padding = 0.019; // razmik med merilom in napisom LEVEL
			var map_level_font_height = 0.018; // velikost napisa LEVEL

			var map_level_label_padding = 0.001; // razmik med napisom LEVEL in dejansko vrednostjo level (e.g. Občine)
			var map_legend_level_font_height = 0.015; // velikost napisa LEVEL

			var map_legend_padding = 0.019; // razmik med merilom in napisom LEGEND
			var map_legend_font_height = 0.018; // velikost napisa LEGEND

			var map_legend_label_padding = 0.015; // razmik med napisom LEGEND in dejansko legendo
			var map_legend_label_font_height = 0.015; // velikost napisa LEGEND

			var legend_legend_y = 0.37; // širina kvadratka legende
			var legend_lineHeight = 0.017; // visiva vrstice v legendi legende

			var map_description_padding = 0.019; // razmik med spodnjim kvadratkom legende in napisom DESCRIPTION
			var map_description_font_height = 0.018; // velikost napisa DESCRIPTION

			var map_description_label_padding = 0.0025; // razmik med napisom DESCRIPTION in dejansko legendo
			var map_description_label_font_height = 0.013; // velikost opisa
			var map_description_label_line_height = 0.015; // višina vrstice opisa

			var map_copyright_label_line_height = 0.013; // višina vrstice copyright
			var map_copyright_label_font_height = 0.012; // velikost pisave copyright
			var render_copyright_y_location = canvas_height * 0.91; // pozicija copyright

			// *********** calculated values ******************
			// izvedene vrednosti odmikov zunanjega okvirja
			var x_padding_top = canvas_width * (1 - border_padding_horizontal) / 2;
			var y_padding_top = canvas_height * (1 - border_padding_vertical) / 2;
			var x_padding_right = canvas_width * border_padding_horizontal;
			var y_padding_bottom = canvas_height * border_padding_vertical;

			// izvedene vrednosti locilne crte
			var y_sep_line_length = y_padding_top + canvas_height * border_padding_vertical;
			var x_sep_line_length = x_padding_top + canvas_width * inner_border_padding;

			// izvedene vrednosti vsebine legende
			var legend_content_x = x_padding_top + canvas_width * inner_content_padding;
			var y_padding_logo_surs = canvas_height * top_logo_padding;
			var y_padding_title = y_padding_logo_surs + top_logo_height + canvas_height * map_title_padding;
			map_title_lineHeight = canvas_height * map_title_lineHeight;
			var maxWidth_legend_content = x_sep_line_length - x_padding_top - 2 * (legend_content_x - x_padding_top);
			scale_line_padding = scale_line_padding * canvas_width;
			legend_legend_y = legend_legend_y * canvas_height;
			legend_lineHeight = legend_lineHeight * canvas_height;
			map_description_label_line_height = canvas_height * map_description_label_line_height;
			map_copyright_label_line_height = canvas_height * map_copyright_label_line_height;



			var canvas = $('#previewcanvas')[0];
			canvas.width = canvas_width;
			canvas.height = canvas_height;
			var ctx = canvas.getContext('2d');

			// render outer background
			ctx.fillStyle = background;
			ctx.fillRect(0, 0, canvas_width, canvas_height);
			// render external border
			ctx.lineWidth = external_lw;
			ctx.strokeRect(x_padding_top, y_padding_top, x_padding_right, y_padding_bottom);

			// render the vertical line
			ctx.lineWidth = internal_lw;
			ctx.beginPath();
			ctx.moveTo(x_sep_line_length, y_padding_top);
			ctx.lineTo(x_sep_line_length, y_sep_line_length);
			ctx.stroke();

			// render logo SURS
			var img_logo_surs = new Image();

			if (locale.exportLogo !== undefined|| val===false ){
				img_logo_surs.src = locale.exportLogo+selected_language+'.png';
			}
			else{
				img_logo_surs.src = 'logos/blank.png'
			}
			img_logo_surs.onload = function() {
				ctx.drawImage(img_logo_surs, legend_content_x, y_padding_logo_surs, top_logo_width, top_logo_height);
			}

			// render map title
			ctx.font = "bold " + canvas_height * map_title_font_height + "px " + map_title_style;
			ctx.fillStyle = map_title_color;
			var map_title = $('#set_map_title').val();
			render_y_location = wrapText(ctx, map_title.toUpperCase(), legend_content_x, y_padding_title, maxWidth_legend_content, map_title_lineHeight);

			render_y_location = render_y_location + canvas_height* title_bottom_padding;
			//calculate map image position and size

			var mapData = controller.getMapData();
			var tname = mapData.table_name;
			var extents = JSON.parse(mapData.extents);
			var coordinates = extents.coordinates;

			var p1=L.latLng(coordinates[0][0][1],coordinates[0][0][0]);
			var p2=L.latLng(coordinates[0][1][1],coordinates[0][1][0]);
			var p3=L.latLng(coordinates[0][2][1],coordinates[0][2][0]);
			var heightInMeters=p1.distanceTo(p2);
			var sw = controller.map.options.crs.project(p1); //in 900913
			var ne = controller.map.options.crs.project(p3); //in 900913

			var bbox = sw.x + ',' + sw.y + ',' + ne.x + ',' + ne.y;

			var mapImgHeight = parseInt(border_padding_vertical * canvas_height * 0.95);
			var mapImgWidth =  parseInt(canvas_width * (border_padding_horizontal - inner_border_padding)*0.95);

			var extentsHeight=ne.y-sw.y;
			var extentsWidth=ne.x-sw.x;

			var extentsImageWidth=parseInt((extentsWidth/extentsHeight)*mapImgHeight);
			var extentsImageHeight=mapImgHeight;

			var yOffset=0;
			var xOffset=0;

			if (extentsImageWidth>mapImgWidth) {
				extentsImageWidth=mapImgWidth;
				extentsImageHeight=parseInt((extentsHeight/extentsWidth)*mapImgWidth);
				yOffset=parseInt((mapImgHeight-extentsImageHeight)/2);
			}
			else{
				xOffset=parseInt((mapImgWidth-extentsImageWidth)/2);
			}

			var map_scale=extentsImageHeight/heightInMeters; //scale in px/m

			scale_length=parseInt(map_scale*50000);
			// render the scale
			if ($('#scale_cb').is(":checked")) {
				render_y_location = render_y_location + canvas_height * map_scale_padding;

				ctx.font = "bold " + canvas_height * map_scale_font_height + "px " + map_title_style;
				ctx.fillText("0", legend_content_x, render_y_location + map_scale_lw / 2);

				ctx.lineWidth = map_scale_lw;
				ctx.beginPath();
				ctx.moveTo(legend_content_x + scale_line_padding, render_y_location);
				ctx.lineTo(legend_content_x + scale_line_padding+scale_length, render_y_location);
				ctx.stroke();

				ctx.font = "bold " + canvas_height * map_scale_font_height + "px " + map_title_style;
				ctx.fillText("50 km", legend_content_x + scale_length + 2*scale_line_padding, render_y_location + map_scale_lw / 2);
				render_y_location = render_y_location + map_scale_bottom_padding*canvas_height;
			}

			// render level
			if ($('#level_cb').is(":checked")) {

				render_y_location = render_y_location + canvas_height * map_level_padding;
				ctx.font = "bold " + canvas_height * map_level_font_height + "px " + map_title_style;
				ctx.fillText(labels.level_label, legend_content_x, render_y_location);
				render_y_location = render_y_location + canvas_height * map_level_font_height + canvas_height * map_level_label_padding;
				ctx.font = canvas_height * map_legend_level_font_height + "px " + map_title_style;
				var su_text = $('#su_select').find('option:selected').text();
				// var var_date = $('#tab2 #selected-variable_date select option:selected').text();
				ctx.fillText(su_text, legend_content_x, render_y_location);
				render_y_location = render_y_location + map_legend_level_font_height*canvas_height;
			}
			// render level
			if ($('#year_cb').is(":checked")) {

				render_y_location = render_y_location + canvas_height * map_level_padding;
				ctx.font = "bold " + canvas_height * map_level_font_height + "px " + map_title_style;
				ctx.fillText(t['Year'], legend_content_x, render_y_location);
				render_y_location = render_y_location + canvas_height * map_level_font_height + canvas_height * map_level_label_padding;
				ctx.font = canvas_height * map_legend_level_font_height + "px " + map_title_style;
				// var su_text = $('#su_select').find('option:selected').text();
				var var_date = $('#tab2 #selected-variable_date select option:selected').text();
				ctx.fillText(var_date, legend_content_x, render_y_location);
				render_y_location = render_y_location + map_legend_level_font_height*canvas_height;
			}

			// render legend
			if ($('#legend_cb').is(":checked")) {

				render_y_location = render_y_location + canvas_height * map_legend_padding;
				ctx.font = "bold " + canvas_height * map_legend_font_height + "px " + map_title_style;
				ctx.fillText(labels.legend_label, legend_content_x, render_y_location);
				render_y_location = render_y_location + canvas_height * map_level_font_height + canvas_height * map_legend_label_padding;

				var legend_html = document.getElementById("legend").getElementsByClassName('table')[0];
				try {
					var legend_data = [];
					for (var i = 0, row; row = legend_html.rows[i]; i++) {
						new_row = [];
						var color = row.innerHTML.split(';"></td>')[0].split(':')[1].trim();
						var label = row.innerHTML.split(';"></td>')[1].split('<')[0].replace('&nbsp;–&nbsp;', " - ");
						var label = row.innerHTML.split(';"></td>')[1].split('>')[1].split('</')[0].replace('&nbsp;–&nbsp;', " - ");
						new_row.color = color.replace('; width', '');
						new_row.label = label;
						legend_data.push(new_row);
					}
				} catch (e) {
					console.log("variable not selected", e)
				}

				ctx.font = canvas_height * map_legend_label_font_height + "px " + map_title_style;

				render_y_location = renderLegend(ctx, legend_data, legend_content_x, render_y_location, maxWidth_legend_content, legend_lineHeight);
			}

			// render description
			if ($('#desctiption_cb').is(":checked")) {
				render_y_location = render_y_location + canvas_height * map_description_padding;
				ctx.font = "bold " + canvas_height * map_description_font_height + "px " + map_title_style;
				ctx.fillText(labels.description_label, legend_content_x, render_y_location);
				render_y_location = render_y_location + canvas_height * map_description_font_height + canvas_height * map_description_label_padding;
				var description_text = $('#set_map_description').val();
				ctx.font = canvas_height * map_description_label_font_height + "px " + map_title_style;
				// ctx.fillText(description_text, legend_content_x, render_y_location);
				render_y_location = wrapText(ctx, description_text, legend_content_x, render_y_location, maxWidth_legend_content, map_description_label_line_height);
			}

			// render copyright
			if ($('#footer_cb').is(":checked")) {
				ctx.font = canvas_height * map_copyright_label_font_height + "px " + map_title_style;
				// place legend label on the canvas
				wrapText(ctx, labels.copyright, legend_content_x, render_copyright_y_location, maxWidth_legend_content, map_copyright_label_line_height);
			}


			// *************** RENDER CHOROPLET MAP PICTURE ********************************************

			var mapUrl = 'proxy.php?service=WMS&version=1.1.0&request=GetMap&_port='+controller.port+'&layers=stage:' + tname + '&styles=&bbox=' + bbox + '&width=' + extentsImageWidth + '&height=' + extentsImageHeight + '&srs=EPSG:900913&format_options=antialias:none&format=image%2Fpng';

			const mapCanvas = document.createElement("canvas");
			const mapCtx = mapCanvas.getContext("2d");

			var imageObj = new Image();
			const borderObj = new Image();

			imageObj.xOffset=borderObj.xOffset = xOffset;
			imageObj.yOffset=borderObj.yOffset = yOffset;

			function putMapOnCanvas() {
				ctx.drawImage(mapCanvas, x_sep_line_length + canvas_width * inner_content_padding , y_padding_logo_surs);
			}
			
			borderObj.onload=function() {
                mapCtx.drawImage(this, this.xOffset, this.yOffset);
				putMapOnCanvas();
            }

			imageObj.onload = function() {
				
				mapCanvas.width = this.width+this.xOffset;
				mapCanvas.height = this.height+this.yOffset;
				
				mapCtx.drawImage(this, this.xOffset, this.yOffset);
				U.changeContextColors(mapCtx, mapData.variableValues, mapData.special_values, mapData.legendData.cba, mapData.legendData.cbac);
				
				if ($('#sub_cb').is(":checked")) {
					borderObj.src = 'proxy.php?service=WMS&version=1.1.0&request=GetMap&_port='+controller.port+'&layers=stage:' + tname + '&styles=line&transparent=true&bbox=' + bbox + '&width=' + extentsImageWidth + '&height=' + extentsImageHeight + '&srs=EPSG:900913&format_options=antialias:none&format=image%2Fpng';
				}
				else {
					putMapOnCanvas();
				}
			};

			imageObj.onerror = function() {};
			borderObj.onerror = function() {};

			imageObj.src = mapUrl;
		}
	}

	function renderLegend(context, legend_data, x, y, maxWidth, lineHeight) {

		lineHeight = lineHeight * 1.7;
		for (var n = 0; n < legend_data.length; n++) {
			context.fillStyle = legend_data[n].color;
			context.fillRect(x, y - lineHeight / 2, lineHeight * 0.9, lineHeight / 2);
			context.fillStyle = 'black';
			context.fillText(legend_data[n].label, x + lineHeight * 1.4, y);
			y += lineHeight*0.7;
		}
		return y;
	}

	/**
	 * Function is used to split text into lines based on the max width
	 */
	function wrapText(context, text, x, y, maxWidth, lineHeight, splitter) {
		if (splitter===undefined) splitter=' ';
		var words = text.split(splitter);
		var line = '';

		if (splitter=='\t') splitter='';

		for (var n = 0; n < words.length; n++) {
			var testLine = line + words[n] + splitter;
			var metrics = context.measureText(testLine);
			var testWidth = metrics.width;
			if (testWidth > maxWidth && n > 0) {
				if (splitter==' '){
					metrics=context.measureText(line);
					if (metrics.width>maxWidth && (line.indexOf('http://')!=-1 || line.indexOf('https://')!=-1)) {
						line=line.replace(/-/g,'-\t').replace(/\//g,'/\t').replace(/\/\t\/\t/g,'//');
						y=lineHeight+wrapText(context, line, x, y, maxWidth, lineHeight, '\t');
						line = words[n] + splitter;
						continue;
					}
				}
				context.fillText(line, x, y);
				line = words[n] + splitter;
				y += lineHeight;
			} else {
				line = testLine;
			}
		}

		if (line.length>0){
			context.fillText(line.slice(0,-1), x, y);
		}
		return y;
	}

})
