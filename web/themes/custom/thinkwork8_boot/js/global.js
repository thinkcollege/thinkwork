/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.thinkwork8_boot = {
    attach: function (context, settings) {

    }
  };
})(jQuery, Drupal);

(function($) {
    $(function() {
      $(document).tooltip({ selector: '[data-toggle="tooltip"]' });
      $(document).popover({ selector: '[data-toggle="popover"]' });
    });
  })(jQuery);

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.thinkwork8_boot = {
    attach: function (context, settings) {
      $('button.navbar-toggler').click(function() {
        if (!$('#superfish-main-accordion').hasClass('sf-expanded')) {
          $('#superfish-main-toggle').click().hide();
        }
      });
    }
  };
})(jQuery, Drupal);
