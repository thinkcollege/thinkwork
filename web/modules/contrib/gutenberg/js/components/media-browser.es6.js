((wp, Drupal, DrupalGutenberg, drupalSettings) => {
  const { components, element, editor } = wp;
  const { Component, Fragment } = element;
  const { MediaBrowserDetails } = DrupalGutenberg.Components;
  const { Button, FormFileUpload } = components;
  const { mediaUpload } = editor;

  class MediaBrowser extends Component {
    constructor() {
      // eslint-disable-next-line prefer-rest-params
      super(...arguments);
      this.state = {
        data: [],
        selected: {},
        active: null,
        search: '',
      };
      this.uploadFromFiles = this.uploadFromFiles.bind(this);
      this.addFiles = this.addFiles.bind(this);
      this.selectMedia = this.selectMedia.bind(this);
      this.toggleMedia = this.toggleMedia.bind(this);
      this.uncheckMedia = this.uncheckMedia.bind(this);
    }

    componentWillMount() {
      this.getMediaFiles();
    }

    componentDidMount() {
      const { multiple, value } = this.props;
      const selected =
        {} &&
        (multiple && value
          ? {
              ...value.reduce((result, item) => {
                result[item] = true;
                return result;
              }, {}),
            }
          : { [value]: true });

      this.setState({
        selected,
        active: Object.keys(selected)[0],
      });
    }

    getMediaFiles() {
      const { allowedTypes } = this.props;

      if (allowedTypes.length === 0) {
        allowedTypes.push('*');
      }

      const search = allowedTypes.join('+');
      // TODO use $.ajax rather than fetch for API consistency.
      fetch(Drupal.url(`editor/media/search/${search}/*`))
        .then(response => response.json())
        .then(json => {
          this.setState({ data: json });
        });
    }

    uploadFromFiles(event) {
      this.addFiles(event.target.files);
    }

    addFiles(files) {
      const { allowedTypes } = this.props;

      mediaUpload({
        allowedTypes,
        filesList: files,
        onFileChange: () => {
          this.getMediaFiles();
        },
      });
    }

    async selectMedia() {
      const { selected, data } = this.state;
      const { onSelect } = this.props;
      const medias = data.filter(item => selected[item.id]);

      medias.map(async media => {
        const title = typeof media.title === 'string' ? media.title : '';
        const caption = typeof media.caption === 'string' ? media.caption : '';
        const { alt_text } = media;

        await fetch(Drupal.url(`editor/media/update_data/${media.id}`), {
          method: 'post',
          body: JSON.stringify({
            title,
            caption,
            alt_text,
          }),
        });
      });

      onSelect(medias);
    }

    toggleMedia(ev, id) {
      const { selected, active } = this.state;
      const { multiple } = this.props;
      this.setState({ active: id });

      if (multiple) {
        this.setState({
          selected: { ...selected, [id]: active === id ? !selected[id] : true },
        });
      } else {
        this.setState({
          selected: { [id]: active === id ? !selected[id] : true },
        });
      }
    }

    uncheckMedia(ev, id) {
      const { selected } = this.state;
      const { multiple } = this.props;

      if (multiple) {
        this.setState({
          selected: { ...selected, [id]: false },
        });
      }

      ev.stopPropagation();
    }

    render() {
      const { data, selected, active, search } = this.state;
      const { multiple } = this.props;

      const getMedia = id => data.filter(item => item.id === id)[0];
      const activeMedia = getMedia(active);

      function updateMedia(attributes) {
        const { title, altText, caption } = attributes;

        activeMedia.title = title;

        if (caption) {
          activeMedia.caption = caption;
        }

        activeMedia.alt_text = altText;
        activeMedia.alt = altText;
      }

      return (
        <div className="media-browser">
          <div className="content">
            <div className="toolbar">
              <div className="form-item">
                <input
                  name="media-browser-search"
                  className="text-full"
                  placeHolder={Drupal.t('Search')}
                  type="text"
                  onChange={value => {
                    this.setState({ search: value.target.value.toLowerCase() });
                  }}
                />
              </div>
            </div>
            <ul className="list">
              {data
                .filter(
                  item =>
                    item.media_details.file.toLowerCase().includes(search) ||
                    (typeof item.title === 'string' &&
                      item.title.toLowerCase().includes(search)) ||
                    (typeof item.caption === 'string' &&
                      item.caption.toLowerCase().includes(search)) ||
                    (typeof item.alt === 'string' &&
                      item.alt.toLowerCase().includes(search)),
                )
                .map((media, index) => (
                  // eslint-disable-next-line jsx-a11y/click-events-have-key-events
                  <li
                    tabIndex={index}
                    // eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
                    role="checkbox"
                    onClick={ev => this.toggleMedia(ev, media.id)}
                    aria-label={media.filename}
                    aria-checked="true"
                    data-id={media.id}
                    className={`attachment save-ready ${
                      active === media.id ? 'details' : ''
                    } ${selected[media.id] ? 'selected' : ''}`}
                  >
                    <div
                      className={[
                        'attachment-preview',
                        'js--select-attachment',
                        `type-${media.media_type}`,
                        `subtype-${media.mime_type.split('/')[1]}`,
                        media.media_details.width < media.media_details.height
                          ? 'portrait'
                          : 'landscape',
                      ].join(' ')}
                    >
                      <div className="thumbnail">
                        <div className="centered">
                          {media.media_type === 'image' && (
                            <img
                              src={
                                media.media_details.sizes &&
                                media.media_details.sizes.large
                                  ? media.media_details.sizes.large.source_url
                                  : media.source_url
                              }
                              draggable="false"
                              alt={media.filename}
                            />
                          )}
                        </div>
                        {media.media_type !== 'image' && (
                          <div className="filename">
                            {media.media_details.file}
                          </div>
                        )}
                      </div>
                    </div>
                    <button
                      type="button"
                      className="check"
                      tabIndex={index}
                      onClick={ev => this.uncheckMedia(ev, media.id)}
                    >
                      <span className="media-modal-icon" />
                      <span className="screen-reader-text">Deselect</span>
                    </button>
                  </li>
                ))}
            </ul>
            <div className="media-details">
              {activeMedia && (
                <Fragment>
                  <h2>{Drupal.t('Media details')}</h2>
                  <MediaBrowserDetails
                    key={activeMedia.id}
                    onChange={updateMedia}
                    media={activeMedia}
                  />
                </Fragment>
              )}
            </div>
          </div>
          <div className="form-actions">
            {multiple && (
              <div className="selected-summary">
                {`${Drupal.t('Total selected')}: ${
                  Object.values(selected).filter(item => item).length
                }`}
              </div>
            )}
            <div className="buttons">
              <FormFileUpload
                isLarge
                className="editor-media-placeholder__button"
                onChange={this.uploadFromFiles}
                accept="image" // { accept }
                multiple={multiple}
              >
                {Drupal.t('Upload')}
              </FormFileUpload>

              <Button
                isLarge
                disabled={
                  Object.values(selected).filter(item => item).length === 0
                }
                isPrimary
                onClick={this.selectMedia}
              >
                {Drupal.t('Select')}
              </Button>
            </div>
          </div>
        </div>
      );
    }
  }

  MediaBrowser.defaultProps = {
    allowedTypes: ['image'],
  };

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.MediaBrowser = MediaBrowser;
})(wp, Drupal, DrupalGutenberg, drupalSettings);
