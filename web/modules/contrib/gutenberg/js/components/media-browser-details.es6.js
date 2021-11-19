((wp, Drupal, moment) => {
  const { element, components } = wp;
  const { Component, Fragment } = element;
  const { TextControl, TextareaControl } = components;
  const customTypes = ['image', 'audio', 'video'];

  function toSize(bytes) {
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const value = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
    return `${Math.round(bytes / 1024 ** value, 2)} ${sizes[value]}`;
  }

  class MediaBrowserDetails extends Component {
    constructor() {
      super(...arguments);
      const { media } = this.props;
      this.state = {
        width: null,
        height: null,
        duration: null,
        title: media.title,
        altText: media.alt,
        caption: media.caption,
      };
      this.updateVideo = this.updateVideo.bind(this);
      this.updateAudio = this.updateAudio.bind(this);
    }

    componentDidUpdate(prevProps) {
      const { onChange } = this.props;
      const { title, altText, caption } = this.state;

      onChange({ title, altText, caption });
    }

    updateVideo(ev) {
      this.setState({
        width: ev.target.videoWidth,
        height: ev.target.videoHeight,
        duration: moment.unix(ev.target.duration).format('HH:mm:ss'),
      });
    }

    updateAudio(ev) {
      this.setState({
        // width: ev.target.videoWidth,
        // height: ev.target.videoHeight,
        duration: moment.unix(ev.target.duration).format('HH:mm:ss'),
      });
    }

    render() {
      const { media } = this.props;
      const { width, height, duration, title, altText, caption } = this.state;

      return (
        <Fragment>
          {media.media_type === 'image' && (
            <Fragment>
              <figure>
                <img alt={media.media_details.file} src={media.source_url} />
                <figcaption>{media.media_details.file}</figcaption>
              </figure>
              <div>{`${media.media_details.width} x ${media.media_details.height}`}</div>
              <div>{toSize(media.media_details.filesize)}</div>
              <TextControl
                value={title}
                onChange={value => this.setState({ title: value })}
                label="Title"
              />
              <TextControl
                value={altText}
                onChange={value => this.setState({ altText: value })}
                label="Alt text"
              />
              <TextareaControl
                value={caption}
                onChange={value => this.setState({ caption: value })}
                label="Caption"
              />
            </Fragment>
          )}
          {media.media_type === 'video' && (
            <Fragment>
              <figure>
                <video
                  onLoadedData={this.updateVideo}
                  controls
                  src={media.source_url}
                />
                <figcaption>{media.media_details.file}</figcaption>
              </figure>
              <div>{`${width} x ${height}`}</div>
              <div>{toSize(media.media_details.filesize)}</div>
              <div>{`${duration}`}</div>
            </Fragment>
          )}
          {media.media_type === 'audio' && (
            <Fragment>
              <figure>
                <audio
                  onLoadedData={this.updateAudio}
                  controls
                  src={media.source_url}
                />
                <figcaption>{media.media_details.file}</figcaption>
              </figure>
              <div>{toSize(media.media_details.filesize)}</div>
              <div>{`${duration}`}</div>
            </Fragment>
          )}

          {!customTypes.includes(media.media_type) && (
            <Fragment>
              <div className="filename">{media.media_details.file}</div>
              <div>{toSize(media.media_details.filesize)}</div>
            </Fragment>
          )}
        </Fragment>
      );
    }
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.MediaBrowserDetails = MediaBrowserDetails;
})(wp, Drupal, moment);
