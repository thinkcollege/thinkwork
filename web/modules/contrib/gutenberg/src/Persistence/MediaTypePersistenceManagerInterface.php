<?php

namespace Drupal\gutenberg\Persistence;

use Drupal\file\Entity\File;

/**
 * Handles media type persistence process.
 *
 * @package Drupal\gutenberg\Persistence
 */
interface MediaTypePersistenceManagerInterface {

  /**
   * Perform saving process of an uploaded file as media entity.
   *
   * @param string $media_type
   *   Media type machine name.
   * @param \Drupal\file\Entity\File $file_entity
   *   File entity instance.
   *
   * @return \Drupal\media\Entity\Media|null
   *   Created media type or null if failed.
   *
   * @throws \Exception
   */
  public function save(string $media_type, File $file_entity);

  /**
   * Returns the media source file settings.
   *
   * @param string $media_type
   *   Media type machine name.
   *
   * @return array
   *   List of media file settings.
   */
  public function getFileSettings(string $media_type);

}
