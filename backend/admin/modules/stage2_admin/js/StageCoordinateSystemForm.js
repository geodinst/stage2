(function ($) {
  Drupal.behaviors.stage2_admin = {
    attach: function (context, settings) {

      var t = $("#tableselect_id");
      t.once().sieve();

      $("#tableselect_id tr:nth-of-type(1)").find('input:checkbox').attr("disabled", true);

}
};
 }(jQuery));
