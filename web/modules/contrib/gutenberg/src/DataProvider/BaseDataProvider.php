<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;

/**
 * Provides base abstraction for entity data providers.
 *
 * @package Drupal\gutenberg\DataProvider
 */
abstract class BaseDataProvider implements DataProviderInterface {

  /**
   * Entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Image factory instance.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * BaseDataProvider constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, ImageFactory $image_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->imageFactory = $image_factory;
  }

  /**
   * Get file managed data of the provided file.
   *
   * @param string $fid
   *   File entity ID.
   *
   * @return array
   *   The file data.
   */
  protected function getFileData(string $fid) {
    $query = $this->connection->select('file_managed_data', 'data', []);
    $query->condition('data.fid', $fid);
    $query->fields('data', ['fid', 'data']);
    $result = $query->execute()->fetchAll();
    return isset($result[0]->data) ? unserialize($result[0]->data) : [];
  }

  /**
   * Get sizes of image styles for the source.
   *
   * @param \Drupal\gutenberg\DataProvider\string $source_url
   *   The source URL.
   * @param \Drupal\gutenberg\DataProvider\string $uri
   *   The URI.
   *
   * @return array
   *   The sizes of the image styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSizes(string $source_url, string $uri) {
    // SVG's don't have image styles.
    if (strtolower(pathinfo($source_url, PATHINFO_EXTENSION)) === 'svg') {
      return [];
    }

    $styles = $this->entityTypeManager->getStorage('image_style')
                                      ->loadMultiple();
    $sizes = [
      'full' => [
        'source_url' => $source_url,
      ],
    ];

    foreach ($styles as $style) {
      /** @var \Drupal\image\Entity\ImageStyle $style */
      $sizes[$style->getName()] = [
        'source_url' => file_url_transform_relative($style->buildUrl($uri)),
      ];
    }

    return $sizes;
  }

}
