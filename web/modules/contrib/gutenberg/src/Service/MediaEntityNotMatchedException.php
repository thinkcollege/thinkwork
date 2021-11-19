<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if media entity couldn't be matched.
 *
 * @package Drupal\gutenberg\Service
 */
class MediaEntityNotMatchedException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = "Couldn't match any media type for the file.";

}
