	define(['js/utils','js/SidebarTab',
			'tabs/tab0','tabs/tab1','tabs/tab2','tabs/tab5',
			'tabs/tab6','tabs/tab7','tabs/tab8','tabs/tab9','tabs/edit_interval',
			'tabs/opt','tabs/tab0','tabs/tab_lang'],function(U,SidebarTab){
	var module={};
	var tabStartIndex=2;
	var $tabs;
	var $content;
	var tabs=arguments;
	var tabsReferences={};

	module.init=function(){
		var $sidebar=$('#sidebar');
		$tabs=$('<div/>',{class:'sidebar-tabs'});
		$content=$('<div/>',{class:'sidebar-content'});
		$sidebar.append($tabs);
		$sidebar.append($content);


		for (var i=tabStartIndex;i<tabs.length;++i){
			tabs[i].appendTo($tabs,$content);
			tabsReferences[tabs[i].id()]=tabs[i];
		}

		tabs[tabStartIndex].enable(true);
		tabsReferences['tab0'].enable(false);
		tabsReferences['tab1'].enable(true);
		tabsReferences['tab6'].enable(true);
		tabsReferences['tab_lang'].enable(true);
		$tabs.find('li').click(function(){
			module.isClosed=false;
		});
		
		$('.sidebar-header.sidebar-close').click(function(){
			module.isClosed=true;
			$('.sidebar-tabs li.active').addClass('main-foc');
		});
	};


	/**
	 *Enables tabs in array
	 *@param {Array} tabs array of tab ids to enable/disable
	 *@param {boolean} enable whether to enable or disable tabs
	*/

	module.enable=function(tabs,enable){
		$.each(tabs, function( key, tabID ) {
			if (tabID!='tab5'){
				tabsReferences[tabID].enable(enable);
			}
			else if (window.app.delineation===true){
				tabsReferences[tabID].enable(enable);
			}
		});
	};

	/**
	 *Returns tab by ID
	 */
	module.get=function(tabID){
		return tabsReferences[tabID];
	};


	return module;
});
