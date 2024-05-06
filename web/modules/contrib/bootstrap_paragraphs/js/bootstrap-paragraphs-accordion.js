/**
 * @file
 * The JavaScript file for Bootstrap Paragraphs Accordion.
 */

(function ($) {
  $(document).ready(function ($) {
    var buttonSelector = ".bp-accordion-button";

    /*
     * Loop through every bootstrap paragraphs accordion container to check if
     * all the accordions are open then the "Expand/Collapse All" button text
     * needs to be changed.  This is a very unique case for example if there is
     * only 1 accordion and that was default one to be open then the
     * "Expand/Collapse All" needs to be changed.
     */
    $(".paragraph--bp-accordion-container").each(function () {
      if ($(".panel-collapse.in", this).length === $(".panel-collapse", this).length) {
        changePanelButtonToCollapse($(buttonSelector, this));
      }
    });

    /*
     * When the "Expand/Collapse All" button is click this function will be
     * called. If the button has the class "active" then open all the accordions
     *  else close all the accordions.
     */
    $(buttonSelector).click(function () {
      if (!$(this).hasClass("active")) {
        openAllPanels(this);
      }
      else {
        closeAllPanels(this);
      }
    });

    /*
     * When an accordion is opened this function will be called.
     */
    $(".paragraph--type--bp-accordion").on('shown.bs.collapse', function () {
      // Get the number of open accordions in the container.
      var numPanelOpen = $(this).find(".panel-collapse.in").length;

      // Remove aria-expanded attribute from div added by Bootstrap,
      // to avoid accessibility issues.
      $(this).find(".panel-collapse").each(function() {
        $(this).removeAttr("aria-expanded");
      });

      // Get the total number of accordions in the container.
      var totalNumberPanels = $(this).find(".panel-collapse").length;

      // If the number of open accordions equals the total number of accordions
      // then the "Expand/Collapse All" button needs to be changed.
      if (numPanelOpen === totalNumberPanels) {
        changePanelButtonToCollapse($(this).siblings(buttonSelector));
      }
    });

    /*
     * When an accordion is closed this function will be called.
     */
    $(".paragraph--type--bp-accordion").on('hidden.bs.collapse', function () {
      // Get the number of open accordions in a container.
      var numPanelOpen = $(this).find(".panel-collapse.in").length;

      // Remove aria-expanded attribute from div added by Bootstrap,
      // to avoid accessibility issues.
      $(this).find(".panel-collapse").each(function() {
        $(this).removeAttr("aria-expanded");
      });

      // If the number of open accordions equals 0 then the
      // "Expand/Collapse All" button needs to be changed.
      if (numPanelOpen === 0) {
        changePanelButtonToExpand($(this).siblings(buttonSelector));
      }
    });

    /*
     * Take in a container id and open all the panels within that container.
     */
    function openAllPanels(id) {
      $(id).siblings('.paragraph').find(".panel-collapse").collapse('show');
    }

    /*
     * Take in a container id and close all the panels within that container.
     */
    function closeAllPanels(id) {
      $(id).siblings('.paragraph').find(".panel-collapse").collapse('hide');
    }

    /*
     * Take in an id parameter.  First check that the variable sent has the
     * class 'active' this would mean it is open.  If the variable does then go
     * ahead and switch the title text and display text to say 'Collapse All'.
     */
    function changePanelButtonToCollapse(id) {
      if ($(id).hasClass("active")) {
        return;
      }
      $(id).attr("title", Drupal.t("Click to collapse all accordions in this section."));
      $(id).text(Drupal.t("Collapse All"));
      $(id).toggleClass("active");
    }

    /*
     * Take in an id parameter.  First check that the variable sent doesn't have
     *  class 'active' this would mean it is open.  If the variable does not
     * then go ahead and switch the title text and display text to say
     * 'Expand All'.
     */
    function changePanelButtonToExpand(id) {
      if (!$(id).hasClass("active")) {
        return;
      }
      $(id).attr("title", Drupal.t("Click to expand all accordions in this section."));
      $(id).text(Drupal.t("Expand All"));
      $(id).toggleClass("active");
    }

  });
})(jQuery);
