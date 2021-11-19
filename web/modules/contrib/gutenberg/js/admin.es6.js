/**
 * @file
 * Javascript behaviors for the Gutenberg module admin.
 */

/* eslint func-names: ["error", "never"] */
(function($, Drupal) {
  /**
   * Adds summaries to the book outline form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to book outline forms.
   */
  Drupal.behaviors.gutenbergAdmin = {
    attach(context) {
      if (context !== document) {
        return;
      }

      $('.view-reusable-blocks .views-row').click(e => {
        $(e.currentTarget)
          .find('input[type="checkbox"]')
          .click();
      });

      $('.view-reusable-blocks .views-row input[type="checkbox"]').click(e => {
        e.stopPropagation();
      });

      $('input[name*="allowed_blocks_"]:not([value*="/all"])').click(ev => {
        const category = $(ev.currentTarget)
          .val()
          .split('/')[0];
        const checked = $(ev.currentTarget).is(':checked');

        if (checked) {
          return;
        }

        $(`input[name="allowed_blocks_${category}[${category}/all]"]`).prop(
          'checked',
          checked,
        );
      });

      $('input[name*="allowed_blocks_core"][value*="/all"]').click(ev => {
        const category = $(ev.currentTarget)
          .val()
          .split('/')[0];
        const checked = $(ev.currentTarget).is(':checked');

        $(`input[name*="allowed_blocks_${category}[${category}"]`).prop(
          'checked',
          checked,
        );
      });
    },
  };
})(jQuery, Drupal);
