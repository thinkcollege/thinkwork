<?php

namespace Drupal\gutenberg;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for gutenberg library plugin managers.
 */
interface GutenbergLibraryManagerInterface extends PluginManagerInterface {

  /**
   * Gets a list of modules with .gutenberg.yml definitions.
   *
   * @return array
   *   The modules and their definitions.
   */
  public function getModuleDefinitions();

  /**
   * Gets a list of themes with .gutenberg.yml definitions.
   *
   * @return array
   *   The themes and their definitions.
   */
  public function getThemeDefinitions();

  /**
   * Gets a list of the active theme's .gutenberg.yml definitions.
   *
   * It also pulls in the base theme definitions.
   *
   * @return array
   *   The theme definitions.
   */
  public function getActiveThemeDefinitions();

  /**
   * Get the active theme merged definition.
   *
   * @return array
   *   The merged theme definitions.
   */
  public function getActiveThemeMergedDefinition();

  /**
   * Get the list of gutenberg.yml definitions and group them by their type.
   *
   * @return array
   *   The definitions grouped by theme and module.
   */
  public function getDefinitionsByExtension();

}
