<?php

namespace Drupal\migrate_manifest;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Simple Migrate Message implementation that uses drush to output.
 *
 * @package Drupal\migrate_manifest
 */
class DrushLogMigrateMessage implements MigrateMessageInterface {

  /**
   * @inheritdoc
   */
  public function display($message, $type = 'status') {
    drush_log($message, $type);
  }

}
