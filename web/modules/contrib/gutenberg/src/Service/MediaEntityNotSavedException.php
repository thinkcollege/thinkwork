<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if media entity couldn't be saved.
 *
 * @package Drupal\gutenberg\Service
 */
class MediaEntityNotSavedException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = 'Media entity could not be saved.';

}
