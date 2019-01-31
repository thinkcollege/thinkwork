<?php

/**
 * @file
 * Contains \Drupal\styles_api\Plugin\Layout\StyleBase.
 */

namespace Drupal\styles_api\Plugin\Style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\style_api\Style;

/**
 * Provides a base class for Styles plugins.
 */
abstract class StyleBase extends PluginBase implements StyleInterface {

  /**
   * @var array
   * The style configuration.
   */
  protected $configuration = [];

  /**
   * Get the plugin's description.
   *
   * @return string
   *   The style description
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get the base path for all resources.
   *
   * @return string
   *   The full base to all resources.
   */
  public function getLabel() {
    return isset($this->pluginDefinition['label']) && $this->pluginDefinition['label'] ? $this->pluginDefinition['label'] : FALSE;
  }

  /**
   * Get the full path to the icon image.
   *
   * This can optionally be used in the user interface to show the layout of
   * regions visually.
   *
   * @return string
   *   The full path to preview image file.
   */
  public function getIconFilename() {
    return isset($this->pluginDefinition['icon']) && $this->pluginDefinition['icon'] ? $this->pluginDefinition['icon'] : FALSE;
  }

  /**
   * Get the configuration for theme template.
   *
   * @return string
   *   Theme function name.
   */
  public function getConfiguration() {
    return isset($this->pluginDefinition['configuration']) && $this->pluginDefinition['configuration'] ? $this->pluginDefinition['configuration'] : FALSE;
  }

}
