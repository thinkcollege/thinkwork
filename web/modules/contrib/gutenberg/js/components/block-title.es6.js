/**
 * WordPress dependencies
 */
// import { withSelect } from '@wordpress/data';
// import { getBlockType } from '@wordpress/blocks';
const { blocks, data } = wp;
const { withSelect } = data;
const { getBlockType } = blocks;
/**
 * Renders the block's configured title as a string, or empty if the title
 * cannot be determined.
 *
 * @example
 *
 * ```jsx
 * <BlockTitle clientId="afd1cb17-2c08-4e7a-91be-007ba7ddc3a1" />
 * ```
 *
 * @param {Object}  props
 * @param {?string} props.name Block name.
 *
 * @return {?string} Block title.
 */

function BlockTitle(_ref) {
  // const name = _ref.name;
  const { name } = _ref;

  if (!name) {
    return null;
  }

  const blockType = getBlockType(name);

  if (!blockType) {
    return null;
  }

  return blockType.title;
}

Object.defineProperty(wp.blockEditor, 'BlockTitle', {
  get: withSelect((select, ownProps) => {
    const _select = select('core/block-editor');
    const { getBlockName } = _select;
    console.log('BlockTitle');

    const { clientId } = ownProps;
    return {
      name: `${getBlockName(clientId)} - from Drupal!!!`,
    };
  })(BlockTitle),
});

// wp.blockEditor.BlockTitle = withSelect((select, ownProps) => {
//   const _select = select('core/block-editor');
//   const { getBlockName } = _select;
//   console.log('BlockTitle');

//   const { clientId } = ownProps;
//   return {
//     name: `${getBlockName(clientId)} - from Drupal!!!`,
//   };
// })(BlockTitle);

// wp.editor.BlockTitle = wp.blockEditor.BlockTitle;
