define(['js/SidebarTab', 'text!tabs/tab9.html', 'js/controller', 'js/Share'], function(SidebarTab, tpl, controller, Share) {
	var t = window.app.T;

	var vscTplFun = _.template(tpl);
	var view_settings_content = vscTplFun({
		t: t
	});
	var tab = new SidebarTab('group1', 'tab9', false, '<span id="msg.cap2"></span>', view_settings_content);
	return tab;
});
