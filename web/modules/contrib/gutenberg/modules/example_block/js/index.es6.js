const { blocks, data, element, components, editor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select } = data;
const { Fragment } = element;
const { PanelBody, BaseControl, Icon, RangeControl, IconButton, Toolbar, SelectControl } = components;
const { InnerBlocks, RichText, InspectorControls, PanelColorSettings, MediaUpload, BlockControls } = editor;
const __ = Drupal.t;

const settings = {
  title: __('Gutenberg Example Block'),
  description: __('Gutenberg Example Block'),
  icon: 'welcome-learn-more',
  attributes: {
    title: {
      type: 'string',
    },
    subtitle: {
      type: 'string',
    },
    text: {
      type: 'string',
    },
  },

  edit({ className, attributes, setAttributes, isSelected }) {
    const { title, subtitle, text } = attributes;

    return (
      <Fragment>
        <div className={className}>
          <div className="column">
            <RichText
              identifier="title"
              tagName="h2"
              value={title}
              placeholder={__('Title')}
              onChange={nextTitle => {
                setAttributes({
                  title: nextTitle,
                });
              }}
              onSplit={() => null}
              unstableOnSplit={() => null}
            />

            <RichText
              identifier="subtitle"
              tagName="div"
              value={subtitle}
              placeholder={__('Subtitle')}
              onChange={nextSubtitle => {
                setAttributes({
                  subtitle: nextSubtitle,
                });
              }}
              onSplit={() => null}
              unstableOnSplit={() => null}
            />

            <RichText
              identifier="text"
              tagName="p"
              value={text}
              placeholder={__('Text')}
              onChange={nextText => {
                setAttributes({
                  text: nextText,
                });
              }}
            />
          </div>
          <div className="column">
            <div className="icon">
              <Icon icon="welcome-learn-more" />
            </div>
            {isSelected && (
              <div className="info">
                <p>This is Gutenberg's example block.</p>
                <p>To test it just fill the "fields" on the left and save.</p>
              </div>
            )}
          </div>
        </div>
        <InspectorControls>
          <PanelBody title={ __('Block Settings') }>
            <div>{title}</div>
          </PanelBody>
        </InspectorControls>
      </Fragment>
    );
  },

  save({ className, attributes }) {
    const { title, subtitle, text } = attributes;

    return (
      <div className={className}>
        <div className="column">
          {title && (
            <h2>{title}</h2>
          )}
          {subtitle && (
            <div>{subtitle}</div>
          )}
          {text && (
            <p>{text}</p>
          )}
        </div>
        <div className="column">
          <div className="icon">
            <Icon icon="welcome-learn-more" />
          </div>
        </div>
      </div>
    );
  },
};

const dynamicBlockSettings = {
    title: __('Gutenberg Example Dynamic Block'),
    description: __('Gutenberg example dynamic block that can be rendered server-side.'),
    icon: 'welcome-learn-more',
    attributes: {
        title: {
            type: 'string',
        },
    },

    edit({ className, attributes, setAttributes, isSelected }) {
        const { title } = attributes;

        return (
            <div className={className}>
                <div>— Hello from the Gutenberg JS editor.</div>
                <div className="dynamic-block-title">
                    <RichText
                        identifier="title"
                        tagName="h2"
                        value={title}
                        placeholder={__('Title')}
                        onChange={title => {
                            setAttributes({
                                title: title,
                            });
                        }}
                        onSplit={() => null}
                        unstableOnSplit={() => null}
                    />
                </div>
                <div className="dynamic-block-content">
                    <InnerBlocks />
                </div>
            </div>
        );
    },

    save({ className, attributes }) {
        const { title } = attributes;

        // Save the inner content block.
        return (
            <InnerBlocks.Content />
        );
    },
};

const category = {
  slug: 'example',
  title: __('Examples'),
};

const currentCategories = select('core/blocks').getCategories().filter(item => item.slug !== category.slug);
dispatch('core/blocks').setCategories([ category, ...currentCategories ]);

registerBlockType(`${category.slug}/example-block`, { category: category.slug, ...settings });
registerBlockType(`${category.slug}/dynamic-block`, { category: category.slug, ...dynamicBlockSettings });
