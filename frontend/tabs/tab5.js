define(['js/SidebarTab', 'text!tabs/tab5.html', 'js/delineation', 'js/controller', 'js/thematic-map-rendering.js', 'js/cache', 'js/delineation', 'js/utils', 'locale'],
  function(SidebarTab, tpl, del, controller, tmr, cache, del, U, locale) {
    var t = window.app.T;
    var tab = new SidebarTab('group1', 'tab5', 'icon ion-stats-bars', '<span id="msg.cap2"></span>', tpl, t['Spatial query']);
    var delineation_simple_div = '<ul><li>' + t['Delineation not alailable. Please select a variable.'] + '</li></ul>';
    tab.$div().find("#delineation_simple").html(delineation_simple_div);

    tab.$div().find("#tab5_title").html(t['Spatial query']);

    // ******************** Pupulate tab 5 ********************
    var statistics_container_add_remove_div =
			'<button type="button" id = "add_single_delineation" class = "style_button">' + t['New variable'] + '</button>';
    tab.$div().find("#statistics_container_add_remove").append(statistics_container_add_remove_div);
    // append jqueryUI accordion
    tab.$div().find("#delineation_accordion").accordion({
      // collapsible:true,
      heightStyle: "content",
      icons: null,
    });

    var delineation_statistics_div =
      '<button type="button" id = "btn_del_statistics" class = "style_button" disabled>' + t['Spatial query statistics'] + '</button>';
    tab.$div().find("#delineation_statistics_div").append(delineation_statistics_div);

    //******************** Manipulate tab 5 ********************
    tab.$div().find("#add_single_delineation").click(function() {
      controller.openTab('tab1');
    });
		// Redundant function because the button is disabled by default
    tab.$div().find("#btn_del_statistics").click(function() {
      del.remove_filter();
      if ($('#delineation_accordion').children('h3').length > 0) {
        del.doDelineationStatistics();
      } else {
        alert("Delineation data is not available.");
      }
    });
    return tab;
  });
