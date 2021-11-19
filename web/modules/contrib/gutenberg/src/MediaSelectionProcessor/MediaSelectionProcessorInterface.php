<?php

namespace Drupal\gutenberg\MediaSelectionProcessor;

/**
 * Interface MediaSelectionProcessorInterface.
 *
 * @package Drupal\gutenberg\MediaSelectionProcessor
 */
interface MediaSelectionProcessorInterface {

  /**
   * Process data and get array of created media entities.
   *
   * @param array $data
   *   Array of data to process.
   *
   * @return \Drupal\media\MediaInterface[]
   *   The list of media entities.
   */
  public function process(array $data);

}
