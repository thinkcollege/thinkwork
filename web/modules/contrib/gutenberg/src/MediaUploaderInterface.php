<?php

namespace Drupal\gutenberg;

use Drupal\editor\Entity\Editor;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Provides media upload for Gutenberg editor.
 *
 * @package Drupal\gutenberg
 */
interface MediaUploaderInterface {

  /**
   * Upload media to the filesystem.
   *
   * @param string $form_field_name
   *   A string that is the associative array key of the upload form element in
   *   the form array.
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $uploaded_file
   *   Uploaded file instance.
   * @param \Drupal\editor\Entity\Editor $editor
   *   Editor entity.
   * @param array $file_settings
   *   A list of file configurations. e.g. 'file_extensions' etc.
   *
   * @return \Drupal\file\Entity\File|null
   *   File entity or null on failure.
   */
  public function upload(string $form_field_name, UploadedFile $uploaded_file, Editor $editor, array $file_settings = []);

}
