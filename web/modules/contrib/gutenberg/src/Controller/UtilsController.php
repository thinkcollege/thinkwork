<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Controller\ControllerBase;

/**
 * Utility controller.
 *
 * @package Drupal\gutenberg\Controller
 */
class UtilsController extends ControllerBase {

  /**
   * Get blocks settings.
   */
  public static function getBlocksSettings() {
    $settings = &drupal_static(__FUNCTION__);

    if (!isset($settings)) {
      $module_handler = \Drupal::service('module_handler');
      $path = $module_handler->getModule('gutenberg')->getPath();

      $file_path = DRUPAL_ROOT . '/' . $path . '/' . 'gutenberg.blocks.yml';
      if (file_exists($file_path)) {
        $file_contents = file_get_contents($file_path);
        $settings = Yaml::parse($file_contents);
      }
    }

    return $settings;
  }

  /**
   * Gets allowed blocks.
   */
  public static function getAllowedBlocks() {
    $settings = &drupal_static(__FUNCTION__);

    if (!isset($settings)) {
      $module_handler = \Drupal::service('module_handler');
      $path = $module_handler->getModule('gutenberg')->getPath();

      $file_path = DRUPAL_ROOT . '/' . $path . '/' . 'gutenberg.blocks.yml';
      if (file_exists($file_path)) {
        $file_contents = file_get_contents($file_path);
        $settings = Yaml::parse($file_contents);
      }
    }
    \Drupal::moduleHandler()->alter('allowed_gutenberg_blocks_default_list', $settings);

    return $settings;
  }

  /**
   * Gets allowed custom blocks.
   * It fetches from *gutenberg.yml file within themes and modules
   *
   */
  public static function getAllowedCustomBlocks() {
    $settings = &drupal_static(__FUNCTION__);

    if (!isset($settings)) {
      $gutenberg_library_manager = \Drupal::service('plugin.manager.gutenberg.library');
      $theme_definitions = $gutenberg_library_manager->getThemeDefinitions();
      $module_definitions = $gutenberg_library_manager->getModuleDefinitions();
      $definitions = array_merge($theme_definitions, $module_definitions);
      foreach ($definitions as $definition) {
        if (!empty($definition['custom-blocks'])) {
          foreach ($definition['custom-blocks']['categories'] as $category) {
            if (isset($settings['categories'][$category['reference']])) {
              // Merge blocks of both categories together.
              if (isset($category['blocks']) && isset($settings['categories'][$category['reference']]['blocks'])) {
                $settings['categories'][$category['reference']]['blocks'] =
                  array_merge($settings['categories'][$category['reference']]['blocks'],
                    $category['blocks']);
              }
            }
            else {
              // If the category does not exist, add it to settings.
              $settings['categories'][$category['reference']] = $category;
            }
          }
        }
      }
    }

    return $settings;
  }

  /**
   * Get all the entity text fields.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   An entity instance.
   *
   * @return array
   *   The entity text fields.
   */
  public static function getEntityTextFields(FieldableEntityInterface $entity) {
    /*
     * TODO Make the Gutenberg text field configurable rather than searching for
     *  the first formattable field.
     */

    $text_fields = [];

    // Iterate over all node fields and apply gutenberg text format
    // on first text field found.
    $field_names = self::getEntityFieldNames($entity);

    foreach ($field_names as $value) {
      $field_properties = array_keys($entity
        ->getFieldDefinition($value)
        ->getFieldStorageDefinition()
        ->getPropertyDefinitions());

      // It is long text field if it has format property.
      if (in_array('format', $field_properties)) {
        $text_fields[] = $value;
      }
    }

    return $text_fields;
  }

  /**
   * Get a list of entity field names.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   An entity instance.
   *
   * @return array
   *   The field names.
   */
  public static function getEntityFieldNames(FieldableEntityInterface $entity) {
    return array_keys($entity->getFields());
  }

}
