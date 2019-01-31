<?php
/**
 * @file
 * Contains StyleapiManager.
 */

namespace Drupal\styles_api;

/**
 * Stylesapi plugin manager.
 */
class Style {


  /**
   * Returns the plugin manager for the Layout plugin type.
   *
   * @return \Drupal\style_plugin\Plugin\Style\StylePluginManagerInterface
   *   Layout manager.
   */
  public static function stylePluginManager() {
    return \Drupal::service('plugin.manager.styles_api');
  }

  /**
   * Return all available layout as an options array.
   *
   * If group_by_category option/parameter passed group the options by
   * category.
   *
   * @param array $params
   *   (optional) An associative array with the following keys:
   *   - group_by_category: (bool) If set to TRUE, return an array of arrays
   *   grouped by the category name; otherwise, return a single-level
   *   associative array.
   *
   * @return array
   *   Layout options, as array.
   *
   * @deprecated
   *   Use \Drupal\styles_api\Plugin\Style\StylePluginManagerInterface::getLayoutOptions().
   */
  public static function getStyleOptions(array $params = []) {
    return static::styleapiPluginManager()->getStyleOptions($params);
  }

  /**
   * Return theme implementations for layouts that give only a template.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   *
   * @deprecated
   *   Use \Drupal\styles_api\Plugin\Style\StylePluginManagerInterface::getThemeImplementations().
   */
  public static function getThemeImplementations() {
    return static::styleapiPluginManager()->getThemeImplementations();
  }

}
