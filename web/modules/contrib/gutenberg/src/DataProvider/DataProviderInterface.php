<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides data for entity type.
 *
 * @package Drupal\gutenberg\DataProvider
 */
interface DataProviderInterface {

  /**
   * Returns array data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity instance to process.
   * @param array $data
   *   Array of additional data to process.
   *
   * @return array
   *   The array data.
   */
  public function getData(ContentEntityInterface $entity, array $data = []);

}
