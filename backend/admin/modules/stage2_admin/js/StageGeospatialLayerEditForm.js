  (function ($) {
    window.onInput2table=function(){
      $('#select_columns_container').hide();
      $('#edit-save').hide();
    };

    Drupal.behaviors.stage2_admin1 = {
      attach: function (context, settings) {

        $('#edit_id_id').hide();
        $('#get_headers_btn').hide();
        var $datetime = $('#edit-valid-from');
        // hide time
  			$datetime.children().eq(1).hide();
        // add sellect 2 to select fields
        $('.stage_select_box').once().select2({dropdownAutoWidth : true});
        $('.stage_select_box.error').next('span.select2').addClass('error');


        // loading process
        $('#save_shp_btn').click(function() {
          var overlay = jQuery('<div id="overlay"><div class="loader"></div><div id="overlay_text" ><p>Please note that this may take several minutes.</br> Thank you for your patience.</p></div></div>');
          overlay.appendTo(document.body);
        });

      }
    };
  })(jQuery);
