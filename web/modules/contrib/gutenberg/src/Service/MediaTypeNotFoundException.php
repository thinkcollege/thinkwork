<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if media type entity couldn't be found.
 *
 * @package Drupal\gutenberg\Service
 */
class MediaTypeNotFoundException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = 'Media type not found.';

}
