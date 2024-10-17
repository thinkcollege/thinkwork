<?php

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\imce\Imce;
use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

/**
 * Defines Imce Resize plugin.
 *
 * @ImcePlugin(
 *   id = "resize",
 *   label = "Resize",
 *   operations = {
 *     "resize" = "opResize"
 *   }
 * )
 */
class Resize extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'resize_images' => $this->t('Resize images'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    // Check if resize permission exists.
    if ($fm->hasPermission('resize_images')) {
      $page['#attached']['library'][] = 'imce/drupal.imce.resize';
    }
  }

  /**
   * Operation handler: resize.
   */
  public function opResize(ImceFM $fm) {
    $width = min(10000, (int) $fm->getPost('width'));
    $height = min(10000, (int) $fm->getPost('height'));
    $copy = (bool) $fm->getPost('copy');
    $items = $fm->getSelection();
    if ($this->validateResize($fm, $items, $width, $height, $copy)) {
      $this->resizeItems($fm, $items, $width, $height, $copy);
    }
  }

  /**
   * Validates item resizing.
   */
  public function validateResize(ImceFM $fm, array $items, $width, $height, $copy) {
    return $items
      && $fm->validateDimensions($items, $width, $height)
      && $fm->validateImageTypes($items)
      && $fm->validatePermissions($items, 'resize_images');
  }

  /**
   * Resizes a list of imce items and returns succeeded ones.
   */
  public function resizeItems(ImceFM $fm, array $items, $width, $height, $copy = FALSE) {
    $factory = \Drupal::service('image.factory');
    $fs = \Drupal::service('file_system');
    $success = [];
    foreach ($items as $item) {
      $uri = $item->getUri();
      $image = $factory->get($uri);
      // Check if image is valid.
      if (!$image->isValid()) {
        continue;
      }
      // Check if resizing is needed.
      $resize = $image->getWidth() != $width || $image->getHeight() != $height;
      if (!$resize && !$copy) {
        continue;
      }
      if ($resize && !$image->resize($width, $height)) {
        continue;
      }
      // Save.
      $destination = $copy ? $fs->createFilename($fs->basename($uri), $fs->dirname($uri)) : $uri;
      if (!$image->save($destination)) {
        continue;
      }
      // Create a new file record.
      $filesize = $image->getFileSize();
      if ($copy) {
        $filename = $fs->basename($destination);
        $values = [
          'uid' => $fm->user->id(),
          'status' => 1,
          'filename' => $filename,
          'uri' => $destination,
          'filesize' => $filesize,
          'filemime' => $image->getMimeType(),
        ];
        /** @var \Drupal\file\FileStorage $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('file');
        $file = $storage->create($values);
        // Check quota.
        $quota = $fm->getConf('quota');
        if ($quota && ($storage->spaceUsed(\Drupal::currentUser()->id()) + $filesize) > $quota) {
          $fs->delete($destination);
          $fm->setMessage(t('The file is %filesize which would exceed your disk quota of %quota.', [
            '%filesize' => Imce::formatSize($filesize),
            '%quota' => Imce::formatSize($quota),
          ]));
        }
        else {
          $file->save();
          // Add imce item.
          $item->parent->addFile($filename)->addToJs();
        }
      }
      // Update existing.
      else {
        $file = Imce::getFileEntity($uri);
        if ($file) {
          $file->setSize($filesize);
          $file->save();
        }
        // Add to js.
        $item->addToJs();
      }
      $success[] = $item;
    }
    return $success;
  }

}
