<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Supports management for entity type data providers.
 *
 * @package Drupal\gutenberg\DataProvider
 */
class EntityDataProviderManager implements EntityDataProviderManagerInterface {

  /**
   * Array of registered data providers.
   *
   * @var array
   */
  protected $dataProviders = [];

  /**
   * {@inheritDoc}
   */
  public function registerDataProvider(DataProviderInterface $data_provider, string $entity_type) {
    if ($this->isRegistered($entity_type)) {
      throw new \Exception(sprintf('Data provider for %s entity type does already exist.', $entity_type));
    }

    $this->dataProviders[$entity_type] = $data_provider;
  }

  /**
   * {@inheritDoc}
   */
  public function getData(string $entity_type, ContentEntityInterface $entity, array $data = []) {
    if (!$this->isRegistered($entity_type)) {
      throw new \Exception(sprintf("Data type doesn't exist for %s entity type.", $entity_type));
    }

    return $this->dataProviders[$entity_type]->getData($entity, $data);
  }

  /**
   * {@inheritDoc}
   */
  public function isRegistered(string $data_provider) {
    return array_key_exists($data_provider, $this->dataProviders);
  }

}
