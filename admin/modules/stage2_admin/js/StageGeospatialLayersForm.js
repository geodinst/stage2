(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

      $("#tableselect_id").once().sieve();
			$("#tableselect_id thead tr th:first").once().prepend('<input type="checkbox" id = "custom_select_all"name="selectAll" />');
			$('#custom_select_all').once().click(function (e) {
			    $(this).closest('table').find('td input:checkbox:visible').prop('checked', this.checked);
			});
}
};
 }(jQuery));
