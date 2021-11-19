<?php

namespace Drupal\gutenberg;

/**
 * Guesses media type of the file.
 *
 * @package Drupal\gutenberg
 */
interface MediaTypeGuesserInterface {

  /**
   * Flag for ::guess method, returns all guessed media types.
   */
  const RETURN_ALL = 0;

  /**
   * Flag for ::guess method, returns only one negotiated media type.
   */
  const RETURN_NEGOTIATED = 1;

  /**
   * Guesses all media types based on general mime type.
   *
   * @param \Drupal\gutenberg\string $general_mime_type
   *   General mime type (image, video, audio, ...).
   * @param int $return_option
   *   If self::RETURN_ALL - return all guessed media types.
   *   If self::RETURN_NEGOTIATED - returns only one negotiated media type.
   *
   * @return string|array
   *   The guessed media types.
   */
  public function guess(string $general_mime_type, $return_option = self::RETURN_ALL);

}
