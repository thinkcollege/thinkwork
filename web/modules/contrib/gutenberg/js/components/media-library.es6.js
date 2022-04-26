((wp, Drupal) => {
  const {element, components} = wp;
  const {Button} = components;
  const {Component} = element;
  const __ = Drupal.t;

  class MediaLibrary extends Component {
    constructor() {
      super(...arguments);
      this.openDialog = this.openDialog.bind(this);
      this.closeDialog = this.closeDialog.bind(this);
    }

    closeDialog(callback) {
      console.log(this);
      if (this.frame) {
        this.frame.close();
        delete this.frame;
      }
      if (this.mediaBrowserWrapper) {
        Drupal.detachBehaviors(this.mediaBrowserWrapper);
        this.mediaBrowserWrapper.remove();
        delete this.mediaBrowserWrapper;
      }
      this.props.onDialogClose && this.props.onDialogClose();
      callback && callback();
    }

    openDialog() {
      const {
        allowedTypes = [],
        allowedBundles = [],
        onDialogInsert,
        onDialogCreate,
        onClose = () => {},
        getDialog,
        multiple,
      } = this.props;

      getDialog({
        allowedTypes,
        allowedBundles,
        onSelect: () => this.closeDialog(onClose)
      }).then(result => {
        this.mediaBrowserWrapper = document.createElement('div');
        this.mediaBrowserWrapper.setAttribute('id', 'media-entity-browser-modal');

        element.render(
          <result.component {...this.props} />,
          this.mediaBrowserWrapper,
          () => {
            this.frame = Drupal.dialog(this.mediaBrowserWrapper, {
              title: __('Media library'),
              width: '95%',
              height: document.documentElement.clientHeight - 100,
              buttons: {
                [__('Insert')]: () => {
                  onDialogInsert && onDialogInsert(this.mediaBrowserWrapper, this.props);
                  this.closeDialog(onClose);
                },
                [__('Cancel')]: () => this.closeDialog(onClose),
              },
              create: event => onDialogCreate(event.target, multiple),
              // close: () => { onClose(); /*this.closeDialog();*/ },
            });

            this.frame && this.frame.showModal();
            this.mediaBrowserWrapper && Drupal.attachBehaviors(this.mediaBrowserWrapper);
          },
        );
      }).catch(reason => console.log('reason', reason))
    }

    render() {
      const {render} = this.props;

      if (render) {
        return render({open: this.openDialog});
      }

      return (
        <Button isLarge
                isSecondary
                title={__('Media Library')}
                onClick={this.openDialog}>
          {__('Media Library')}
        </Button>
      )
    }
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.MediaLibrary = MediaLibrary;
})(wp, Drupal, DrupalGutenberg, jQuery, drupalSettings);
