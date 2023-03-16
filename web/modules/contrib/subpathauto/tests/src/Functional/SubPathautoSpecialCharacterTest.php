<?php

namespace Drupal\Tests\subpathauto\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class SubPathautoSpecialCharacterTest.
 *
 * @group subpathauto
 */
class SubPathautoSpecialCharacterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'subpathauto',
    'path_alias',
    'node',
    'user',
    'text',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that special character urls do not return 404 page.
   */
  public function testSpecialCharacterPath(): void {
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateNode();

    $aliasStorage = \Drupal::entityTypeManager()
      ->getStorage('path_alias');

    $path_alias = $aliasStorage->create([
      'path' => '/node/1',
      'alias' => '/test-alias%',
    ]);
    $path_alias->save();

    $alias_white_list = $this->container->get('path_alias.whitelist');
    $alias_white_list->set('node', TRUE);

    $admin_user = $this->drupalCreateUser([
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('/test-alias%/edit');
    $this->assertSession()->statusCodeEquals(200);
  }

}
