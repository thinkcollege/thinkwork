/**
 * @file
 * Provides custom filters for blocks.
 * Documentation: https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/
 */

((wp) => {
  const { components, data, hooks, i18n } = wp;
  const { __ } = i18n;
  const { useSelect } = data;
  const { addFilter } = hooks;
  const { Popover } = components;

  /**
   * Adds a warning label for the inner blocks inside a reusable block.
   */
  function withReusableBlockWarning(Edit) {
    return (props) => {
      const { clientId } = props;
      const is_inner_block_selected = useSelect(
        ( select ) => select( 'core/block-editor' ).hasSelectedInnerBlock( clientId )
      );
    
      return (
        <div className="wp-block reusable-block__wrapper">
          { (is_inner_block_selected) && (
            <Popover className="reusable-block__popover" noArrow={false} position="bottom right">
              <div class="reusable-block__message">
                ⚠️ { __( 'You are editing a reusable block. Any changes made here will apply wherever this block is used.' ) }
              </div>
            </Popover>
          ) }
          <Edit {...props} />
        </div>
      )
    };
  }

  /**
   * This filter will alter reusable block.
   */
  addFilter(
    'blocks.registerBlockType',
    'gutenberg/reusable-block-warning',
    (settings, name) => {
      if (name !== 'core/block') {
        return settings;
      }

      return {
        ...settings,
        styles: [],
        edit: withReusableBlockWarning(settings.edit),
      };
    },
  );

  })(wp);