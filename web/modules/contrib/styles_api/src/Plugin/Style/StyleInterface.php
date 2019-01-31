<?php

/**
 * @file
 * Contains \Drupal\styles_api\Plugin\StylePluginInterface.
 */

namespace Drupal\styles_api\Plugin\Style;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for static Style plugins.
 */
interface StyleInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

}
