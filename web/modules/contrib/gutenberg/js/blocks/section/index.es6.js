const { blocks, blockEditor, data } = wp;
const { compose } = wp.compose;
const { withSelect } = data;
const { registerBlockType } = blocks;
const { InnerBlocks } = blockEditor;

const style = {
  minHeight: '40px',
  margin: '0 -40px',
  padding: '0 40px',
};

const template = [['core/paragraph', {}]];

function SectionEdit() {
  return (
    <main style={style}>
      <InnerBlocks templateLock={false} template={template} />
    </main>
  );
}

const settings = {
  title: Drupal.t('Section'),
  description: Drupal.t('Section block for template use.'),
  icon: 'media-document',
  attributes: {},
  supports: {
    inserter: false,
    align: true,
    html: false,
  },

  edit: compose([
    withSelect((select, { clientId }) => {
      const { getBlock } = select('core/block-editor');

      const block = getBlock(clientId);

      return {
        hasInnerBlocks: !!(block && block.innerBlocks.length),
      };
    }),
  ])(SectionEdit),

  save({ className }) {
    return (
      <main className={className}>
        <InnerBlocks.Content />
      </main>
    );
  },
};

registerBlockType(`drupal/section`, { category: 'common', ...settings });
