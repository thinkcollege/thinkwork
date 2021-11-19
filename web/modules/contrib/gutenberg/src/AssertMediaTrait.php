<?php

namespace Drupal\gutenberg;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * Provides utility to assert data.
 *
 * @package Drupal\gutenberg\DataProvider
 */
trait AssertMediaTrait {

  /**
   * Assert if provided entity is type of FileInterface.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity instance to check.
   *
   * @throws \Exception
   */
  protected function assertIsFileEntity(ContentEntityInterface $entity) {
    if (!$entity instanceof FileInterface) {
      throw new \Exception(sprintf('Entity must by type of %s.', FileInterface::class));
    }
  }

  /**
   * Assert if provided entity is type of MediaInterface.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity instance to check.
   *
   * @throws \Exception
   */
  protected function assertIsMediaEntity(ContentEntityInterface $entity) {
    if (!$entity instanceof MediaInterface) {
      throw new \Exception(sprintf('Entity must by type of %s.', MediaInterface::class));
    }
  }

}
