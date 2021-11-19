<?php

namespace Drupal\gutenberg\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Delete reusable block.
 *
 * @Action(
 *   id = "reusable_block_delete_action",
 *   label = @Translation("Delete reusable block"),
 *   type = "block_content"
 * )
 */
class ReusableBlockDelete extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\block_content\BlockContentInterface $entity */
    $entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\block_content\BlockContentInterface $object */
    return $object->access('delete', $account, $return_as_object);
  }

}
