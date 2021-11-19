<?php

namespace Drupal\gutenberg\MediaSelectionProcessor;

/**
 * Provides contract for media selection processor manager.
 *
 * @package Drupal\gutenberg\MediaSelectionProcessor
 */
interface MediaSelectionProcessorManagerInterface {

  /**
   * Register media selection processor.
   *
   * @param \Drupal\gutenberg\MediaSelectionProcessor\MediaSelectionProcessorInterface $processor
   *   Media selection processor instance.
   * @param \Drupal\gutenberg\MediaSelectionProcessor\string $processor_name
   *   Processor name.
   */
  public function registerProcessor(MediaSelectionProcessorInterface $processor, string $processor_name);

  /**
   * Process media selection data by provided processor.
   *
   * @param string $processor_name
   *   Processor name.
   * @param array $data
   *   Data to process.
   *
   * @return mixed
   *   The processed media selection data.
   */
  public function process(string $processor_name, array $data);

  /**
   * Process selection data.
   *
   * If numeric string is provided, default processor will be fired.
   *
   * @param string $data
   *   Serialized data to process.
   *
   * @return \Drupal\media\MediaInterface[]
   *   List of media entities.
   */
  public function processData(string $data);

}
