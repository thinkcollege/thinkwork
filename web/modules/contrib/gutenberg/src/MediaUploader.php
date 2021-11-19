<?php

namespace Drupal\gutenberg;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload files from Gutenberg editor upload method.
 *
 * @package Drupal\gutenberg
 */
class MediaUploader implements MediaUploaderInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * MediaUploader constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritDoc}
   */
  public function upload(string $form_field_name, UploadedFile $uploaded_file, Editor $editor, array $file_settings = []) {
    $image_settings = $editor->getImageUploadSettings();
    // @todo use the relevant media file settings over the Editor's "Image" plugin one?
    //   Or ultimately just use Drupal's builtin file_managed in a modal to
    //   handle all of this logic like CKeditor does.
    $destination = $image_settings['scheme'] . '://' . $image_settings['directory'];

    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      return NULL;
    }

    $validators = [];

    if (explode('/', $uploaded_file->getClientMimeType())[0] === 'image') {
      // Validate file type.
      // Image type.
      if (!empty($image_settings['max_dimensions']['width']) || !empty($image_settings['max_dimensions']['height'])) {
        $max_dimensions = $image_settings['max_dimensions']['width'] . 'x' . $image_settings['max_dimensions']['height'];
      }
      else {
        $max_dimensions = 0;
      }
      if (version_compare(\Drupal::VERSION, '9.1', '<')) {
        // @see https://www.drupal.org/node/3162663
        $max_filesize = min(Bytes::toInt($image_settings['max_size']), Environment::getUploadMaxSize());
      }
      else {
        $max_filesize = min(Bytes::toNumber($image_settings['max_size']), Environment::getUploadMaxSize());
      }

      $validators['file_validate_size'] = [$max_filesize];
      $validators['file_validate_image_resolution'] = [$max_dimensions];
    }

    if (!empty($file_settings['file_extensions'])) {
      // Validate the media file extensions.
      $validators['file_validate_extensions'] = [$file_settings['file_extensions']];
    }

    // Upload a temporary file.
    $result = file_save_upload($form_field_name, $validators, $destination);
    if (is_array($result) && $result[0]) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $result[0];
      return $file;
    }

    return NULL;
  }

}
