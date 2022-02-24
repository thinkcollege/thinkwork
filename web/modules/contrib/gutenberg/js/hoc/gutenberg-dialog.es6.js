((DrupalGutenberg, $) => {
  const {MediaBrowser} = DrupalGutenberg.Components;

  const withGutenbergDialog = Component => {
    // Deprecated. To be removed.
    const onDialogCreate = element => {
    };

    const getDialog = ({ allowedTypes, onSelect }) => {
      return new Promise(resolve => {
        resolve({
          component: props => (
            <MediaBrowser {...props}
                          allowedTypes={allowedTypes}
                          value={[]}
                          onSelect={media => {
                            props.onSelect(props.multiple ? media : media[0]);
                            onSelect();
                          }} />
            )
        });
      });
    };

    return props => (
      <Component {...props}
                 onDialogCreate={onDialogCreate}
                 getDialog={getDialog}
      />
    );
  };

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {};
  window.DrupalGutenberg.Components.withGutenbergDialog = withGutenbergDialog;
})(DrupalGutenberg, jQuery);
