<?php

namespace Drupal\gutenberg\Service;

/**
 * Thrown if file and file entity couldn't be saved.
 *
 * @package Drupal\gutenberg\Service
 */
class FileEntityNotSavedException extends \Exception {

  /**
   * {@inheritDoc}
   */
  protected $message = 'The file entity could not be saved. Please ensure the file type is supported.';

}
