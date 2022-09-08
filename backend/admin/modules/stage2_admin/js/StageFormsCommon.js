(function ($) {
  window.StageFormsCommon=function(){

		$("#edit-import-filter-table thead tr th:first").prepend('<input type="checkbox" id = "custom_select_all"name="selectAll" />');
		$('#custom_select_all').click(function (e) {
		    $(this).closest('table').find('td input:checkbox:visible').prop('checked', this.checked);
		});

    // SYNHRONIZE SELECTS IN ONE ROW
		$(document).on("change", "#block_name", function () {
		   if($("#block_number").val() != id){
		      $("#block_number").select2('val',id);
		   }
		});

		$(document).on("change", "#block_number", function () {
		   id = $(this).val();
		   if($("#block_name").val() != id){
		       $("#block_name").select2('val',id);
		   }

		 });
    $(".select_var").once().change(function(){
			id = $(this).val();
			if ($(this).parent().parent().next('td').find('select').val()!= id){
				$(this).parent().parent().next('td').find('select').val(this.value).trigger('change');
			}
			markSelected($(this),id);
    });
    $(".select_acr").once().change(function(){
			id = $(this).val();
			if ($(this).parent().parent().prev('td').find('select').val()!= id){
					$(this).parent().parent().prev('td').find('select').val(this.value).trigger('change');
			}
			markSelected($(this),id);
    });

	function markSelected(element, id){
		if(id > -1){
				if(!element.closest('tr').hasClass('acronym_found')){
					element.closest('tr').addClass('acronym_found');
				}
				element.closest('tr').children(':first').find("input").prop("checked", true);
			}else{
				if(element.closest('tr').hasClass('acronym_found')){
					element.closest('tr').removeClass('acronym_found');
				}
				element.closest('tr').children(':first').find("input").prop("checked", false);
			}
	}

	$("#edit-import-filter-table .form-checkbox").change(function(){
		if($(this).is(":checked")){
			if($(this).closest('tr').children(':nth-child(3)').find("select").val() > -1){
				if(!$(this).closest('tr').hasClass('acronym_found')){
						$(this).closest('tr').addClass('acronym_found');
					}
			}
		}else{
			if($(this).closest('tr').hasClass('acronym_found')){
					$(this).closest('tr').removeClass('acronym_found');
				}
		}
	});

    $(".remove-offending-file").once().click(function(){
      var fname=$(this).data('fname');
      if (fname===""){
        $('#uploaded_files_list').empty();
        $('#uploaded_files').val('');
        location.href=location.href;
      }

      var upld=$('#uploaded_files').val();
      if (upld){
        upld=JSON.parse(upld);
        var inx=upld.indexOf(fname);
        if (inx > -1) {
          upld.splice(inx, 1);
        }
        $('#uploaded_files_list').html(upld.join('<br>'));
        $(this).remove();
        $('#uploaded_files').val(JSON.stringify(upld));
        if (upld.length===0){
          location.href=location.href;
        }
        else{
          $('#get_headers_btn').click();
        }
      }
    });

		$(".select_acr").select2({ width: '100%' });
		$(".select_var").select2({ width: '100%',
				templateResult: formatResult,
				templateSelection: slected_template,
		});
		function formatResult(node) {
			var $result = $('<span style="padding-left:' + (20 * (node.text.match(/>/g) || []).length) + 'px;">' + node.text.replace(/\>/g, '') + '</span>');
			return $result;
		};
		function slected_template(data, container){
			return data.text.replace(/\>/g, '');
		};

  };
}(jQuery));
