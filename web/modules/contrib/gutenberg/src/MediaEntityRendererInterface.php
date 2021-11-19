<?php

namespace Drupal\gutenberg;

/**
 * Renders media entities.
 *
 * @package Drupal\gutenberg
 */
interface MediaEntityRendererInterface {

  /**
   * Render media entities.
   *
   * @param \Drupal\media\Entity\Media|string $media_entity
   *   Media entity ID or loaded media entity instance.
   * @param string $view_mode
   *   (optional) View mode of media entity, 'full' is default.
   *
   * @return string
   *   Rendered HTML of the media entity.
   */
  public function render($media_entity, string $view_mode = 'full');

  /**
   * Return the media entity render array.
   *
   * @param \Drupal\media\Entity\Media|string $media_entity
   *   Media entity ID or loaded media entity instance.
   * @param string $view_mode
   *   (optional) View mode of media entity, 'full' is default.
   *
   * @return array
   *   Render array of the media entity.
   */
  public function renderArray($media_entity, string $view_mode = 'full');

}
