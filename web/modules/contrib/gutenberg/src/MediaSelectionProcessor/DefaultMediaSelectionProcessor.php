<?php

namespace Drupal\gutenberg\MediaSelectionProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles default processing for media library selection.
 *
 * @package Drupal\gutenberg\MediaSelectionProcessor
 */
class DefaultMediaSelectionProcessor implements MediaSelectionProcessorInterface {

  /**
   * Entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DefaultMediaSelectionProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function process(array $data) {
    return $this->entityTypeManager->getStorage('media')->loadMultiple($data);
  }

}
