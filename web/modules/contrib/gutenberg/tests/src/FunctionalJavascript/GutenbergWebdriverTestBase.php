<?php

namespace Drupal\Tests\gutenberg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for JS enabled tests.
 */
abstract class GutenbergWebdriverTestBase extends WebDriverTestBase {

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
    // Try to close that welcome tour thing.
    $tour_close_selector = '.edit-post-welcome-guide .components-modal__header button';
    $this->assertSession()->waitForElement('css', $tour_close_selector);
    $this->getSession()->getPage()->find('css', $tour_close_selector)->click();
  }

  /**
   * Helper to assert that a block is not enabled.
   */
  protected function assertBlockIsNotEnabled($block, $content_type = 'article') {
    $blocks = $this->findBlocksByText($block, $content_type);
    if (!empty($blocks)) {
      throw new \Exception('Block ' . $block . ' was found, but should not have been.');
    }
  }

  /**
   * Helper to assert that a block is enabled.
   */
  protected function assertBlockIsEnabled($block, $content_type = 'article') {
    $blocks = $this->findBlocksByText($block, $content_type);
    if (empty($blocks)) {
      throw new \Exception('Block ' . $block . ' was not found, but should have been.');
    }
  }

  /**
   * Helper to find blocks by text.
   */
  protected function findBlocksByText($block, $content_type = 'article') {
    $this->drupalGet('/node/add/' . $content_type);
    $insert_block_button = '.edit-post-header-toolbar__inserter-toggle';
    $this->assertSession()->waitForElement('css', $insert_block_button);
    $page = $this->getSession()->getPage();
    $page->find('css', $insert_block_button)->click();
    $elements = $page->findAll('css', '.block-editor-inserter__block-list .block-editor-block-types-list__list-item');
    $blocks = [];
    foreach ($elements as $element) {
      $text = trim($element->getText());
      if ($text === $block) {
        $blocks[] = $element;
      }
    }
    return $blocks;
  }

}
