<?php

namespace Drupal\gutenberg\BlockProcessor;

/**
 * Gutenberg block processor manager.
 *
 * Holds an array of gutenberg block processor objects and uses them
 * to sequentially process a block, in order of processor priority.
 */
class GutenbergBlockProcessorManager {

  /**
   * Holds the array of block processors to cycle through.
   *
   * @var GutenbergBlockProcessorInterface[]
   *   An array whose keys are priorities and whose values are arrays of path
   *   processor objects.
   */
  protected $blockProcessors = [];

  /**
   * Holds the array of block processors, sorted by priority.
   *
   * @var GutenbergBlockProcessorInterface[]
   *   An array of path processor objects.
   */
  protected $sortedProcessors;

  /**
   * Adds a block processor object.
   *
   * @param \Drupal\gutenberg\BlockProcessor\GutenbergBlockProcessorInterface $processor
   *   The processor object to add.
   * @param int $priority
   *   The priority of the processor being added.
   */
  public function addProcessor(GutenbergBlockProcessorInterface $processor, $priority = 0) {
    $this->blockProcessors[$priority][] = $processor;
    $this->sortedProcessors = NULL;
  }

  /**
   * Gets the sorted array of block processors.
   *
   * @return GutenbergBlockProcessorInterface[]
   *   An array of processor objects.
   */
  public function getSortedProcessors() {
    if ($this->sortedProcessors === NULL) {
      $this->sortedProcessors = $this->sortProcessors();
    }

    return $this->sortedProcessors;
  }

  /**
   * Sorts the processors according to priority.
   */
  protected function sortProcessors() {
    $sorted = [];
    krsort($this->blockProcessors);

    foreach ($this->blockProcessors as $processors) {
      $sorted = array_merge($sorted, $processors);
    }
    return $sorted;
  }

}
