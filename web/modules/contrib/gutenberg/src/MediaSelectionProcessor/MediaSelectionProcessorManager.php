<?php

namespace Drupal\gutenberg\MediaSelectionProcessor;

/**
 * Provides management for tagged media selection processor services.
 *
 * @package Drupal\gutenberg\MediaSelectionProcessor
 */
class MediaSelectionProcessorManager implements MediaSelectionProcessorManagerInterface {

  /**
   * List of registered processors for media library selections.
   *
   * @var MediaSelectionProcessorInterface[]
   */
  protected $processors = [];

  /**
   * {@inheritDoc}
   */
  public function registerProcessor(MediaSelectionProcessorInterface $processor, string $processor_name) {
    if ($this->isRegistered($processor_name)) {
      throw new \Exception(sprintf("Processor '%s' has been already registered", $processor_name));
    }

    $this->processors[$processor_name] = $processor;
  }

  /**
   * {@inheritDoc}
   */
  public function process(string $processor_name, array $data) {
    if (!$this->isRegistered($processor_name)) {
      throw new \Exception(sprintf("Processor '%s' is not registered", $processor_name));
    }

    return $this->processors[$processor_name]->process($data);
  }

  /**
   * {@inheritDoc}
   */
  public function processData(string $data) {
    $media_entities = [];

    if (is_numeric($data)) {
      $media_entities = $this->process('default', [$data]);
    }
    else {
      $special_data = json_decode($data, TRUE);

      foreach ($special_data as $processor => $processor_data) {
        $media_entities = $this->process($processor, $processor_data);
      }
    }

    return $media_entities;
  }

  /**
   * Check if media selection processor is registered in the manager.
   *
   * @param string $processor_name
   *   Name of media selection processor.
   *
   * @return bool
   *   TRUE if the specified processor type exists, FALSE otherwise.
   */
  public function isRegistered(string $processor_name) {
    return array_key_exists($processor_name, $this->processors);
  }

}
