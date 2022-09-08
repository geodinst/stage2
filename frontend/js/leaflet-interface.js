define(['js/utils','locale'], function(U,locale) {
	var module = {};
	var map;
	var sidebar;
	var menuControlActive = false;
	var containerForSpatialLayerSelector;

	var t = window.app.T;
	var selected_language = window.app.lang;


	module.init = function(mapid) {
		map = L.map(mapid, {
			center: [46.01889973891195, 14.967498779296877],
			minZoom: 2,
			zoom: 9
		});

		if (locale.attribution!==undefined && locale.attribution!=='') {
			var attribution=locale.attribution[window.app.lang];
			if (attribution!==undefined){
				map.attributionControl.addAttribution(attribution);
			}
		}

		map.zoomControl.setPosition('bottomright');
		var control = L.control.zoomBox({
			modal: false,
			position: 'bottomright'
		});
		map.addControl(control);

		// get footer content
		if (locale.hide_footer===undefined || locale.hide_footer===false){
			U.get(window.app.s2c+'client_get_advanced_settings', {setting:'footer_wm'}, function(data){
				if (data[selected_language]===undefined || data[selected_language].trim()=='') return;
				var footer_text = (data[selected_language]);
				var logo = L.control({position: 'bottomleft'});
				logo.onAdd=function (map){
				var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
					container.innerHTML=footer_text;
					container.style="bottom:10px;padding:2px";
					return container;
				};
				logo.addTo(map);

			 },
			 'json', $('body'));
		}

		var mapInfoContent = '<div id="mapInfo"></div)>';
		var mapInfo = L.control({
			position: 'topright'
		});
		mapInfo.onAdd = function(map) {
			var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control ');
			container.innerHTML = mapInfoContent;
			return container;
		};
		mapInfo.addTo(map);
		L.Control.geocoder({
			defaultMarkGeocode: false,
			position: 'topright'
		})
		.on('markgeocode', function(e) {

			var result = e.geocode || e;

			map.fitBounds(result.bbox);

			L.popup({
				autoClose: false
			})
			.setLatLng(result.center)
			.setContent(result.html || result.name)
			.openOn(map);
		//
		})
		.addTo(map);

		// get layers
		var baseMaps = {};
		U.get(window.app.s2c + 'layers', {}, function(data) {
				var sLayer;
				for (var i in data) {
					var layer = data[i];
					var name = ("name" in layer) ? layer['name'] : "";
					var url = ("url" in layer) ? layer['url'] : "";
					var attribution = ("attribution" in layer) ? layer['attribution'] : "";
					var subdomains = ("subdomains" in layer) ? layer['subdomains'] : [];

					sLayer = L.tileLayer(url, {
						attribution: attribution,
						subdomains: subdomains
					});

					baseMaps[name] = sLayer;
				}
				
				if (sLayer!==undefined) sLayer.addTo(map);

				map.layerControl = L.control.layers(baseMaps).addTo(map);
				map.layerControl.setPosition('bottomright');

				var $close=$('<i class="icon ion-close pull-right"></i>');
				$('.leaflet-control-layers-list').prepend($close);
				$close.click(function(){
					$(".leaflet-control-layers").removeClass("leaflet-control-layers-expanded");
				});

			},
			'json', $('body'));


		return map;
	};

	module.fitBounds = function(a) {
		map.fitBounds(a);
	};

	/**
	 * Returns the HTML DOM Element Object used as a container for STAGE2 spatial layer selector.
	 */

	module.containerForSpatialLayerSelector = function() {
		if (containerForSpatialLayerSelector) return containerForSpatialLayerSelector;
		var ctrl = L.control({
			position: 'topleft'
		});
		ctrl.onAdd = function(map) {
			containerForSpatialLayerSelector = L.DomUtil.create('div');
			L.DomEvent.disableClickPropagation(containerForSpatialLayerSelector);
			return containerForSpatialLayerSelector;
		};
		ctrl.addTo(map);
		return containerForSpatialLayerSelector;
	};

	module.setOnZoomEndCallback = function(fun) {
		map.on('zoomend', function() {
			fun();
		});
	};

	module.setOnMoveEndCallback = function(fun) {
		map.on('moveend', function() {
			fun();
		});
	};

	module.sidebar = function() {
		if (sidebar) return sidebar;
		sidebar = L.control.sidebar('sidebar').addTo(map);
		return sidebar;
	};

	return module;

});
