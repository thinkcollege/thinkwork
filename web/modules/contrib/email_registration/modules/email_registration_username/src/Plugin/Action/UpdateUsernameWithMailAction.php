<?php

namespace Drupal\email_registration_username\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Auto username mail rename bulk action.
 *
 * Note, this action is replacing the "UpdateUsernameAction" action.
 *
 * @Action(
 *   id = "email_registration_update_username",
 *   label = @Translation("Update username (from email_registration)"),
 *   type = "user",
 * )
 */
class UpdateUsernameWithMailAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Rename the given user:
    if (!empty($account) && $account instanceof UserInterface) {
      $mail = $account->getEmail();
      $account->setUsername($mail);

      // If the validation for an account fails for some reason, log it and
      // return void:
      if ($account->isValidationRequired() && !$account->validate()) {
        \Drupal::logger('email_registration')->error('Email registration failed setting the new name on user @id.', ['@id' => $account->id()]);
        return;
      }
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
