(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

      $("#tableselect_id").once().sieve();

	  // remove first column header
	 $("#tableselect_id th.select-all").remove();
	 // remove first column of other rows
	$("#tableselect_id .form-checkbox").parent().parent().remove();

	$html_table = $("#tableselect_id");


	$html_table.css('max-width', $html_table.width() + "px");
	$('#translation_table td').each(function() {
			$(this).css('max-width', (100) + "px");     // css attribute of your <td> width:15px; i.e.
			$(this).css('max-heigh', (100) + "px");     // css attribute of your <td> width:15px; i.e.
			$(this).css('overflow', "hidden");     // css attribute of your <td> width:15px; i.e.
	});

}
};
 }(jQuery));
