<?php

namespace Drupal\gutenberg;

/**
 * Guesses all possible file extensions by mime type.
 *
 * @package Drupal\gutenberg
 */
interface FileExtensionGuesserInterface {

  /**
   * Guesses all possible file extensions by general mime type.
   *
   * @param string $general_mime_type
   *   General mime type (image, video, audio, ...).
   *
   * @return array
   *   The mime types.
   */
  public function guessByGeneralMimeType(string $general_mime_type);

}
