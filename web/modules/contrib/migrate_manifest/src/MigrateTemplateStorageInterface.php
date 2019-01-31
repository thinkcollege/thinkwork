<?php

namespace Drupal\migrate_manifest;

/**
 * The MigrateTemplateStorageInterface interface.
 *
 * Direct copy of the template storage interface removed from core.
 * @see https://www.drupal.org/node/2676258
 */
interface MigrateTemplateStorageInterface {

  /**
   * Find all migration templates with the specified tag.
   *
   * @param string $tag
   *   The tag to match.
   *
   * @return array
   *   Any templates (parsed YAML config) that matched, keyed by the ID.
   */
  public function findTemplatesByTag($tag);

  /**
   * Retrieve a template given a specific name.
   *
   * @param string $name
   *   A migration template name.
   *
   * @return null|array
   *   A parsed migration template, or NULL if it doesn't exist.
   */
  public function getTemplateByName($name);

  /**
   * Retrieves all migration templates belonging to enabled extensions.
   *
   * @return array
   *   Array of parsed templates, keyed by the fully-qualified id.
   */
  public function getAllTemplates();

}
