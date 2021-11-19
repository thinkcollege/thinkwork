<?php

namespace Drupal\gutenberg;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;

/**
 * Guesses all possible extensions .
 *
 * @package Drupal\gutenberg
 */
class FileExtensionGuesser extends ExtensionMimeTypeGuesser implements FileExtensionGuesserInterface {

  /**
   * {@inheritDoc}
   */
  public function guessByGeneralMimeType(string $general_mime_type) {
    $mime_types = array_filter($this->defaultMapping['mimetypes'], function ($mime_type) use ($general_mime_type) {
      $pieces = explode('/', $mime_type);
      return $pieces[0] === $general_mime_type;
    });

    $mime_type_ids = array_keys($mime_types);
    $filtered_extensions = array_filter($this->defaultMapping['extensions'], function ($mime_type_id) use ($mime_type_ids) {
      return in_array($mime_type_id, $mime_type_ids);
    });

    return array_keys($filtered_extensions);
  }

}
