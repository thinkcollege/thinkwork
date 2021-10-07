<?php

namespace Drupal\Tests\content_access\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Automated BrowserTest Case for having a tiny test to run fast.
 *
 * @group Access
 */
class ContentAccessTinyTest extends BrowserTestBase {
  use ContentAccessTestHelperTrait;
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['content_access', 'acl'];

  /**
   * A user with permission to non administer.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $testUser;

  /**
   * A user with permission to administer.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Content type for test.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * Node object to perform test.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node1;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Setup configuration before each test.
   */
  protected function setUp() {
    parent::setUp();

    if (!\Drupal::moduleHandler()->moduleExists('acl')) {
      $this->pass('No ACL module present, skipping test');
      return;
    }

    // Create test user with separate role.
    $this->testUser = $this->drupalCreateUser();

    // Get the value of the new role.
    // @see drupalCreateUser().
    $testUserRoles = $this->testUser->getRoles();
    foreach ($testUserRoles as $role) {
      if (!in_array($role, [AccountInterface::AUTHENTICATED_ROLE])) {
        $this->rid = $role;
        break;
      }
    }

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'grant content access',
      'grant own content access',
      'bypass node access',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Rebuild content access permissions.
    node_access_rebuild();

    // Create test content type.
    $this->contentType = $this->drupalCreateContentType();

    // Create test node.
    $this->node1 = $this->drupalCreateNode(['type' => $this->contentType->id()]);
  }

  /**
   * Test Viewing accessibility with permissions for single users.
   */
  public function testViewAccess() {
    // Exit test if ACL module could not be enabled.
    if (!\Drupal::moduleHandler()->moduleExists('acl')) {
      $this->pass('No ACL module present, skipping test');
      return;
    }

    // Restrict access to this content type.
    // Enable per node access control.
    $accessPermissions = [
      'view[anonymous]' => FALSE,
      'view[authenticated]' => FALSE,
      'per_node' => TRUE,
    ];
    $this->changeAccessContentType($accessPermissions);

    // Allow access for test user.
    $edit = [
      'acl[view][add]' => $this->testUser->getAccountName(),
    ];
    $this->drupalPostForm('node/' . $this->node1->id() . '/access', $edit, $this->t('Add User'));
    $this->drupalPostForm(NULL, [], $this->t('Submit'));

    // Logout admin, try to access the node anonymously.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node1->id());
    $this->assertSession()->pageTextContains($this->t('Access denied'));

    // Login test user, view access should be allowed now.
    $this->drupalLogin($this->testUser);
    $this->drupalGet('node/' . $this->node1->id());
    $this->assertSession()->pageTextNotContains($this->t('Access denied'));

    // Login admin and disable per node access.
    $this->drupalLogin($this->adminUser);
    $this->changeAccessPerNode(FALSE);

    // Logout admin, try to access the node anonymously.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node1->id());
    $this->assertSession()->pageTextContains($this->t('Access denied'));

    // Login test user, view access should be denied now.
    $this->drupalLogin($this->testUser);
    $this->drupalGet('node/' . $this->node1->id());
    $this->assertSession()->pageTextContains($this->t('Access denied'));
  }

  /*
   * Test Editing accessibility with permissions for single users.
   */
  /*
  public function testEditAccess() {
  // Exit test if ACL module could not be enabled.
  if (!\Drupal::moduleHandler()->moduleExists('acl')) {
  $this->pass('No ACL module present, skipping test');
  return;
  }

  // Enable per node access control.
  $this->changeAccessPerNode();

  // Allow edit access for test user.
  $edit = [
  'acl[update][add]' => $this->testUser->getAccountName(),
  ];
  $this->drupalPostForm('node/' . $this->node1->id() . '/access', $edit,
  t('Add User'));
  $this->drupalPostForm(NULL, [], t('Submit'));

  // Logout admin, try to edit the node anonymously.
  $this->drupalLogout();
  $this->drupalGet('node/' . $this->node1->id() . '/edit');
  $this->assertSession()->pageTextContains(t('Access denied'));

  // Login test user, edit access should be allowed now.
  $this->drupalLogin($this->testUser);
  $this->drupalGet('node/' . $this->node1->id() . '/edit');
  $this->assertSession()->pageTextNotContains(t('Access denied'));

  // Login admin and disable per node access.
  $this->drupalLogin($this->adminUser);
  $this->changeAccessPerNode(FALSE);

  // Logout admin, try to edit the node anonymously.
  $this->drupalLogout();
  $this->drupalGet('node/' . $this->node1->id() . '/edit');
  $this->assertSession()->pageTextContains(t('Access denied'));

  // Login test user, edit access should be denied now.
  $this->drupalLogin($this->testUser);
  $this->drupalGet('node/' . $this->node1->id() . '/edit');
  $this->assertSession()->pageTextContains(t('Access denied'));
  }
   */

  /*
   * Test Deleting accessibility with permissions for single users.
   */
  /*
  public function testDeleteAccess() {
  // Exit test if ACL module could not be enabled.
  if (!\Drupal::moduleHandler()->moduleExists('acl')) {
  $this->pass('No ACL module present, skipping test');
  return;
  }

  // Enable per node access control.
  $this->changeAccessPerNode();

  // Allow delete access for test user.
  $edit = [
  'acl[delete][add]' => $this->testUser->getAccountName(),
  ];
  $this->drupalPostForm('node/' . $this->node1->id() . '/access', $edit,
  t('Add User'));
  $this->drupalPostForm(NULL, [], t('Submit'));

  // Logout admin, try to delete the node anonymously.
  $this->drupalLogout();
  $this->drupalGet('node/' . $this->node1->id() . '/delete');
  $this->assertSession()->pageTextContains(t('Access denied'));

  // Login test user, delete access should be allowed now.
  $this->drupalLogin($this->testUser);
  $this->drupalGet('node/' . $this->node1->id() . '/delete');
  $this->assertSession()->pageTextNotContains(t('Access denied'));

  // Login admin and disable per node access.
  $this->drupalLogin($this->adminUser);
  $this->changeAccessPerNode(FALSE);

  // Logout admin, try to delete the node anonymously.
  $this->drupalLogout();
  $this->drupalGet('node/' . $this->node1->id() . '/delete');
  $this->assertSession()->pageTextContains(t('Access denied'));

  // Login test user, delete access should be denied now.
  $this->drupalLogin($this->testUser);
  $this->drupalGet('node/' . $this->node1->id() . '/delete');
  $this->assertSession()->pageTextContains(t('Access denied'));
  }
   */

}
