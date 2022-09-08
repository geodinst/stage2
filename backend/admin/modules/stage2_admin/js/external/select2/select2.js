(function ($) {
    Drupal.behaviors.select2 = {
        attach: function (context, settings) {
            $('.stage2_admin-select2').once().each(function () {
                $this = $(this);
                id = $this.attr('id');
                options = {};
                if (drupalSettings.d8_form_elements && drupalSettings.d8_form_elements[id]) {
                    options = drupalSettings.d8_form_elements[id];
                }

                // modal window fix
                if ($this.closest('#drupal-modal').length > 0) {
                    options.select2.dropdownParent = $('#drupal-modal');
                    $('#drupal-modal').css('overflow', 'visible');
                }
                $this.select2(options.select2);
            });
        }
    };
})(jQuery);
