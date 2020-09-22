define(['js/leaflet-interface.js','js/sidebarTabs','js/controller','js/delineation','js/animation'],function(mapInterface,sidebarTabs,controller,del,animation){
   var module={};


	 var center = [[45.42,13.38],[46.88,16.61]];
 	if (window.app.center){center = window.app.center;}


   module.init=function(mapid){
      this.map=mapInterface.init(mapid);
      sidebarTabs.init();
      this.sidebar=mapInterface.sidebar();
      this.sidebar.tabs=sidebarTabs;
      this.sidebar.open('tab0');
      mapInterface.fitBounds(center);
      controller.setContainerForSpatialLayerSelectors($('#su_select_info_container'));
      controller.setSidebar(this.sidebar);
      controller.map=this.map;
      mapInterface.setOnZoomEndCallback(controller.onMapZoomEnd);
      mapInterface.setOnMoveEndCallback(controller.onMapMoveEnd);
			$("[name='logo']").click(function(){
				controller.openTab('tab0');
			});
			$('a[role="tab"]').on("click", function() {
					del.remove_filter();
               del.chart_selection();
               animation.stopAnimation();
			});
   };

   return module;
});
