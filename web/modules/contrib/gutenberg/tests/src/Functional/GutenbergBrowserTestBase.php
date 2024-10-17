<?php

namespace Drupal\Tests\gutenberg\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for non-JS enabled tests.
 */
abstract class GutenbergBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gutenberg',
    'node',
  ];

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // When we create a content type like this it will automatically get a body
    // field.
    $this->drupalCreateContentType([
      'type' => 'article',
    ]);
    $this->config('gutenberg.settings')
      ->set('article_enable_full', TRUE)->save();
    $this->account = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'use text format gutenberg',
      'use gutenberg',
      // We need this to edit this content type and enable gutenberg and the
      // blocks we want.
      'administer content types',
    ]);
    $this->drupalLogin($this->account);
    $this->drupalGet('/node/add/article');
  }

  /**
   * Assert that gutenberg is supposed to be there.
   *
   * Since we are not using JS here, we just find the element that the module
   * creates, which is the loading element.
   */
  public function assertGutenbergEditorOnPage() {
    $css = '#gutenberg-loading';
    $this->assertSession()->elementExists('css', $css);
  }

}
