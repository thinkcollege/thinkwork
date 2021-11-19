<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\gutenberg\AssertMediaTrait;

/**
 * Provides data for media entity type for Gutenberg editor.
 *
 * Usually called when a media file is uploaded.
 *
 * @package Drupal\gutenberg\DataProvider
 */
class MediaEntityDataProvider extends BaseDataProvider {

  use AssertMediaTrait;

  /**
   * {@inheritDoc}
   */
  public function getData(ContentEntityInterface $entity, array $data = []) {
    $this->assertIsFileEntity($entity);

    /** @var \Drupal\file\FileInterface $entity */
    $uri = $entity->getFileUri();
    $source_url = file_url_transform_relative(file_create_url($uri));
    $file_data = $this->getFileData($entity->id());

    $result = [
      'id' => (int) $entity->id(),
      'link' => $source_url,
      'source_url' => $source_url,
      'url' => $source_url,
      'media_type' => explode('/', $entity->getMimeType())[0],
      'mime_type' => $entity->getMimeType(),
      'author' => 1,
      'status' => 'inherit',
      'type' => 'attachment',
      'date_gmt' => date('c', $entity->getCreatedTime()),
      'date' => date('c', $entity->getCreatedTime()),
      // Structure required:
      // https://github.com/WordPress/gutenberg/blob/8afed75c6f2daa2624b0f91781aa4bee163d4e93/packages/media-utils/src/utils/upload-media.js#L182
      'title' => [
        'raw' => $file_data['title'] ?? '',
        'rendered' => $file_data['title'] ?? '',
      ],
      // Prop used on inline-image.
      'alt' => $file_data['alt_text'] ?? '',
      'alt_text' => $file_data['alt_text'] ?? '',
      'data' => [
        'entity_type' => $data['media_type'],
        'entity_uuid' => $entity->uuid(),
        'image_style' => 'original',
      ],
      'media_data' => $file_data,
      'media_details' => [
        'file' => $entity->getFilename(),
        'filesize' => $entity->getSize(),
      ],
      'media_entity' => [
        'id' => $data['media_id'] ?? 0,
        'type' => $data['media_type'] ?? '',
      ],
    ];

    if ($data['media_type'] === 'image') {
      $image = $this->imageFactory->get($uri);
      $result['width'] = $image->getWidth();
      $result['media_details']['width'] = $image->getWidth();
      $result['media_details']['height'] = $image->getHeight();
      $result['media_details']['sizes'] = $this->getSizes($source_url, $uri);
      $result['media_details']['image_meta'] = [];
    }

    if (isset($file_data['caption'])) {
      $result['caption'] = [
        'raw' => $file_data['caption'],
        'rendered' => $file_data['caption'],
      ];
    }

    return $result;
  }

}
