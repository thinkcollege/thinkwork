// import { data } from '@frontkom/gutenberg-js';
// import DrupalBlock from './components/drupal-block';
// import DrupalIcon from './components/drupal-icon';

/* eslint func-names: ["error", "never"] */
(function(wp, $, Drupal, drupalSettings) {
  const { data, blocks, editor } = wp;
  const { BlockAlignmentToolbar, BlockControls } = editor;
  const { Fragment } = wp.element;
  const { DrupalIcon, DrupalBlock } = window.DrupalGutenberg.Components;

  const providerIcons = {
    system: DrupalIcon, // 'admin-home',
    user: 'admin-users',
    views: 'media-document',
    core: DrupalIcon,
  };

  function isBlackListed(definition, blackList) {
    for (const key in blackList) {
      if (blackList.hasOwnProperty(key)) {
        const values = blackList[key];

        for (const value of values) {
          if (definition[key] === value) {
            return true;
          }
        }
      }
    }

    return false;
  }

  function filterBlackList(definitions, blackList) {
    const result = {};

    for (const key in definitions) {
      if (definitions.hasOwnProperty(key)) {
        const definition = definitions[key];

        if (!isBlackListed(definition, blackList)) {
          result[key] = definition;
        }
      }
    }

    return result;
  }

  function registerBlock(id, definition) {
    const blockId = `drupalblock/${id}`.replace(/_/g, '-').replace(/:/g, '-');

    blocks.registerBlockType(blockId, {
      title: `${definition.admin_label} [${definition.category}]`,
      icon: providerIcons[definition.provider] || DrupalIcon,
      category: 'drupal',
      supports: {
        align: true,
        html: false,
        reusable: false,
      },
      attributes: {
        blockId: {
          type: 'string',
        },
        settings: {
          type: 'object',
        },
        align: {
          type: 'string',
        },
      },
      edit({ attributes, className, setAttributes }) {
        const { align, settings } = attributes;
        setAttributes({ blockId: id });

        return (
          <Fragment>
            <DrupalBlock
              className={className}
              id={id}
              name={definition.admin_label}
              settings={settings}
            />
          </Fragment>
        );
      },
      save() {
        return null;
      },
    });
  }

  function registerDrupalBlocks(contentType) {
    return new Promise(resolve => {
      $.ajax(Drupal.url(`editor/blocks/load_by_type/${contentType}`)).done(
        definitions => {
          const category = {
            slug: 'drupal',
            title: Drupal.t('Drupal Blocks'),
          };

          const categories = [
            ...data.select('core/blocks').getCategories(),
            category,
          ];

          data.dispatch('core/blocks').setCategories(categories);

          /* eslint no-restricted-syntax: ["error", "never"] */
          for (const id in definitions) {
            if ({}.hasOwnProperty.call(definitions, id)) {
              const definition = definitions[id];
              if (definition) {
                registerBlock(id, definition);
              }
            }
          }
          resolve();
        },
      );
    });
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.registerDrupalBlocks = registerDrupalBlocks;
})(wp, jQuery, Drupal, drupalSettings);
