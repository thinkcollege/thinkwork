<?php

namespace Drupal\gutenberg\Persistence;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;

/**
 * Provides management for all media types.
 *
 * @package Drupal\gutenberg\Persistence
 */
class MediaTypePersistenceManager implements MediaTypePersistenceManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * MediaTypePersistenceManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * {@inheritDoc}
   */
  public function save(string $media_type, File $file_entity) {
    $media_type_entity = $this->entityTypeManager->getStorage('media_type')->load($media_type);
    $source = $media_type_entity->getSource();
    if (!$source) {
      return NULL;
    }
    $field_config = $source->getSourceFieldDefinition($media_type_entity);
    $field_name = $field_config->getName();

    $media_entity = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => $media_type_entity->id(),
      'name' => $file_entity->getFilename(),
      'uid' => $this->account->id(),
      $field_name => [
        'target_id' => $file_entity->id(),
      ],
    ]);

    try {
      $media_entity->save();
    }
    catch (EntityStorageException $exception) {
      return NULL;
    }

    return $media_entity;
  }

  /**
   * {@inheritDoc}
   */
  public function getFileSettings(string $media_type) {
    /** @var \Drupal\media\MediaTypeInterface $media_type_entity */
    $media_type_entity = $this->entityTypeManager->getStorage('media_type')->load($media_type);
    $source = $media_type_entity->getSource();
    $source_field_definition = $source->getSourceFieldDefinition($media_type_entity);
    if ($source_field_definition) {
      return $source_field_definition->getSettings();
    }

    return [];
  }

}
