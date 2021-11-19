/* eslint func-names: ["error", "never"] */
(function(Drupal, wp) {
  const { blocks, element, blockEditor } = wp;
  const { registerBlockType } = blocks;
  const { Fragment } = element;
  const { RichText } = blockEditor;
  const __ = Drupal.t;

  const settings = {
    title: __('Simple text'),
    description: __('Simple text block.'),
    icon: 'editor-textcolor',
    supports: {
      inserter: false,
    },
    attributes: {
      tag: {
        type: 'string',
        default: 'p',
      },
      text: {
        type: 'string',
      },
      placeholder: {
        type: 'string',
        default: 'Insert text',
      },
    },

    edit({ className, attributes, setAttributes }) {
      const { text, tag, placeholder } = attributes;
      return (
        <Fragment>
          <div className={className}>
            <RichText
              tagName={tag}
              value={text}
              placeholder={placeholder}
              keepPlaceholderOnFocus
              allowedFormats={[]}
              onChange={newValue => setAttributes({ text: newValue })}
            />
          </div>
        </Fragment>
      );
    },

    save({ attributes }) {
      const { text } = attributes;
      return text;
    },
  };

  registerBlockType(`drupal/simple-text`, { category: 'common', ...settings });
})(Drupal, wp);
