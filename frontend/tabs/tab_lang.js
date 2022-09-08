define(['js/SidebarTab','text!tabs/tab_lang.html','js/utils','js/controller'],function(SidebarTab,tpl,U,controller){
  var t=window.app.T;
	var vscTplFun=_.template(tpl);
	var view_settings_content = vscTplFun({t:t});
	var tab=new SidebarTab('group2','tab_lang','fa fa-language','<span id="msg.cap2"></span>',tpl);
  tab.$div().find("#tab6_lang").html(t['Languages']);
  var $ul =$('<ul>');
	U.get(window.app.s2c+'languages', null, function(data){
		$.each(data, function( key, value ) {
			$ul.append('<li><a href="#lang='+key+'">'+value+'</a></li>');
		});

		tab.$div().find('#languages').html($ul); //$('#languages').html($ul) might not work if this ajax call returns really quickly
	},
	'json', $('body'));
	return tab;
});
