<?php

namespace Drupal\Tests\content_access\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\Role;

/**
 * Helper class with auxiliary functions for content access module tests.
 */
trait ContentAccessTestHelperTrait {

  /**
   * Role ID for authenticated users.
   *
   * @var string
   */
  protected $rid = AccountInterface::AUTHENTICATED_ROLE;

  /**
   * Change access permissions for a content type.
   */
  public function changeAccessContentType($accessSettings) {
    $this->drupalPostForm(
      'admin/structure/types/manage/' . $this->contentType->id() . '/access',
      $accessSettings,
      t('Submit')
    );
    // Both these may be printed:
    // 'Permissions have been changed' || 'No change' => 'change'.
    $this->assertSession()->pageTextContains(t('change'));
  }

  /**
   * Access keyword for content type.
   *
   * Change access permissions for a content type by a given keyword for the
   * role of the user.
   */
  public function changeAccessContentTypeKeyword($keyword, $access = TRUE, AccountInterface $user = NULL) {
    $roles = [];

    if ($user === NULL) {
      $role = Role::load($this->rid);
      $roles[$role->id()] = $role->id();
    }
    else {
      $userRoles = $user->getRoles();
      foreach ($userRoles as $role) {
        $roles[$role] = $role;
        break;
      }
    }

    $accessSettings = [
      $keyword . '[' . key($roles) . ']' => $access,
    ];

    $this->changeAccessContentType($accessSettings);
  }

  /**
   * Change the per node access setting for a content type.
   */
  public function changeAccessPerNode($access = TRUE) {
    $accessPermissions = [
      'per_node' => $access,
    ];
    $this->changeAccessContentType($accessPermissions);
  }

  /**
   * Access keyword for node.
   *
   * Change access permissions for a node by a given keyword (view, update
   * or delete).
   */
  public function changeAccessNodeKeyword(NodeInterface $node, $keyword, $access = TRUE) {
    $user = $this->testUser;
    $userRoles = $user->getRoles();
    foreach ($userRoles as $rid) {
      $role = Role::load($rid);
      $roles[$role->id()] = $role->get('label');
    }

    $accessSettings = [
      $keyword . '[' . key($roles) . ']' => $access,
    ];

    $this->changeAccessNode($node, $accessSettings);
  }

  /**
   * Change access permission for a node.
   */
  public function changeAccessNode(NodeInterface $node, $accessSettings) {
    $this->drupalPostForm('node/' . $node->id() . '/access', $accessSettings, t('Submit'));
    $this->assertSession()->pageTextContains(t('Your changes have been saved.'));
  }

}
