((wp, $, drupalSettings, Drupal) => {
  const withNativeDialog = Component => {
    const onDialogCreate = (element, multiple) => {
      drupalSettings.media_library = drupalSettings.media_library || {};
      drupalSettings.media_library.selection_remaining = multiple ? 1000 : 1;

      // @todo: it's temporary bugfix for the issue with initial loading (cannot upload a file in media library dialog).
      setTimeout(() => {
        $('#media-library-wrapper li:first-child a').click();
      }, 0);
    };

    const getDefaultMediaSelections = () =>
      (Drupal.MediaLibrary.currentSelection || []).filter(
        selection => +selection,
      );

    const getSpecialMediaSelections = () =>
      [...(Drupal.SpecialMediaSelection.currentSelection || [])].map(
        selection =>
          JSON.stringify({
            [selection.processor]: selection.data,
          }),
      );

    async function onDialogInsert(element, props) {
      const { onSelect, handlesMediaEntity, multiple } = props;

      let selectionData = [];
      let selections = [
        ...getDefaultMediaSelections(),
        ...getSpecialMediaSelections(),
      ];
      selections = multiple ? selections : selections.slice(0, 1);

      const endpointUrl = handlesMediaEntity
        ? Drupal.url('editor/media/render')
        : Drupal.url('editor/media/load-media');

      for (const selection of selections) {
        const response = await fetch(
          `${endpointUrl}/${encodeURIComponent(selection)}`,
        );
        selectionData.push(await response.json());
      }

      if (handlesMediaEntity) {
        selectionData = selectionData.map(
          selectionItem =>
            selectionItem.media_entity && selectionItem.media_entity.id,
        );
      }

      onSelect(multiple ? selectionData : selectionData[0]);
    }

    const onDialogClose = () => {
      const modal = document.getElementById('media-entity-browser-modal');
      if (modal) {
        modal.remove();
      }

      const nodes = document.querySelectorAll(
        '[aria-describedby="media-entity-browser-modal"]',
      );
      nodes.forEach(node => node.remove());
    };

    const getDialog = ({ allowedTypes }) =>
      new Promise((resolve, reject) => {
        wp.apiFetch({
          path: 'load-media-library-dialog',
          data: { allowedTypes },
        })
          .then(result => {
            resolve({
              component: props => (
                <div
                  {...props}
                  dangerouslySetInnerHTML={{ __html: result.html }}
                />
              ),
            });
          })
          .catch(reason => {
            reject(reason);
          });
      });

    return props => (
      <Component
        {...props}
        onDialogCreate={onDialogCreate}
        onDialogInsert={onDialogInsert}
        onDialogClose={onDialogClose}
        getDialog={getDialog}
      />
    );
  };

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.withNativeDialog = withNativeDialog;
})(wp, jQuery, drupalSettings, Drupal);
