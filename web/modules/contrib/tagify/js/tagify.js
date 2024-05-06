// eslint-disable-next-line func-names
(function ($, Drupal, drupalSettings, Sortable) {
  Drupal.behaviors.tagifyAutocomplete = {
    attach: function attach(context) {
      // See: https://github.com/yairEO/tagify#ajax-whitelist.
      const elements = $(once('tagify-widget', 'input.tagify-widget', context));

      // eslint-disable-next-line func-names
      elements.each(function () {
        const input = this;
        const { identifier } = input.dataset;
        const cardinality = parseInt(input.attributes.cardinality.value, 10);

        /**
         * Counts the number of selected tags.
         * @return {int} - The number of selected tags.
         */
        function countSelectedTags() {
          const tagsElement = document.querySelector(`.${identifier}`);
          const tagElements = tagsElement.querySelectorAll('.tagify__tag');
          return tagElements.length;
        }

        /**
         * Checks if the tag limit has been reached.
         * @return {boolean} - True if the tag limit has been reached, otherwise false.
         */
        function isTagLimitReached() {
          return cardinality > 0 && countSelectedTags() >= cardinality;
        }

        /**
         * Creates loading text markup.
         */
        function createLoadingTextMarkup() {
          const tagsElement = document.querySelector(`.${identifier}`);
          const loadingText = document.createElement('div');
          loadingText.className = 'tagify--loading-text hidden';
          loadingText.textContent = 'Loading...';
          tagsElement.appendChild(loadingText);
        }

        /**
         * Removes loading text markup.
         */
        function removeLoadingTextMarkup() {
          const tagsElement = document.querySelector(`.${identifier}`);
          if (tagsElement) {
            const loadingText = tagsElement.querySelector(
              '.tagify--loading-text',
            );
            if (loadingText) {
              loadingText.remove();
            }
          }
        }

        /**
         * Checks if the info label is a valid image source.
         * @param {string} infoLabel - The info label input.
         * @return {boolean} True if the info label is a valid image source.
         */
        function validImgSrc(infoLabel) {
          const pattern = new RegExp(
            '^(https?:\\/\\/)?' +
              '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' +
              '((\\d{1,3}\\.){3}\\d{1,3}))' +
              '(\\:\\d+)?' +
              '(\\/[-a-z\\d%_.~+]*)*' +
              '(\\.(?:jpg|jpeg|png|gif|bmp|svg|webp))' +
              '(\\?[;&a-z\\d%_.~+=-]*)?' +
              '(\\#[-a-z\\d_]*)?$',
            'i',
          );
          return !!pattern.test(infoLabel);
        }

        /**
         * Highlights matching letters in a given input string by wrapping them in <strong> tags.
         * @param {string} inputTerm - The input string for matching letters.
         * @param {string} searchTerm - The term to search for within the input string.
         * @return {string} The input string with matching letters wrapped in <strong> tags.
         */
        function highlightMatchingLetters(inputTerm, searchTerm) {
          // Escape special characters in the search term.
          const escapedSearchTerm = searchTerm.replace(
            /[.*+?^${}()|[\]\\]/g,
            '\\$&',
          );
          // Create a regular expression to match the search term globally and case insensitively.
          const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
          // Check if there are any matches.
          if (!escapedSearchTerm) {
            // If no matches found, return the original input string.
            return inputTerm;
          }
          // Replace matching letters with the same letters wrapped in <strong> tags.
          return inputTerm.replace(regex, '<strong>$1</strong>');
        }

        /**
         * Generates HTML markup for an entity id.
         * @param {string} entityId - The entity id.
         * @return {string} The entity id markup HTML.
         */
        function entityIdMarkup(entityId) {
          return parseInt(input.dataset.showEntityId, 10) && entityId
            ? `<div id="tagify__tag-items" class="tagify__tag_with-entity-id"><div class='tagify__tag__entity-id-wrap'><span class='tagify__tag-entity-id'>${entityId}</span></div></div>`
            : '';
        }

        /**
         * Generates HTML markup for an info label.
         * @param {string} infoLabel - The info label information.
         * @return {string} The info label markup HTML.
         */
        function infoLabelMarkup(infoLabel) {
          // Info label markup (Image or plain text).
          const markup = validImgSrc(infoLabel)
            ? `<div id='tagify__tag__info-label-wrap' class='tagify__tag__info-label-wrap'><div class='tagify__tag-info-label-image'><img onerror="this.style.visibility='hidden'" src="${infoLabel}"></div></div>`
            : `<div id='tagify__tag__info-label-wrap' class='tagify__tag__info-label-wrap'><span class='tagify__tag-info-label'>${infoLabel}</span></div>`;
          return infoLabel ? markup : '';
        }

        /**
         * Generates HTML markup for a tag.
         * @param {string} tagLabel - The label.
         * @param {string} tagInfoLabel - The info label information.
         * @param {string} tagEntityId - The entity id.
         * @return {string} The tag markup HTML.
         */
        function tagMarkup(tagLabel, tagInfoLabel, tagEntityId) {
          return `<div id="tagify__tag-items">${tagEntityId}
            <span class="${
              tagEntityId
                ? 'tagify__tag-text-with-entity-id'
                : 'tagify__tag-text'
            }">${tagLabel}</span>${tagInfoLabel}</div>`;
        }

        /**
         * Generates HTML markup for a tag based on the provided tagData.
         * @param {Object} tagData - Data for the tag, including value, entity_id, class, etc.
         * @return {string} - The HTML markup for the generated tag.
         */
        function tagTemplate(tagData) {
          // Avoid 'undefined' values on paste event.
          const label = tagData.label ?? tagData.value;

          return `<tag title="${tagData.label}"
            contenteditable='false'
            spellcheck='false'
            tabIndex="-1"
            class="tagify__tag ${tagData.class ? tagData.class : ''}"
            ${this.getAttributes(tagData)}>
              <x id="tagify__tag-remove-button"
                title='Remove ${tagData.label}'
                class='tagify__tag__removeBtn'
                role='button'
                aria-label='remove ${tagData.label} tag'
                tabindex="0">      
              </x>
              ${tagMarkup(
                label,
                infoLabelMarkup(tagData.info_label),
                entityIdMarkup(tagData.entity_id),
              )}
          </tag>`;
        }

        /**
         * Generates the HTML template for a suggestion item in the Tagify dropdown based on the provided tagData.
         * @param {Object} tagData - The data representing the suggestion item.
         * @return {string} - The HTML template for the suggestion item.
         */
        function suggestionItemTemplate(tagData) {
          // Returns suggestion item when the field cardinality is unlimited or
          // field cardinality is bigger than the number of selected tags.
          return !isTagLimitReached()
            ? `<div ${this.getAttributes(
                tagData,
              )} class='tagify__dropdown__item ${
                tagData.class ? tagData.class : ''
              }' tabindex="0" role="option"><div class="tagify__dropdown__item-highlighted">
            ${highlightMatchingLetters(tagData.label, this.state.inputText)}
          </div>${infoLabelMarkup(tagData.info_label)}</div>`
            : '';
        }

        /**
         * Generates the HTML template for a suggestion footer in the Tagify dropdown based on the provided tagData.
         * @return {string} - The HTML template for the suggestion footer.
         */
        function suggestionFooterTemplate() {
          // Returns empty dropdown footer when field cardinality is unlimited or
          // field cardinality is bigger than the number of selected tags.
          return isTagLimitReached()
            ? `<footer
          data-selector='tagify-suggestions-footer'
          class="${this.settings.classNames.dropdownFooter}">
            <p>You can only add <strong>${cardinality} item(s)</strong></p>
         </footer>`
            : '';
        }

        // eslint-disable-next-line no-undef
        const tagify = new Tagify(input, {
          dropdown: {
            enabled: parseInt(input.dataset.suggestionsDropdown, 10),
            highlightFirst: true,
            fuzzySearch: !!parseInt(input.dataset.matchOperator, 10),
            maxItems: input.dataset.maxItems ?? Infinity,
            closeOnSelect: true,
            searchKeys: ['label'],
            mapValueTo: 'label',
          },
          templates: {
            tag: tagTemplate,
            dropdownItem: suggestionItemTemplate,
            dropdownFooter: suggestionFooterTemplate,
            dropdownItemNoMatch: (data) =>
              !isTagLimitReached()
                ? `<div class='${tagify.settings.classNames.dropdownItem} tagify--dropdown-item-no-match'
              value="noMatch"
              tabindex="0"
              role="option">
                <p>No matching suggestions found for: </p><strong class="tagify--value">${data.value}</strong>
              </div>`
                : '',
          },
          whitelist: [],
          placeholder: parseInt(input.dataset.placeholder, 10),
          tagTextProp: 'label',
          editTags: false,
          maxTags: cardinality > 0 ? cardinality : Infinity,
        });

        let controller;

        // Avoid creating tag when 'Create referenced entities if they don't
        // already exist' is disallowed and when tag limit is reached.
        tagify.settings.enforceWhitelist =
          isTagLimitReached() && cardinality > 1
            ? false
            : !$(this).hasClass('tagify--autocreate');
        tagify.settings.skipInvalid = isTagLimitReached()
          ? false
          : $(this).hasClass('tagify--autocreate');

        /**
         * Binds Sortable to Tagify's main element and specifies draggable items.
         */
        Sortable.create(tagify.DOM.scope, {
          // See: (https://github.com/SortableJS/Sortable#options)
          draggable: `.${tagify.settings.classNames.tag}:not(tagify__input)`,
          forceFallback: true,
          onEnd() {
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
        function handleAutocomplete(value, selectedEntities) {
          tagify.whitelist = null;
          // https://developer.mozilla.org/en-US/docs/Web/API/AbortController/abort
          // eslint-disable-next-line no-unused-expressions
          controller && controller.abort();
          controller = new AbortController();
          // Create Loading text markup.
          createLoadingTextMarkup();
          // Show loading animation meanwhile the dropdown suggestions are hided.
          // eslint-disable-next-line no-unused-expressions
          value !== '' ? tagify.loading(true) : tagify.loading(false);
          fetch(
            `${$(input).attr('data-autocomplete-url')}?q=${encodeURIComponent(
              value,
            )}&selected=${selectedEntities}`,
            { signal: controller.signal },
          )
            .then((res) => res.json())
            // eslint-disable-next-line func-names
            .then(function (newWhitelist) {
              const newWhitelistData = [];
              // eslint-disable-next-line func-names
              newWhitelist.forEach(function (current) {
                newWhitelistData.push({
                  value: current.entity_id,
                  entity_id: current.entity_id,
                  info_label: current.info_label,
                  label: current.label,
                  editable: current.editable,
                  ...current.attributes,
                });
              });
              // Build the whitelist with the values coming from the fetch.
              if (newWhitelistData) {
                // Update whitelist Array in-place.
                tagify.whitelist = newWhitelistData;
                // Render the suggestion dropdown.
                tagify.loading(false).dropdown.show(value);
                // Remove Loading text markup.
                removeLoadingTextMarkup();
              }
              // Show dropdown suggestion if the input is or not matching.
              if (isTagLimitReached()) {
                tagify.dropdown.show();
                tagify.DOM.scope.parentNode.appendChild(tagify.DOM.dropdown);
              }
            })
            .catch((error) => {
              // eslint-disable-next-line no-console
              console.error('Error fetching data:', error);
            });
        }

        // Tagify input event.
        // eslint-disable-next-line func-names
        const onInput = Drupal.debounce(function (e) {
          const { value } = e.detail;
          handleAutocomplete(
            value,
            tagify.value.map((item) => item.entity_id),
          );
        });

        // Tagify change event.
        // eslint-disable-next-line func-names
        const onChange = Drupal.debounce(function () {
          if (isTagLimitReached() && cardinality > 1) {
            tagify.settings.enforceWhitelist = false;
            tagify.settings.skipInvalid = false;
          }
        });

        // Input event (when a tag is being typed/edited. e.detail exposes
        // value, inputElm & isValid).
        tagify.on('input', onInput);
        // Change event (any change to the value has occurred. e.detail.value
        // callback listener argument is a String).
        tagify.on('change', onChange);

        // If 'On click' dropdown suggestions is enabled (Simulated 'Select').
        if (!tagify.settings.dropdown.enabled) {
          const tagsElement = document.querySelector(`.${identifier}`);
          tagsElement.classList.add('tagify-select');
        }

        /**
         * Handles click events on Tagify's input, triggering autocomplete if
         * conditions are met.
         * @param {Event} e - The click event object.
         */
        function handleClickEvent(e) {
          const containerClass = `.${identifier}`;
          const isTagifyInput = e.target.classList.contains('tagify__input');
          const isDesiredContainer = e.target.closest(containerClass);
          if (isTagifyInput && isDesiredContainer) {
            handleAutocomplete(
              '',
              tagify.value.map((item) => item.entity_id),
            );
          }
        }
        // If 'On click' dropdown suggestions is enabled.
        if (!tagify.settings.dropdown.enabled) {
          document.addEventListener('click', handleClickEvent);
        }
      });
    },
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

      // eslint-disable-next-line no-shadow,func-names
      $context.find('.node-form-author').drupalSetSummary(function (context) {
        const $authorContext = $(context);
        const name = $authorContext
          .find('.field--name-uid input')
          .val()
          .split('[{"value":"')
          .pop()
          .split('",')[0];
        const date = $authorContext.find('.field--name-created input').val();

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
    },
  };
})(jQuery, Drupal, drupalSettings, Sortable);
