((drupalSettings, wp) => {
  window.wp = wp || {};

  // User settings
  window.userSettings = window.userSettings || {
    secure: drupalSettings.user.permissionsHash,
    time: 1234567,
    uid: drupalSettings.user.uid,
  };

  // API settings
  window.wpApiSettings = window.wpApiSettings || {};
  window.wpApiSettingsroot = window.wpApiSettings.root || window.location.origin;
  window.wpApiSettingsnonce = window.wpApiSettings.nonce || '123456789';
  window.wpApiSettingsversionString = window.wpApiSettings.versionString || 'wp/v2/';

  // postboxes
  window.postboxes = window.postboxes || {
    add_postbox_toggles: (page, args) => {
      // console.log('page', page);
      // console.log('args', args);
    },
  };

  drupalSettings.gutenberg = drupalSettings.gutenberg || {
    _listeners: {},
  };

  drupalSettings.gutenberg._listeners = {
    init: [],
  };

  drupalSettings.gutenberg.addListener = (type, callback) => {
    if (!drupalSettings.gutenberg._listeners[type]) {
      throw new Error(
        `Type ${type} not defined as an event listener type for Drupal Gutenberg.`,
      );
    }

    drupalSettings.gutenberg._listeners[type].push(callback);
  };
})(drupalSettings, window.wp);
