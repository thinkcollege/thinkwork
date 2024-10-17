<?php

namespace Drupal\Tests\gutenberg\FunctionalJavascript;

/**
 * Tests the reusable blocks feature.
 */
class ReusableBlocksTest extends GutenbergWebdriverTestBase {
  /**
   * Tests creating and using reusable blocks in Gutenberg.
   */
  public function testCreatingAndUsingReusableBlocks() {
    self::assertCount(0, $this->getReusableBlocks());
    $js = <<<JS
document.querySelector('.edit-post-visual-editor [contenteditable="true"]').textContent = 'test content';
JS;

    // So here's a trick. First we insert a paragraph block, then we write
    // something and it will end up there.
    $insert_block_button = '.edit-post-header-toolbar__inserter-toggle';
    $this->assertSession()->waitForElement('css', $insert_block_button);
    $page = $this->getSession()->getPage();
    $page->find('css', $insert_block_button)->click();
    $page->find('css', '.editor-block-list-item-paragraph')->click();
    // Now close the block inserter thing.
    $page->find('css', $insert_block_button)->click();
    $this->getSession()->evaluateScript($js);
    // Now we should be able to select the content and make it a reusable block.
    $this->getSession()->getPage()->find('css', '.edit-post-visual-editor [contenteditable="true"]')->click();
    // Click the context button on the paragraph.
    $this->getSession()->getPage()->find('css', '.block-editor-block-settings-menu')->click();
    $all_items = $page->findAll('css', '.components-popover__content .components-menu-group button span');
    foreach ($all_items as $item) {
      $text = $item->getText();
      if (strpos($text, 'Add to Reusable blocks') === FALSE) {
        continue;
      }
      $item->click();
      break;
    }
    $page->find('css', '.reusable-blocks-menu-items__convert-modal .components-text-control__input')->setValue('test reusable block');
    $page->find('css', '.reusable-blocks-menu-items__convert-modal .components-button.is-primary')->click();
    // The block should be saved now. Let's wait for the popup to close.
    $this->assertSession()->assertWaitOnAjaxRequest();
    self::assertCount(1, $this->getReusableBlocks());
  }

}