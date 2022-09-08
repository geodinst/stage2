(function ($) {
	Drupal.behaviors.stage2_admin = {
		attach: function (context, settings) {

			// hide unneccessary elements
			$('.progress').once().hide();
			$('#get_headers_btn').once().hide();
			$('#edit-spatial-layer-id').select2();
			$( "[name='geo_column_id_select']").select2();

			$("[data-drupal-selector='edit-import-filter-table'").once().sieve({textSelector:':text'});
			var dateFormat='yy-mm-dd';
			$( "input.date_var").once().datepicker({ dateFormat: dateFormat }); // Manualy add datepicker. Because the default drupal datepiker does not get rendered in firefox
			//$( "input[name^='date_var_']").once().datepicker({ dateFormat: dateFormat });

			window.StageFormsCommon();

			$('#variable_date').click(function(){
				var value = $("#date_for_all").val();
				$("input.date_var").val(value);
			});

			// SYNHRONIZE SELECTS  AND ACRONYM
			var test_arr = $("input.header_name_var");
			$.each(test_arr, function(i, item) {
				var name=$(item).attr('name');
				var i1=name.indexOf('[');
				var i2=name.indexOf(']');
				var id = name.slice(i1+1,i2);
				var value = $(item).val();
				var $acrsel = $("[name='import_filter_table["+id+"][acronym]']");
				var $varnamsel = $("[name='import_filter_table["+id+"][variable_name]']");

				var $optionsFound=$acrsel.find('option').filter(function() {
					return $(this).text().toUpperCase() === value.toUpperCase();
				});

				if ($optionsFound.length ==1){
					$acrsel.find("option").filter(function () { return $(this).html() == value; }).prop('selected', true)
					$varnamsel.val($acrsel.val()).change();
					$acrsel.val($acrsel.val()).change();

					$acrsel.closest('tr').addClass('acronym_found');

					$("[name='import_filter_table["+id+"][checked]']").attr('checked','checked');
				}
			});

			// Synhonize Geo reference
			$( "[name='geo_column_id_select']").once().change(function(){
				enable_elements();
				var selected_id = parseInt(this.value)+1;

				$("input[name='import_filter_table["+selected_id+"][header_id]']").addClass('input_disabled');
				$("[name='import_filter_table["+selected_id+"][variable_name]']").attr("disabled", true).addClass('input_disabled');
				$("[name='import_filter_table["+selected_id+"][acronym]']").attr("disabled", true).addClass('input_disabled');
				$("[name='import_filter_table["+selected_id+"][date]']").attr("disabled", true).addClass('input_disabled');
				$("[name='import_filter_table["+selected_id+"][checked]']").prop('checked', false).attr("disabled", true).addClass('input_disabled');
			}).trigger('change');

			function enable_elements(){
				$(".header_name_var").removeClass('input_disabled');
				$(".select_var").attr("disabled", false).removeClass('input_disabled');
				$(".select_acr").attr("disabled", false).removeClass('input_disabled');
				$(".date_var").attr("disabled", false).removeClass('input_disabled');
				$( "[name^='import_filter_table']").attr("disabled", false).removeClass('input_disabled');
			}
		}
	}
}(jQuery));
