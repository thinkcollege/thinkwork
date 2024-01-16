(function ($, Drupal, drupalSettings, Sortable) {
  'use strict';
  Drupal.behaviors.tagifyAutocomplete = {
    attach: function attach(context, settings) {
      // See: https://github.com/yairEO/tagify#ajax-whitelist.
      let elements = $(once('tagify-widget', 'input.tagify-widget', context));

      elements.each(function () {
        let input = this,
          tagify = new Tagify(input, {
            dropdown: {
              enabled: parseInt(input.dataset.suggestionsDropdown),
              highlightFirst: true,
              fuzzySearch: !!parseInt(input.dataset.matchOperator),
              maxItems: input.dataset.maxItems ?? Infinity,
              closeOnSelect: true,
            },
            templates: {
              tag: tagTemplate,
              dropdownFooter(){
                return '';
              }
            },
            whitelist: [],
            placeholder: parseInt(input.dataset.placeholder),
          }),
          controller;

        /**
         * Generates HTML markup for a tag based on the provided tagData.
         * @param {Object} tagData - Data for the tag, including value, entity_id, class, etc.
         * @returns {string} - HTML markup for the generated tag.
         */
        function tagTemplate (tagData) {
          const entityIdDiv = parseInt(input.dataset.showEntityId) &&
          tagData.entity_id
            ? `<div id="tagify__tag-items" class="tagify__tag_with-entity-id"><div class='tagify__tag__entity-id-wrap'><span class='tagify__tag-entity-id'>${tagData.entity_id}</span></div><span class='tagify__tag-text'>${tagData.value}</span></div>`
            : `<div id="tagify__tag-items"><span class='tagify__tag-text'>${tagData.value}</span></div>`

          return `<tag title="${tagData.value}"
            contenteditable='false'
            spellcheck='false'
            tabIndex="-1"
            class="tagify__tag ${tagData.class ? tagData.class : ''}"
            ${this.getAttributes(tagData)}>
              <x id="tagify__tag-remove-button" class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
              ${entityIdDiv}
          </tag>`
        }

        // Avoid creating tag when 'Create referenced entities if they don't
        // already exist' is disallowed.
        tagify.settings.enforceWhitelist = !$(this).
          hasClass('tagify--autocreate')
        tagify.settings.skipInvalid = !!$(this).hasClass('tagify--autocreate')
        // Avoid creating tag when the cardinality is 0.
        tagify.settings.maxTags = $(this).hasClass('tagify--limited')
          ? 1
          : Infinity

        /**
         * Binds Sortable to Tagify's main element and specifies draggable items.
         */
        Sortable.create(tagify.DOM.scope, {
          // See: (https://github.com/SortableJS/Sortable#options)
          draggable: "." + tagify.settings.classNames.tag +':not(tagify__input)',
          forceFallback: true,
          onEnd: function() {
            // Must update Tagify's value according to the re-ordered nodes
            // in the DOM.
            tagify.updateValueByDOMTags();
          },
        });

        /**
         * Handles autocomplete functionality for the input field using Tagify.
         * @param {string} value - The current value of the input field.
         * @param {string[]} selectedEntities - An array of selected entities.
         */
        function handleAutocomplete(value, selectedEntities){
          tagify.whitelist = null;
          // https://developer.mozilla.org/en-US/docs/Web/API/AbortController/abort
          controller && controller.abort();
          controller = new AbortController();
          // Show loading animation meanwhile the dropdown suggestions are hided.
          value !== '' ? tagify.loading(true) : tagify.loading(false);
          fetch($(input).attr('data-autocomplete-url') + '?q=' + encodeURIComponent(value) + '&selected=' + selectedEntities, {signal: controller.signal})
          .then(res => res.json())
          .then(function (newWhitelist) {
            let newWhitelistData = [];
            newWhitelist.forEach(function (current) {
              newWhitelistData.push({
                value: current.label,
                entity_id: current.entity_id,
                ...current.attributes
              });
            });
            // Build the whitelist with the values coming from the fetch.
            if (newWhitelistData) {
              // Update whitelist Array in-place.
              tagify.whitelist = newWhitelistData;
              // Render the suggestion dropdown.
              tagify.loading(false).dropdown.show(value)
            }
          }).catch(error => {
            console.error('Error fetching data:', error);
          });
        }

        // onInput event.
        let onInput = Drupal.debounce(function (e) {
          let value = e.detail.value;
          handleAutocomplete(value, tagify.value.map(item => item.entity_id));
        }, 500);

        // onEditInput event.
        let onEditInput = Drupal.debounce(function (e) {
          let value = e.detail.data.newValue;
          handleAutocomplete(value, tagify.value.map(item => item.entity_id));
        }, 500);

        // Edit input event (When user is editing the tag).
        tagify.on('edit:input', onEditInput)
        // Input event (When user is creating the tag).
        tagify.on('input', onInput)

        // If 'On click' dropdown suggestions is enabled.
        if (!tagify.settings.dropdown.enabled) {
          document.addEventListener('click', handleClickEvent);
        }

        /**
         * Handles click events on Tagify's input, triggering autocomplete if
         * conditions are met.
         * @param {Event} e - The click event object.
         */
        function handleClickEvent(e) {
          const identifier = input.attributes.name.value;
          const containerClass = '.' + identifier;
          const isTagifyInput = e.target.classList.contains('tagify__input');
          const isDesiredContainer = e.target.closest(containerClass);
          if (isTagifyInput && isDesiredContainer) {
            handleAutocomplete('', tagify.value.map(item => item.entity_id));
          }
        }

      });
    }
  };

  /**
   * Behaviors for tabs in the node edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the node edit form.
   */
  Drupal.behaviors.nodeDetailsSummaries = {
    attach: function attach(context) {
      const $context = $(context);

      $context.find('.node-form-author').drupalSetSummary(function (context) {
        var $authorContext = $(context);
        var name = $authorContext.find('.field--name-uid input').val().split('[{"value":"').pop().split('",')[0];
        var date = $authorContext.find('.field--name-created input').val();

        if (name && date) {
          return Drupal.t('By @name on @date', {
            '@name': name,
            '@date': date,
          });
        }
        if (name) {
          return Drupal.t('By @name', { '@name': name });
        }
        if (date) {
          return Drupal.t('Authored on @date', { '@date': date });
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings, Sortable);
