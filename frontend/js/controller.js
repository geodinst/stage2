define(['js/cache','js/utils','cmp/Tselect','cmp/Legend','cmp/utils/numberWithCommas3','locale','cmp/alertable','cmp/t'],function(cache,U,Tselect,Legend,numberWithCommas3,locale,alertable,tfun){

	var module={};
	var spatialLayersSelect2;
	var variableDatesSelect;
	var $containerForSpatialLayerSelectors;
	var sidebar;
	var borderLayers={};
	var defaultBorderLayer;
	var mapData={};
	var deepLink={};
	var deepLinkInput={};
	var currentlyShown={
		spatialLayer:null,
		variableDate:null
	};
	var $colors; // color schems obtained from allsetings service
   module.animation=false;

   module.borderLayers = borderLayers;

   module.properties={ //references to the properties (tab2) objects
		cm:null, //classification method
		cb:null, //number of class breaks
		legend:null //settings legend
   };

   module.getVariableDatesControl=function(){
      return variableDatesSelect;
   };

   module.getMapData=function(){
     return mapData;
   };

   module.setSidebar=function(sb){
      sidebar=sb;
		sidebar._open=sidebar.open;
		sidebar.open=function(_tab) {
			var $before=$('.sidebar-tabs li.active');
			if (sidebar.tabs.isClosed===true && module.$activeTab!==undefined) {
				var ptab=module.$activeTab.data('tab');
				if (ptab!==undefined) {
					_tab=ptab;
				}
			}
			
			sidebar._open(_tab);
			
			var $after=$('.sidebar-tabs li.active');
			
			if ($after.length>0){
				module.$activeTab=$after;
			}
			else if ($before.length>0){
				module.$activeTab=$before;
			}
			
			var tab=sidebar.tabs.get(_tab);
			
			if (module.$activeTab!==undefined){
				module.$activeTab.data('tab',_tab);
				module.$activeTab.removeClass('main-foc');
				if (!tab.hasIcon()) {
					module.$activeTab.addClass('foc');
				}
				else{
					module.$activeTab.removeClass('foc');
				}
			}
		};
   };

   module.getSidebar=function(){
      return sidebar;
   };

   module.setTransparency=function(transparency,updateSlider){
      if (mapData.dlay){
         mapData.dlay.setOpacity(1.0-(transparency/100.0));
      }

      if (updateSlider===true){
         sidebar.tabs.get('opt').slider.noUiSlider.set(transparency);
      }
   };

   module.setSettingsLegend=function(isLegendEditable){

      var legendSettings=mapData.legendData;
      if (!legendSettings) return;

      if (mapData.variableValues.length<3) isLegendEditable=false;

		if (isLegendEditable) {
			try{
				legendSettings.cba[0]=Number.NEGATIVE_INFINITY;
				legendSettings.cba[legendSettings.cba.length-1]=Number.POSITIVE_INFINITY;
			}
			catch(e){}
		}

		var _legendSettings=$.extend({},legendSettings);


		var cm=parseInt(module.properties.cm.val());
		var cb=parseInt(module.properties.cb.val());

		if (cm!==0){
			if (parseInt(legendSettings.cm)!=cm || (parseInt(legendSettings.cm)!=8 && parseInt(legendSettings.cb)!=cb)){
				_legendSettings.cm=cm;
				if (cm==8) {
					cb={or_less: "", or_more: ""};
				}
				_legendSettings.cb=cb;
				delete _legendSettings.cba;
				delete _legendSettings.cbac;
			}
		}
		else{ //manual
			if (parseInt(legendSettings.cm)===8){ // reset classes if categorized
				_legendSettings.cm=0;
				_legendSettings.cba=[Number.NEGATIVE_INFINITY, Number.POSITIVE_INFINITY];
			}
		}

      console.log('beforeLegend',$.extend({},_legendSettings));

		var slegend=new Legend(mapData.variableValues,_legendSettings,
									{modalTemplate:2,
									intervalEditContainer:sidebar.tabs.get('opt').$div().find('#interval-editor'),
									edit:isLegendEditable?true:false,
									confirmBeforeRemove:true,
									onIntervalEdit:isLegendEditable?function onIntervalEdit(){
										//module.openTab('edit_interval');
									}:function(){},
					});

		var $legend=slegend.$el();
		$legend.removeClass('table-bordered');
      $('#opt #settings-legend').html($legend);
      module.properties.legend=slegend;
   };

   module.getSelectionIds = function() {
      return {sid: deepLink.sid, vid: deepLink.vid, tid: deepLink.tid};
   }

   module.deepLink=function(){
      deepLinkInput={};
      var tree_id=window.app.getHashValue('tid');
      if (!tree_id) return false;
      deepLinkInput.tid=tree_id;

      var values_id=window.app.getHashValue('vid');
      if (!values_id) return false;
      deepLinkInput.vid=values_id;

      var spu_id=window.app.getHashValue('sid');
      if (!spu_id) return false;
      deepLinkInput.sid=spu_id;

      deepLinkInput.p=JSON.parse(window.app.getHashValue('p'));
      deepLinkInput.c=JSON.parse(window.app.getHashValue('c'));
      deepLinkInput.z=window.app.getHashValue('z');
      deepLinkInput.o=window.app.getHashValue('o');
      deepLinkInput.ifrm=window.app.getHashValue('ifrm')?true:false;
      location.hash='';

      module.onVariableSelected(tree_id);
   };

	module.onClassBreaksSelected=function(id,silent){
		var idInt=parseInt(id);
		if (!silent) module.setSettingsLegend(idInt===0);
	};

   module.onClassificationMethodSelected=function(id,silent){
      var idInt=parseInt(id);

		if (module.properties.legend!==null) module.properties.legend.hideManualIntervalEditor();

      if (idInt===0 || idInt===8){
         module.properties.cb.$el().hide();
      }
      else{
         module.properties.cb.$el().show();
      }

      if (!silent) module.setSettingsLegend(idInt===0);
   };

   module.getDeepLink=function(){
      if (!mapData.dlay) return false;
      if (!deepLink.sid || !deepLink.vid || !deepLink.tid) return false;
      var location=window.location;
      var legendData={
         cm:mapData.legendData.cm,
         cb:mapData.legendData.cb,
         cp:mapData.prop.color_palette,
         cba:mapData.legendData.cba,
	     inverse_pallete_checkbox:mapData.inverse_pallete_checkbox ? true:false,
         decimals:mapData.prop.decimals,
         disabled:mapData.prop.disabled
      };
      return location.origin+location.pathname+
         '#lang='+window.app.lang+
         '&tid='+deepLink.tid+
         '&sid='+deepLink.sid+
         '&vid='+deepLink.vid+
         '&p='+JSON.stringify(legendData)+
         '&z='+module.map.getZoom()+
         '&o='+module.getOpacity()+
         '&c='+JSON.stringify(module.map.getCenter());
   };

   module.setMapProperties=function(settings){
      $.extend(mapData.prop,settings);
   };

	 module.getMapProperties=function(){
		 return mapData.prop;
	 };

    module.filterOutSpecialValues=function(response){

		if (response.special_values) {
			if (response.specialValuesLegend.length>0){
				sidebar.tabs.get('opt').initUnclassified(response.specialValuesLegend);
         }
         else {
            sidebar.tabs.get('opt').withoutUnclassified();
         }
			return; //filter only once
      }
      else {
         sidebar.tabs.get('opt').withoutUnclassified();
      }

      var specialValues={};
      if (response.prop.special_values){
         $.each(response.prop.special_values,function(key,sv){
            sv.color=U.hex2rgb(sv.color);
            specialValues[sv.value]=sv;
         });
      }

      var variableValues=[];
		var specialValuesLegend=[]; //distinct special values used in the map only
      var usedSpecialValues={};	//special values by gid
      $.each(response.data,function(key,val){
         var sv=specialValues[val];
         if (sv) {
            usedSpecialValues[key]=sv;
				if (sv.added!==true) specialValuesLegend.push(sv);
            val=NaN;
				sv.added=true;
         }
         variableValues.push(parseFloat(val));
      });

		response.specialValuesLegend=specialValuesLegend;

      response.data=variableValues;
      response.special_values= usedSpecialValues;
		if (specialValuesLegend.length>0){
			sidebar.tabs.get('opt').initUnclassified(specialValuesLegend);
		}
    };

    module.bringToFrontBorderLayers=function(glay){
      $.each(borderLayers,function(i,bl){
         bl.layer.bringToFront();
      });
		
		if (glay===undefined) return;
		var blay=_.find(borderLayers,function(layer){return layer.table_name==glay;});
		
		if (blay!==undefined) {
			if (blay.borders=='1') {
			   blay.layer.addTo(module.map);
			   blay.layer.bringToFront();
            defaultBorderLayer=blay;
         }
		}
    };

	/**
    * This func is executed when spatial selector value changes and new var_values are obtained (either from server or the cache).
    */
   module.onValues=function(var_tree_id,response,delineationTabStorage){
      /******************add borders select options******************/
		if (defaultBorderLayer!==undefined) defaultBorderLayer.layer.remove();
      var blayers={};
      $.each(response.borders,function(i,borderProp){
         if (borderLayers[borderProp.slid]!==undefined && borderLayers[borderProp.slid].table_name==borderProp.table_name) {
            borderProp.layer=borderLayers[borderProp.slid].layer;
         }
         else{
            borderProp.layer = L.tileLayer.wms('proxy.php?', {
               layers: 'stage:'+borderProp.table_name,
               styles: 'line',
               transparent:'true',
               format:'image/png',
					_port: module.port
            });

            borderProp.layer.__main_border = true;
         }
         blayers[borderProp.slid]=borderProp;
      });
      //remove unused layers from map and remove all overlay layers from the layer control
      $.each(borderLayers,function(i,borderLayer){
         module.map.layerControl.removeLayer(borderLayer.layer);
         if (blayers[borderLayer.slid]===undefined) {
            borderLayer.layer.remove();
         }
      });
			//sort
			var srb = [];
			for (var br in blayers) {
			  srb.push({br:blayers[br],w:blayers[br].weight});
			}
			srb.sort(function(x,y){return x.w - y.w}).reverse();

			//add layers to the control
			$.each(srb,function(i,borderLayer){
				module.map.layerControl.addOverlay(borderLayer.br.layer,borderLayer.br.name);
			});

		$('.leaflet-control-layers-overlays .leaflet-control-layers-selector').click(function(){
			module.bringToFrontBorderLayers();
		});

      borderLayers=module.borderLayers = blayers;
      /**************************************************************/

      //hide the Jenks classification option if more than 75.000 samples as it freezes the browser
      if (response.data.length>75000) {
         $('#class_method option[value=4]').hide();
         if (response.prop!==undefined &&
             response.prop.auto_classification!==undefined &&
             parseInt(response.prop.auto_classification.interval)===4){
            response.prop.auto_classification.interval=2; //change from jenks to equal intervals
         }
      }
      else{
         $('#class_method option[value=4]').show();
      }

      module.filterOutSpecialValues(response);
      mapData.variableValues=response.data;
      mapData.special_values=response.special_values;
	  mapData.specialValuesLegend=response.specialValuesLegend;
      mapData.table_name=response.table_name;
	  mapData.inverse_pallete_checkbox=response.prop.inverse_pallete_checkbox;
      if (delineationTabStorage===undefined){
         mapData.prop=$.extend({},response.prop);
         mapData.extents=response.extents;
      }
      else{
         mapData.prop=$.extend({},delineationTabStorage.prop);
         mapData.legendData=$.extend({},delineationTabStorage.legendData);
      }

      module.setViewSettings(mapData.prop);

      if (deepLinkInput.c){
         module.map.setView(deepLinkInput.c,deepLinkInput.z);
      }

      module.renderThematicMap();
   };

   function postRenderDeepLinkProcess(){
      if (deepLinkInput.o){
         module.setTransparency((1.0-parseFloat(deepLinkInput.o))*100.0,true);
      }

      if (deepLinkInput.ifrm) module.iframeAdaptations();

      deepLinkInput={};
    //if (window.app.opentab){sidebar.open(window.app.opentab);}
			// $('#btn_container').trigger('click'); // delineation EXPORT DEBUG
   }

   module.iframeAdaptations=function(){
     $('.leaflet-control-layers').hide();
     $('.leaflet-zoom-box-control').hide();
     $('.leaflet-control-zoom').hide();
     $('.leaflet-top.leaflet-left .select').parent().hide();
     $('#sidebar').hide();
     
     $('.sidebar-map').css('margin-left',0).click(()=>{
      window.open(this.getDeepLink(), '_blank');
     });

     $('.leaflet-control-geocoder').hide();
     $('#selected-variable a').off('click');
     $('#selected-variable_date select').prop('disabled',true);
     $('#share_content').parent().hide();
   };

   module.storeMapSettings=function(additionalKey){
      var deepLinkData=module.getDeepLinkData();

      if (additionalKey===undefined) {
         additionalKey='';
      }
      else{
         additionalKey='_'+additionalKey;
      }

      var vid=deepLinkData.vid;
      var key='rvid'+vid+additionalKey;

      var store={
         legendData:$.extend({},mapData.legendData),
         prop:$.extend({},mapData.prop),
         sid:deepLinkData.sid,
         vid:vid,
         tid:deepLinkData.tid
      };

      cache.set(key,store);
   };

	module.resetLegendCache=function(){
		//cache.remove('cm0-'+deepLink.sid+'-'+deepLink.tid);
		var key='varval'+deepLink.vid;
		var cached=cache.get(key);
		if (cached) {
			return $.extend({},cached.prop);
		}
	};

   module.renderThematicMap=function(settingsApplied){
      var prop={};
      if (deepLinkInput.p){
         prop=deepLinkInput.p;
			if (prop.cba){
				if (prop.cba[0]===null) prop.cba[0]=Number.NEGATIVE_INFINITY;
				if (prop.cba[prop.cba.length-1]===null) prop.cba[prop.cba.length-1]=Number.POSITIVE_INFINITY;
			}
      }
      else
      {
         var autoClassification=parseInt(mapData.prop.classification)===0;

         prop={cp:mapData.prop.color_palette,
               cb:autoClassification?mapData.prop.auto_classification.class_breaks:mapData.prop.manual_classification.manual_breaks,
               cm:autoClassification?parseInt(mapData.prop.auto_classification.interval):0,
			   inverse_pallete_checkbox:$("#inverse_colors_checkbox").is( ':checked' ) ? true: false,
               decimals:mapData.prop.decimals,
               disabled:mapData.prop.disabled
         };

			if (parseInt(prop.cm)==8) {
				prop.cb=mapData.prop.auto_classification.categorized;
			}

			if (autoClassification){
				if (mapData.prop.auto_classification.cba!==undefined){
					prop.cba=mapData.prop.auto_classification.cba;
					delete mapData.prop.auto_classification.cba;
				}
			}
			else{
            var cba=mapData.prop.manual_classification.manual_breaks;
            if (cba && cba.constructor!==Array){
               cba=cba.split(',').map(function(x){return parseFloat(x);});
            }
            prop.cba=cba;
         }
      }
		mapData.legendData=module.tmr.render(module.map,mapData.table_name,mapData.popup_title?mapData.popup_title:mapData.variableName,mapData.sldNames,mapData.variableValues,
                 prop,mapData.special_values,mapData.specialValuesLegend);
        mapData.dlay=mapData.legendData.dlay;
	  	mapData.legendData.inverse_pallete_checkbox = mapData.inverse_pallete_checkbox ? true:false;
		currentlyShown.spatialLayer=spatialLayersSelect2.val();
		currentlyShown.variableDate=variableDatesSelect.val();

		if (locale.opacityIsSet===undefined) {
			module.setTransparency(locale.tr,true);
			locale.opacityIsSet=true;
		}

      /*
		if (settingsApplied===true) {
			cache.set('cm0-'+deepLink.sid+'-'+deepLink.tid,$.extend({},mapData.prop)); //remember legend data
		}
      */
      const key = 'cm0-'+deepLink.sid+'-'+deepLink.tid;
      !cache.get(key) && cache.set(key, $.extend({},mapData));

		module.setSettingsClassification();
		module.setSettingsLegend(deepLinkInput.p?parseInt(deepLinkInput.p.cm)===0:parseInt(mapData.prop.classification)!==0);

		postRenderDeepLinkProcess();
		module.setMapInfo();

		if (window.onbeforeunload===null){
			window.onbeforeunload = function() {
				return true;
			};
		}

		if (module.rendering!==undefined){ delete module.rendering;}
   };

	module.setMapInfo=function(animation){
      var $variable = $('#selected-variable a').text();
      var $su = $('#su_select option:selected').text();
      var $date = $('#selected-variable_date select option:selected').text();
      var $info_container = $('#mapInfo');
      if (animation!==true){
         $info_container[0].innerHTML= '<p><b>'+$variable +'</b>, '+$su +', '+$date +'</p>';
      }
      else{
         $info_container[0].innerHTML= '<p><b>'+$variable +'</b>, '+$su+'</p>'+
                                       '<div id="anim-date"></div>';
      }

      $info_container.show();
   };

   module.setSettingsClassification=function(){

      var cm;
      if (deepLinkInput.p){
         cm=parseInt(deepLinkInput.p.cm);
         var cb=parseInt(deepLinkInput.p.cb);

         if (cm!==0){
            module.properties.cb.val(cb,true);
         }
      }
      else{
         cm=mapData.legendData.cm;
      }

      module.properties.cm.val(cm,true);
      module.onClassificationMethodSelected(cm,true);

   };

	module.getOpacity=function(){
      var transparency=parseFloat(sidebar.tabs.get('opt').slider.noUiSlider.get());
		return 1.0-(transparency/100.0);
	};

	function updateShare(){
		var $mapLinkInput=$('#tab8').find('#map_link_ta');
		if ($mapLinkInput.val()==='') return;
		var deepLink = module.getDeepLink();
		$mapLinkInput.val(deepLink);

		var $mapEmbedInput=$('#tab8').find('#map_link_embed');
		var $iframe=$($mapEmbedInput.val());
		$iframe.attr('src',deepLink+'&ifrm=1');

		var $div=$('<div/>');
		$div.html($iframe);
		$mapEmbedInput.val($div.html());
	}

   module.onMapZoomEnd=function(){
      if (mapData.dlay){
         mapData.dlay.setOpacity(module.getOpacity());
      }
		updateShare();
   };

	module.onMapMoveEnd=function(){
      updateShare();
   };

   module.setViewSettings=function(prop){
      var $cpRadio=$('#'+prop.color_palette+'_colorsOption_cb');
      if ($cpRadio.length===0) {
         prop.color_palette='YlOrRd';
         $cpRadio=$('#'+prop.color_palette+'_colorsOption_cb');
      }
      $cpRadio.prop('checked', true);

      if (parseInt(prop.classification)===0){ //auto classification method
         module.properties.cm.val(prop.auto_classification.interval);
         module.properties.cb.val(prop.auto_classification.class_breaks);
      }
      else{ //manual classification

      }
	  // check inverse_pallete_checkbox if available
	  if (prop.inverse_pallete_checkbox === 1){
		  var $inverse_pallete_checkbox = $('#inverse_colors_checkbox');
		  $inverse_pallete_checkbox.prop('checked', true);
		  module.set_colors_list_element();

	  }
   };

   module.setContainerForSpatialLayerSelectors=function($div){
      $containerForSpatialLayerSelectors=$div;
   };

   function subdsc(id,key,data){
      var $subdscDiv=$('#tab2 #selected-variable_subdescription');
      $subdscDiv.empty();
      var subdsc=cache.get(key+'sdsc');
      var $vs,$p;
      if (subdsc){
				$p = (subdsc.desc);
				$vs = subdsc.vs ? subdsc.vs_lay+": "+subdsc.vs:"";
				$subdscDiv.html("<div class = 'tdd'>"+$p+"<br>"+$vs+"</div>");
      }
      else{
         U.get(window.app.s2c+'varpropdesc', {lang:window.app.lang,var_values_id:id}, function(subdsc){
            cache.set(key+'sdsc',subdsc);
            if (subdsc){

               if (subdsc.vs){
                  subdsc.vs=numberWithCommas3(parseFloat(subdsc.vs).toFixed(data.prop.decimals),window.app.T['.'],window.app.T[',']);
               }

               $p = (subdsc.desc);
               $vs = subdsc.vs ? subdsc.vs_lay+": "+subdsc.vs:"";
               $subdscDiv.html("<div class = 'tdd'>"+$p+"<br>"+$vs+"</div>");
            }
         },'json');
      }
   }
	
	function reindexData(data){
		var res=[];
		var sldnames=[];
		var cgid=0;
		for (let i=0;i<data.gids.length;i++) {
			var gid=data.gids[i];
			for (let j=cgid+1;j<gid;j++){
				res.push(null);
				sldnames.push(null);
			}
			res.push(data.data[i]);
			sldnames.push({'idgid':gid, 'id':data.codes[i], 'name':data.names[i]});
			cgid=gid;
	}
		
		for (let i=cgid;i<data.cnt;i++){
			res.push(null);
			sldnames.push(null);
		}
		
		delete data.gids;
		delete data.names;
		delete data.geocodes;
		
		data.data=res;
		data.sldnames=sldnames;
	}

   module.onSpatialLayerDateSelect=function(var_tree_id,id,delineationTabStorage,limit){
      deepLink.vid=id;

      const key='varval'+id;
      var data=cache.get(key);
      if (data) {
			mapData.sldNames=data.sldnames;
         subdsc(id,key,data);
         module.onValues(var_tree_id,data,delineationTabStorage);
      }
      else{
			if (parseInt(localStorage.getItem("disable-limit-warning"))==1) limit=false;
         U.get(window.app.s2c+'varval', {prop:1,var_values_id:id,lang:window.app.lang,limit:limit===false?0:1}, function(data){
				if (data.limit===true) {
					var op={};
					op.title='';
					var c=numberWithCommas3(parseInt(data.c),window.app.T['.'],window.app.T[',']);
					op.prompt='<h4>'+tfun(_.template("WARNING: You are about to download data for <%=c%> spatial units.")({c:c}))+'</h4><br>'+
					'<h5>'+tfun('This could result in signifficant network traffic (several MBytes) and map rendering delay.')+'</h5><br>'+
					'<label>'+
					'<input id="disable-limit-warning" type="checkbox" name="show" value="show">&nbsp;' +
					tfun("Don't show this warning anymore")+
					'</label>';
					op.ok=function(){
						module.onSpatialLayerDateSelect(var_tree_id,id,delineationTabStorage,false);
						if ($('#disable-limit-warning').prop('checked')) localStorage.setItem("disable-limit-warning", 1);
					};
					op.cancel=function(){
						if ($('#disable-limit-warning').prop('checked')) localStorage.setItem("disable-limit-warning", 1);

						if (spatialLayersSelect2.val()!=currentlyShown.spatialLayer) spatialLayersSelect2.val(currentlyShown.spatialLayer);
						if (variableDatesSelect.val()!=currentlyShown.variableDate) variableDatesSelect.val(currentlyShown.variableDate);

					};
					alertable(op);
					return;
            }
            
            if (data.keyedCodes === true) {
               let codes = [];
               for (const key in data.codes) {
                  codes[data.codes[key]] = key;
               }
               data.codes = codes;
            }
				
				reindexData(data);
				mapData.sldNames=data.sldnames;
            cache.set(key,data);
            subdsc(id,key,data);
            module.onValues(var_tree_id,data);

         },
         'json', $('body'));
      }
   };

   module.onVariableSelected=function(var_tree_id,delineationTabStorage){
      deepLink.tid=var_tree_id;
      var key='varspat'+var_tree_id;
      var data=cache.get(key);

      if (data){
         module.spatialLayers(var_tree_id,data,delineationTabStorage);
      }
      else{
         U.get(window.app.s2c+'varspat', {lang:window.app.lang,var_tree_id:var_tree_id,unpublished:window.app.isadmin?'true':'false'}, function(data){
            var spUnits={};
            var select2data=[];
            $.each(data.result,function(key,obj){
               spUnits[obj.su_id]=obj;
               select2data.push({id:obj.su_id,text:obj.name});
            });

            data.select2data=select2data;
            data.result=spUnits;
            cache.set(key,data);
            module.spatialLayers(var_tree_id,data);
         },
         'json', $('body'));
      }
   };


	 /** The function is also used when user switches the delineation accordion item. If the parameter setDate is set to false first item in the date picher is selected
	 *
	 */
   module.onSpatialLayerSelect=function(var_tree_id,id,setDate,delineationTabStorage) {
      if (!id) {
         alert(window.app.T['Spatial layer name is not translated therefore the data are not going to be shown.']);
         window.location.reload();
         return;
      }
      var data=cache.get('varspat'+var_tree_id).result;

      deepLink.sid=id;

      //set variable description
      var vdsc=data[id].variable_description;
      if (vdsc) {
            var $p=$('<p/>');
            $p.html(vdsc);
            $('#tab2 #selected-variable_description').html($p);
      }
      else{
         $('#tab2 #selected-variable_description').empty();
      }

      var dates=data[id].dates;
		var availableDates=[];
      var select2data=[];
      $.each(dates,function(inx,obj){
		var dateText=locale.full_date===true?obj.date:obj.date.split('-')[0];
        select2data.push({id:obj.id,text:dateText});
			availableDates.push(dateText);
      });

      var $div=$('#tab2 #selected-variable_date');
		var currentDateText;

      if (variableDatesSelect!==undefined){
			try{
				currentDateText=variableDatesSelect.getTextFromID(variableDatesSelect.val());
			}
			catch(e){}
			variableDatesSelect.$el().empty();
		}
      variableDatesSelect=new Tselect($div,select2data,null,{onSelect:function(id){module.onSpatialLayerDateSelect(var_tree_id,id);}});
     //  $div.append('<button type="button"><i class="icon ion-funnel ion-play"></i></button>');
      $div.parent().show();

      var values_id;

      if (delineationTabStorage!==undefined) {
         values_id=delineationTabStorage.vid;
         variableDatesSelect.val(values_id,true);
         module.onSpatialLayerDateSelect(var_tree_id,values_id,delineationTabStorage);
         return;
      }

      values_id=deepLinkInput.vid;

      if (values_id){
         variableDatesSelect.val(values_id);
         return;
      }

      if (setDate){
         variableDatesSelect.setByText(setDate);
      }
		else if (currentDateText!==undefined && availableDates.indexOf(currentDateText)!==-1){
			variableDatesSelect.setByText(currentDateText);
		}
      else{
         variableDatesSelect.selectFirstItem();
      }
   };

   function selectDefaultSpatialUnit(var_tree_id,psu,select2,delineationTabStorage){

      var spu_id=deepLinkInput.sid;

      if (delineationTabStorage!==undefined) {
         spu_id=delineationTabStorage.sid;
      }

      if (spu_id){ //always set if delineationTabStorage!==undefined
         select2.val(spu_id,true);
         module.onSpatialLayerSelect(var_tree_id,spu_id,false,delineationTabStorage);
         return;
      }

      if (psu!==false){
            select2.val(psu);
      }
      else{
         select2.selectFirstItem();
      }
   }

   module.spatialLayers=function(var_tree_id,data,delineationTabStorage){

      mapData.popup_title=data.popup_title;

      /*******variable settings**********************************************************/
      var imgKey='var_img'+var_tree_id;
      var img=cache.get(imgKey);
      if (img){
         $('#selected-variable-picture').html(img);
      }
      else{
         U.get(window.app.s2c+'var_img', {var_tree_id:var_tree_id}, function(data){
            $('#selected-variable-picture').html(data);
            cache.set(imgKey,data);
         },
         'text');
      }
			$('#selected_variable_legend_title').html(data.legend_title);
      //variable name from the tree
      var $a=$('<a/>',{href:'#'});
      var $litems=$('a[href="#'+var_tree_id+'"]').parents('li');

      mapData.variableName=$.map($litems.find('a:first'),function(a){
         return $(a).text();
      }).join(' > ');

      $a.text(mapData.variableName);

      $a.click(function(){ //click on variable <ul> leads to tab1 variable selector
         sidebar.open('tab1');
         return false;
      });

      $('#tab2 #selected-variable').first().html($a);
      sidebar.tabs.enable(['tab2'],true);
			$('#delineation_simple').addClass('hidden');
			$('#delineation_main_container').removeClass('hidden');

      if (parseInt($(window).width()) < 769){
			var $activeTabBefore=$('.sidebar-tabs li.active');
         sidebar.close();
			$activeTabBefore.addClass('foc');
      }
      else if (delineationTabStorage===undefined){
         sidebar.open('tab2');
      }
      /***********************************************************************************/

      var select2data=data.select2data;
      if (!spatialLayersSelect2) {
         var $div=$('<div/>',{class:'select',id:'su_select'});
         spatialLayersSelect2=new Tselect($div,select2data,null,
                                          {
                                             onSelect:function(val){
                                                module.onSpatialLayerSelect(var_tree_id,val,false);
                                             }
                                          });
         $containerForSpatialLayerSelectors.append($div);
      }
      else{
         spatialLayersSelect2.reinit(select2data);
         spatialLayersSelect2.resetOnSelect(function(e){
            module.onSpatialLayerSelect(var_tree_id,e,false);
         });
      }

		var sid = module.getDeepLinkData().sid;

		if (sid!==undefined && sid!==false && data.result[sid]!==undefined) data.prefered_su=sid;	//override the prefered_su if spatial unit is already selected

      selectDefaultSpatialUnit(var_tree_id,data.prefered_su,spatialLayersSelect2,delineationTabStorage);
   };

   module.getvid=function(){
		 if (!deepLink.sid || !deepLink.vid || !deepLink.tid) return false;
		 return deepLink.vid;
	};

	module.getDeepLinkData=function(){
		var dl= {};
		if (!mapData.dlay) return false;
		if (!deepLink.sid || !deepLink.vid || !deepLink.tid) return false;
		dl.lang= window.app.lang;
		dl.tid= deepLink.tid;
		dl.sid = deepLink.sid;
		dl.vid= deepLink.vid;
		dl.p = mapData.prop;
		dl.inverse_pallete_checkbox = mapData.inverse_pallete_checkbox;
		dl.z= module.map.getZoom();
		dl.o = module.getOpacity();
		dl.c = module.map.getCenter();
		return dl;
	};

	 /**
	 * Function is called when the delineation accordion tab is changed TODO
 	 * @param {Integer} vid The id of the values displayed on the map
	 */
	 module.onDelineationTabChanged = function(vid,accordion_id){
      var delineationTabStorage=cache.get('rvid'+vid+'_'+accordion_id);

      if (module.getDeepLinkData().vid==vid && _.isEqual(mapData.prop,delineationTabStorage.prop)) return;
      module.onVariableSelected(delineationTabStorage.tid,delineationTabStorage);
			sidebar.open('tab5');
	 };

	/**
	* The function is called when user filters map elements to be displayed in the chart TODO
	* @param {Array} data The id of the values displayed on the map
	*/
	module.onChartFiltered = function(filtered_elements){
		module.map.delineationHighlighter.highlight(filtered_elements);
		if (filtered_elements.length === 0){

			var key = 'mh-' + mapData.table_name;
			var mapHighlighter = cache.get(key);
			mapHighlighter.hideAll();
		}
	};

	module.getSpatialLayers = function(){
		var_tree_id = deepLink.tid;
		var data=cache.get('varspat'+var_tree_id).result;
		var dat_arr = [];
		$.each(data,function(i,v){
		dat_arr.push(v);
		});
		var byWeight = dat_arr.slice(0);
		byWeight.sort(function(a,b) {
		return a.su_weight - b.su_weight;
		});
		return byWeight.reverse();
	};

	module.onDelineationTimecheck = function(var_tree_id,id,date){
		var hasDate =false;
		var data=cache.get('varspat'+var_tree_id).result;
		deepLink.sid=id;
		var dates=data[id].dates;
		$.each(dates,function(i,val){
			if (val.date.split('-')[0] == date){hasDate = true}
		});
		if (hasDate){
			return true;
		}
		else{
			window.alert('The values for the selected variable are not available for year '+date);
			return false;
		}
	}

	 module.openTab = function(tab){
		sidebar.open(tab);
	 };

	 /*
	  * this funcion is called from delineation and animation module
	  */
	 module.closeSidebar = function(){
		sidebar.close();
	 };

	 module.getPopupTitle = function(){
		 return mapData.popup_title;
	 };
	 // module.endel = function(){
		//  sidebar.tabs.enable(['tab5'],true);
	 // }


	 module.set_colors_list_element = function(){

		 $color_palette = $('input[name=selector_colors]:checked').attr('id').split("_")[0];
		 $inverse_colors =  $("#inverse_colors_checkbox").is( ':checked' );
		 parsed_colors = module.parse_colors($inverse_colors, $color_palette);
		 $("#colors_list_element").html(parsed_colors);

	 }

	/**
	* Parse colors json
	* @param  {boolean} inverse 	[if true inverse color scale]
	* @return {innerHTML}         [content to popule collor picker in tab opt]
	*/
	module.parse_colors = function(inverse,color_palette) {

		var result = '';
		for (var i in $colors) {

			var colors1 = inverse ? ($colors[i].slice().reverse()) : ($colors[i]);
			result += '<li id= ' + i + '_colorsOption>';
			result += '<div class="color_picker_line" id= "' + i + '_selectedColorScheme">';
			checked = (i === color_palette) ? 'value="" checked="checked"':'';
			result += '<input type="radio" id="' + i + '_colorsOption_cb" name="selector_colors" class = "collor_picker '+checked+'">';
			result += '<svg width="180" height="30" class = "colorpicker_class">' +
			'<rect fill="' + colors1[0] + '" width="30" height="30" y="0"></rect>' +
			'<rect fill="' + colors1[1] + '" width="30" height="30" x="30"></rect>' +
			'<rect fill="' + colors1[2] + '" width="30" height="30" x="60"></rect>' +
			'<rect fill="' + colors1[3] + '" width="30" height="30" x="90"></rect>' +
			'<rect fill="' + colors1[4] + '" width="30" height="30" x="120"></rect>' +
			'<rect fill="' + colors1[5] + '" width="30" height="30" x="150"></rect>' +
			'</svg>';
			// result+='<label for="'+i+'_colorsOption"> '+i+'</label>';
			result += '</div>';
			result += '</li>';
			var key = i;
			var individual_color = colors1[i];

		}
		return result;
	}

	module.setavailablecolors = function(colors){
		$colors = colors;
	}
	module.getavailablecolors = function(colors){
		return $colors;
	}

	return module;

})
