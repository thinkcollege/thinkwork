<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Handles media type persistence process.
 *
 * @package Drupal\gutenberg\Persistence
 */
interface EntityDataProviderManagerInterface {

  /**
   * Register entity type data provider.
   *
   * @param \Drupal\gutenberg\DataProvider\DataProviderInterface $data_provider
   *   Data provider to register.
   * @param string $entity_type
   *   Entity type machine name.
   *
   * @throws \Exception
   */
  public function registerDataProvider(DataProviderInterface $data_provider, string $entity_type);

  /**
   * Get data for specified entity type.
   *
   * @param string $entity_type
   *   Entity type machine name.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity instance.
   * @param array $data
   *   (optional) Additional data to process.
   *
   * @return mixed
   *   The data for the specified entity type.
   *
   * @throws \Exception
   */
  public function getData(string $entity_type, ContentEntityInterface $entity, array $data = []);

  /**
   * Check if data provider for specified entity type exists.
   *
   * @param string $data_provider
   *   Machine name of a data provider (entity type machine name).
   *
   * @return bool
   *   TRUE if the specified entity type exists, FALSE otherwise.
   */
  public function isRegistered(string $data_provider);

}
