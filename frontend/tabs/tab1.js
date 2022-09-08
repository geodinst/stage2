define(['js/SidebarTab', 'text!tabs/tab1.html', 'js/utils', 'js/controller'], function(SidebarTab, tpl, U, controller) {
  var t = window.app.T;
  var tab = new SidebarTab('group1', 'tab1', 'fa fa-list', '<span id="msg.cap2"></span>', tpl,t['Variables']);

  var selected_language = window.app.lang;

  tab.$div().find("#tree_menu_tab_title").html(t['Variables']);
  U.get(window.app.s2c + 'tree', {
      lang: selected_language,
      unpublished: window.app.isadmin
    }, function(data) {
      var tree = parseJsonAsHTMLTree(data);
      var $tree = tab.$div().find("#tree_menu");
      $tree.html(tree);
      $tree.vmenuModule({
        Speed: 200,
        autostart: false,
        autohide: false
      });

      $tree.find('a').click(function() {
        var href = $(this).attr('href');
        if (href === undefined) return false;
        $('.u-vmenu1 a.varsel').removeClass('varsel');
        $(this).addClass('varsel');
        controller.onVariableSelected($.trim(href.replace('#', '')));
        return false;
      });

      controller.deepLink();
    },
    'json', $('body'));

  //get variable tree in the selected language
  var parseJsonAsHTMLTree = function(jsn) {
    var result = '';

    if (jsn.name) {
      if (jsn.parentid != 0) {
        if (jsn.children) {
          result += '<ul><li>' + '<a>' + jsn.name + '</a>';

        } else {
          result += '<ul><li>' + '<a href="#' + jsn.id + '">' + jsn.name + '</a>';

        }
      }
      for (var i in jsn.children)
        result += parseJsonAsHTMLTree(jsn.children[i]);
      result += '</li></ul>';
    }
    return result;
  };

  return tab;
});
