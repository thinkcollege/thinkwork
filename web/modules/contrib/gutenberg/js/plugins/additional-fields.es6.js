/* eslint func-names: ["error", "never"] */
(function(wp, Drupal) {
  const { components, editPost } = wp;
  const { Fragment } = wp.element;
  const { PanelBody } = components;
  const { PluginSidebar, PluginSidebarMoreMenuItem } = editPost;

  function AdditionalFieldsPluginSidebar() {
    return (
      <Fragment>
        <PluginSidebarMoreMenuItem
          target="gutenberg-boilerplate-sidebar">
          Gutenberg Boilerplate
        </PluginSidebarMoreMenuItem>
        <PluginSidebar
          name="additional-fields"
          title="Additional fields"
          icons="forms"
          isPinnable="false"
        >
          <PanelBody />
        </PluginSidebar>
      </Fragment>
    );
  }

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Plugins = window.DrupalGutenberg.Plugins || {};
  window.DrupalGutenberg.Plugins.AdditionalFieldsPluginSidebar = AdditionalFieldsPluginSidebar;
})(wp, Drupal);
