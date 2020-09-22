(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {
      // check if sieve already exists
	  if(!$("div.element-search").length){
		$("#tableselect_id").sieve();
	  }


$("#tableselect_id thead tr th:first").once().prepend('<input type="checkbox" id = "custom_select_all"name="selectAll" />');
$('#custom_select_all').click(function (e) {
    $(this).closest('table').find('td input:checkbox:visible').prop('checked', this.checked);
});

      $('#edit-publish').attr("disabled", true);
      $('#edit-delete').attr("disabled", true);
      $('#edit-unpublish').attr("disabled", true);
      $('input[type = checkbox]').change(function(){

        if ($('input[type=checkbox]:checked').length){

          $('#edit-publish').attr("disabled", false);
          $('#edit-delete').attr("disabled", false);
          $('#edit-unpublish').attr("disabled", false);

        }
        else{
          $('#edit-publish').attr("disabled", true);
          $('#edit-delete').attr("disabled", true);
          $('#edit-unpublish').attr("disabled", true);
        }
      });
}
};
 }(jQuery));
