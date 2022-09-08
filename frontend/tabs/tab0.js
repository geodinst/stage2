define(['js/SidebarTab', 'text!tabs/tab0.html', 'js/utils', 'js/controller'], function(SidebarTab, tpl, U, controller) {

  var t = window.app.T;
  var selected_language = window.app.lang;
  var tab = new SidebarTab('group1', 'tab0', false, '<span id="msg.cap2">' + '' + '</span>', tpl);

  var $content_landing_page = tab.$div().find("#landing_page");


  U.get(window.app.s2c + 'client_get_advanced_settings', {
      setting: 'landing_page'
    }, function(data) {

      var $page_html = (data[selected_language]);
      $content_landing_page.html($page_html);
      var $btn_variables = tab.$div().find("#btn_variables");
      var $btn_about = tab.$div().find("#btn_about");
      $btn_variables.click(function() {
        controller.openTab('tab1');
      });
      $btn_about.click(function() {
        controller.openTab('tab6');
      });

      let openItems = window.app.getHashValue('open');
      if (openItems) {
        $('#btn_variables').trigger('click');
        openItems = JSON.parse(openItems);
        for(const item of openItems) {
          $(`a:contains(${item})`).trigger('click');
        }
      }

    },
    'json', $('body'));




  return tab;
});
