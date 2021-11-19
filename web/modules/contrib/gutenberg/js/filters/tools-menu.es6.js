(wp => {
  const { registerPlugin } = wp.plugins;
  const { dispatch } = wp.data;
  const { createSlotFill, MenuItem } = wp.components;
  const { __ } = wp.i18n;
  const { Fill: ToolsMoreMenuGroup } = createSlotFill('ToolsMoreMenuGroup');

  function toggleWelcomeGuide() {
    dispatch('core/edit-post').toggleFeature('welcomeGuideDrupalDisabled');
  }

  const DrupalToolsMenu = () => (
    <ToolsMoreMenuGroup>
      <MenuItem role="menuitem" onClick={toggleWelcomeGuide}>
        {__('Welcome Guide')}
      </MenuItem>
      <MenuItem
        role="menuitem"
        href="https://www.drupal.org/docs/8/modules/gutenberg"
        target="_new"
      >
        {__('Help')}
      </MenuItem>
    </ToolsMoreMenuGroup>
  );

  registerPlugin('more-menu-item-test', { render: DrupalToolsMenu });
})(wp);
