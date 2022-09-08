(function ($) {

  var legend;
  var variableValues=[];
  var filteredSpecialValues={};
  
  function updateUI(){
    if (isManual()) return;
    var cm=parseInt($('[data-drupal-selector="edit-manual-parameters-manual-param-input-auto-classification-interval"]').find(':selected').val());
    
    if (cm==8) {
      $('#cb').hide();
      $('#less_more').show();
    }
    else{
      $('#cb').show();
      $('#less_more').hide();
    }
  }

  function renderNotesOnSpatialUnits(data) {
    window.define = window.stageRequireJs.define;
    window.stageRequireJs.require.config({baseUrl: window.location.origin + drupalSettings.path.baseUrl + 'modules/stage2_admin/js/cmp/js/'});
    window.stageRequireJs.require(['SuNotes'],function(SuNotes) {
      const sun = new SuNotes(data);
    });  
  }
  
  function renderLegend(refreshClassBreaks){
    
    var legendSettings=manualLegendSettings(refreshClassBreaks);
    legendSettings.cm=0;
    window.define = window.stageRequireJs.define;
    window.stageRequireJs.require.config({baseUrl: window.location.origin + drupalSettings.path.baseUrl + 'modules/stage2_admin/js/cmp/js/'});
    window.stageRequireJs.require(['Legend'],function(Legend) {
      legend=new Legend(variableValues,legendSettings,{intervalEditContainer: $('#interval-editor'), edit:true,modalTemplate:2,onTableRefreshed:onTableRefreshed});
      $('[name="manual_parameters[manual_param_input][manual_classification][manual_breaks]"]').hide();
      $('#settings-legend').html(legend.$el());
    });
  }
  
  var onTableRefreshed=function(legendData){
    $('[name="manual_parameters[manual_param_input][manual_classification][manual_breaks]"]').val(legendData.cba.join(','));
  };
  
  function isManual(){
    return $('[name="manual_parameters[manual_param_input][classification]"]:checked').val()=="1"?true:false;
  }
  
  function manualLegendSettings(refreshClassBreaks){
    var legendSettings={cp:$('#colorBrewerInput').val(),
                   cb: -1,
                   cm: 0,
                   decimals:parseInt( $('[data-drupal-selector="edit-manual-parameters-manual-param-input-decimals"]').find(':selected').val())
            };
    
    if (!refreshClassBreaks){
      if (legend){
        legendSettings.cba=legend.getData().cba;
      }
      else{
        var cba=$('[name="manual_parameters[manual_param_input][manual_classification][manual_breaks]"]').val().split(',').map(function(x){return parseFloat(x);});
        if (cba.length>1){
          legendSettings.cba=cba;
        }
      }
    }
    
    return legendSettings;
  }
  
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

			$("#edit-table-select thead tr th:first").once().prepend('<input type="checkbox" id = "custom_select_all"name="selectAll" />');
			$('#custom_select_all').click(function (e) {
			    $(this).closest('table').find('td input:checkbox:visible').prop('checked', this.checked);
			});

      $("[data-drupal-selector='edit-table-select'").once().sieve();


      $('[name="var_id"]').change(function(){
        $( "form input:checkbox" ).prop('checked', false);
        $( "form input:checkbox" ).unbind('click');
        $('#edit-table-select-'+this.value).prop('checked', true);

        $('#edit-table-select-'+this.value).on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
        });
      });
      
      if (drupalSettings.variableValues && ($(context).data('drupal-selector')=='stage-geospatial-layers-edit-form')){
        
        var rawVariableValues=drupalSettings.variableValues.data;
        
        if (variableValues.length===0) {
          for (var i=0,c=rawVariableValues.length;i<c;++i){
            variableValues.push(parseFloat(rawVariableValues[i]));
          }
        }
        
        renderNotesOnSpatialUnits(drupalSettings.suNotes);
        
        updateUI();
        
        var refreshClassBreaks=false;
        var $specialValuesTable=$("[data-drupal-selector='edit-manual-parameters-manual-param-input-special-values-special-values-table'] tr");
        var specialValues=[];
        $specialValuesTable.each(function(){
          specialValues.push($(this).find('td:nth-child(2)').text());
        });
        
        //for special values that were removed from the table
        $.each(filteredSpecialValues,function(key) {
          if (specialValues.indexOf(key)==-1){
            $.each(rawVariableValues,function(i,value){
              if (key==value) {
                variableValues[i]=parseFloat(rawVariableValues[i]);
                refreshClassBreaks=true;
              }
            });
            delete filteredSpecialValues[key];
          }
        });
        
        //for special values that were added to the table
        $.each(specialValues,function(spi,specialValue){
          if ($.trim(specialValue)==='') return true;
          if (filteredSpecialValues[specialValue]===undefined){
            $.each(rawVariableValues,function(i,value){
              if (specialValue==value) {
                variableValues[i]=NaN;
                refreshClassBreaks=true;
              }
            });
            filteredSpecialValues[specialValue]=true;
          }
        });
        
        refreshClassBreaks=false;
        
        if (isManual()){
          renderLegend(refreshClassBreaks);
        }
        
        $('#colorBrewerInput, [name="manual_parameters[manual_param_input][classification]"]').once().change(function(){
          if (!isManual()) return;
          renderLegend();
        });
				
				$('[name="manual_parameters[manual_param_input][decimals]"]').once().change(function(){
					if (!isManual()) return;
					if (confirm('Changing the number of decimals will reset the legend! Do you want to proceed?')){
						renderLegend(true);
					}
				});
        
        $('[data-drupal-selector="edit-manual-parameters-manual-param-input-auto-classification-interval"]').once().change(function(){
          updateUI();
        });
      }
      
  }};
}(jQuery));
