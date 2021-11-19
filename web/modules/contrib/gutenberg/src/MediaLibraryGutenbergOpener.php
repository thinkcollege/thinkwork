<?php

namespace Drupal\gutenberg;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;

/**
 * The media library opener for Gutenberg editor.
 *
 * @package Drupal\gutenberg
 */
class MediaLibraryGutenbergOpener implements MediaLibraryOpenerInterface {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('use gutenberg'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    // This is not being triggered by Gutenberg and it's handled on React side,
    // but we need to implement the interface, so we just return an empty AJAX
    // response.
    return new AjaxResponse();
  }

}
