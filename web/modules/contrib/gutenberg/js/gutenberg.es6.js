/**
 * @file
 * Gutenberg implementation of {@link Drupal.editors} API.
 */

/* eslint func-names: ["error", "never"] */
(function(Drupal, DrupalGutenberg, drupalSettings, wp, $) {
  /**
   * Check if Drupal's media module is enabled.
   *
   * @return {boolean} The media module is enabled.
   */
  Drupal.isMediaEnabled = () =>
    (drupalSettings.gutenberg || false) &&
    drupalSettings.gutenberg['media-enabled'];

  /**
   * Check if Drupal's media_library module is enabled.
   *
   * @return {boolean} The media library module is enabled.
   */
  Drupal.isMediaLibraryEnabled = () =>
    (drupalSettings.gutenberg || false) &&
    drupalSettings.gutenberg['media-library-enabled'];

  /**
   * Toggles Gutenberg loader.
   *
   * @param {string} state The loader state.
   */
  Drupal.toggleGutenbergLoader = state => {
    const $gutenbergLoader = $('#gutenberg-loading');
    if (state === 'show') {
      $gutenbergLoader.removeClass('hide');
    } else if (state === 'hide') {
      $gutenbergLoader.addClass('hide');
    }
  };

  /**
   * Display error message.
   *
   * @param {string} message   Notice message.
   * @param {boolean} rawHTML Render as HTML.
   *
   * @return {Object} Action object.
   */
  Drupal.notifyError = (message, rawHTML = false) =>
    wp.data.dispatch('core/notices').createErrorNotice(message, {
      isDismissible: true,
      __unstableHTML: rawHTML,
    });

  /**
   * Display success message.
   *
   * @param {string} message   Notice message.
   * @param {boolean} rawHTML Render as HTML.
   *
   * @return {Object} Action object.
   */
  Drupal.notifySuccess = (message, rawHTML = false) =>
    wp.data.dispatch('core/notices').createSuccessNotice(message, {
      isDismissible: true,
      __unstableHTML: rawHTML,
    });

  /**
   * Add new command for reloading a block.
   */
  Drupal.AjaxCommands.prototype.reloadBlock = function() {
    // Place content in current-msg div.
    // $('#current-msg h2').html(response.subject);
    // $('#current-msg p').html(response.content);
    // // Remove from unread list.
    // $('#msg-' + response.mid).remove();
    // // Add message to read list.
    // $('#read-msgs').append('<li>' + response.subject + '</li>');
    // console.log('reloadBlock', ajax, response, status);
    const { select, dispatch } = wp.data;
    const selectedBlock = select('core/block-editor').getSelectedBlock();
    const { clientId } = selectedBlock;
    const { mediaEntityIds } = selectedBlock.attributes;

    (async () => {
      await dispatch('core/block-editor').updateBlock(clientId, {
        attributes: { mediaEntityIds: [] },
      });

      setTimeout(() => {
        dispatch('core/block-editor').updateBlock(clientId, {
          attributes: { mediaEntityIds },
        });
      }, 100);
      // await dispatch('core/block-editor').updateBlock(clientId, {
      //   attributes: { mediaEntityIds },
      // });
      // console.log('yo 2!', clientId, mediaEntityIds);
    })();
  };

  /**
   * @namespace
   */
  Drupal.editors.gutenberg = {
    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {string} format
     *   The text format for the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    async attach(element, format) {
      // A bit of a hack. This avoids Gutenberg to be reinit'd on AJAX calls.
      // TODO: could be done in another way?
      if (drupalSettings.gutenbergLoaded) {
        return false;
      }
      drupalSettings.gutenbergLoaded = true;

      const { contentType, allowedBlocks, blackList } = format.editorSettings;
      const { data, blocks, hooks } = wp;
      const { dispatch } = data;
      const { addFilter } = hooks;
      const { unregisterBlockType } = blocks;
      const {
        registerDrupalStore,
        registerDrupalBlocks,
        registerDrupalMedia,
      } = DrupalGutenberg;

      // Register plugins.
      // Not needed now. Leaving it here for reference.
      // const { AdditionalFieldsPluginSidebar } = DrupalGutenberg.Plugins;
      // plugins.registerPlugin('drupal', {
      //   icon: 'forms',
      //   render: AdditionalFieldsPluginSidebar,
      // });

      // Add 'mapping field' and 'mapping attribute' attributes to all blocks.
      // @deprecated in gutenberg:8.x-1.11
      await addFilter(
        'blocks.registerBlockType',
        'drupalgutenberg/custom-attributes',
        (settings, name) => {
          settings.attributes = Object.assign(settings.attributes, {
            mappingField: {
              type: 'string',
              default: '',
            },
            mappingAttribute: {
              type: 'string',
              default: '',
            },
          });

          if (name === 'core/block') {
            settings.attributes.ref = {
              type: 'number',
            };
          }

          return settings;
        },
      );

      await registerDrupalStore(data);

      await registerDrupalBlocks(contentType);
      await registerDrupalMedia();

      await this._initGutenberg(element);

      if (drupalSettings.gutenberg._listeners.init) {
        drupalSettings.gutenberg._listeners.init.forEach(callback => {
          callback();
        });
      }

      if (drupalSettings.gutenberg.messages) {
        Object.keys(drupalSettings.gutenberg.messages).forEach(key => {
          drupalSettings.gutenberg.messages[key].forEach(message => {
            switch (key) {
              case 'error':
                dispatch('core/notices').createErrorNotice(message);
                break;
              case 'warning':
                dispatch('core/notices').createWarningNotice(message);
                break;
              case 'success':
                dispatch('core/notices').createSuccessNotice(message);
                break;
              default:
                dispatch('core/notices').createWarningNotice(message);
                break;
            }
          });
        });
      }

      // Handle late messages, i.e. processed after node edit form hook.
      // Example: System update messages are coming after node edit form.
      // TODO: There must be a better way to do this on server side.
      $('div.messages--error').each((index, el) => {
        dispatch('core/notices').createErrorNotice($(el).html(), {
          __unstableHTML: $(el).html(),
        });
        $(el).remove();
      });

      $('div.messages--warning').each((index, el) => {
        dispatch('core/notices').createWarningNotice($(el).html(), {
          __unstableHTML: $(el).html(),
        });
        $(el).remove();
      });

      $('div.messages--success').each((index, el) => {
        dispatch('core/notices').createSuccessNotice($(el).html(), {
          __unstableHTML: $(el).html(),
        });
        $(el).remove();
      });

      // Process blacklist.
      blackList
        .filter(value => !value.includes('drupalblock/'))
        .forEach(value => {
          unregisterBlockType(value);
        });

      // Process allowed blocks.
      /* eslint no-restricted-syntax: ["error", "never"] */
      for (const key in allowedBlocks) {
        if (allowedBlocks.hasOwnProperty(key)) {
          const value = allowedBlocks[key];
          if (!value && !key.includes('/all') && !blackList.includes(key)) {
            unregisterBlockType(key);
          }
        }
      }

      // Remove unused blocks.
      const categories = data
        .select('core/blocks')
        .getCategories()
        .filter(item => {
          if (item.slug === 'widgets') {
            return false;
          }
          return true;
        });

      data.dispatch('core/blocks').setCategories(categories);

      // On page load always select sidebar's document tab.
      data.dispatch('core/edit-post').openGeneralSidebar('edit-post/document');

      data.dispatch('core/edit-post').setAvailableMetaBoxesPerLocation({
        advanced: ['drupalSettings'],
      });

      // Disable status panel from sidebar
      data.dispatch('core/edit-post').removeEditorPanel('post-status');

      // Disable Welcome Guide
      const isWelcomeGuide = data
        .select('core/edit-post')
        .isFeatureActive('welcomeGuide');

      if (isWelcomeGuide) {
        data.dispatch('core/edit-post').toggleFeature('welcomeGuide');
      }

      setTimeout(() => {
        const $metaBoxContainer = $('.edit-post-meta-boxes-area__container');
        drupalSettings.gutenberg.metaboxes.forEach(id => {
          const $metabox = $(`#${id}`);
          const metabox = $metabox.get(0);

          // Re-initialize the original editors used within the metabox elements
          // which can break after they've been moved.
          Drupal.behaviors.editor.detach(metabox, drupalSettings);
          $metabox.appendTo($metaBoxContainer);
          Drupal.behaviors.editor.attach(metabox, drupalSettings);
        });
      }, 0);

      // Create fake form for metabox.
      // On post save, REQUEST_META_BOX_UPDATES action is called and
      // it relies on metaboxes forms.
      // The only way to bypass an exception is to create the "advanced" metabox form.
      // It has no other practical use.
      const metaboxesContainer = $(document.createElement('div'));
      metaboxesContainer.attr('id', 'metaboxes');
      $('body').append(metaboxesContainer);
      const metaboxForm = $(document.createElement('form'));
      metaboxForm.addClass('metabox-location-advanced');
      metaboxesContainer.append(metaboxForm);

      // Disable form validation
      // We need some ninja hacks because every button in Gutenberg will
      // cause the form to submit.
      $(document.forms[0]).attr('novalidate', true);

      setTimeout(() => {
        $('.edit-post-header__settings').append(
          $('.gutenberg-header-settings'),
        );
        $('.gutenberg-full-editor').addClass('ready');
        Drupal.toggleGutenbergLoader('hide');
      }, 0);

      let isFormValid = false;


      $('#edit-submit, #edit-preview').on('mousedown', e => {
        const { openGeneralSidebar } = data.dispatch('core/edit-post');

        // is checkValidity supported? If not, validate the form. 
        if (typeof element.form.checkValidity === 'function') {
          isFormValid = element.form.checkValidity();
        } else {
          isFormValid = true;
        }

        if (!isFormValid) {
          let isMetaboxValid = true;

          // Expand "More Settings" set on error.
          $('#edit-metabox-fields :input').each(function (index, el) {
            if (!el.checkValidity()) {
              $('#edit-metabox-fields').attr('open', '');
              isMetaboxValid = false;
              return false;
            }
          });

          if (isMetaboxValid) {
            // Calling openGeneralSidebar() is not working properly when sidebar
            // is hidden. To overcome this we need to call it twice.
            openGeneralSidebar('edit-post/document');
            openGeneralSidebar('edit-post/document');
          }

          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      });

      $('#edit-submit, #edit-preview').on('click', e => {
        $(e.currentTarget).attr('active', true);

        // Expand "More Settings" set.
        $('#edit-additional-fields').attr('open', '');

        // For these buttons enable form validation.
        $(element.form).removeAttr('novalidate');

        // Wait for the next tick, React/Gutenberg is
        // doing its DOM stuff.
        setTimeout(() => {
          // This will not work on IE (<10?). But it's ok because
          // we have the server side validation fallback.
          isFormValid = element.form.reportValidity();

          if (isFormValid) {
            // We need to submit the form via button click.
            // Drupal's form submit handler needs it.
            // TODO: Could we submit and passing the button reference to formState?
            $(e.currentTarget).click();
          } else {
            // Not active anymore.
            $(e.currentTarget).removeAttr('active');
          }

          // Then disable form validation again :(
          $(element.form).attr('novalidate', true);
        });

        // No need to proceed to form validation,
        // it'll just throw a "not focusable" console
        // error.
        if (!isFormValid) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      });

      let formSubmitted = false;
      // Gutenberg is full of buttons which cause the form
      // to submit (no default prevent).
      $(element.form).on('submit', e => {
        // Get the original button clicked...
        const $source = $('input[active="true"]');
        // ...and reset its active state.
        $source.removeAttr('active');

        // If none of those buttons were clicked...
        if (
          $source.attr('id') !== 'edit-submit' &&
          $source.attr('id') !== 'edit-preview' &&
          $source.attr('id') !== 'edit-delete'
        ) {
          // Just stop everything.
          e.preventDefault();
          e.stopPropagation();
          return false;
        }

        // Update editor textarea with gutenberg content.
        $(element).val(data.select('core/editor').getEditedPostContent());

        // We need to update the 'editor-value-is-changed' flag otherwise
        // the content won't be updated.
        $(element).data({ 'editor-value-is-changed': true });
        $(element).attr('data-editor-value-is-changed', true);

        // Clear content "dirty" state.
        if (!formSubmitted) {
          (async () => {
            await data.dispatch('core/editor').savePost();

            formSubmitted = true;
            // Submit again to save content on Drupal.
            // We need to submit the form via button click.
            // Drupal's form submit handler needs it.
            // TODO: Could we submit and passing the button reference to formState?
            $source.click();
          })();

          // savePost() is async so we must cancel form submission
          // to avoid to "changes not saved" alert.
          e.preventDefault();
          e.stopPropagation();
        }
      });

      return true;
    },

    /**
     * Attaches an inline editor to a DOM element.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {object} format
     *   The text format used in the editor.
     * @param {string} [mainToolbarId]
     *   The id attribute for the main editor toolbar, if any.
     * @param {string} [floatedToolbarId]
     *   The id attribute for the floated editor toolbar, if any.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    attachInlineEditor(element, format, mainToolbarId, floatedToolbarId) {
      // We define this function so that quickedit doesn't throw an error.
      return false;
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     *   The element to detach the editor from.
     * @param {string} format
     *   The text format used for the editor.
     * @param {string} trigger
     *   The event trigger for the detach.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    detach(element, format, trigger) {
      return true;
    },

    /**
     * Reacts on a change in the editor element.
     *
     * @param {HTMLElement} element
     *   The element where the change occured.
     * @param {function} callback
     *   Callback called with the value of the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    onChange(element, callback) {
      return true;
    },

    /**
     * Initializes the editor on a given element.
     *
     * @param {HTMLElement} element
     *   The element where the editor will be initialized.
     */
    async _initGutenberg(element) {
      const { editPost, data } = wp;
      const $textArea = $(element);
      const target = `editor-${$textArea.data('drupal-selector')}`; // 'editor-' + $textArea.data('drupal-selector');
      const $editor = $(`<div id="${target}" class="gutenberg__editor"></div>`); // $('<div id="' + target + '" class="gutenberg__editor"></div>');
      $editor.insertAfter($textArea);
      $textArea.hide();

      wp.node = {
        categories: [],
        content: {
          block_version: 0,
          protected: false,
          raw: $(element).val(),
          rendered: '',
        },
        featured_media: 0,
        id: 1,
        parent: 0,
        permalink_template: '',
        revisions: { count: 0, last_id: 1 },
        status: 'auto-draft',
        theme_style: true,
        type: 'page',
        slug: '',
      };

      const defaultThemeSupport = {
        disableCustomColors: false,
        disableCustomFontSizes: false,
        alignWide: true,
      };

      const editorSettings = {
        ...defaultThemeSupport,
        ...drupalSettings.gutenberg['theme-support'],
        availableTemplates: [],
        allowedBlockTypes: true,
        disablePostFormats: false,
        mediaLibrary: true,
        // See issue: https://www.drupal.org/project/gutenberg/issues/3035313
        imageSizes: drupalSettings.gutenberg['image-sizes'],
        titlePlaceholder: Drupal.t('Add title'),
        bodyPlaceholder: Drupal.t('Add text or type / to add content'),
        isRTL: drupalSettings.gutenberg['is-rtl'],
        localAutosaveInterval: 0,
        autosaveInterval: 0, // Must set > 0 for undo and redo to work.
        // Following properties were from G-JS.
        // canAutosave: false, // to disable Editor Autosave featured (default: true)
        // canPublish: false, // to disable Editor Publish featured (default: true)
        // canSave: false, // to disable Editor Save featured (default: true)    };
        template: drupalSettings.gutenberg.template || '',
        templateLock:
          drupalSettings.gutenberg['template-lock'] === 'none'
            ? false
            : drupalSettings.gutenberg['template-lock'] || false,
      };

      data.subscribe(() => {
        // We need to deal with the top left logo when in fullscreen mode.
        const isFullscreenMode = data
          .select('core/edit-post')
          .isFeatureActive('fullscreenMode');

        setTimeout(() => {
          const fullscreenLink = $(
            '.edit-post-header a.edit-post-fullscreen-mode-close:not(.drupal)',
          );

          const drupalFullscreenLink = $(
            '.edit-post-header a.edit-post-fullscreen-mode-close.drupal',
          );

          if (
            isFullscreenMode &&
            fullscreenLink.length > 0 &&
            drupalFullscreenLink.length === 0
          ) {
            // Define back URL
            const params = new URLSearchParams(window.location.search);
            let backUrl = `${drupalSettings.path.baseUrl}admin/content`;

            if (
              RegExp(/node\/\d+\/edit/g).test(drupalSettings.path.currentPath)
            ) {
              backUrl =
                drupalSettings.path.baseUrl +
                drupalSettings.path.currentPath.replace('/edit', '');
            }

            backUrl = params.get('destination') || backUrl;

            // Add container for the new button
            const domContainer = $('<div style="display: contents"></div>');
            fullscreenLink.after(domContainer);

            const icon = (
              <svg
                version="1.1"
                id="Layer_1"
                x="0px"
                y="0px"
                className="dashicon"
                viewBox="0 0 42.2 55.5"
              >
                <g id="Livello_2">
                  <g id="Livello_1-2">
                    <path
                      d="M29.8,11.7C25.9,7.9,22.2,4.2,21.1,0c-1.1,4.2-4.8,7.9-8.7,11.7C6.6,17.5,0,24.1,0,34
                      c-0.3,11.6,9,21.3,20.6,21.5s21.3-9,21.5-20.6c0-0.3,0-0.6,0-0.9C42.2,24.1,35.6,17.5,29.8,11.7z M10.8,35.9
                      c-0.6,0.8-1.2,1.7-1.6,2.6c-0.1,0.1-0.2,0.3-0.4,0.3H8.7c-0.5,0-1-0.9-1-0.9l0,0c-0.1-0.2-0.3-0.5-0.4-0.7L7.2,37
                      C5.9,34.2,7,30.3,7,30.3l0,0c0.5-1.9,1.4-3.8,2.5-5.4c0.7-1,1.5-2,2.3-3l1,1l4.7,4.8c0.2,0.2,0.2,0.5,0,0.7l-4.9,5.5l0,0
                      L10.8,35.9z M21.3,49.7c-4,0-7.3-3.3-7.2-7.3c0-1.8,0.7-3.5,1.8-4.8c1.5-1.8,3.4-3.6,5.5-6c2.4,2.6,4,4.3,5.5,6.3
                      c0.1,0.1,0.2,0.3,0.3,0.5c0.8,1.2,1.3,2.6,1.3,4.1C28.6,46.5,25.3,49.7,21.3,49.7C21.3,49.7,21.3,49.7,21.3,49.7z M35,38.1
                      L35,38.1c-0.1,0.3-0.4,0.5-0.7,0.6h-0.1c-0.3-0.1-0.5-0.3-0.7-0.5l0,0c-1.3-1.9-2.7-3.7-4.3-5.3l-1.9-2l-6.4-6.6
                      c-1.3-1.2-2.6-2.6-3.8-3.9c0-0.1-0.1-0.1-0.1-0.1c-0.2-0.3-0.4-0.6-0.5-1c0-0.1,0-0.1,0-0.2c-0.2-1.1,0.2-2.2,1-3
                      c1.2-1.2,2.5-2.5,3.7-3.8c1.3,1.4,2.7,2.8,4.1,4.2l0,0c2.8,2.6,5.3,5.5,7.6,8.6c1.9,2.7,2.9,5.8,2.9,9.1
                      C35.6,35.4,35.4,36.8,35,38.1z"
                    />
                  </g>
                </g>
              </svg>
            );

            const { render } = wp.element;
            const { Button } = wp.components;
            const drupalButton = (
              <Button
                className="edit-post-fullscreen-mode-close drupal"
                icon={icon}
                iconSize={36}
                href={backUrl}
                label={Drupal.t('Back')}
              />
            );

            render(drupalButton, domContainer[0]);
          }
        });

        // Clear template validation.
        // Force template validity to true.
        if (!data.select('core/block-editor').isValidTemplate()) {
          // see https://github.com/WordPress/gutenberg/issues/11681
          data.dispatch('core/block-editor').setTemplateValidity(true);
        }
      });

      // To avoid restore backup notices from local autosave.
      sessionStorage.removeItem('wp-autosave-block-editor-post-1');
      localStorage.removeItem('wp-autosave-block-editor-post-1');

      await editPost.initializeEditor(target, 'page', 1, editorSettings);
    },
  };

  /**
   * Gutenberg media library behavior.
   *
   * @type {{attach(): (undefined)}}
   */
  Drupal.behaviors.gutenbergMediaLibrary = {
    attach() {
      const $form = $('#media-entity-browser-modal .media-library-add-form');

      if (!$form.length) {
        return;
      }

      $form
        .find('[data-drupal-selector="edit-save-insert"]')
        .css('display', 'none');
    },
  };
})(Drupal, DrupalGutenberg, drupalSettings, window.wp, jQuery);
