<?php

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

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
    $replace = $fm->getConf('replace', 0);
    $validators = [];
    $exts = $fm->getConf('extensions', '');
    if ($exts === '*') {
      $exts = NULL;
    }
    $width = $fm->getConf('maxwidth');
    $height = $fm->getConf('maxheight');
    $dims = $width || $height ? ($width ?: 10000) . 'x' . ($height ?: 10000) : '';
    $maxsize = $fm->getConf('maxsize');
    $quota = $fm->getConf('quota');
    // Fix exif orientation before resizing.
    if ($dims && function_exists('exif_orientation_validate_image_rotation')) {
      $validators['exif_orientation_validate_image_rotation'] = [];
    }
    if (class_exists('Drupal\file\Plugin\Validation\Constraint\FileExtensionConstraint')) {
      $validators['ImceFileName'] = ['filter' => $fm->getNameFilter()];
      $validators['FileExtension'] = ['extensions' => $exts];
      $validators['FileSizeLimit'] = [
        'fileLimit' => $maxsize,
        'userLimit' => $quota,
      ];
      if ($dims) {
        $validators['FileImageDimensions'] = ['maxDimensions' => $dims];
      }
    }
    else {
      $validators['imce_file_validate_name'] = [$fm->getNameFilter()];
      $validators['file_validate_extensions'] = [$exts];
      $validators['file_validate_size'] = [$maxsize, $quota];
      if ($dims) {
        $validators['file_validate_image_resolution'] = [$dims];
      }
    }
    // Save files.
    $files = file_save_upload('imce', $validators, $destination, NULL, $replace);
    if ($files) {
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
