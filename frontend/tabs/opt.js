define(['js/SidebarTab', 'text!tabs/opt.html', 'js/controller',
'js/Share', 'js/utils', 'cmp/Tselect', 'locale', 'js/animation',
'js/thematic-map-rendering.js', 'js/cache', 'js/delineation','cmp/Legend','cmp/Table'],
function(
  SidebarTab, tpl, controller,
  Share, U, Tselect, locale, animation,
  tmr, cache, del,Legend,Table) {
  var t = window.app.T;
  var selected_language = window.app.lang;

  var vscTplFun = _.template(tpl);
  var view_settings_content = vscTplFun({
    t: t
  });

  var tab = new SidebarTab('group1', 'opt', false, '<span id="msg.cap2">' + '' + '</span>', view_settings_content);
  // add slider to the translarency
  var slider = tab.$div().find("#transparency_slider")[0];
  noUiSlider.create(slider, {
    start: [0],
    connect: [false, true],
    range: {
      'min': 0,
      'max': 100
    }
  });

  tab.slider = slider;

  slider.noUiSlider.on('slide', function() {
    controller.setTransparency(slider.noUiSlider.get());
  });

  function onUnclassifiedVisibilityChanged($icon,$trs){
    var $this=$icon;
    if ($this.hasClass('ion-eye')){
      $this.removeClass('ion-eye');
      $this.addClass('ion-eye-disabled');
    }
    else{
      $this.removeClass('ion-eye-disabled');
      $this.addClass('ion-eye');
    }

    var disabled = [];
    $trs.each(function(index, tr) {
      if ($(tr).find('.ion-eye-disabled').length>0) {
        disabled.push(index);
      }
    });

    var settings=controller.getMapProperties();
    settings.disabled = disabled;
    controller.setMapProperties(settings);
    controller.renderThematicMap();
  }

  tab.initUnclassified=function(usedSpecialValues){
    var table=new Table({trashColumn:false,
									   header:['','',''],
									   hideHeader:true,
									   removeDefaultClasses:true,
									   addClass:"table table-bordered"});

    $.each(usedSpecialValues,function(key,sv){
      Legend._addSpecialValue(sv,table,true);
    });

    var $trs=table.$el().find('tr');
    table.$el().find('.icon').click(function(){
      var $this=$(this);
      onUnclassifiedVisibilityChanged($this,$trs);
    });

    $('#unclassified-container').html(table.$el());
    $('#unclassified').show();
  };

  tab.withoutUnclassified = function() {
    $('#unclassified-container').empty();
    $('#unclassified').hide();
  };

  // get all available settings
  U.get(window.app.s2c + 'allsetings', {}, function(data) {
      var colors = data.colors;
	  controller.setavailablecolors(colors);
    controller.port=data.port;

      var parsed_colors = controller.parse_colors();
      var $colors_list_element = tab.$div().find("#colors_list_element");

	  $colors_list_element.html(parsed_colors);
      var class_breaks = data.class_breaks;
      var parsed_class_breaks = json2optionBox(class_breaks);
      var $class_count = tab.$div().find("#class_count");
      controller.properties.cb = new Tselect($class_count, parsed_class_breaks, t['Number of class breaks (excessive number of may corrupt display)'],
                                             {onSelect: controller.onClassBreaksSelected}
                                             );
      for (const key in data.classification_methods) {
        const value = data.classification_methods[key];
        const trans = window.app.T[value];
        if (trans) data.classification_methods[key] = trans;
      }
      var parsed_cm = json2optionBox(data.classification_methods);
      //parsed_cm.push({value:-1,text:t['manual class breaks']});
      controller.properties.cm = new Tselect(tab.$div().find("#class_method"), parsed_cm, t['Classification method'], {
        onSelect: controller.onClassificationMethodSelected
      });

      var $options_container = tab.$div().find("#options_container");
      $options_container.vmenuModule({
        Speed: 200,
        autostart: false,
        autohide: false
      });

      // tab.$div().find('input[name=selector_colors]:radio').hide();
      // add on click zo whole list cell
      var colors_svg_click = tab.$div().find(".color_picker_line");
      colors_svg_click.click(function(event) {
        var contentPanelId = $(this).attr("id").split("_")[0] + '_colorsOption_cb';
        $('#' + contentPanelId).prop('checked', true);
        $apply_style_settings.click();
      });

      tab.$div().find("#reset_legend_settings").click(function(){
        controller.resetLegendCache();

        const {sid, vid, tid} = controller.getSelectionIds();
        
        const key = 'cm0-' + sid + '-' + tid;
        const prop = cache.get(key);

        if (!prop) return;

        controller.setMapProperties(prop);
        controller.setSettingsLegend(parseInt(prop.classification)!==0);
        controller.renderThematicMap();
      });

      var $apply_style_settings = tab.$div().find("#apply_style_settings");
      tab.$div().find('input[name=selector_colors]:radio').change(function() {
        $apply_style_settings.click();
      });
      // apply settings events
      $apply_style_settings.click(function() {
        var settings = {};
        var classificationMethod = parseInt(tab.$div().find('#class_method select').val());
        settings.transparency = slider.noUiSlider.get();
        settings.classification = classificationMethod === 0 ? 1 : 0;
        settings.auto_classification = {};
        settings.auto_classification.class_breaks = tab.$div().find('#class_count select').val();
        settings.auto_classification.interval = classificationMethod;
        settings.manual_classification = {};

        var cba=controller.properties.legend.getData().cba;

        if (classificationMethod === 0) {
          settings.manual_classification.manual_breaks = cba;
        }
        else{
          settings.auto_classification.cba = cba; //prevents classification of the same data set once more
        }

        settings.color_palette = tab.$div().find('input[name=selector_colors]:checked').attr('id').split("_")[0];

		settings.inverse_pallete_checkbox = tab.$div().find("#inverse_colors_checkbox").is( ':checked' ) ? true: false;

        var disabled = [];
        settings.disabled = disabled;
        controller.resetLegendCache();
        controller.setMapProperties(settings);
        controller.renderThematicMap(true);
        controller.setTransparency(slider.noUiSlider.get());
        // if (parseInt($(window).width()) < 769) {
        //   controller.closeSidebar();
        // }
      });

		tab.$div().find("#inverse_colors_checkbox").click(function(){
			controller.set_colors_list_element();
			$apply_style_settings.click();

		});

    },
    'json', $('body'));



  function json2optionBox(jsn) {
    var result = [];
    for (var i in jsn) {
      result.push({
        value: i,
        text: jsn[i]
      });
    }
    return result;
  }


  // close popup and clear its content
  $('#close_popup').click(function() {
    $popup_content = $('#popup-content');
    $popup_content.empty();
    $overlay = $('#overlay');
    $overlay.css({
      display: 'none'
    });
  });

  // Send settings to administrator
  if (window.app.isadmin) {

    tab.$div().find('#share_list_menu').append('<button type="button" id="send_settings" class="style_button">Send settings</button>');


    tab.$div().find('#send_settings').click(function() {
      $overlay = $('#overlay').css({
        'display': 'inline'
      });
      var popup = $('#popup-content');
      var vid = controller.getvid();

      var param = JSON.stringify(controller.getMapProperties());
      var publish = locale.s2c + 'publish_var?var_values_id=' + vid;
      var content =
        '<div class = "popup_title"><b>' + t['Publish parameter settings changes'] + '<br><br></div>' +
        '<div id = "popup_send_settings"><textarea disabled>' + param + '</textarea>' +
        '<button id = "save_param_var_popup" type="button" class="save_export_data_popup style_button">' + t['Save parametrs'] + '</button>' +
        '<button type="button" id = "publish_variable" class="save_export_data_popup style_button">' + t['Publish variable'] + '</button>';
      popup.html(content);
      popup.find('#save_param_var_popup').click(function() {
        U.get(window.app.s2c + 'update_var_param', {
          var_values_id: controller.getvid(),
          param: param
        }, function(data) {}, 'json', $('body'));
      });

      popup.find('#publish_variable').click(function() {
        U.get(window.app.s2c + 'publish_var', {
          var_values_id: controller.getvid()
        }, function(publish) {}, 'json', $('body'));

      });

      U.get(window.app.s2c + 'ispublished', {
        var_values_id: controller.getvid()
      }, function(publish) {
        publish ? (popup.find('#publish_variable').hide()) : false;
      }, 'json', $('body'));
    })
  }

  return tab;
});
