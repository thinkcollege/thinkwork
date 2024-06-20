<?php

namespace Drupal\Tests\gutenberg\FunctionalJavascript;

/**
 * Test enable/disable the media block.
 *
 * @group gutenberg
 */
class EnableDisableMediaTest extends GutenbergWebdriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
  ];

  /**
   * Test that gutenberg shows, and the expected blocks are there.
   *
   * Also tests that some unexpected blocks are not there.
   *
   * @dataProvider getEnableMediaVariations
   */
  public function testGutenbergEnabledBlocksAsExpected($enable_media) : void {
    $this->drupalGet('/admin/structure/types/manage/article');
    // We want to manually click all of the things, since that will make sure we
    // disable both current list of blocks, but also new ones added in the
    // future.
    $page = $this->getSession()->getPage();
    $page->find('css', 'a[href="#edit-gutenberg"]')->click();
    // Enable it.
    $page->find('css', 'input[name="enable_gutenberg_experience"]')->check();
    // Now we need to expand all fieldsets so we can click the checkboxes.
    $sets = $page->findAll('css', 'details[data-drupal-selector="edit-gutenberg"] details');
    foreach ($sets as $set) {
      $set->click();
    }
    // Now loop over all of the checkboxes and disable them one by one. The
    // reason we want to do that, is to avoid any new blocks added in the future
    // having to be unset in config in this test as well. Plus of course, if we
    // change the config schema, which we probably should, this test will still
    // hopefully work after that.
    $boxes = $page->findAll('css', 'details[data-drupal-selector="edit-gutenberg"] details input[type="checkbox"]');
    foreach ($boxes as $box) {
      $box->uncheck();
    }
    // Enable paragraph.
    $page->find('css', 'input[name="allowed_blocks_core[core/paragraph]"]')->check();
    // If we want to enable that media block, do it now.
    if ($enable_media) {
      $page->find('css', 'input[name="allowed_drupal_blocks_media[drupalmedia/drupal-media-entity]"]')->check();
    }
    // Now let's save the content type.
    $page->find('css', '[data-drupal-selector="edit-submit"]')->click();
    $this->drupalGet('/node/add/article');
    $insert_block_button = '.edit-post-header-toolbar__inserter-toggle';
    $this->assertSession()->waitForElement('css', $insert_block_button);
    $this->getSession()->getPage()->find('css', $insert_block_button)->click();
    // Now let's find all of the blocks we are allowed to use. This should be
    // only the paragraph one, certainly not any media ones. And I guess the
    // group design block?
    $elements = $page->findAll('css', '.block-editor-inserter__block-list .block-editor-block-types-list__list-item');
    $ok_to_find = [
      'Paragraph',
      'Group',
    ];
    if ($enable_media) {
      $ok_to_find[] = 'Media';
    }
    $unexpected_finds = [];
    foreach ($elements as $element) {
      $text = trim($element->getText());
      if (in_array($text, $ok_to_find)) {
        continue;
      }
      $unexpected_finds[] = $text;
    }
    self::assertEmpty($unexpected_finds, sprintf('Found the following unexpected allowed %s: %s', count($unexpected_finds) > 1 ? 'blocks' : 'block', implode(', ', $unexpected_finds)));
    // If we are enabling the media thing, we also want to make sure its
    // actually available at this point.
    $required_to_find = [];
    if ($enable_media) {
      $required_to_find[] = 'Media';
    }
    foreach ($required_to_find as $item) {
      foreach ($elements as $element) {
        $text = trim($element->getText());
        if ($text === $item) {
          continue 2;
        }
      }
      throw new \Exception('Was not able to find the required block: ' . $item);
    }
  }

  /**
   * Dataprovider for the test.
   */
  public function getEnableMediaVariations() : array {
    return [
      [
        'enable' => TRUE,
      ],
      [
        'enable' => FALSE,
      ],
    ];
  }

}
