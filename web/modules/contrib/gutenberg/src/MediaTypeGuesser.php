<?php

namespace Drupal\gutenberg;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Guesses media type of the file.
 *
 * @package Drupal\gutenberg
 */
class MediaTypeGuesser implements MediaTypeGuesserInterface {

  /**
   * Contains array of default media types.
   *
   * @var array
   */
  protected $defaultMediaTypes = [
    'image',
    'audio',
    'video',
    'remote_video',
    'file',
  ];

  /**
   * Entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file extension guesser.
   *
   * @var \Drupal\gutenberg\FileExtensionGuesserInterface
   */
  protected $fileExtensionGuesser;

  /**
   * MediaTypeGuesser constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\gutenberg\FileExtensionGuesserInterface $file_extension_guesser
   *   The file extension guesser.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileExtensionGuesserInterface $file_extension_guesser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileExtensionGuesser = $file_extension_guesser;
  }

  /**
   * {@inheritDoc}
   */
  public function guess(string $general_media_type, $return_option = self::RETURN_ALL) {
    $file_extensions = $this->fileExtensionGuesser->guessByGeneralMimeType($general_media_type);
    $media_type_entities = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    $media_types = [];

    /** @var \Drupal\media\Entity\MediaType $media_type_entity */
    foreach ($media_type_entities as $media_type_entity) {
      $field_config = $media_type_entity->getSource()->getSourceFieldDefinition($media_type_entity);
      $media_type_file_extensions = explode(' ', $field_config->getSetting('file_extensions'));
      if (array_intersect($media_type_file_extensions, $file_extensions)) {
        $media_types[] = $media_type_entity->id();
      }
    }

    if ($return_option === self::RETURN_ALL) {
      return $media_types;
    }

    if ($return_option === self::RETURN_NEGOTIATED) {
      return $this->negotiateMediaTypes($media_types);
    }

    throw new \InvalidArgumentException('Invalid guesser return type provided.');
  }

  /**
   * Negotiate which media type should be chosen.
   *
   * If in guessed media types exist one of the default (existing in the
   * Drupal Core) - choose this one, if not choose first one.
   *
   * @param array $media_types
   *   Array of found media types.
   *
   * @return string
   *   The chosen media type.
   */
  public function negotiateMediaTypes(array $media_types) {
    if (!$media_types) {
      return '';
    }

    if ($default_media_types = array_intersect($this->defaultMediaTypes, $media_types)) {
      return reset($default_media_types);
    }

    return reset($media_types);
  }

}
