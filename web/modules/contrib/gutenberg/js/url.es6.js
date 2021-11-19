(function url(Drupal, drupalSettings) {
  function addQueryArgs(url, args) {
    const esc = encodeURIComponent;
    const qs = Object.keys(args).map(key => `${esc(key)}=${esc(args[key])}`).join('&');

    if (url === 'edit.php') {
      // 'Manage All Reusable Blocks'
      if (args && args.post_type === 'wp_block') {
        return Drupal.url('admin/content/reusable-blocks');
      }
    } else if (url === 'post.php') {
      if (args.post && args.action === 'edit') {
        // The post's edit url.
        return Drupal.url(drupalSettings.path.currentPath);
      }
    }

    if (qs) {
      if (url.indexOf('?') === -1) {
        return `${url}?${qs}`;
      }
      return `${url}&${qs}`;
    }

    return url;
  }

  window.wp = window.wp || {};
  window.wp.url = { ...window.wp.url, addQueryArgs };
})(Drupal, drupalSettings);
