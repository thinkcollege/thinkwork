<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if file entity couldn't be found.
 *
 * @package Drupal\gutenberg\Service
 */
class FileEntityNotFoundException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = 'File entity not found.';

}
