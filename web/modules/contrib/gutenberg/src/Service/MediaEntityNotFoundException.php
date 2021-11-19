<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if media entity couldn't be found.
 *
 * @package Drupal\gutenberg\Service
 */
class MediaEntityNotFoundException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = 'Media entity not found.';

}
