<?php

namespace Drupal\Tests\viewfield\Functional;

/**
 * Tests Viewfield formatters.
 *
 * @group viewfield
 */
class ViewfieldFormatterTest extends ViewfieldFunctionalTestBase {

  /**
   * Test viewfield_default formatter.
   */
  public function testViewfieldFormatterDefault() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();


    $session->fieldExists("field_view_test[0][target_id]");
    $session->fieldExists("field_view_test[0][display_id]");
    $session->fieldExists("field_view_test[0][arguments]");

    $page->fillField('field_view_test[0][target_id', 'content_test');
    $this->waitForAjaxToFinish();

    // Test basic entry of Viewfield.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_view_test[0][display_id]' => 'block_1',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Page 1');
  }

  /**
   * Test viewfield_title formatter.
   */
  public function testViewfieldFormatterTitle() {

  }

  /**
   * Test Viewfield argument handling.
   */
  public function testViewfieldArgumentHandling() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();


    $session->fieldExists("field_view_test[0][target_id]");
    $session->fieldExists("field_view_test[0][display_id]");
    $session->fieldExists("field_view_test[0][arguments]");

    $page->fillField('field_view_test[0][target_id', 'content_test');
    $this->waitForAjaxToFinish();

    // Test argument handling
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_view_test[0][target_id]' => "content_test",
      'field_view_test[0][display_id]' => 'block_1',
      'field_view_test[0][arguments]' => 'page_test',
    ];

    $this->drupalPostForm('node/add/article_test', $edit, t('Save'));
    $this->assertSession()->responseContains('Page 1');
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->pageTextContains('This is jus ta test');

  }

  /**
   * Test Viewfield "Items to display" override.
   */
  public function testViewfieldItemsToDisplay() {

  }

  /**
   * Test Viewfield "Empty" view results.
   */
  public function testViewfieldEmptyView() {

  }

}
