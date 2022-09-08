define(['js/controller', 'js/utils', 'cmp/giss', 'js/cache', 'js/corChart','cmp/utils/numberWithCommas3','locale'], function(controller, U, giss, cache, CC,numberWithCommas3,locale) {
  var del = {};
  var accordions = {}; // The variable is used to cache data about accordions
  var selectionMethod = 'popup'; // By default when user clicks on map element popup with poligon values is displayed
  var t = window.app.T;
  // sidebar chart settings
  var chart_options = {
    scaleStartValue: 0,
    onClick: highlightMapElement,
    scales: {
      xAxes: [{
        ticks: {
          beginAtZero: true,
          callback: function(value, index, values) {
              return numberWithCommas3(parseFloat(value).toFixed(2),window.app.T['.'],window.app.T[',']);
          }
        }
      }],
      yAxes: [{
        ticks: {
          callback: function(tick) {
            if (tick.length >= 14) {
              return tick.slice(0, tick.length).substring(0, 13).trim() + '...';
            }
            return tick;
          }
        }
      }],
    },
    legend: {
      display: false
    },
    tooltips: {
			custom: function(tooltip) {
        if (!tooltip) return;
        // disable displaying the color box;
        tooltip.displayColors = false;
      },
      callbacks: {
        title: function(tooltipItems, data) {
          return '';
        },
        label: function(tooltipItem, data) {
          var datasetLabel = '';
          var label = data.labels[tooltipItem.index];
          return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
        },
      }
    }
  };

  del.getaccordions = function() {
    return accordions;
  };

  /**
   * Create single accordion element to be appended to the sidebar
   * The function returns the accordion id
   * @param {String} var_name The variable name
   * @param {String} var_date The variable date
   * @param {String} var_su The variable spatial unit
   * @param {String} embeded_link The variable embeded_link
   * @param {Array} vidAllData the array of all poligions currently displayed on the map
   */
  del.createAcordionElement = function(var_name, var_date, var_su, embeded_link, vidAllData,vidAllDataFiltered) {
    var rtn = {};
    accordion_id = del.new_accordionID();
    var vid = controller.getDeepLinkData().vid;
    var decimals = controller.getDeepLinkData().p.decimals;
    var PopupTitle = controller.getPopupTitle();
    if(vidAllDataFiltered === undefined){
      vidAllDataFiltered = vidAllData;
    }
    del.updateCacheAccordion(vidAllData, vidAllDataFiltered, embeded_link, vid, var_name, var_su, var_date, accordion_id,PopupTitle,decimals);

    var element =
      '<h3><div class = "accordion_id" accordion_id = "' + accordion_id + '" vid = "' + vid + '" var_su = "'+var_su+'" hidden></div><div style = "max-width: 90%; float: left;">' + controller.getPopupTitle() + '; ' + var_su + ' (' + var_date + ') </div><button name = "remove_delineation_btn" class = "remove_delineation" id ="' + accordion_id + '_remove_delineation">X</button><div style="clear: both;"></div></h3>' +
      '<div id = "' + accordion_id + '_chart_master_div" class = "single_delineation_accordion">' +
      '<div class = "accordion_options">' +
      '<button title="' + t['Time series'] + '" type="button" 		name = 	"time_delineation" 	    id = "' + accordion_id + '_time_delineation"><i class="stage-icon-small">&#xe809;</i></button>' +
      '<button title="' + t['Sort alphabetically'] + '" type="button"    value = "sort_alpha_asc"    		id = "' + accordion_id + '_sort_alpha_asc"><i class="fa fa-sort-alpha-asc" aria-hidden="true"></i></button>' +
      '<button title="' + t['Sort numerically'] + '" type="button"    value = "sort_amount_desc"  		id = "' + accordion_id + '_sort_amount_desc"><i class="fa fa-sort-amount-asc"></i></button>' +
      '</div>' +
      '<div id = "sidebar_chart_' + accordion_id + '" class = "chart">' +
      '<canvas width="250" height="250" id = "del_chart_' + accordion_id + '"/>' +
      '</div>' +
      '<div class = "delineation_bottom_btn delineation_show_all ">' +
        '<button type="button" 	  name = 	"chart_load_more" 	    id = "' + accordion_id + '_display_chart_all"><i class="icon ion-more"></i></button>' +
      '</div>' +
      '<div class = "delineation_bottom_btn">' +
      '<button type="button"   	id = "add_child_delineation">' +
      '<div id="btn_container"><i class="stage-icon-medium">&#xe808;</i></div><span>' + t['Child units'] + '</span>' +
      '</button>' +
      '<button type="button"  id = "add_parent_delineation">' +
      '<div id="btn_container"><i class="stage-icon-medium">&#xe807;</i></div><span>' + t['Parent units'] + '</span>' +
      '</button>' +
      '<button style="float: right;" type="button" id = "' + accordion_id + '_accordion_filter"  name = 	"accordion_filter" >' +
      '<div id="btn_container"><i class="stage-icon-medium">&#xe802;</i></div><span>' + t['Select'] + '</span>' +
      '</button>' +
      '<button style="float: right;display: none;" type="button" id = "' + accordion_id + '_accordion_filter_cancel"  name = 	"accordion_filter_cancel" >' +
      '<div id="btn_container"><i class="fa fa-ban disabled"></i></div><span>' + t['Remove selected']+ '</span>' +
      '</button>' +
      '</div>';

    rtn.el = element;
    rtn.accordion_id = accordion_id;
    controller.storeMapSettings(accordion_id);
    return rtn;
  };

  /**
   * save all elements that are displayed in the delineation tab into cache element accordions
   * @param {Object} vidAllData the array of all poligions currently displayed on the map
   * @param {Object} vidAllDataFiltered the array data ot he  poligions filtered
   * @param {String} embeded_link the embeded link to the content currently displayed on map
   * @param {Integer} vid The id of the values displayed on the map
   * @param {String} var_name variable name as displayed in the menu
   * @param {String} var_su variable spatial unit name as displayed in the menu
   * @param {String} var_date variable date as displayed in the menu
   * @param {Integer} accordion_id The accordion element id
   * @param {String} PopupTitle nice variable name from the menu tree
   * @param {Integer} decimals number of decimal places
   */
  del.updateCacheAccordion = function(vidAllData, vidAllDataFiltered, embeded_link, vid, var_name, var_su, var_date, accordion_id,PopupTitle,decimals) {
    data_element = [];
    data_element.vidAllDataRaw = vidAllData;
    data_element.vidAllDataFiltered = vidAllDataFiltered;
    data_element.embeded_link = embeded_link;
    data_element.vid = vid;
    data_element.var_name = var_name;
    data_element.var_su = var_su;
    data_element.var_date = var_date;
    data_element.PopupTitle = PopupTitle;
    data_element.decimals = decimals;
    accordions[accordion_id] = data_element;
  };

  /**
   * The function is used after accordion element is created
   * it renders the chart into an accordion
   * @param {Integer} accordion_id The accordion element id
   * @param {boolean} desc set true if the elemets are to be sorted descending
   * @param {Integer} limit the number of elements to be displayed
   * @param {boolean} isNumeric set true if sort by numbers
   * @param {string} atr the atribute by which the array is to be sorted
   */
  del.accordionChart = function(accordion_id, desc, limit, isNumeric, atr) {

    var chartData = accordions[accordion_id].vidAllDataFiltered;
    // $('#del_chart_' + accordion_id).remove();
    $chart_div = $("#sidebar_chart_" + accordion_id).
    html('<canvas width="250" height="250" id = "del_chart_' + accordion_id + '"/>');
    var ctx = $('#del_chart_' + accordion_id);
    var chartData = del.sort(chartData, atr, desc, isNumeric); // sort data

	var middlewareToMakeTicksUnique = function(next) {
	    return function(value, index, values) {
	        var nextValue = next(value);

	        if (index && values.length > index+1 && // always show first and last tick
	            // don't show if next or previous tick is same
	            (next(values[index + 1]) === nextValue || next(values[index - 1]) === nextValue)
	        ) {
	            return null;
	        }

	        return nextValue;
	    }
	};


    chart_options.tooltips ={
      callbacks: {
        title: function() {
        },
        label: function(tooltipItem, data) {
          var v = data['datasets'][0]['data'][tooltipItem['index']];
          return numberWithCommas3(parseFloat(v).toFixed(accordions[accordion_id].decimals),window.app.T['.'],window.app.T[',']);
        },
      },
      displayColors: false
    }

    chart_options.scales = {
        xAxes: [{
          ticks: {
            beginAtZero: true,
			display: chartData.length !== 0,
			callback: middlewareToMakeTicksUnique(function (value) {
			    return numberWithCommas3(parseFloat(value).toFixed(accordions[accordion_id].decimals),window.app.T['.'],window.app.T[',']);
	        })
            // callback: function(value, index, values) {
            //     return numberWithCommas3(parseFloat(value).toFixed(accordions[accordion_id].decimals),window.app.T['.'],window.app.T[',']);
            // }
          }
        }]
    };

    chart_options.title = {
      display: true,
      text: '# '+chartData.length,
      fontStyle: 'bold',
      position:'bottom',
      fontSize: 12
    };

    if (limit) {
      chartData = chartData.slice(0, limit);
      chart_options.title.text = t['Showing'] +' '+  chartData.length  +' '+t['of']+' '+ accordions[accordion_id].vidAllDataFiltered.length +' '+ t['selected'];
    }
    ctx.prop('height', chartData.length * 20 + 80);
    // Initiate chart
    var myChart = new Chart(ctx, {
      type: 'horizontalBar',
      data: del.parseDataChart(chartData),
      options: chart_options,
    })
  };


  /**
   * Draw all time dataa chart in tab8 "POPUP"
   * @param {Integer} accordion_id The accordion element id
   *
   */
  del.drawTimePopupChart = function(accordion_id) {
    data = accordions[accordion_id];
    var vid = parseInt(data.vid);
    var su_ids = [];
    var DelData = {};

    var content =
    '<a id="back_toinfo"><button type="button" class = "style_button style_buttonBack">' + t['Back'] + '</button></a>'+
    '<a id = "download_all_delineation"><button  type="button" class = "style_button">' + t['Download'] + '</button></a>'+
      '<div class = "popup_chart" id = "popup_chart"><canvas id = "load_all_results_chart"/></div>' +
      '<div class = "share_chart">';

    $tab8 = $('#tab8').find('#share_content_div').html(content);
    $('#tab8').find('#share_content_tab_title').html(t['Time series']);

    $tab8.find('#back_toinfo').click(function() {
      controller.openTab('tab5');
    });
    var selected = data.vidAllDataFiltered;
    var su_list = $(selected).map(function(){return $(this).attr("label");}).get();

    if (su_list.length > 50){
      su_list =false;
      $('#tab8').find('#popup_chart').append('<p>' + t['Only 50 results displayed in a single query.'] + '</p>');
      // return false;
    }
    U.get(window.app.s2c + 'varvids', {
        var_values_id: vid,
        su_ids: su_list
      }, function(data1) {

        DelData.labels = data1.labels;
        DelData.datasets = data1.datasets;
        // if (DelData.labels.length > 20) {
        //   $('#tab8').find('#share_content_div').append('<p>' + t['Only 12 results displayed in a single query.'] + '</p>');
        // }
        if (data1.labels) {
          // Initiate chart
          var ctx = $('#load_all_results_chart');
          ctx.prop('height', DelData.datasets[0].data.length * 70 + 160);
          var myChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: DelData,
            options: {
						// 	legend: {
						//   onClick: (e) => e.stopPropagation()
						// },
              title: {
                display: true,
                text: data.PopupTitle+"; "+data.var_su,
                fontStyle: 'bold',
                fontSize: 12
              },
              maintainAspectRatio: false,
              tooltips: {
                enabled: false,
              },
              scales: {
                xAxes: [{
                  ticks: {
                    beginAtZero: true,
                    callback: function(value, index, values) {
                        return numberWithCommas3(parseFloat(value).toFixed(parseInt(JSON.parse(data1.properties).decimals)),window.app.T['.'],window.app.T[','])
                      }
                    }
                }],
                yAxes: [{
                  ticks: {
                    callback: function(tick) {
                      if (tick.length >= 14) {
                        return tick.slice(0, tick.length).substring(0, 13).trim() + '...';
                      }
                      return tick;
                    }
                  }
                }],
              },
					    tooltips: {
                callbacks: {
                  title: function() {
                  },
                  label: function(tooltipItem, data) {
                    var v = tooltipItem.xLabel;
                    return numberWithCommas3(parseFloat(tooltipItem.xLabel).toFixed(accordions[accordion_id].decimals),window.app.T['.'],window.app.T[',']);
                  },
                },
                displayColors: false
					    }
            }
          });
          $('#tab8').find('#download_all_delineation').click(function() {
            this.href = $('#tab8').find('#load_all_results_chart')[0].toDataURL();
            this.download = 'STAGE.png';
          });
        } else {
          alert(t['Time delineation is only available for published variables. Taking into account time dependence.']);
          controller.openTab('tab5');
        }

      },
      'json', $('body'));
  };
  /**
   * Draw all data  chart in tab8 "POPUP"
   * @param {Integer} accordion_id The accordion element id
   *
   */
  del.dravPopupChart = function(accordion_id) {
    data = accordions[accordion_id];
    var content =
    '<a id="back_toinfo"><button type="button" class = "style_button style_buttonBack ">' + t['Back'] + '</button></a>'+
    '<a id = "download_all_delineation"><button  type="button" class = "style_button">' + t['Download'] + '</button></a>' +
      '<div class = "popup_chart" id = "popup_chart"><canvas id = "load_all_results_chart"/></div>' +
      '<div class = "share_chart">';
    if (window.app.isadmin) {
      content += '</br><a id = "generate_chart_embeded_link"><button  type="button" class = "style_button save_export_image share_chart_btn_class">' + t['Generate chart link'] + '</button></a></br>';
      content += '<div id="static_content_popup" class = "side-popup">';
      content += '<ul id = "shareChart" hidden>'
      content += '<li><h3>' + t['Share link'] + '</h3>';
      content += '<input type="text" id = "chart_link" class ="share_chart_class">';
      content += '<button id = "copy_chart_link" type="button" class = "style_button">' + t['Copy'] + ' <i class="icon ion-ios-copy-outline"></i> </button></li>';
      content += '<li><h3>' + t['Embed chart'] + '</h3>';
      content += '<input type="text" id = "chart_link_embed" class ="share_chart_class">';
      content += '<button id = "copy_embed_chart_link" type="button" class = "style_button">' + t['Copy'] + ' <i class="icon ion-ios-copy-outline"></i> </button></li></div>';
    }
    var acrDara = accordions[accordion_id];
    var chartData = acrDara.vidAllDataFiltered;

    if (chartData.length > 50) {
      content += '<p>' + t['Only 50 results displayed in a single query.'] + '</p>';
    }
    $tab8 = $('#tab8').find('#share_content_div').html(content);
    $('#tab8').find('#share_content_tab_title').html(acrDara.var_name);
    $tab8.find('#back_toinfo').click(function() {
      controller.openTab('tab5');
    });
    chartData = chartData.slice(0, 100);
    var ctx = $('#load_all_results_chart');
    ctx.prop('height', chartData.length * 50 + 80);
    var myChart = new Chart(ctx, {
      type: 'horizontalBar',
      data: del.parseDataChart(chartData),
      options: {
        title: {
          display: true,
          text: acrDara.PopupTitle,
          fontStyle: 'bold',
        },
        scaleStartValue: 0,
        // onClick: highlightMapElement,
        tooltips: {
          enabled: false,
        },

        scales: {
          xAxes: [{
            ticks: {
              beginAtZero: true,
              callback: function(value, index, values) {
                return numberWithCommas3(parseFloat(value).toFixed(acrDara.decimals),window.app.T['.'],window.app.T[',']);
              }
            }
          }],
          yAxes: [{
            ticks: {
              callback: function(tick) {
                if (tick.length >= 14) {
                  return tick.slice(0, tick.length).substring(0, 13).trim() + '...';
                }
                return tick;
              }
            }
          }],
        },
        legend: {
          display: false
        },
		    tooltips: {
					custom: function(tooltip) {
		        if (!tooltip) return;
		        // disable displaying the color box;
		        tooltip.displayColors = false;
		      },
		      callbacks: {
		        title: function(tooltipItems, data) {
		          return '';
		        },
		        label: function(tooltipItem, data) {
		          var datasetLabel = '';
		          var label = data.labels[tooltipItem.index];
		          return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
		        },
		      }
		    }
      },
    })

    $('#tab8').find('#download_all_delineation').click(function() {
      this.href = $('#tab8').find('#load_all_results_chart')[0].toDataURL();
      this.download = 'STAGE.png';
    });

    // create embeded links
    var url = window.location.href.replace('stage_admin=true', '') + 'chart=true';
    $('#generate_chart_embeded_link').click(function() {
      U.post(window.app.s2c + 'publish_chart', {
          'cd': JSON.stringify(accordions[accordion_id].vidAllDataFiltered)
        }, function(link) {

          url = window.location.href.replace('stage_admin=true', '') + 'chart=true' + '?cid=' + link.url;
          $('#chart_link_embed').val('<iframe src=' + url + ' frameborder="0" style="width: 560px; height: 315px;"></iframe>');

          $('#chart_link').val(url);
          $('#shareChart').removeAttr('hidden');

        },
        'json', $('body'));

    });
    $tab8.find('#copy_chart_link').click(function() {
      var $copy_text = $tab8.find('#chart_link').val();
      copyToClipboard($copy_text);
    });
    $tab8.find('#copy_embed_chart_link').click(function() {
      var $copy_text = $tab8.find('#chart_link_embed').val();
      copyToClipboard($copy_text);
    });

  };

  function copyToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    try {
      var successful = document.execCommand('copy');
    } catch (err) {
      console.log('Oops, unable to copy');
    }
    document.body.removeChild(textArea);
  }

  /**
   * Add events to time delineation sorting, filtering, load all buttons
   * @param {Integer} accordion_id The accordion element id
   */
  del.accordionFUnctions = function(accordion_id) {

    $("#add_child_delineation").click(function() {
      del.remove_filter();
      var nextSelect = $('#su_select').find(":selected").next(); //.attr('selected', 'selected'); add atribute selected to next element
      if (nextSelect.val() !== undefined) {

        if ($(document).width() <= 760) {
          controller.closeSidebar();
        }
        $('#mapInfo').append('<div id="child_select"><p>' + t['Select element on the map.'] + '</p>');
        controller.map.on('click', function(e) {
          var mapData = controller.getMapData();
          var idgid = U.latlng2gid(controller.map, mapData.table_name, e.latlng, cache) + 1;
          var parent_vid = controller.getDeepLinkData().vid;
          controller.rendering = true;

          $('#su_select').find("select").val(nextSelect.val()).trigger('change');

          var tid = setInterval(function() {
            if (controller.rendering === false || controller.rendering === undefined) {
              clearInterval(tid);
              var child_vid = controller.getDeepLinkData().vid;
              del.remove_filter();

              // Get ehe data to be displayed in the chart
              U.get(window.app.s2c + 'get_child_data', {
                parent_vid: parent_vid,
                child_vid: child_vid,
                parent_selected_id: idgid
              }, function(childFilter) {
                if (childFilter.length > 0) {
                  // Get what is selected on the map
                  var var_name = controller.getPopupTitle();// Long name $('#tab2 #selected-variable').first().text();
                  var var_date = $('#tab2 #selected-variable_date select option:selected').text();
                  var var_su = $('#su_select option:selected').text();
                  var embeded_link = controller.getDeepLinkData();
                  var vidAllDataFiltered = del.vidAllData(embeded_link.vid, childFilter);
                  var vidAllData = del.vidAllData(embeded_link.vid);

                  // Create new accordion element
                  rtn_el = del.createAcordionElement(var_name, var_date, var_su, embeded_link, vidAllData,vidAllDataFiltered);
                  var delcordion_container = $("#delineation_accordion");
                  delcordion_container.prepend(rtn_el.el).accordion("refresh").accordion("option", "active", 0);
                  // add chart to accordion
                  del.accordionChart(rtn_el.accordion_id, true, 12, true, 'value');
                  // add all functions to an accordion
                  del.accordionFUnctions(rtn_el.accordion_id);
                  $('#'+ rtn_el.accordion_id + '_accordion_filter_cancel').show();

                  accordions[rtn_el.accordion_id].isFiltered=true;
                  controller.openTab('tab5');
                } else {
                  alert(t["There is no subordinate polygon to cover at least 90% with the parent unit."]);
                  controller.rendering = undefined;

                  var nextSelect = $('#su_select').find(":selected").prev(); //.attr('selected', 'selected');
                  $('#su_select').find("select").val(nextSelect.val()).trigger('change');
                }
              });
            }
          }, 300);

        });
      } else {
        alert(t['Child unit does not exist']);
      }
    });



    $("#add_parent_delineation").click(function() {
      del.remove_filter();
      var nextSelect = $('#su_select').find(":selected").prev(); //.attr('selected', 'selected');
      if (nextSelect.val() !== undefined) {
        controller.rendering = true;

        $('#su_select').find("select").val(nextSelect.val()).trigger('change');

        var tid = setInterval(function() {
          if (controller.rendering === false || controller.rendering === undefined) {
            clearInterval(tid);
            del.remove_filter();
            // Get what is selected on the map
            var var_name = controller.getPopupTitle();// Long name $('#tab2 #selected-variable').first().text();
            var var_date = $('#tab2 #selected-variable_date select option:selected').text();
            var var_su = $('#su_select option:selected').text();
            var embeded_link = controller.getDeepLinkData();
            var vidAllData = del.vidAllData(embeded_link.vid);

            // Create new accordion element
            rtn_el = del.createAcordionElement(var_name, var_date, var_su, embeded_link, vidAllData);
            var delcordion_container = $("#delineation_accordion");
            delcordion_container.prepend(rtn_el.el).accordion("refresh").accordion("option", "active", 0);
            // add chart to accordion
            del.accordionChart(rtn_el.accordion_id, true, 12, true, 'value');
            // add all functions to an accordion
            del.accordionFUnctions(rtn_el.accordion_id);
            controller.openTab('tab5');
          }
        }, 100);
      } else {
        alert(t['Parent unit does not exist']);
      }
    });

    // on accordion change accordion
    $("#delineation_accordion").accordion({
      activate: function(event, ui) {
        var active = $("#delineation_accordion").accordion("option", "active");
        var el = $("#delineation_accordion h3").eq(active).children()[0];
        var vid = parseInt(el.getAttribute("vid"));
        var aid = parseInt(el.getAttribute("accordion_id"));
        var filtered_highlight = [];
        controller.onDelineationTabChanged(vid, aid);
        controller.onChartFiltered(filtered_highlight);
        if (accordions[aid].isFiltered) {
          $.each(accordions[aid].vidAllDataFiltered, function(key, val) {
            filtered_highlight.push(parseInt(val.idgid));
          });
          controller.onChartFiltered(filtered_highlight);
        }

      }
    });
    // load all time
    $("button[name=time_delineation]").click(function() {
      var accordion_id = parseInt(this.id.split('_')[0]); // get the index of the accordion
      del.drawTimePopupChart(accordion_id);
      del.remove_filter();
      controller.openTab('tab8');
    });
    // sort by name
    $("button[value=sort_alpha_asc]").one().click(function() {
      if ($(this).val() == 'sort_alpha_desc') {
        $(this).val("sort_alpha_asc");
        $(this).html('<i class="fa fa-sort-alpha-desc"></i>');
        del.accordionChart(accordion_id, false, 12, false, 'label');
        del.remove_filter();
        controller.openTab('tab5');
      } else {
        $(this).val("sort_alpha_desc");
        $(this).html('<i class="fa fa-sort-alpha-asc"></i>');
        del.accordionChart(accordion_id, true, 12, false, 'label');
        del.remove_filter();
        controller.openTab('tab5');
      };

    });
    //sort by value
    $("button[value=sort_amount_desc]").one().click(function() {
      if ($(this).val() == 'sort_amount_desc') {
        $(this).val("sort_amount_asc");
        $(this).html('<i class="fa fa-sort-amount-desc"></i>');
        del.accordionChart(accordion_id, false, 12, true, 'value');
        del.remove_filter();
        controller.openTab('tab5');
      } else {
        $(this).val("sort_amount_desc");
        $(this).html('<i class="fa fa-sort-amount-asc"></i>');
        del.accordionChart(accordion_id, true, 12, true, 'value');
        del.remove_filter();
        controller.openTab('tab5');
      };
    });

    // Remove delineation acordion element
    $("button[name=remove_delineation_btn]").one().click(function() {
      var accordion_id = parseInt(this.id.split('_')[0]); // get the index of the accordion
      $(this).closest('h3').next().remove();
      $(this).closest('h3').remove();
      delete accordions[accordion_id];
      $("#delineation_accordion").accordion("refresh");
      $("#delineation_accordion").accordion("option", "active", 0);
      del.remove_filter();
      if (Object.keys(accordions).length == 0){
				if (controller.map.delineationHighlighter) controller.map.delineationHighlighter.remove();
        $('#btn_del_statistics').prop('disabled', true);
        controller.getSidebar().tabs.enable(['tab5'],false);
      }
      var mapData = controller.getMapData();
      var key = 'mh-' + mapData.table_name;
      var mapHighlighter = cache.get(key);
      if (!mapHighlighter) return;
      mapHighlighter.hideAll();
    });

    $('#' + accordion_id + '_display_chart_all').click(function() {
      var accordion_id = parseInt(this.id.split('_')[0]); // get the index of the accordion
      del.dravPopupChart(accordion_id);
      del.remove_filter();
      controller.openTab('tab8');
    });

    // add filter to accordion element
    $('#' + accordion_id + '_accordion_filter').click(function() {

      if (drawControl._map) {
        del.remove_filter();
        return;
      }
      var vid = controller.getDeepLinkData().vid;
      var marker_cache = [];

      controller.map.off('draw:created');
      controller.map.addControl(drawControl); // Add the drawControl
      controller.map.addLayer(drawnItems);
      // hide sidebar if sidebar is fullscreen
      if ($(document).width() <= 760) {
        controller.closeSidebar();
        $('.leaflet-draw-draw-circle').hide();
        $('.leaflet-draw-draw-rectangle').hide();
      }
      $('.leaflet-draw-section').css("padding-top", $('#mapInfo').height() + 25 + 'px');
      controller.map.on(L.Draw.Event.CREATED, function(e) {
        switch (e.layerType) {
          case 'circle':
            var lat = String(e.layer.editing._shape._latlng.lat);
            var lng = String(e.layer.editing._shape._latlng.lng);
            var radius = e.layer.editing._shape.options.radius;
            U.get(window.app.s2c + 'circle_query', {
                var_values_id: vid,
                lat: lat,
                lon: lng,
                r: radius
              }, function(data) {
                data = data.replace(/'/g, '"');
                data = JSON.parse(data);
                del.filterCacheElements(data, accordion_id,false);
                accordions[accordion_id].isFiltered = true;
                $('#'+ accordion_id + '_accordion_filter_cancel').show();
                del.accordionChart(accordion_id, true, 12, true, 'value');
                del.remove_filter();
                var to_mark =[];
                $.each(accordions[accordion_id].vidAllDataFiltered, function(key, value) {
                  to_mark.push(value.idgid);
                });
                controller.onChartFiltered(to_mark);
                controller.openTab('tab5');
              },
              'json', $('body'));
            break;
          case 'rectangle':
            var latNE = e.layer.editing._shape.editing._shape._bounds._northEast.lat;
            var lonNE = e.layer.editing._shape.editing._shape._bounds._northEast.lng;
            var latSW = e.layer.editing._shape.editing._shape._bounds._southWest.lat;
            var lonSW = e.layer.editing._shape.editing._shape._bounds._southWest.lng;

            U.get(window.app.s2c + 'square_query', {
                var_values_id: vid,
                latNE: latNE,
                lonNE: lonNE,
                latSW: latSW,
                lonSW: lonSW
              }, function(data) {
                data = data.replace(/'/g, '"');
                data = JSON.parse(data);
                del.filterCacheElements(data, accordion_id,false);
                accordions[accordion_id].isFiltered = true;
                del.accordionChart(accordion_id, true, 12, true, 'value');
                del.remove_filter();
                var to_mark =[];
                $.each(accordions[accordion_id].vidAllDataFiltered, function(key, value) {
                  to_mark.push(value.idgid);
                });
                $('#'+ accordion_id + '_accordion_filter_cancel').show();
                controller.onChartFiltered(to_mark);
                controller.openTab('tab5');

              },
              'json', $('body'));
            break;
          case 'polygon':

            var _poly = e.layer.editing._poly.editing.latlngs["0"]["0"];
            var poly = '';
            $.each(_poly, function(key, value) {
              poly = poly + value.lng + '+' + value.lat + ',';
            });
            poly = encodeURI(poly.substring(0, poly.length - 1));

            U.get(window.app.s2c + 'polygon_query', {
                var_values_id: vid,
                poly: poly
              }, function(data) {

                data = data.replace(/'/g, '"');
                data = JSON.parse(data);
                del.filterCacheElements(data, accordion_id,false);
                accordions[accordion_id].isFiltered = true;
                del.accordionChart(accordion_id, true, 12, true, 'value');
                del.remove_filter();
                var to_mark =[];
                $.each(accordions[accordion_id].vidAllDataFiltered, function(key, value) {
                  to_mark.push(value.idgid);
                });
                $('#'+ accordion_id + '_accordion_filter_cancel').show();
                controller.onChartFiltered(to_mark);
                controller.openTab('tab5');
              },
              'json', $('body'));
            break;
          case 'marker':
            var mapData = controller.getMapData();
            var idgid = U.latlng2gid(controller.map, mapData.table_name, e.layer.editing._marker._latlng, cache) + 1;
            marker_cache.indexOf(idgid) === -1 ? marker_cache.push(idgid) : console.log(marker_cache + " item already exists.");
            del.filterCacheElements(marker_cache, accordion_id,true);
            controller.onChartFiltered(marker_cache);
            accordions[accordion_id].isFiltered = true;
            $('#'+ accordion_id + '_accordion_filter_cancel').show();
            del.accordionChart(accordion_id, true, 12, true, 'value');
            break;
        };
      });
    });


    // Disable filter
    $('#' + accordion_id + '_accordion_filter_cancel').click(function() {
      accordions[accordion_id].isFiltered = false;
      controller.onChartFiltered([]);
      accordions[accordion_id].vidAllDataFiltered = accordions[accordion_id].vidAllDataRaw;
      del.accordionChart(accordion_id, true, 12, true, 'value');
      $('#'+ accordion_id + '_accordion_filter_cancel').hide();
    });
  };

  //*************************************************************************************
  //************ DEFINE  leaflet-draw-toolbar USED TO SELECT ELEMENTS ON MAP ************
  //*************************************************************************************
  var drawnItems = new L.FeatureGroup();
  var drawControl = new L.Control.Draw({
    position: 'topleft',
    draw: {
      polyline: false,
      polygon: {
        allowIntersection: false,
        metric: true,
        showArea: true,
        color: '#e1e100', // Color the shape will turn when intersects
        message: '<strong>Oh snap!<strong> you can\'t draw that!'
      },
      circle: {
        metric: true,
        feet: false,
        shapeOptions: {
          color: '#662d91'
        }
      },
      marker: {
        repeatMode: true
      }
    },
  });
  L.drawLocal.draw.toolbar.buttons.polygon = t['Polygon'];
  L.drawLocal.draw.handlers.polygon.tooltip.start = t['Click to start drawing shape.'];
  L.drawLocal.draw.handlers.polygon.tooltip.cont = t['Click to continue drawing shape.'];
  L.drawLocal.draw.handlers.polygon.tooltip.end = t['Click first point to close this shape.'];
  L.drawLocal.draw.handlers.marker.tooltip.start = t['Click map to place marker.'];
  L.drawLocal.draw.handlers.rectangle.tooltip.start = t['Click and drag to draw rectangle.'];
  L.drawLocal.draw.handlers.circle.tooltip.start = t['Click and drag to draw circle.'];
  L.drawLocal.draw.toolbar.buttons.circle = t['Circle'];
  L.drawLocal.draw.toolbar.buttons.marker = t['Marker'];
  L.drawLocal.draw.toolbar.undo.title = '';
  L.drawLocal.draw.toolbar.actions.title = '';
  L.drawLocal.draw.toolbar.actions.text = t['Cancel'];
  L.drawLocal.draw.toolbar.finish.title = '';
  L.drawLocal.draw.toolbar.finish.text = t['Finish'];


  del.remove_filter = function() {
    if (drawControl===undefined) return;
    controller.map.removeControl(drawControl); // Use this command to removi draw control
  }

  //******************************************************
  //************  SUPPORT FUNCTIONS  *********************
  //******************************************************


  /**
   * Remove elements from single barchart cache element
   * @param {array} selected_elements the items to be rendered in the chart
   * @param {Integer} accordion_id The accordion element id
   * @param {Boolean} marker If selection method is markte true
   */
  del.filterCacheElements = function(selected_elements, accordion_id,marker) {
    filtered_elements = [];
    $.each(selected_elements, function(key, value) {
      vidAllDataRaw = accordions[accordion_id].vidAllDataRaw;

      $.each(vidAllDataRaw, function(keyn, val) {
        if(marker){
          if (val.idgid == value) {
            filtered_elements.push(val);
          }

        }
        else{
          if (val.id == value) {
            filtered_elements.push(val);
          }

        }
      });
    });
    accordions[accordion_id].vidAllDataFiltered = filtered_elements;
  }
  /**
   * Prepare the data to be rendered with chart.js
   * @param {array} cahrt_data the items to be rendered in the chart
   */
  del.parseDataChart = function(cahrt_data) {
    var labels = [];
    var dataset = [];
    var backgroundColor = [];

    $.each(cahrt_data, function(accordion_id, item) {
      if (typeof item !== 'undefined') {
        labels.push(item.label);
        dataset.push(item.value);
        if (typeof item.color !== null) {
          backgroundColor.push(parseColorOBJ(item.color));
        }
      }
    });
    var cahrt_data_export = {
      labels: labels,
      datasets: [{
        borderWidth: 1,
        data: dataset,
        backgroundColor: backgroundColor,
      }]
    };
    return cahrt_data_export;
  };

	/**
	* Sort elements by attribute
	* @param {Object} data the array of objects to be sorted
	* @param {string} atr the atribute by which the array is to be sorted
	* @param {boolean} desc set true if the elemets are to be sorted descending
	* @param {boolean} isNumeric set true if sort by numbers
	*/
	del.sort = function(data, atr, desc , isNumeric ) {

		if (isNumeric){
			var sort = data.sort(dynamicSort(atr, isNumeric));
		} else {
			var sort = data.sort(function(a,b){
				return a[atr].localeCompare(b[atr]);
			});
		}
		desc ? sort.reverse() : false;
		return sort;

	};

	// Support function for sorting elements
	function dynamicSort(property, isNumeric) {
		var sortOrder = 1;
		if (property[0] === "-") {
			sortOrder = -1;
			property = property.substr(1);
		}
		return function(a, b) {
			var result = (parseFloat(a[property]) < parseFloat(b[property])) ? -1 : (parseFloat(a[property]) > parseFloat(b[property])) ? 1 : 0;
			return result * sortOrder;
		}
	};

  /**
   * @param {Integer} vid The id in the s2.varval table in the STAGE DB
   * @param {Array} childFilter the idgid's of child units
   */
  del.vidAllData = function(vid, childFilter) {
    var mapData = controller.getMapData();
    var specialValues = controller.getMapData().special_values;
    var cba = mapData.legendData.cba;
    var cbac = mapData.legendData.cbac;
    var data = [];
    var specialValueKeys = Object.keys(specialValues);
    var i = 0;
    for (var i = 0, c = mapData.sldNames.length; i < c; ++i) {
      var value = String(mapData.variableValues[i]).replace(",", ".");
      var name = mapData.sldNames[i];
      var color = giss.getColorFromValue(value, cba, cbac);
      if (!isNaN(value)) {
        if (childFilter) {
          if ($.inArray(parseInt(name.idgid), childFilter) != -1) {
            data.push({
              value: value,
              id: name.id,
              idgid: name.idgid,
              label: name.name,
              color: color
            });
          }
        } else {
          data.push({
            value: value,
            id: name.id,
            idgid: name.idgid,
            label: name.name,
            color: color
          });
        }
      }
    }

    return data;
  };
  /**
   * Parse bacghrouncolor element to RGBA
   * @param {Integer} accordion_id The accordion element id
   */
  function parseColorOBJ(bce) {
    if (bce == 'yelow') {
      // return 'rgba(255,255,0,0.3)';
			return 'rgba(' + 0 + ',' + 0 + ',' + 0 + ',' + 0.5 + ')';
    }
    return 'rgba(' + bce.r + ',' + bce.g + ',' + bce.b + ',' + bce.a + ')';

  }


  // This method is used to decide what to do when user clicks on map
  // if selectionMethod = 'popup' popup is displayed othervise the side bar barchart is updated
  del.getSellectionMethod = function() {
    return selectionMethod;
  };
  // Generate new Accordion ID
  del.new_accordionID = function() {
    var new_accordion_id;
    var keys = $.map(accordions, function(element, index) {
      return parseInt(index);
    });
    var key = Math.max.apply(Math, $.map(accordions, function(element, index) {
      return parseInt(index);
    }));
    if (keys.length > 0) {
      new_accordion_id = key + 1;
    } else {
      new_accordion_id = 0;
    }
    return new_accordion_id;
  };

  /**
   * gets the active accordion ID
  */

  function getActiveAccordion(){
    var active = $("#delineation_accordion").accordion("option", "active");
    var ch=$("#delineation_accordion h3").eq(active).children()[0];
    if (ch===undefined) return;
    var vid = parseInt(ch.getAttribute("vid"));
    var active_accordion_id = parseInt(ch.getAttribute("accordion_id"));
    return active_accordion_id;
  }

  /**
   * gets the GID of selected spatial unit in accordion chart
  */
  function getCurrentlySelectedGID(active_accordion_id,i){
    if (active_accordion_id===undefined) return;
    var gid = parseInt(accordions[active_accordion_id].vidAllDataFiltered[i[0]._index].idgid) - 1;
    return gid;
  }

  /**
   * Highlight the selected element on map when user clicks on bar in the barchart
   */
  function highlightMapElement(c, i) {
    if ($(document).width() <= 760) {
      controller.closeSidebar();
    }

    var active_accordion_id=getActiveAccordion();
    if (active_accordion_id===undefined) return;
    var activeAccordion=accordions[active_accordion_id];
    del.onMapClick(getCurrentlySelectedGID(active_accordion_id, i), activeAccordion.vidAllDataRaw);
  }

  /**
   * shows the border of spatial unit with id=gid
   */

  function showSUborder(gid){
    var mapData = controller.getMapData();
    var key = 'mh-' + mapData.table_name;
    var mapHighlighter = cache.get(key);
    if (!mapHighlighter) return;
    controller.map.closePopup();
    mapHighlighter.showBorder(gid, true);
  }

  del.chart_selection=function() {
    if ($('#tab5').hasClass('active')) {
      var active_accordion_id=getActiveAccordion();
      if (active_accordion_id===undefined) return;
      var activeAccordion=accordions[active_accordion_id];
      var gid=activeAccordion.highlightedElementGid;
      if (gid===undefined) return;
      del.onMapClick(gid, accordions[active_accordion_id].vidAllDataRaw);
    }
    else{
      var mapData = controller.getMapData();
      var key = 'mh-' + mapData.table_name;
      var mapHighlighter = cache.get(key);
      if (!mapHighlighter) return;
      mapHighlighter.hideAll();
    }
  }

  /** TODO Function is used to highlight chart element based on what user clicks on the map
   * @param {String} gid id of the poligon displayed on the map
   * @param {Object} fname the data which polygon was selected on the map DEPRICATED
   */
  del.onMapClick = function(gid, fname) {
    if (!$('#tab5').hasClass('active')) return; //play the game only if delineation tab is active
    if ($('#delineation_accordion').children('h3').length > 0 && !drawControl._map) {
      var active = $("#delineation_accordion").accordion("option", "active");
      var active_accordion_id = parseInt($("#delineation_accordion h3").eq(active).children()[0].getAttribute("accordion_id"));

      var activeAccordion=accordions[active_accordion_id];
      vidAllDataFiltered = activeAccordion.vidAllDataFiltered;
      activeAccordion.highlightedElementGid=gid;

      showSUborder(gid);
      var showing = 0;
      chartData = [];
      $.each(vidAllDataFiltered, function(keyn, val) {
        var nVal = {};
        nVal.id = val.id;
        nVal.label = val.label;
        nVal.value = val.value;
        nVal.idgid = val.idgid;
        nVal.color = val.color;
        if (parseInt(val.idgid) == parseInt(gid)+1) {
          nVal.color = 'yelow';
        }
        chartData.push(nVal);
        showing++;
      });
      $chart_div = $("#sidebar_chart_" + active_accordion_id).
      html('<canvas width="250" height="250" id = "del_chart_' + active_accordion_id + '"/>');
      var ctx = $('#del_chart_' + active_accordion_id);

      chartData = chartData.slice(0, 12);
      ctx.prop('height', chartData.length * 20 + 80);
      // Initiate chart
      var myChart = new Chart(ctx, {
        type: 'horizontalBar',
        data: del.parseDataChart(chartData),
        options: {
          title:{
            display: true,
            text: t['Showing'] +' '+  chartData.length  +' '+t['of']+' '+ showing +' '+ t['selected'],
            fontStyle: 'bold',
            position:'bottom',
            fontSize: 12
          },
          animation: {
            duration: 0
          },
          scaleStartValue: 0,
          onClick: highlightMapElement,
					tooltips: {
						custom: function(tooltip) {
			        if (!tooltip) return;
			        // disable displaying the color box;
			        tooltip.displayColors = false;
			      },
			      callbacks: {
			        title: function(tooltipItems, data) {
			          return '';
			        },
			        label: function(tooltipItem, data) {
			          var datasetLabel = '';
			          var label = data.labels[tooltipItem.index];
			          return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
			        },
			      }
			    },
          scales: {
            xAxes: [{
              ticks: {
                beginAtZero: true,
                callback: function(value, index, values) {
                    return numberWithCommas3(parseFloat(value).toFixed(accordions[active_accordion_id].decimals),window.app.T['.'],window.app.T[',']);
                }
              }
            }],
            // yAxes: [{
            //   ticks: {
            //     callback: function(tick) {
            //       if (tick.length >= 14) {
            //         return tick.slice(0, tick.length).substring(0, 13).trim() + '...';
            //       }
            //       return tick;
            //     }
            //   }
            // }],
          },
          legend: {
            display: false
          },
        },
      })
    }
  };


  del.doDelineationStatistics = function() {
    var content =
    '<a id="back_toinfo"><button type="button" class = "style_button style_buttonBack ">' + t['Back'] + '</button></a>'+
    '<a id = "download_all_delineation"><button  type="button" class = "style_button ">' + t['Download'] + '</button></a>'+
      '<div class = "popup_chart" id = "popup_chart"><canvas id = "load_all_results_chart"/></div>' +
      '<div class = "share_chart">';
    $tab8 = $('#tab8').find('#share_content_div').html(content);
        $('#tab8').find('#share_content_tab_title').html(t['Spatial query statistics']);
    del.delineation_statistics_get_data();
    controller.openTab('tab8');

    $tab8.find('#back_toinfo').click(function() {
      controller.openTab('tab5');
    });
  }


  //*********************************************************************
  //**********************************  TODO ****************************
  //*********************************************************************
  del.doDelineationCorrelation = function() {
    var content =
      '<div class = "popup_chart" id = "popup_chart"><canvas id = "load_all_results_chart"/></div>' +
      '<div class = "share_chart">' +
      '<a id = "download_all_delineation"><button  type="button" class = "style_button save_export_image share_chart_btn_class">' + t['Download'] + '</button></a>';
    $tab8 = $('#tab8').find('#share_content_div').html(content);
    CC.drawChart(accordions);

    controller.openTab('tab8');
  }

  /**
   * Calculate delineation statistics data
   * @param {Object} variable The variable name
   */
  del.delineation_statistics_get_data = function() {
    var result = {};
    var datasets = [];
    var labels = [];

    var element = [];
    var data = [];
    var dca = {}; // delineation cache to be passed in the delineation service call


    var decimals = accordions[Object.keys(accordions)[0]].decimals;
    // Because the cache is too big to be passed via URL irelevand data has to be excluded
    $.each(accordions, function(j, j_element) {
      var selected = j_element.vidAllDataFiltered.map(function(a) {
        return a.id;
      });

      dca[j] = {
        vid: j_element.vid,
        date: j_element.var_date,
        var_name: j_element.PopupTitle + ', ' + j_element.var_su + ' ( '+j_element.var_date+' ) :' ,
        sid: j_element.embeded_link.sid,
        selected: selected,
      }
    });
    // Get the delineation data
    U.post(window.app.s2c + 'delineation', {
        dca: JSON.stringify(dca)
      }, function(DelData) {
        if (DelData.datasets) {

          var ctx = $('#load_all_results_chart');
          var clientWidth = ctx[0].clientWidth;
          ctx.prop('height', DelData.datasets[0].data.length * 70 + 60+50);
          var myChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: DelData,
            options: {

              legend: {
                display: false
              },
              title: {
                display: false,
                text: $('#btn_del_statistics').text(),
                fontStyle: 'bold',
                fontSize: 13,
              },
              maintainAspectRatio: false,
              tooltips: {
                enabled: false,
              },
              scales: {
                xAxes: [{
                  gridLines: {
                    display: false
                  },
                  ticks: {
                    beginAtZero: true,
                    callback: function(value, index, values) {
                        return numberWithCommas3(parseFloat(value).toFixed(decimals),window.app.T['.'],window.app.T[',']);
                    }
                  }
                }],
                yAxes: [{
                  gridLines: {
                    display: false
                  },
                  barThickness: 58,
                  ticks: {
                    display: false,
                  }
                }],
              },
              events: [],
              animation: {
                duration: 500,
                easing: "easeOutQuart",
                onComplete: function() {
                  var ctx = this.chart.ctx;
                  // ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
                  ctx.font = "12px Arial";
                  ctx.textAlign = 'left';
                  ctx.textBaseline = 'bottom';

                  this.data.datasets.forEach(function(dataset) {
                    for (var i = 0; i < dataset.data.length; i++) {
                      var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
                        scale_max = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._yScale.maxHeight;
                      left = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._xScale.left;
                      offset = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._xScale.longestLabelWidth;
                      ctx.fillStyle = 'black';
                      var y_pos = model.y - 90;
                      var bar_value = numberWithCommas3(parseFloat(DelData.datasets[0].data[i]).toFixed(1),window.app.T['.'],window.app.T[','])
                      var label = splitIntoLines(model.label , 38,bar_value).slice(0, 4);
                      if ((scale_max - model.y) / scale_max >= 0.93)
                        y_pos = model.y + 20;
                      var lines = 0;
                      $.each(label, function(key, value) {

                        ctx.fillText(value, left + 10, model.y - 10 + lines);
                        lines += 13;
                      });
                    }
                  });
                }
              }
            }
          });
          $('#tab8').find('#download_all_delineation').click(function() {
            this.href = $('#tab8').find('#load_all_results_chart')[0].toDataURL();
            this.download = 'STAGE.png';
          });
        } else {
          alert(t['Delineation statistics is not possible for the selected set of variables.']);
          controller.openTab('tab5');

        }
      },
      'json', $('body'));
  }

  function splitIntoLines(input, len,bar_value) {
    var i;
    var output = [];
    var lineSoFar = "";
    var temp;
    var words = input.split(' ');
    for (i = 0; i < words.length;) {
      // check if adding this word would exceed the len
      temp = addWordOntoLine(lineSoFar, words[i]);
      if (temp.length > len) {
        if (lineSoFar.length == 0) {
          lineSoFar = temp; // force to put at least one word in each line
          i++; // skip past this word now
        }
        output.push(lineSoFar); // put line into output
        lineSoFar = ""; // init back to empty
      } else {
        lineSoFar = temp; // take the new word
        i++; // skip past this word now
      }
    }
    if (lineSoFar.length > 0) {
      output.push(lineSoFar);
    }
    output.push(bar_value);

    return (output);
  }

  function addWordOntoLine(line, word) {
    if (line.length != 0) {
      line += " ";
    }
    return (line += word);
  }

  return del;
});
