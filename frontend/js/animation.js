define(['js/controller','js/utils','js/cache','cmp/Legend'],function(controller,U,cache,Legend){
   "use strict";
   var module={};

   module.mainLayers = [];
   module.borderLayers = [];

   var t = window.app.T;

   function beginAnimation(loadedLayers,layerNumber) {
      module.mainLayers = [];
      module.borderLayers = [];

      if (loadedLayers!==layerNumber) return;

      var legendSettings=controller.getMapData().legendData;
      if (legendSettings===undefined) return;
      
      $.LoadingOverlay("show");

      var animationLegend=new Legend([0,0,0],legendSettings,{edit:false});

      controller.animation=true;
      controller.setMapInfo(true);

      var layers=[];

      var variableDatesSelect=controller.getVariableDatesControl();

      var $select=variableDatesSelect.$el();

      var cba=legendSettings.cba.slice(),
          cbac=legendSettings.cbac.slice();

      var cbali=cba.length-1;

      var $animDateDiv=$('#anim-date').empty();

      const mapData = controller.getMapData();
      const glay = mapData.table_name;
      const blay=_.find(controller.borderLayers || [],function(layer){return layer.table_name===glay;});

      const showBorders = blay && controller.map.hasLayer(blay.layer) ? true : false;

      $($select.find('select option').get().reverse()).each(function(index,option){
         var id=$(option).val();
         var key='varval'+id;
         var response=cache.get(key);
         controller.filterOutSpecialValues(response);
         var layer=L.tileLayer.wms('proxy.php?', {
           layers: 'stage:'+response.table_name,
           format_options:'antialias:none',
           styles: 'stage_color',
           format:'image/png',
           _port:controller.port
         });
         layer.date=$(option).text();
         var $animDate=$('<div class="anim-hide anim-current-date">'+layer.date+'</div>');
         $animDateDiv.append($animDate);
         layer.$animDate=$animDate;
         layers.push(layer);

         if (showBorders) {
            layer.borders = L.tileLayer.wms('proxy.php?', {
               layers: 'stage:'+response.table_name,
               styles: 'line',
               transparent:'true',
               format:'image/png',
               _port:controller.port
             });
   
             layers.push(layer.borders);
         }
         
         if (response.data.length<3){
            if (legendSettings.cba) delete legendSettings.cba;
            if (legendSettings.cbac) delete legendSettings.cbac;
            layer.animationLegend=new Legend(response.data,legendSettings,{edit:false}); //new Legend values for every layer
            var legendData=layer.animationLegend.getData();
            cba=legendData.cba;
            cbac=legendData.cbac;
         }
         else{
            layer.animationLegend=animationLegend;
            //////////////////////////adjust a legend borders if necessary
            cba[0]=Number.NEGATIVE_INFINITY;
            cba[cbali]=Number.POSITIVE_INFINITY;
            //////////////////////////////////////////////////////////
         }

         (function(cba,cbac,response){
            layer.on('tileload', function(event) {
              U.onTileLoad(event,response.data,response.special_values,cba,cbac);
            });
         })(cba,cbac,response);
         
      });

      $animDateDiv.append('<div><br></div>');

      var fg=module.fg=L.featureGroup(layers);
      fg.addTo(controller.map).bringToFront();

      var nlayers=layers.length;

      for (var i=0;i<nlayers;++i){
         $(layers[i].getContainer()).addClass('anim-hide');
      }

      $(layers[0].getContainer()).addClass('anim-show');
      showBorders && $(layers[1].getContainer()).addClass('anim-show');

      layers[0].$animDate.addClass('anim-show');
      
      var $legend=$('<div/>');
      
      animationLegend=layers[0].animationLegend;
      $legend.html(animationLegend.$el().hide());

      var $pause=$('<button id="pause_animation" class="style_button animationBTN" ><i class="icon ion-pause"></i></button>');
      
      $.LoadingOverlay("hide");

      const step = showBorders ? 2 : 1;
      
      var cli = step; //curent layer indicator
      module.timerIntervalID=setInterval(function(){
         if (paused) return;

         if (cli==nlayers) {
            $pause.click();
            for (var i=0;i<nlayers;i=i+step){
              $(layers[i].getContainer()).removeClass('anim-show');
              layers[i].$animDate.removeClass('anim-show');
            }
             cli=0;
         }

         var layer=layers[cli];
         if (!layer) return;
         $(layer.getContainer()).addClass('anim-show');

         if (showBorders) {
            const layerBorders=layers[cli+1];
            if (!layerBorders) return;
            $(layerBorders.getContainer()).addClass('anim-show');
         }

         layer.$animDate.addClass('anim-show');
         animationLegend=layer.animationLegend;
         $legend.html(animationLegend.$el().hide());
         cli = cli + step;
      },2000);

      controller.map.eachLayer(function(layer) {
         //if( layer instanceof L.TileLayer )
         if (layer.__main === true) {
            module.mainLayers.push(layer);
            layer.remove();
         }
         else if (layer.__main_border === true) {
            module.borderLayers.push(layer);
            layer.remove();
         }
      });

      var paused=false;

      if (t['show legend']===undefined) t['show legend']='t(show legend)';
      if (t['hide legend']===undefined) t['hide legend']='t(hide legend)';

      var $showLegend=$('<button class="style_button animationLegendBTN" />').text(t['show legend']);
			var $stop=$('<button id="stop_animation" class="style_button animationBTN"><i class="icon ion-stop"></i></button>');
      // $legend.append($showLegend);

			$animDateDiv.parent().append($pause);
			$animDateDiv.parent().append($stop);
			$animDateDiv.parent().append($showLegend.hide());
			$animDateDiv.parent().append($legend);

      $legend.hide();
      $pause.click(function(){
         if (paused) {
            $(this).find('i').removeClass('ion-play').addClass('ion-pause');
            paused=false;
            $legend.hide();
            $showLegend.hide();
            $showLegend.text(t['hide legend']);
         }
         else{
            $(this).find('i').removeClass('ion-pause').addClass('ion-play');
            paused=true;
            $legend.show();
				$showLegend.show();
            $showLegend.text(t['show legend']);
         }
      });


      $stop.click(function(){module.stopAnimation();});

      $showLegend.click(function(){
         if (animationLegend.$el().is(":visible")) {
            animationLegend.$el().hide();
            $(this).text(t['show legend']);
         }
         else{
            animationLegend.$el().show();
            $(this).text(t['hide legend']);
         }
      });
   }

   module.stopAnimation=function(){
      if (controller.animation===false) return;
      controller.animation=false;

      clearInterval(module.timerIntervalID);

      module.mainLayers.map(l => l.addTo(controller.map));
      module.borderLayers.map(l => l.addTo(controller.map));

      module.fg.remove();
      controller.setMapInfo();
      controller.getSidebar().open('tab2');
   };

   module.animateData=function(){
      controller.closeSidebar();
      var variableDatesSelect=controller.getVariableDatesControl();
      var $select=variableDatesSelect.$el();
      var layerNumber=$select.find('select option').length;
      var loadedLayers=0;
      $select.find('select option').each(function(index,option){
         var id=$(option).val();
         var key='varval'+id;
         var data=cache.get(key);
         if (data) {
           loadedLayers++;
           beginAnimation(loadedLayers,layerNumber);
         }
         else {
           U.get(window.app.s2c+'varval', {prop:1,var_values_id:id,lang:window.app.lang}, function(data){
             cache.set(key,data);
             loadedLayers++;
             beginAnimation(loadedLayers,layerNumber);
           },
           'json', $('body'));
         }
      });
   };

   return module;
});
