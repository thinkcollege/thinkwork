((wp, drupalSettings) => {
  const uid = drupalSettings.user ? drupalSettings.user.uid || 1 : 1;
  const storageKey = `WP_DATA_USER_${uid}`;
  const { data } = wp;

  data.use(data.plugins.persistence, { storageKey });
  data.plugins.persistence.__unstableMigrate({ storageKey });
})(wp, drupalSettings);
