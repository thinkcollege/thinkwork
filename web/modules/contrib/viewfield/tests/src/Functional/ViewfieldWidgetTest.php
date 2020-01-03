<?php

namespace Drupal\Tests\viewfield\Functional;

/**
 * Tests Viewfield widgets.
 *
 * @group viewfield
 */
class ViewfieldWidgetTest extends ViewfieldFunctionalTestBase {

  /**
   * Test select widget.
   */
  public function testSelectWidget() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_title',
      'weight' => 1,
    ])->save();

    $session = $this->assertSession();

    // Confirm field label and description are rendered.
    $this->drupalGet('node/add/article_test');
    $session->fieldExists("field_view_test[0][target_id]");
    $session->fieldExists("field_view_test[0][display_id]");
    $session->fieldExists("field_view_test[0][arguments]");
    $session->responseContains('Viewfield');
    $session->responseContains('Viewfield description');

    // Test basic entry of color field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_view_test[0][target_id]' => "content_test",
      'field_view_test[0][display_id]' => "block_1",
      'field_view_test[0][arguments]' => "article_test",
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Test response.
    $session->responseContains('content_test');
    $session->responseContains('block_1');
    $session->responseContains('article_test');
  }

}