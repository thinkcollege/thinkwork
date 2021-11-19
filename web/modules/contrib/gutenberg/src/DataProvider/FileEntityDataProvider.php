<?php

namespace Drupal\gutenberg\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\gutenberg\AssertMediaTrait;

/**
 * Provides data for file entity type for Gutenberg editor.
 *
 * @package Drupal\gutenberg\DataProvider
 */
class FileEntityDataProvider extends BaseDataProvider {

  use AssertMediaTrait;

  /**
   * {@inheritDoc}
   *
   * @see wp_prepare_attachment_for_js
   */
  public function getData(ContentEntityInterface $entity, array $data = []) {
    $this->assertIsFileEntity($entity);

    /** @var \Drupal\file\FileInterface $entity */
    $uri = $entity->getFileUri();
    $source_url = file_url_transform_relative(file_create_url($uri));
    $image = $this->imageFactory->get($uri);
    $file_data = $this->getFileData($entity->id());

    $mime = $entity->getMimeType();
    $mime_type_sections = explode('/', $mime);
    $mime_type = $mime_type_sections[0];
    $result = [
      'id' => (int) $entity->id(),
      'title' => $file_data['title'] ?? '',
      'filename' => urldecode($entity->getFilename()),
      'url' => $source_url,
      'link' => file_create_url($source_url),
      'alt' => $file_data['alt_text'] ?? '',
      'author' => $entity->getOwnerId(),
      'description' => '',
      'caption' => $file_data['caption'] ?? '',
      'name' => '',
      'status' => 'inherit',
      'uploadedTo' => 0,
      'date' => date('c', $entity->getCreatedTime()),
      'modified' => date('c', $entity->getChangedTime()),
      /* 'menuOrder' => 0, */
      'mime' => $mime,
      'type' => $mime_type,
      'subtype' => $mime_type_sections[1],
      /* 'icon' => '', */
      /* 'dateFormatted' => date('F j, Y', $entity->getCreatedTime()), */
      /* 'nonces' => ['update' => FALSE, 'delete' => FALSE, 'edit' => FALSE], */
      /* 'editLink' => FALSE, */
      /* 'meta' => FALSE, */
      // Drupal specific properties below.
      'source_url' => $source_url,
      'media_type' => $mime_type,
      'mime_type' => $mime,
      'date_gmt' => date('c', $entity->getCreatedTime()),
      // Prop used on inline-image.
      'width' => $image->getWidth(),
      'data' => [
        'entity_type' => 'file',
        'entity_uuid' => $entity->uuid(),
        'image_style' => 'original',
      ],
      'media_data' => $file_data,
      'media_details' => [
        'file' => $entity->getFilename(),
        'width' => $image->getWidth(),
        'height' => $image->getHeight(),
        'filesize' => $entity->getSize(),
        'image_meta' => [],
        // See issue: https://www.drupal.org/project/gutenberg/issues/3035313
        'sizes' => $this->getSizes($source_url, $uri),
      ],
    ];

    return $result;
  }

}
