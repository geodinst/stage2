define(['js/utils','cmp/Legend','js/cache','js/delineation','js/controller','js/MapHighlighter','js/DelineationHighlighter',
		'cmp/utils/numberWithCommas3'],function(U,Legend,cache,delineation,controller,MapHighlighter,DelineationHighlighter,numberWithCommas3){
	"use strict";
	var dlay=null;
	var module={};

	var format=function (v,dc,decimalSign,separatorSign){
		return numberWithCommas3(v.toFixed(dc),decimalSign,separatorSign);
	};

	var popup = L.popup({autoPan:false});

	var mapHighlighter;
	var delineationHighlighter;

	// var drawnItems = new L.FeatureGroup();
	controller.tmr=module;

	//default legend properties (always overriden by server side properties)
	var legendProperties={cp:'YlOrRd',
		cb:4,
		cm:4,
		decimals:0,
		t:window.app.T,
		decimalSign:window.app.T['.'],
		separatorSign:window.app.T[',']
	};


	/**
	 *@param map map reference
	 *@param glay geoserver WMS layer name
	 *@param vname variable name
	 *@param {Array} fdata spatial units names
	 *@param {Array} variableValues variable values array in the same order as spatial units names
	 *@param {Object} prop properties {cm:classification method, cb: class breaks, cp: color palette}
	 */

	module.render=function(map,glay,vname,fdata,variableValues,prop,specialValues,specialValuesLegend){
		var legendSettings=$.extend({},legendProperties,prop);
		var legend=new Legend(variableValues,legendSettings);

		for (var i=0,c=specialValuesLegend.length;i<c;++i){
			var sv=specialValuesLegend[i];
			sv.color.a=255;
			legend.addSpecialValue(sv);
		}

		var $legend=legend.$el();
		$('#legend').html($legend);

		var legendData=legend.getData();
		var decimals=legendData.decimals;

		/*fix variableValues - you could do it before, e.g. when filtering special values, but in general prop.decimals can be undefined,
		* if undefined it gets defined in preprocessValues in Legend constructor. Thus, the fix is performed here.
		*/
		for (var i=0,c=variableValues.length;i<c;i++){
			variableValues[i]=parseFloat(variableValues[i].toFixed(decimals));
		}

		var cba=legendData.cba;
		var cbac=legendData.cbac;

		if (prop.disabled && prop.disabled.length>0) {
			legendData.disabled=prop.disabled;
			for (var i=0,c=prop.disabled.length;i<c;++i){
				specialValuesLegend[prop.disabled[i]].color.a=0;
			}
		}

		map.closePopup(popup);
		if (dlay) map.removeLayer(dlay);

		dlay = L.tileLayer.wms('proxy.php?', {
			layers: 'stage:'+glay,
			format_options:'antialias:none',
			styles: 'stage_color',
			format:'image/png',
			_port:controller.port
		}).addTo(map);

		legendData.dlay=dlay; //access to _container property
		dlay.setOpacity(controller.getOpacity());

		dlay.__main = true; //used to hide this layer when animation is in progress

		dlay.bringToFront();
		controller.bringToFrontBorderLayers(glay);

		var key='mh-'+glay;
		if (delineationHighlighter) {
			delineationHighlighter.remove();
			delineationHighlighter.setLayer(glay);
		}
		else{
			map.delineationHighlighter=delineationHighlighter=new DelineationHighlighter(map,glay);
		}

		if (mapHighlighter) mapHighlighter.hideAll();
		if (!mapHighlighter || mapHighlighter.key!==key) {
			mapHighlighter=cache.get(key);
			if (mapHighlighter){

			}
			else{
				mapHighlighter=new MapHighlighter(map,glay);
				mapHighlighter.key=key;
				cache.set(key,mapHighlighter);
			}
		}

		map.off('click');
		map.on('click',function (e) {
			stagePopup(e,true);
		});

		map.off('mousemove');
		map.on('mousemove',function(e){
			stagePopup(e);
		});

		function stagePopup(e,mapWasClicked){
			if (controller.animation) return; //disable on click if animation is running
			if (e.latlng===undefined) return;
			var gid=U.latlng2gid(map,glay,e.latlng,cache);

			map.closePopup(popup);

			var fname=fdata[gid];
			if (fname===undefined) return;
			var vvalue=variableValues[gid];
			if (vvalue===undefined) return;

			var specialValue=false;

			if (isNaN(vvalue)) {
				if (specialValues[gid]!==undefined) {
					vvalue=specialValues[gid].legend_caption;
					specialValue=true;
				}
			}

			if (mapWasClicked===true){
				delineation.onMapClick(gid,fname);
			}

			if (delineation.getSellectionMethod()=='popup'){
				if (specialValue===false){
					if (isNaN(vvalue)) {
						vvalue=window.app.T['no data'];
					}
					else{
						vvalue=format(vvalue,legendSettings.decimals,legendSettings.decimalSign,legendSettings.separatorSign);
					}
				}

				if (!/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
					popup
					.setLatLng(e.latlng)
					.setContent((U.empty(fname) ? '' : (fname.name.toUpperCase()+'<br>'))+
					vname+'<br><b>'+vvalue+'</b>')
					.openOn(map);
				}
			}
		}


		dlay.on('tileload', function(event) {
			var features=U.onTileLoad(event,variableValues,specialValues,cba,cbac,fdata);
			if (!features) return;
			var key=glay+'.'+event.coords.z;
			var cobj=cache.get(key);
			if (!cobj) {
				cobj={};
				cache.set(key,cobj);
			}
			cobj[event.coords.x+'.'+event.coords.y]=features;
		});

		return legendData;

	};
return module;
});
