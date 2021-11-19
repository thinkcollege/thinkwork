/**
 * @file
 * Javascript behaviors for the Gutenberg Cloud module admin.
 */

(function ($, Drupal) {

  /**
   * Filters gutenberg blocks.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches admin behaviour to the Gutenberg Cloud Blocks filters.
   */
  Drupal.behaviors.gutenbergCloudFilters = {
    attach(context) {
      var $filters = $('.gutenberg-cloud__filters', context);
      var $filterItems = $filters.find('.filter');
      var $blocks = $('.gutenberg-cloud__blocks', context);
      var $search = $filters.find('.search');
      var itemsSelector = '.gutenberg-cloud__item';


      $filterItems.once().click(function () {
        $(this).trigger('filterActive');

        var type = $(this).data('filter');
        var $items = $(itemsSelector, context);

        if (type !== 'popular') {
          $items.sort(function (a, b) {
            return parseInt($(a).data('weight')) > parseInt($(b).data('weight'));
          }).appendTo($blocks);
        }

        $search.val('');

        switch (type) {
          case 'enabled':
            $items.each(function () {
              $(this).hasClass('enabled') ? $(this).show() : $(this).hide();
            });
            break;

          case 'popular':
            $items.sort(function (a, b) {
              return parseInt($(a).data('popularity')) < parseInt($(b).data('popularity'));
            }).appendTo($blocks);
            $items.show();
            break;

          default:
            $items.show();
            break;
        }
      });

      $filterItems.on('filterActive', function () {
        $(this)
          .siblings()
          .trigger('filterInactive')
          .end()
          .addClass('active');
      }).on('filterInactive', function () {
        $(this).removeClass('active');
      });

      $search.once().on('keyup change onpaste', Drupal.debounce(function () {
        $filterItems.trigger('filterInactive');

        var $items = $(itemsSelector, context);
        var phrase = $(this).val();

        $items.each(function () {
          var title = $(this).find('.title').text();
          var regexp = new RegExp(phrase, 'ig');

          title.search(regexp) === -1 ? $(this).hide() : $(this).show();
        });

      }, 200)).on('focusout', function () {
        if ($(this).val().length === 0) {
          $filters.find('[data-filter="all"]').trigger('filterActive');
        }
      });

      if ($search.val()) {
        $search.trigger('keyup');
      }
      else {
        $filters.find('[data-filter="all"]').trigger('click');
      }

    },
  };

})(jQuery, Drupal);
