<?php
/**
 * @file
 * Contains \Drupal\style_api\Plugin\Style\StylePluginManagerInterface.
 */

namespace Drupal\styles_api\Plugin\Style;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of layout plugins.
 */
interface StylePluginManagerInterface extends PluginManagerInterface {

  /**
   * Get all available styles as an options array.
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
   *   Style options, as array.
   */
  public function getStyleOptions(array $params = []);

  /**
   * Get theme implementations.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   */
  public function getThemeImplementations();

}
