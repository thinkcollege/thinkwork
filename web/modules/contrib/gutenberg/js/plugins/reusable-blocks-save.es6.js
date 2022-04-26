/* eslint func-names: ["error", "never"] */
(function(wp, Drupal, lodash) {
  const { some } = lodash;
  const { components, element, data, editPost, i18n, plugins } = wp;
  const { useSelect, useDispatch } = data;
  const { registerPlugin } = plugins;
  const { PluginSidebar } = editPost;
  const { BaseControl, Button, CheckboxControl, PanelBody, PanelRow } = components;
  const { Fragment, useState, useEffect } = element;
  const { __ } = i18n;

  const Sidebar = () => {
    const dirtyEntityRecords = useSelect( ( select ) => {
      return select(
        'core'
      ).__experimentalGetDirtyEntityRecords();  
    }).filter(item => item.name === 'wp_block');

    const {
      createNotice,
    } = useDispatch('core/notices');

    const {
      saveEditedEntityRecord,
    } = useDispatch('core');

    const {
      setEntitiesToSave,
    } = useDispatch('drupal');

    // Unchecked entities to be ignored by save function.
    const [ unselectedEntities, _setUnselectedEntities ] = useState( [] );
 
  	const setUnselectedEntities = (
      { kind, name, key, property },
      checked
    ) => {
      if ( checked ) {
        _setUnselectedEntities(
          unselectedEntities.filter(
            ( elt ) =>
              elt.kind !== kind ||
              elt.name !== name ||
              elt.key !== key ||
              elt.property !== property
          )
        );
      } else {
        _setUnselectedEntities( [
          ...unselectedEntities,
          { kind, name, key, property },
        ] );
      }
    };

    function getEntitiesToSave() {
      return dirtyEntityRecords.filter(
        ( { kind, name, key, property } ) => {
          return ! some(
            unselectedEntities,
            ( elt ) =>
              elt.kind === kind &&
              elt.name === name &&
              elt.key === key &&
              elt.property === property
          );
        }
      );
    }

    function saveItems() {
      const entitiesToSave = getEntitiesToSave();

      entitiesToSave.forEach( ( { kind, name, key, property } ) => {
        saveEditedEntityRecord( kind, name, key, property );
      } );

      createNotice(
        'success',
        __('Reusable blocks saved!'),
        {
          type: 'snackbar',
          // isDismissible: true,
        }
      );
    }

    // Whenever reusable blocks change, update "entities to save" store item.
    useEffect(() => {
      setEntitiesToSave(getEntitiesToSave());
    });

    return (
      <Fragment>
        {dirtyEntityRecords.length > 0 && (
          <PluginSidebar
            name="state"
            title="Reusable Blocks"
            icon="block-default"
          >
            <PanelBody>
              <BaseControl help={ __('Saving reusable blocks will affect other places where they are being used. You can de-select to not save.') }>
                <BaseControl.VisualLabel>{ __('The selected reusable blocks will be saved when saving the content. Press "Save now" button to save immediately.') }</BaseControl.VisualLabel>
              </BaseControl>
              { dirtyEntityRecords.map (item => (
                <PanelRow>
                  <CheckboxControl
                    key={item.key}
                    label={item.title}
                    checked={
                      ! some(
                        unselectedEntities,
                        ( elt ) =>
                          elt.kind === item.kind &&
                          elt.name === item.name &&
                          elt.key === item.key &&
                          elt.property === item.property
                      )        
                    }
                    onChange={checked => setUnselectedEntities(item, checked)}
                  />
                </PanelRow>
              ))}
              <PanelRow>
                <Button
                  isPrimary
                  onClick={ () => saveItems() }
                >
                  { __('Save now') }
                </Button>
              </PanelRow>
            </PanelBody>
          </PluginSidebar>
        )}
      </Fragment>
    );
  };

  registerPlugin('reusable-blocks-save', {
    render: () => (<Sidebar />)
  });

})(wp, Drupal, lodash);
