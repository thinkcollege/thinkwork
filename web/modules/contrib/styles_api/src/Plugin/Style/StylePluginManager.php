<?php

/**
 * @file
 * Contains \Drupal\styles_api\Plugin\Style\StylePluginManager.
 */

namespace Drupal\styles_api\Plugin\Style;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Plugin type manager for all styles.
 */
class StylePluginManager extends DefaultPluginManager implements StylePluginManagerInterface {


  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a LayoutPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handle to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $plugin_interface = 'Drupal\styles_api\Plugin\Style\StyleInterface';
    $plugin_definition_annotation_name = 'Drupal\styles_api\Annotation\Style';
    parent::__construct("Plugin/Style", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $discovery = $this->getDiscovery();
    $this->discovery = new YamlDiscoveryDecorator($discovery, 'themes', $module_handler->getModuleDirectories() + $theme_handler->getThemeDirectories());
    $this->themeHandler = $theme_handler;

    $this->setCacheBackend($cache_backend, 'styles');
    $this->alterInfo('styles');
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Add the module or theme path to the 'path'.
    if ($this->moduleHandler->moduleExists($definition['provider'])) {
      $definition['provider_type'] = 'module';
      $base_path = $this->moduleHandler->getModule($definition['provider'])->getPath();
    }
    elseif ($this->themeHandler->themeExists($definition['provider'])) {
      $definition['provider_type'] = 'theme';
      $base_path = $this->themeHandler->getTheme($definition['provider'])->getPath();
    }
    else {
      $base_path = '';
    }
    $definition['configuration']['path'] = !empty($definition['configuration']['path']) ? $base_path . '/' . $definition['configuration']['path'] : $base_path;

    // Add the path to the icon filename.
    if (!empty($definition['icon'])) {
      $definition['icon'] = $definition['path'] . '/' . $definition['icon'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getStyleOptions(array $params = []) {
    $plugins = $this->getDefinitions();

    // Sort the plugins first by category, then by label.
    $options = array();
    foreach ($plugins as $id => $plugin) {
      if ($group_by_category) {
        $category = isset($plugin['category']) ? (string) $plugin['category'] : 'default';
        if (!isset($options[$category])) {
          $options[$category] = array();
        }
        $options[$category][$id] = $plugin['label'];
      }
      else {
        $options[$id] = $plugin['label'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeImplementations() {
    $plugins = $this->getDefinitions();

    $theme_registry = [];
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['configuration']['path'])) {
        $theme_registry[$id] = $definition['configuration'];
      }
    }

    return $theme_registry;
  }

}
