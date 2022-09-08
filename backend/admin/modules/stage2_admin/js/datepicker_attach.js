(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {
		var dateFormat='yy-mm-dd';
		$( "input.date_var").once().datepicker({ dateFormat: dateFormat }); // Manualy add datepicker.
		$( "input[name^='date_var_']").once().datepicker({ dateFormat: dateFormat });
		
		$( "input.time_var").once().timepicker(); // Manualy add timepicker.
		$( "input[name^='time_var_']").once().timepicker();
	}
};
}(jQuery));