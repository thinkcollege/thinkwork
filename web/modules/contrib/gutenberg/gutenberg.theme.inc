<?php

/**
 * @file
 * Theme related functions and hooks for the Gutenberg module.
 */

/**
 * Prepares variables for Gutenberg text field templates.
 *
 * Default template: field-gutenberg-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the field.
 *   - attributes: A string containing the attributes for the wrapping div.
 *   - title_attributes: A string containing the attributes for the title.
 */
function template_preprocess_field_gutenberg_text(array &$variables, $hook) {
  // Run through the default field preprocess hook.
  template_preprocess_field($variables, $hook);
}

/**
 * Prepares variables for Gutenberg block templates.
 *
 * Default template: gutenberg-block.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - block_name: The block name.
 *   - block_attributes: Array of block attributes.
 *   - block_content: The block's inner content.
 */
function template_preprocess_gutenberg_block(array &$variables) {
  // Add the default Gutenberg wp-block- class.
  $block_name = str_replace('/', '-', $variables['block_name']);
  if (substr($block_name, 0, 5) === 'core-') {
    // Handle "core/*" blocks specially.
    $block_name = substr($block_name, 5);
  }
  $variables['attributes']['class'][] = 'wp-block-' . $block_name;
}

/**
 * Prepares variables for Drupal media Gutenberg block templates.
 *
 * @see template_preprocess_gutenberg_block
 */
function template_preprocess_gutenberg_block__drupalmedia__drupal_media_entity(&$variables) {
  if (!empty($variables['block_attributes']['mediaEntityIds'])) {
    /** @var \Drupal\gutenberg\MediaEntityRendererInterface $media_entity_renderer */
    $media_entity_renderer = \Drupal::service('gutenberg.media_entity.renderer');
    $variables['block_content'] = $media_entity_renderer->renderArray(
      reset($variables['block_attributes']['mediaEntityIds']),
      $variables['block_attributes']['viewMode'] ?? 'default'
    );
  }
}
