/* eslint func-names: ["error", "never"] */
(function() {
  function registerDrupalStore(data) {
    const { registerStore, dispatch } = data;

    const DEFAULT_STATE = {
      blocks: {},
      mediaEntities: {},
      entitiesToSave: [],
    };

    return registerStore('drupal', {
      reducer(state = DEFAULT_STATE, action) {
        switch (action.type) {
          /**
           * @todo Either remove this action (solely used by DrupalBlock)
           * or figure out a away to retrieve data cosidering block settings.
           */
          case 'SET_BLOCK':
            return {
              ...state,
              blocks: {
                ...state.blocks,
                [action.item]: action.settings,
                [action.item]: action.block,
              },
            };
          case 'SET_MEDIA_ENTITY':
            return {
              ...state,
              mediaEntities: {
                ...state.mediaEntities,
                [action.item]: action.mediaEntity,
              },
            };
          case 'SET_ENTITIES_TO_SAVE':
            return {
              ...state,
              entitiesToSave: {
                ...action.entitiesToSave,
              },
            };
          default:
            return state;
        }
      },

      actions: {
        setBlock(item, settings, block) {
          return {
            type: 'SET_BLOCK',
            item,
            settings,
            block,
          };
        },
        setMediaEntity(item, mediaEntity) {
          return {
            type: 'SET_MEDIA_ENTITY',
            item,
            mediaEntity,
          };
        },
        setEntitiesToSave(entitiesToSave) {
          return {
            type: 'SET_ENTITIES_TO_SAVE',
            entitiesToSave,
          }
        },
      },

      selectors: {
        getBlock(state, item, settings) {
          const { blocks } = state;
          return blocks[item];
        },
        getMediaEntity(state, item) {
          const { mediaEntities } = state;
          return mediaEntities[item];
        },
        getEntitiesToSave(state) {
          return state.entitiesToSave;
        },
      },

      resolvers: {
        async getBlock(item, settings) {
          const response = await fetch(
            Drupal.url(`editor/blocks/load/${item}`),
            {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(settings),
            }
          );
          const block = await response.json();
          dispatch('drupal').setBlock(item, settings, {...block, settings});
          return {
            type: 'GET_BLOCK',
            item,
            settings,
            block: {...block, settings},
          };
        },
        async getMediaEntity(item) {
          const response = await fetch(
            Drupal.url(`editor/media/render/${item}`),
          );

          if (response.ok) {
            const mediaEntity = await response.json();

            if (mediaEntity && mediaEntity.view_modes) {
              dispatch('drupal').setMediaEntity(item, mediaEntity);
              return {
                type: 'GET_MEDIA_ENTITY',
                item,
                mediaEntity,
              };
            }
          }

          if (response.status === 404) {
            Drupal.notifyError("Media entity couldn't be found.");
            return null;
          }

          if (!response.ok) {
            Drupal.notifyError('An error occurred while fetching data.');
            return null;
          }
        },
      },
    });
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.registerDrupalStore = registerDrupalStore;
})();
