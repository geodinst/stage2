(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

      // $( "#textarea-description").trumbowyg();
      var dm = $(".clearfix menu");
      $( "[name='add_area']").once().on('click',function(){
        $( "[name='delineation_formula']").val($( "[name='delineation_formula']").val()+'{area}');
      });

      $( "[name='update_delineation_formula']").once().on('click',function(){
        $( "[name='delineation_formula']").val($( "[name='delineation_formula']").val()+'{'+$( "[name='del_var_name'] option:selected").val()+'}');
      });


      $( "[name='del_var_name']").select2({dropdownAutoWidth : true});
      if ($( "[name='delineation_formula']").val() == 'DELINEATION_DISABLED'){
        $( "[name='delineation_disabled']").prop('checked', true);
        $( "[name='delineation_formula']").val('DELINEATION_DISABLED');
        $( "[name='delineation_formula']").prop('disabled', true);
        $( "[name='add_area']").hide();
        $( "[name='update_delineation_formula']").hide();
        $( "[name='del_var_name']").select2('destroy');
        $( "[name='del_var_name']").hide();
      }

      $( "[name='delineation_disabled']").on('click', function(){
        if ($(this).is(':checked')) {
          $( "[name='delineation_formula']").val('DELINEATION_DISABLED');
          $( "[name='delineation_formula']").prop('disabled', true);
          $( "[name='add_area']").hide();
          $( "[name='update_delineation_formula']").hide();
          $( "[name='del_var_name']").select2('destroy');
          $( "[name='del_var_name']").hide();
          } else {
            $( "[name='delineation_formula']").prop('disabled', false);
            $( "[name='delineation_formula']").prop('disabled', false);
            $( "[name='delineation_formula']").val('');
            $( "[name='add_area']").show();
            $( "[name='update_delineation_formula']").show();
            $( "[name='del_var_name']").select2({dropdownAutoWidth : true});
          }
      });

      $existing_acronyms = $( "[name='existing_acronyms']");
      $acronym = $( "[name='acronym']");
      $existing_acronyms.hide();

      $variable_id_field = $( "[name='variable_id_field']");
      $variable_id_field.hide();

      $dependent_variables_field = $( "[name='dependent_variables_field']");
      $dependent_variables_field.hide();

			$tapic = $( "[name='picture_textarea']");
			$picture_preview_container = $( "#picture_preview_container");
			var picture_blob = $tapic.val();
			$tapic.attr("disabled", true);
			$tapic.hide();
			$picture_preview_container.once().prepend('<div id ="pic_blob">'+ picture_blob +'</div>');

      $desc_cb = $( "[name='desc_cb']");
      $desc_val = $( "[name='desc_val']");
      $desc_warning = $( "[name='desc_warning']");
      $save_btn = $( "[name='save_btn']");

			if(picture_blob == 'The picture is not available.'){
				$( "[name='remove_picture']").hide();
			}
			$( "[name='remove_picture']").click(function(){
				// $picture_preview_container.hide();
				$tapic.attr("disabled", false);
				// $tapic.show();
				$tapic.val('picture_removed');
				$('#pic_blob').html('The picture is not available.');
			})
			// Check if acronym already exists
			var $existing_acronyms_val = $existing_acronyms.val();
			$acronyms_json = JSON.parse($existing_acronyms_val);

			$acronyms_json.splice($.inArray($acronym.val(),$acronyms_json),1);

			$acronym.attr('autocomplete', 'off');
			$acronym.keyup(function(){
        $(this).val($(this).val().toUpperCase());
				var $input_acr = $(this).val();
					if (($($.makeArray($input_acr)).filter($acronyms_json)).length>0){
					$(this).next().text('Existing acronym !');
					$save_btn.attr("disabled", true);
					$acronym.css('outline-color','red');
					$acronym.css('border-color','red');
				}
				else{
					$save_btn.attr("disabled", false);
					$acronym.css('border-color','#ccc');
					$acronym.css('outline-color','#ccc');
					$(this).next().text('Variable acronym to be used when user imports data.');

				}
			});

      if($desc_cb.checked){
        $desc_warning.show();
      } else{
        $desc_warning.hide();
      }

      $desc_cb.change(function() {
          $desc_val.attr('disabled',!this.checked);
          if(this.checked){
            $desc_warning.show();
          } else{
             $desc_warning.hide();
          }

          });
      var $fml = $('[name="delineation_formula"]');
      // check delineation Formula
      var $Allowed = ['+','-','*','/','0','1'];
      $fml.keypress(function(e){

        if ($.inArray(e.key,$Allowed)==-1){
          e.preventDefault();
          $fml.css('outline-color','red');
          $fml.css('border-color','red');
					$('#edit-formula--description').html('Only + - * / allowed');
        }
        else{
          $fml.css('border-color','#ccc');
          $fml.css('outline-color','#ccc');
          $('#edit-formula--description').html('Please test the validity of the formula before publishing the variable.');
          $(this).next().text('');
        }
      });
}
};
 }(jQuery));
