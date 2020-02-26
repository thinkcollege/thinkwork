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
