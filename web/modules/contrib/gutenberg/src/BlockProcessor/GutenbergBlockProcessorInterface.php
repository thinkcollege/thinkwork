<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Defines an interface for classes that process Gutenberg blocks.
 */
interface GutenbergBlockProcessorInterface {

  /**
   * Process the Gutenberg block and its content.
   *
   * The content and block can be manipulated here. Return FALSE
   * to ensure that no other plugins are ran after this instance.
   *
   * @param array $block
   *   The block object.
   * @param string $block_content
   *   The inner block content.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return bool|null
   *   Return FALSE if further processing should be stopped.
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata);

  /**
   * Whether the processor supports this block instance.
   *
   * @param array $block
   *   The block array.
   * @param string $block_content
   *   The inner block content.
   *
   * @return bool
   *   Whether the block is supported.
   */
  public function isSupported(array $block, $block_content = '');

}
