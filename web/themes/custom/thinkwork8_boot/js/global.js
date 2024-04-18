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
        once('chartBuilder',"#chartBuilder").forEach(function(value,i) {
          var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
          var eventer = window[eventMethod];
          var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

          eventer(messageEvent,function(e) {

            // If the message is a resize frame request
            if (e.data.indexOf('reheight::') != -1) {
              var height = e.data.replace('reheight::', '');

              document.getElementById('chartBuilder').style.height = height + 'px';
            }
            else if (e.data.indexOf('rewide::') != -1) {
              var width = e.data.replace('rewide::', '');
              document.getElementById('chartBuilder').style.width = width + 'px';
            }
          } ,false);
          $('input#bbState').on('click', function() {
            var stateSelect = $('#selectState option').filter(':selected').val();
            downloadState(stateSelect);

          });
      });
        function downloadState(d) {
          window.location =  '/sites/default/files/bbstates/' + d + '.pdf';
        }

    }
  };
})(jQuery, Drupal);
