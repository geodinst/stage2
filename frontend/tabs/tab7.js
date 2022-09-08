define(['js/SidebarTab','text!tabs/tab7.html','js/utils','js/controller'],function(SidebarTab,tpl,U,controller){

	var t=window.app.T;
	var tab=new SidebarTab('group2','tab7',false,'<span id="msg.cap2"></span>',tpl);
	var selected_language = window.app.lang;
	var help_tab_container = tab.$div().find("#help_tab_container");




	$overlay = $('#overlay');
	U.get(window.app.s2c+'client_get_advanced_settings', {setting:'help'}, function(data){


		var help = (data[selected_language]);
		$.each(help, function(key, value){
			var acord_el =
			'<h3>'+key+'</h3>'+
				'<div class = "single_delineation_accordion">'+
					'<p>'+value+'</p>'+
				'</div>';
			help_tab_container.append(acord_el);
			tab.$div().find("#help_tab_container").accordion("refresh");

		});

		},
	'json', $('body'));

	// append jqueryUI accordion
	tab.$div().find("#help_tab_container").accordion(
		{
			heightStyle: "content",
			icons: null,
			active:false,
			collapsible: true,

    }
    );
	return tab;
});
