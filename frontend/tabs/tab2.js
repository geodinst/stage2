define(['js/SidebarTab', 'text!tabs/tab2.html', 'js/controller',
    'js/Share', 'js/utils', 'cmp/Tselect', 'locale', 'js/animation',
    'js/thematic-map-rendering.js', 'js/cache', 'js/delineation'
  ],
  function(
    SidebarTab, tpl, controller,
    Share, U, Tselect, locale, animation,
    tmr, cache, del) {
    var t = window.app.T;

    var vscTplFun = _.template(tpl);
    var view_settings_content = vscTplFun({
      t: t
    });
    var tab = new SidebarTab('group1', 'tab2', 'fa fa-info display_info', '<span id="msg.cap2"></span>', view_settings_content, t['Info']);

    var $setting_link_btn = tab.$div().find("#setting_link_btn");
    var setting_link_btn = '<a style="text-indent:0px; -webkit-padding-start: 0px !important; padding:10px; -webkit-padding-end: 10px !important;" id="options_map_btn"><button type="button" style="margin: 0px 0px 10px 0px;" class = "style_button">' + t['Legend settings'] + '</button></a>';
    $setting_link_btn.html(setting_link_btn);
    var $delineation_link = tab.$div().find("#delineation_link");
    var delineation_link = '<a id="delineation_link_btn"><button type="button" class = "style_button">' + t['Spatial query'] + '</button></a>';
    $delineation_link.append(delineation_link);

    // populate share section
    var share_div =
      '<button id = "download_map_btn" 		class = "export_map_btn">		<i class="icon ion-ios-download-outline"></i> 	<br/>' + t['Download'] + '</button>' +
      '<button id = "share_map_btn" 			class = "export_map_btn">		<i class="icon ion-android-share-alt"></i>	 		<br/>' + t['Share'] + '</button>' +
      '<button id = "animate_data" 				class = "export_map_btn">		<i class="icon ion-play"></i> 									<br/>' + t['Animate'] + '</button>';
    var $share_content = tab.$div().find("#share_content");
    $share_content.append(share_div);

    var share = new Share();

		// Start animation
		tab.$div().find("#animate_data").click(function() {
      animation.animateData();
      // alert('Unfortunately animation is not yet available. <br><br>We\'re working on it.');
    });

    // Populate popups
    tab.$div().find("#options_map_btn").click(function() {
      // share.map_options();
      // $('#opt').find('#tab-title').html(t['Settings']);
      $('#opt').find('#selected-variable').html(t['Settings']+'; '+$('#tab2 #selected-variable').first().text());
      controller.openTab('opt');
      $('#opt').find('#back_toinfo').click(function(){
        controller.openTab('tab2');
      });
      //TODO manimupale legend and colors

    });

    tab.$div().find("#download_map_btn").click(function() {
      share.map_download();
      $('#tab8').find('#share_content_tab_title').html(t['Download']+'; '+$('#tab2 #selected-variable').first().text());
      controller.openTab('tab8');
      share.back();
    });

    tab.$div().find("#share_map_btn").click(function() {
			share.populate_share_shp();
      $('#tab8').find('#share_content_tab_title').html(t['Share']+'; '+$('#tab2 #selected-variable').first().text());
      controller.openTab('tab8');
      share.back();
    });

    tab.$div().find("#delineation_link_btn").click(function() {
      
      controller.getSidebar().tabs.enable(['tab5'],true);
      //console.log(controller);
      del.remove_filter();
      $('#btn_del_statistics').prop('disabled', false);
      // Get what is selected on the map
      var var_name = $('#tab2 #selected-variable').first().text();
      var var_date = $('#tab2 #selected-variable_date select option:selected').text();
      var var_su = $('#su_select option:selected').text();
      var embeded_link = controller.getDeepLinkData();
      var vidAllData = del.vidAllData(embeded_link.vid);
      var vidAllDataFiltered = vidAllData;
      if (locale.filter && Object.keys(del.getaccordions()).length > 0) {

        var accordion_id = $('#delineation_accordion h3[aria-selected="true"] div').attr("accordion_id");
        var previously_selected_accordion = del.getaccordions()[accordion_id];

        if (previously_selected_accordion.var_su === var_su) {
          var vidAllData1 = vidAllData;
          var result = vidAllData.filter(function(user) {
            return previously_selected_accordion.vidAllDataFiltered.some(function(authorizedObj) {
              return authorizedObj.id === user.id;
            });
          });
          vidAllDataFiltered = result;
        }
      }

      // Create new accordion element
      rtn_el = del.createAcordionElement(var_name, var_date, var_su, embeded_link, vidAllData, vidAllDataFiltered);
      var delcordion_container = $("#delineation_accordion");
      delcordion_container.prepend(rtn_el.el).accordion("refresh").accordion("option", "active", 0);

      // add chart to accordion
      del.accordionChart(rtn_el.accordion_id, true, 12, true, 'value');
      // add all functions to an accordion
      del.accordionFUnctions(rtn_el.accordion_id);

      if (vidAllDataFiltered.length > 0) del.getaccordions()[rtn_el.accordion_id].isFiltered = true;


    if (vidAllData.length !== vidAllDataFiltered.length){
      $('#'+ rtn_el.accordion_id + '_accordion_filter_cancel').show();
    }
      controller.openTab('tab5');
    });

    return tab;
  });
