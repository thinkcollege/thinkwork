((Drupal, wp, sprintf) => {
  // see https://github.com/WordPress/gutenberg/blob/master/packages/i18n/src/index.js
  wp.i18n = {};

  wp.i18n.__ = value => Drupal.t(value);
  wp.i18n._x = (value, context) => Drupal.t(value, {}, { context });
  wp.i18n._n = (single, plural, number) => {
    if (typeof number === 'undefined') {
      number = 1;
    }
    number = number || 0;
    return Drupal.formatPlural(number, single, plural);
  };
  wp.i18n._nx = (single, plural, number, context) => {
    if (typeof number === 'undefined') {
      number = 1;
    }
    number = number || 0;
    return Drupal.formatPlural(number, single, plural, {}, { context });
  };

  wp.i18n.isRTL = () =>
    Drupal.t('ltr', {}, { context: 'text direction' }) === 'rtl';
  wp.i18n.setLocaleData = () => {
    // eslint-disable-next-line no-console
    console.warn('wp.i18n.setLocaleData() is a noop.');
  };
  wp.i18n.sprintf = (format, ...args) => {
    try {
      return sprintf(format, ...args);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn(`sprintf error: \n\n${error.toString()}`);

      return format;
    }
  };
})(Drupal, wp, sprintf);
