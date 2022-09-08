(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

      window.StageFormsCommon();

      $("[data-drupal-selector='edit-import-filter-table'").once().sieve({textSelector:':text'});

      $( "input[name='public_url']").hide();
      $( "input[name='js_input']").hide();

      // Exclude options in PX date column and PX geocode
      $( '[name="px_date"]').once().change(function(){
        $('[name="px_geocode"]').find('option').show();
        $('[name="px_geocode"] option[value='+this.value+']').hide();
      });

      $( '[name="px_geocode"]').once().change(function(){
        $('[name="px_date"]').find('option').show();
        $('[name="px_date"] option[value='+this.value+']').hide();
      });

      $('#cb-check-all').once().change(function(){
        if ($(this).prop('checked')){
          $('#cb-dates input').prop('checked',true);
        }
        else{
          $('#cb-dates input').prop('checked',false);
        }
      });

      $('#cb-dates input').once().change(function(){
        if (!$(this).prop('checked')){
          $('#cb-check-all').prop('checked',false);
        }
        else{
          var cbcnt=$('#cb-dates input').length;
          var ccbcnt=$('#cb-dates input:checked').length;
          if (cbcnt===ccbcnt){
            $('#cb-check-all').prop('checked',true);
          }
        }
      });
      
      if (drupalSettings.stage2_admin!==undefined){
        var links=drupalSettings.stage2_admin.links;
        if (links!==undefined){
          $.each(links,function(key,link){
            var $el=$('table').find("[data-codes='" + link.codes + "']");
            if ($el[0]!==undefined) {
              var $select=$el.closest('tr').find('.select_acr');
              var $optionsFound=$select.find('option').filter(function() {
                  return $(this).text().toUpperCase() === link.acronym.toUpperCase();
              });
        
              if ($optionsFound.length ==1){
                $select.val($optionsFound.val()).trigger('change');  
              }
            }
          });
        }
      }
    }
};
}(jQuery));
