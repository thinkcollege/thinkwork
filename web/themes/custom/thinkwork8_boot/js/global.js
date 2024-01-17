/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.thinkwork8_boot = {
    attach: function (context, settings) {
      $(function() {
        $(document).tooltip({ selector: '[data-toggle="tooltip"]' });
        $(document).popover({ selector: '[data-toggle="popover"]' });
      });
      $('button.navbar-toggler').click(function() {
        if (!$('#superfish-main-accordion').hasClass('sf-expanded')) {
          $('#superfish-main-toggle').click().hide();
        }
      });
      $('a.sf-depth-1',context).on('mouseover', function(event) {
      return false;
      });
      $('a.menuparent.sf-depth-1').on('click', function(event) {
      // Close any open dropdown menus.
      $.each($(this), function(index, element) {
      $(element).parent().parent().find('li.menuparent > ul').addClass('sf-hidden');
      });
      // Open the clicked dropdown menu.
      $(this).next().removeClass('sf-hidden');
      event.preventDefault();
      });

        $('a.sf-depth-2',context).on('mouseover', function(event) {
        return false;
        });
        $('a.menuparent.sf-depth-2').on('click', function(event) {
        // Close any open dropdown menus.
        $.each($(this), function(index, element) {
        $(element).parent().parent().find('li.menuparent > ul').addClass('sf-hidden');
        });
        // Open the clicked dropdown menu.
        $(this).next().removeClass('sf-hidden');
        event.preventDefault();
        });

    }
  };
})(jQuery, Drupal);
