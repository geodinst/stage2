define(['js/SidebarTab', 'text!tabs/edit_interval.html', 'js/controller'], function(SidebarTab, tpl, controller) {
	var t = window.app.T;

	var vscTplFun = _.template(tpl);
	var tplHtml = vscTplFun({
		t: t,
		title:t['Edit interval']?t['Edit interval']:'{Edit interval}'
	});
	var tab = new SidebarTab('group1', 'edit_interval', false, '<span></span>', tplHtml);
	tab.$div().find('#back_to_tab_opt').click(function(){
		controller.openTab('opt');
	});
	return tab;
});
