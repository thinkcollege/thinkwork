<?php

namespace Drupal\Tests\gutenberg\Functional;

/**
 * Test accessing a gutenberg enabled node/add without all config saved.
 *
 * @group gutenberg
 */
class NoAllowedImageStyleTest extends GutenbergBrowserTestBase {

  /**
   * Test that gutenberg shows, and it can be used.
   */
  public function testGutenbergEnabledAndDoNotCrash() : void {
    $this->assertGutenbergEditorOnPage();
  }

}
