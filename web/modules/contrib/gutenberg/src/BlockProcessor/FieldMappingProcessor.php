<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Processor removing blocks with a "mappingFields" attribute.
 */
class FieldMappingProcessor implements GutenbergBlockProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    // Remove the inner content since the contents of this block will be mapped
    // to an entity field.
    $block_content = '';
    // Stop further processing by returning FALSE?
    // Shouldn't have to if it's of a low enough weight.
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return !empty($block['attrs']['mappingFields']) && is_array($block['attrs']['mappingFields']);
  }

}
