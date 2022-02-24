<?php

namespace Drupal\gutenberg\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for reloading the media block after editing.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.gutenbergUpdateMediaEntities.
 */
class UpdateMediaEntitiesCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'gutenbergUpdateMediaEntities',
    ];
  }

}
