((wp, Drupal, DrupalGutenberg) => {
  const { MediaLibrary, withNativeDialog, withGutenbergDialog } = DrupalGutenberg.Components;

  wp.hooks.addFilter(
    'editor.MediaUpload',
    'core/edit-post/components/media-upload/replace-media-upload',
    () => Drupal.isMediaLibraryEnabled() ? withNativeDialog(MediaLibrary) : withGutenbergDialog(MediaLibrary),
  );

})(wp, Drupal, DrupalGutenberg, drupalSettings);
