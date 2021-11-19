<?php

/**
 * @file
 * Post update functions for Gutenberg.
 */

use Drupal\views\Entity\View;

/**
 * Add access restriction to reusable blocks view.
 */
function gutenberg_post_update_reusable_block_view_access() {
  if (\Drupal::moduleHandler()->moduleExists('views')) {
    if ($view = View::load('reusable_blocks')) {
      $display = &$view->getDisplay('default');
      if (!isset($display['display_options']['access']['type']) || $display['display_options']['access']['type'] === 'none') {
        $display['display_options']['access'] = [
          'type' => 'perm',
          'options' => ['perm' => 'use gutenberg'],
        ];
        $view->save();
      }
    }
  }
}


/**
 * Fix youtube and instagram oEmbed processor.
 */
function gutenberg_post_update_fix_youtube_instagram_oembed_processor(&$sandbox) {
  $filterFormatStorage = \Drupal::entityTypeManager()->getStorage('filter_format');
  /** @var \Drupal\filter\Entity\FilterFormat $format */
  foreach ($filterFormatStorage->loadMultiple() as $format) {
    /** @var \Drupal\filter\Plugin\FilterInterface $filter */
    foreach ($format->filters()->getIterator() as $instanceId => $filter) {
      if ($filter->getPluginId() === 'gutenberg') {
        $configuration = $filter->getConfiguration();
        if (isset($configuration['settings']['processor_settings']['oembed']['providers'])) {
          $providers = $configuration['settings']['processor_settings']['oembed']['providers'];

          // Change the Youtube oEmbed endpoint to use HTTPS.
          $providers = str_replace('http://www.youtube.com/oembed', 'https://www.youtube.com/oembed', $providers);

          // Remove the deprecated instagram endpoint, since it requires proper
          // authentication now and cannot straightforwardly used anymore.
          $providers = str_replace("#https?://(www\.)?instagram.com/p/.*#i | https://api.instagram.com/oembed | true\r\n", '', $providers);

          // Store the changed providers.
          $configuration['settings']['processor_settings']['oembed']['providers'] = $providers;
          $format->setFilterConfig($instanceId, $configuration);
        }

        $format->save();
      }
    }
  }
}