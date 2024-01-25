<?php

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\Core\File\FileSystemInterface;
use Drupal\imce\ImcePluginBase;
use Drupal\imce\ImceFM;

/**
 * Defines Imce Upload plugin.
 *
 * @ImcePlugin(
 *   id = "upload",
 *   label = "Upload",
 *   weight = -10,
 *   operations = {
 *     "upload" = "opUpload"
 *   }
 * )
 */
class Upload extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'upload_files' => $this->t('Upload files'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    if ($fm->hasPermission('upload_files')) {
      $page['#attached']['library'][] = 'imce/drupal.imce.upload';
    }
  }

  /**
   * Operation handler: upload.
   */
  public function opUpload(ImceFM $fm) {
    $folder = $fm->activeFolder;
    if (!$folder || !$folder->getPermission('upload_files')) {
      return;
    }
    // Prepare save options.
    $destination = $folder->getUri();
    $replace = $fm->getConf('replace', FileSystemInterface::EXISTS_RENAME);
    $validators = [];
    // Extension validator.
    $exts = $fm->getConf('extensions', '');
    if ($exts === '*') {
      $exts = NULL;
    }
    $constraints = class_exists('\Drupal\file\Plugin\Validation\Constraint\FileExtensionConstraint');
    if ($constraints) {
      $validators['FileExtension'] = ['extensions' => $exts];
    }
    else {
      $validators['file_validate_extensions'] = [$exts];
    }
    // File size and user quota validator.
    if ($constraints) {
      $validators['FileSizeLimit'] = [
        'fileLimit' => $fm->getConf('maxsize'),
        'userLimit' => $fm->getConf('quota'),
      ];
    }
    else {
      $validators['file_validate_size'] = [
        $fm->getConf('maxsize'),
        $fm->getConf('quota'),
      ];
    }
    // Image resolution validator.
    $width = $fm->getConf('maxwidth');
    $height = $fm->getConf('maxheight');
    if ($width || $height) {
      // Fix exif orientation before resizing.
      if (function_exists('exif_orientation_validate_image_rotation')) {
        $validators['exif_orientation_validate_image_rotation'] = [];
      }
      $dims = ($width ?: 10000) . 'x' . ($height ?: 10000);
      if ($constraints) {
        $validators['FileImageDimensions'] = ['maxDimensions' => $dims];
      }
      else {
        $validators['file_validate_image_resolution'] = [$dims];
      }
    }
    // Name validator.
    $validators['imce_file_validate_name'] = [$fm->getNameFilter()];
    // Save files.
    if ($files = file_save_upload('imce', $validators, $destination, NULL, $replace)) {
      $fs = \Drupal::service('file_system');
      foreach (array_filter($files) as $file) {
        // Set status and save.
        $file->setPermanent();
        $file->save();
        // Add to the folder and to js response.
        $name = $fs->basename($file->getFileUri());
        $item = $folder->addFile($name);
        $item->uuid = $file->uuid();
        $item->addToJs();
      }
    }
  }

}
