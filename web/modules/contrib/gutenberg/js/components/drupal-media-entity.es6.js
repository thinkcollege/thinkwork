/* eslint func-names: ["error", "never"] */
(function(wp, $, Drupal, DrupalGutenberg, drupalSettings) {
  const { element, blockEditor, components, data } = wp;
  const {
    Placeholder,
    Button,
    FormFileUpload,
    SelectControl,
    IconButton,
    PanelBody,
    Toolbar,
    BaseControl,
  } = components;
  const {
    BlockIcon,
    MediaUpload,
    BlockControls,
    InspectorControls,
    URLInput,
  } = blockEditor;
  const { Component, Fragment } = element;
  const { DrupalIcon } = DrupalGutenberg.Components;
  const __ = Drupal.t;
  const { withSelect } = data;

  class DrupalMediaEntity extends Component {
    constructor() {
      super(...arguments);
      this.state = {
        value: '',
        loading: false,
      };
      this.insertMedia = this.insertMedia.bind(this);
      this.openMediaEdit = this.openMediaEdit.bind(this);
      this.onUpload = this.onUpload.bind(this);
      this.changeViewMode = this.changeViewMode.bind(this);
    }

    onUpload(event) {
      const { attributes, mediaUpload, onError } = this.props;
      const { allowedTypes } = attributes;

      mediaUpload({
        allowedTypes,
        filesList: event.target.files,
        onError,
        onFileChange: fileData => {
          if (
            fileData &&
            fileData[0] &&
            fileData[0].media_entity &&
            fileData[0].media_entity.id
          ) {
            this.insertMedia(fileData[0].media_entity.id);
          }
        },
      });
    }

    changeViewMode(viewMode) {
      const { setAttributes } = this.props;
      setAttributes({
        viewMode,
      });
    }

    insertMedia(mediaEntityId) {
      const { setAttributes } = this.props;

      if (isNaN(mediaEntityId)) {
        const regex = /\((\d*)\)$/;
        const match = regex.exec(mediaEntityId);
        mediaEntityId = match[1];
      }

      setAttributes({
        mediaEntityIds: [mediaEntityId],
      });

      this.setState({ value: '' });
    }

    openMediaEdit(mediaEntityIds, clientId) {
      this.setState({ loading: true });

      const elementSettings = {
        progress: { type: 'throbber' },
        dialogType: 'modal',
        dialog: { width: 800 },
        dialogRenderer: null,
        url: Drupal.url(`media/${mediaEntityIds[0]}/edit?gutenberg`),
        base: clientId,
      };

      Drupal.ajax(elementSettings)
        .execute()
        .done(() => {
          this.setState({ loading: false });
        });
    }

    render() {
      const {
        className,
        isMediaLibraryEnabled,
        mediaContent,
        mediaViewModes,
        attributes,
        setAttributes,
        isSelected,
        clientId,
      } = this.props;

      const { value, loading } = this.state;
      const { mediaEntityIds } = attributes;
      const { view_modes: viewModes } = mediaContent;

      const instructions = __(
        'Upload a media file or pick one from your media library.',
      );
      const placeholderClassName = [
        'block-editor-media-placeholder',
        'editor-media-placeholder',
        className,
      ].join(' ');

      if (Array.isArray(mediaViewModes) && mediaViewModes.length) {
        const inspectorControls = (
          <InspectorControls>
            {!attributes.lockViewMode && (
              <PanelBody title={__('Media entity settings')}>
                <SelectControl
                  label={__('View mode')}
                  value={attributes.viewMode}
                  options={mediaViewModes}
                  onChange={this.changeViewMode}
                />
              </PanelBody>
            )}
          </InspectorControls>
        );

        let html = viewModes.default.processedHtml;
        if (viewModes[attributes.viewMode]) {
          html = viewModes[attributes.viewMode].processedHtml;
        }

        return (
          <Fragment>
            <div dangerouslySetInnerHTML={{ __html: html }} />
            {inspectorControls}
            <BlockControls>
              <Toolbar
                controls={[
                  {
                    icon: 'edit',
                    title: __('Edit media'),
                    onClick: () => this.openMediaEdit(mediaEntityIds, clientId),
                  },
                  {
                    icon: 'no',
                    title: __('Clear media'),
                    onClick: () => setAttributes({ mediaEntityIds: [] }),
                  },
                ]}
              >
                {loading && (
                  <div className="ajax-progress ajax-progress-throbber">
                    <div className="throbber">&nbsp;</div>
                  </div>
                )}
              </Toolbar>
            </BlockControls>
          </Fragment>
        );
      }

      const fetchMedia = search =>
        new Promise(resolve => {
          fetch(
            Drupal.url(
              `editor/media/autocomplete?search=${encodeURIComponent(search)}`,
            ),
          )
            .then(response => response.json())
            .then(json => {
              resolve(json);
            });
        });

      const processMediaResult = (url, post) => {
        this.setState({ value: url });
      };

      const linkId = 'search_media_0001';

      const content = isMediaLibraryEnabled ? (
        <MediaUpload
          onSelect={this.insertMedia}
          allowedTypes={attributes.allowedTypes}
          multiple={false}
          handlesMediaEntity={true}
        />
      ) : (
        <Fragment>
          <URLInput
            className="media-entity-search-input"
            value={ value }
            placeholder={__('Type media ID or text to search media')}
            /* eslint-disable jsx-a11y/no-autofocus */
            // Disable Reason: The rule is meant to prevent enabling auto-focus, not disabling it.
            autoFocus={ false }
            /* eslint-enable jsx-a11y/no-autofocus */
            onChange={processMediaResult}
            disableSuggestions={ ! isSelected }
            id={ linkId }
            hasBorder
            __experimentalFetchLinkSuggestions={fetchMedia}
          />
          {/* <input
            type="text"
            value={value}
            onChange={e => this.setState({ value: e.target.value })}
          /> */}
          <Button
            isLarge
            isPrimary
            title={__('Insert')}
            onClick={() => this.insertMedia(value)}
          >
            {__('Insert')}
          </Button>
        </Fragment>
      );

      return (
        <Placeholder
          icon={<BlockIcon icon="admin-media" />}
          label={__('Media')}
          instructions={instructions}
          className={placeholderClassName}
        >
          <FormFileUpload
            onChange={this.onUpload}
            accept={'image/*,video/*,audio/*,application/*'}
            multiple={false}
            render={({ openFileDialog }) => {
              return (
                <Fragment>
                  <Button
                    isLarge
                    onClick={openFileDialog}
                    className={[
                      'block-editor-media-placeholder__button',
                      'editor-media-placeholder__button',
                      'block-editor-media-placeholder__upload-button',
                    ].join(' ')}
                    icon="upload"
                  >
                    {__('Upload')}
                  </Button>
                </Fragment>
              );
            }}
          />
          {content}
        </Placeholder>
      );
    }
  }

  const createClass = withSelect((select, props) => {
    const { getSettings } = select('core/block-editor');
    const { getMediaEntity } = select('drupal');
    const { attributes } = props;
    const mediaEntityIds = attributes.mediaEntityIds || [];

    const defaultData = {
      mediaContent: {},
      mediaViewModes: [],
      mediaUpload: getSettings().mediaUpload,
    };

    if (!mediaEntityIds.length) {
      return defaultData;
    }

    const mediaEntity = getMediaEntity(mediaEntityIds[0]);

    if (!mediaEntity) {
      return defaultData;
    }

    const { view_modes: viewModes } = mediaEntity;
    const mediaViewModes = [];

    if (Object.keys(viewModes).length) {
      // eslint-disable-next-line no-restricted-syntax
      for (const viewMode in viewModes) {
        if (!viewModes.hasOwnProperty(viewMode)) {
          continue;
        }

        mediaViewModes.push({
          value: viewModes[viewMode]['view_mode'],
          label: viewModes[viewMode]['view_mode_name'],
        });

        // Process media HTML.
        const node = document.createElement('div');
        node.innerHTML = viewModes[viewMode].html;
        const formElements = node.querySelectorAll(
          'input, select, button, textarea, a',
        );
        formElements.forEach(element => {
          element.setAttribute('readonly', true);
          element.setAttribute('required', false);

          if (element.tagName === 'A') {
            element.removeAttribute('href');
          }
        });
        viewModes[viewMode].processedHtml = node.innerHTML;
      }
    }

    return {
      mediaContent: mediaEntity,
      mediaViewModes,
      mediaUpload: getSettings().mediaUpload,
    };
  })(DrupalMediaEntity);

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.DrupalMediaEntity = createClass;
})(wp, jQuery, Drupal, DrupalGutenberg, drupalSettings);
